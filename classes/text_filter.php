<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace filter_chemformula;

use filter_chemformula\local\formatter;

/**
 * Filter main class for the filter_chemformula plugin.
 *
 * Detects chemical formulas and equations (e.g. "H2O", "Fe2(SO4)3",
 * "U-238", "Ca2+", "H2 + O2 -> H2O") and scientific notation (e.g.
 * "6.02E23", "6.02x10^23") in text and marks them up with proper
 * subscripts, superscripts, isotope notation and reaction arrows.
 * Admins can also configure exact-match overrides (see settings.php) to
 * force specific rendering, or exempt specific tokens, ahead of the
 * automatic rules.
 *
 * Only text nodes are ever touched, walked via DOMDocument rather than
 * regular expressions against the raw HTML, so existing markup and tag
 * attributes are never disturbed and content inside <pre>, <code>,
 * <script> or <style> is left completely alone.
 *
 * @package    filter_chemformula
 * @copyright  2026 Moodle
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class text_filter extends \core_filters\text_filter {
    /** @var string[] Element tag names whose contents must never be touched. */
    private const SKIP_TAGS = ['pre', 'code', 'script', 'style'];

    /**
     * @var ?array<string, string> Cache of the parsed overrides setting,
     * scoped to this filter instance rather than the class, so it can't
     * leak stale state across separate instances - e.g. in a single
     * PHPUnit process running many tests with different config, or if a
     * future caller ever needs two independently-configured instances.
     */
    private ?array $overridescache = null;

    #[\Override]
    public function filter($text, array $options = []) {
        if (!is_string($text) || $text === '') {
            return $text;
        }

        $overrides = $this->get_overrides();

        // Performance shortcut: every automatically-detected chemistry
        // token starts with an uppercase element-symbol letter, and the
        // only markup emitted without one is scientific notation, which
        // always contains a "digit-e-digit" run or a "^". If none of those
        // is present there is nothing to do - unless an override is
        // configured, since an override token could be anything.
        if (empty($overrides) && !preg_match('/[A-Z]|\d[eE][-+]?\d|\^/', $text)) {
            return $text;
        }

        return $this->apply_to_html($text, $overrides);
    }

    /**
     * Fetch and parse the admin-configured overrides setting, once per
     * request (the setting itself is already backed by Moodle's config
     * cache, but there is no need to re-parse the raw text repeatedly
     * when a page filters many separate pieces of content).
     *
     * @return array<string, string>
     */
    private function get_overrides(): array {
        if ($this->overridescache === null) {
            $this->overridescache = formatter::parse_overrides((string) get_config('filter_chemformula', 'overrides'));
        }
        return $this->overridescache;
    }

    /**
     * Parse $html, replace chemistry in its text nodes, and return the
     * (possibly) updated HTML. If nothing was found to convert, the
     * original string is returned completely unchanged.
     *
     * @param string $html
     * @param array<string, string> $overrides
     * @return string
     */
    private function apply_to_html(string $html, array $overrides): string {
        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML(
            '<?xml encoding="UTF-8"><!DOCTYPE html><html><body>' . $html . '</body></html>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();

        $body = $doc->getElementsByTagName('body')->item(0);
        if ($body === null) {
            return $html;
        }

        if (!$this->process_children($doc, $body, $overrides)) {
            // Nothing matched: return the original bytes untouched rather
            // than a DOMDocument round-trip of unrelated markup.
            return $html;
        }

        $result = '';
        foreach (iterator_to_array($body->childNodes) as $child) {
            $result .= $doc->saveHTML($child);
        }
        return $result;
    }

    /**
     * Recursively walk the children of $node, replacing chemistry in any
     * text node descendant that isn't inside one of {@see SKIP_TAGS}.
     *
     * @param \DOMDocument $doc
     * @param \DOMNode $node
     * @param array<string, string> $overrides
     * @return bool whether any replacement was made anywhere in the subtree.
     */
    private function process_children(\DOMDocument $doc, \DOMNode $node, array $overrides): bool {
        $changed = false;
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                if ($this->replace_text_node($doc, $child, $overrides)) {
                    $changed = true;
                }
            } else if ($child->nodeType === XML_ELEMENT_NODE) {
                if (in_array(strtolower($child->nodeName), self::SKIP_TAGS, true)) {
                    continue;
                }
                if ($this->process_children($doc, $child, $overrides)) {
                    $changed = true;
                }
            }
        }
        return $changed;
    }

    /**
     * Replace a single text node with formatted chemistry markup, if
     * formatting it actually changed anything. Text nodes with no
     * detected chemistry (and no matching override) are left completely
     * untouched.
     *
     * @param \DOMDocument $doc
     * @param \DOMText $textnode
     * @param array<string, string> $overrides
     * @return bool whether the node was replaced.
     */
    private function replace_text_node(\DOMDocument $doc, \DOMText $textnode, array $overrides): bool {
        $text = $textnode->data;
        if (trim($text) === '') {
            return false;
        }

        $formatted = formatter::format($text, $overrides);
        if (!formatter::has_changes($text, $formatted)) {
            return false;
        }

        // An override's replacement is admin-authored HTML, not something
        // this class generated itself, so it might not be well-formed XML
        // (e.g. an unescaped "&" or an unclosed tag). Leave the node
        // untouched rather than risk inserting a broken/partial fragment.
        $fragment = $doc->createDocumentFragment();
        $ok = @$fragment->appendXML($formatted);
        libxml_clear_errors();
        if (!$ok) {
            return false;
        }

        $textnode->parentNode->replaceChild($fragment, $textnode);
        return true;
    }
}
