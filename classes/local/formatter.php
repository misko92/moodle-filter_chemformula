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

namespace filter_chemformula\local;

/**
 * Pure chemistry formula/equation detector and formatter.
 *
 * Ported from the tiny_chemformula TinyMCE plugin's amd/src/formatter.js,
 * which this filter's conversion logic replaces. Takes plain text and
 * returns HTML with chemical formulas and equations marked up
 * (subscripts, superscripts, isotope notation, arrows). Anything that
 * does not fully and unambiguously resolve against the real periodic
 * table is left untouched (HTML-escaped, but otherwise unchanged).
 *
 * This class has no dependency on the DOM or the rest of Moodle: it is a
 * pure function of its input, callable directly and unit-testable in
 * isolation. {@see \filter_chemformula\text_filter} is what wires it up
 * to actual page content via DOMDocument.
 *
 * @package    filter_chemformula
 * @copyright  2026 Moodle
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class formatter {
    /** @var string[] Single-letter IUPAC element symbols. */
    private const ELEMENTS_1 = [
        'H', 'B', 'C', 'N', 'O', 'F', 'P', 'S', 'K', 'V', 'Y', 'I', 'W', 'U',
    ];

    /** @var string[] Two-letter (Titlecase) IUPAC element symbols. */
    private const ELEMENTS_2 = [
        'He', 'Li', 'Be', 'Ne', 'Na', 'Mg', 'Al', 'Si', 'Cl', 'Ar', 'Ca',
        'Sc', 'Ti', 'Cr', 'Mn', 'Fe', 'Co', 'Ni', 'Cu', 'Zn', 'Ga', 'Ge',
        'As', 'Se', 'Br', 'Kr', 'Rb', 'Sr', 'Zr', 'Nb', 'Mo', 'Tc', 'Ru',
        'Rh', 'Pd', 'Ag', 'Cd', 'In', 'Sn', 'Sb', 'Te', 'Xe', 'Cs', 'Ba',
        'La', 'Ce', 'Pr', 'Nd', 'Pm', 'Sm', 'Eu', 'Gd', 'Tb', 'Dy', 'Ho',
        'Er', 'Tm', 'Yb', 'Lu', 'Hf', 'Ta', 'Re', 'Os', 'Ir', 'Pt', 'Au',
        'Hg', 'Tl', 'Pb', 'Bi', 'Po', 'At', 'Rn', 'Fr', 'Ra', 'Ac', 'Th',
        'Pa', 'Np', 'Pu', 'Am', 'Cm', 'Bk', 'Cf', 'Es', 'Fm', 'Md', 'No',
        'Lr', 'Rf', 'Db', 'Sg', 'Bh', 'Hs', 'Mt', 'Ds', 'Rg', 'Cn', 'Nh',
        'Fl', 'Mc', 'Lv', 'Ts', 'Og',
    ];

    /**
     * @var array<string, int> Atomic number (proton count) for every
     * recognised element symbol. Used to check whether a candidate mass
     * number is physically possible for that element (mass number can
     * never be less than atomic number), which is what distinguishes
     * genuine isotope notation from an element symbol followed by a
     * numeric ionic charge in the same "Element-digits" shape.
     */
    private const ATOMIC_NUMBERS = [
        'H' => 1, 'He' => 2, 'Li' => 3, 'Be' => 4, 'B' => 5, 'C' => 6, 'N' => 7, 'O' => 8,
        'F' => 9, 'Ne' => 10, 'Na' => 11, 'Mg' => 12, 'Al' => 13, 'Si' => 14, 'P' => 15,
        'S' => 16, 'Cl' => 17, 'Ar' => 18, 'K' => 19, 'Ca' => 20, 'Sc' => 21, 'Ti' => 22,
        'V' => 23, 'Cr' => 24, 'Mn' => 25, 'Fe' => 26, 'Co' => 27, 'Ni' => 28, 'Cu' => 29,
        'Zn' => 30, 'Ga' => 31, 'Ge' => 32, 'As' => 33, 'Se' => 34, 'Br' => 35, 'Kr' => 36,
        'Rb' => 37, 'Sr' => 38, 'Y' => 39, 'Zr' => 40, 'Nb' => 41, 'Mo' => 42, 'Tc' => 43,
        'Ru' => 44, 'Rh' => 45, 'Pd' => 46, 'Ag' => 47, 'Cd' => 48, 'In' => 49, 'Sn' => 50,
        'Sb' => 51, 'Te' => 52, 'I' => 53, 'Xe' => 54, 'Cs' => 55, 'Ba' => 56, 'La' => 57,
        'Ce' => 58, 'Pr' => 59, 'Nd' => 60, 'Pm' => 61, 'Sm' => 62, 'Eu' => 63, 'Gd' => 64,
        'Tb' => 65, 'Dy' => 66, 'Ho' => 67, 'Er' => 68, 'Tm' => 69, 'Yb' => 70, 'Lu' => 71,
        'Hf' => 72, 'Ta' => 73, 'W' => 74, 'Re' => 75, 'Os' => 76, 'Ir' => 77, 'Pt' => 78,
        'Au' => 79, 'Hg' => 80, 'Tl' => 81, 'Pb' => 82, 'Bi' => 83, 'Po' => 84, 'At' => 85,
        'Rn' => 86, 'Fr' => 87, 'Ra' => 88, 'Ac' => 89, 'Th' => 90, 'Pa' => 91, 'U' => 92,
        'Np' => 93, 'Pu' => 94, 'Am' => 95, 'Cm' => 96, 'Bk' => 97, 'Cf' => 98, 'Es' => 99,
        'Fm' => 100, 'Md' => 101, 'No' => 102, 'Lr' => 103, 'Rf' => 104, 'Db' => 105,
        'Sg' => 106, 'Bh' => 107, 'Hs' => 108, 'Mt' => 109, 'Ds' => 110, 'Rg' => 111,
        'Cn' => 112, 'Nh' => 113, 'Fl' => 114, 'Mc' => 115, 'Lv' => 116, 'Ts' => 117,
        'Og' => 118,
    ];

    /** @var string[] State labels that must be recognised and left completely unstyled. */
    private const STATE_LABELS = ['(aq)', '(s)', '(l)', '(g)'];

    /**
     * @var string Placeholder symbol for an unknown element in isotope and
     * nuclear symbol notation, e.g. "235/92X" or "X-235", as used in
     * "identify element X" problems. Not a real element symbol, so it is
     * only ever recognised in these two notations, never as part of an
     * ordinary formula.
     */
    private const UNKNOWN_ELEMENT_PLACEHOLDER = 'X';

    /** @var string Reaction arrow shorthand, longest alternatives first. */
    private const ARROW_PATTERN = '/<=>|<->|-->|->/';

    /** @var string A candidate span: a maximal run of characters a chemistry token could be made of. */
    private const CANDIDATE_PATTERN = '/[A-Za-z0-9()\[\]+\-^\/?]+/';

    /**
     * Detect chemical formulas and equations in plain text and return
     * HTML with them formatted (subscripts, superscripts, isotope
     * notation and reaction arrows). Anything that does not fully and
     * unambiguously resolve against the real periodic table is left
     * untouched.
     *
     * @param string $text plain text input.
     * @param array<string, string> $overrides admin-configured exact-match
     *        overrides (see {@see parse_overrides}): candidate span => the
     *        HTML to use verbatim instead of the automatic rules. Checked
     *        before automatic detection, so an override can either force a
     *        specific rendering or (by mapping a token to itself) exempt it
     *        from automatic conversion entirely.
     * @return string HTML output.
     */
    public static function format(string $text, array $overrides = []): string {
        if ($text === '') {
            return '';
        }

        $witharrows = self::convert_arrows($text);

        $output = '';
        $lastindex = 0;
        if (preg_match_all(self::CANDIDATE_PATTERN, $witharrows, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $match) {
                [$span, $offset] = $match;
                $output .= self::escape_html(substr($witharrows, $lastindex, $offset - $lastindex));
                if (array_key_exists($span, $overrides)) {
                    $output .= $overrides[$span];
                } else if (preg_match('/[A-Z]/', $span)) {
                    $output .= self::process_candidate_span($span);
                } else {
                    $output .= self::escape_html($span);
                }
                $lastindex = $offset + strlen($span);
            }
        }
        $output .= self::escape_html(substr($witharrows, $lastindex));

        return $output;
    }

    /**
     * Parse the admin-configured overrides setting into a lookup array.
     *
     * One override per line, in the form "token = replacement html".
     * Blank lines and lines starting with "#" are ignored. The token is
     * matched exactly against a candidate span (the same maximal run of
     * formula-shaped characters the automatic detector considers), so an
     * override for "H2O" only matches the standalone text "H2O", not
     * "H2O" inside a longer word.
     *
     * @param string $raw the raw setting value.
     * @return array<string, string> token => replacement HTML.
     */
    public static function parse_overrides(string $raw): array {
        $overrides = [];
        foreach (preg_split('/\r\n|\r|\n/', $raw) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            $parts = explode('=', $line, 2);
            if (count($parts) !== 2) {
                continue;
            }
            $token = trim($parts[0]);
            if ($token === '') {
                continue;
            }
            $overrides[$token] = trim($parts[1]);
        }
        return $overrides;
    }

    /**
     * Whether formatting actually changed anything, used by callers to
     * decide whether a DOM replacement is worthwhile. Compares against a
     * plain HTML-escape of the same input (no arrow conversion, no
     * chemistry, no overrides applied) rather than only checking for
     * <sub>/<sup>, since an override's replacement need not use either,
     * and a pure arrow conversion (e.g. "A -> B") has no sub/sup at all.
     *
     * @param string $text the original plain text that was formatted.
     * @param string $formatted the return value of {@see format()} for that text.
     * @return bool
     */
    public static function has_changes(string $text, string $formatted): bool {
        return $formatted !== self::escape_html($text);
    }

    /**
     * HTML-escape a plain text string.
     *
     * @param string $text
     * @return string
     */
    private static function escape_html(string $text): string {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Replace reaction arrow shorthand with proper arrow characters.
     *
     * @param string $text
     * @return string
     */
    private static function convert_arrows(string $text): string {
        return preg_replace_callback(
            self::ARROW_PATTERN,
            static fn($match) => ($match[0] === '<=>' || $match[0] === '<->') ? '⇌' : '→',
            $text
        );
    }

    /**
     * Attempt to match a single element symbol at the given position,
     * preferring the two-letter symbol (which must be Titlecase) over the
     * one-letter symbol, matching real chemical notation exactly.
     *
     * @param string $str
     * @param int $pos
     * @return ?string the matched symbol, or null if none matches here.
     */
    private static function match_element(string $str, int $pos): ?string {
        $two = substr($str, $pos, 2);
        if (preg_match('/^[A-Z][a-z]$/', $two) && in_array($two, self::ELEMENTS_2, true)) {
            return $two;
        }
        $one = $str[$pos] ?? '';
        if (preg_match('/^[A-Z]$/', $one) && in_array($one, self::ELEMENTS_1, true)) {
            return $one;
        }
        return null;
    }

    /**
     * Recursive-descent parse of a formula body: a sequence of elements
     * (each optionally followed by a digit run) and/or bracketed groups
     * (each optionally followed by a digit run). The whole of $str must
     * be consumed for the parse to be considered valid - partial matches
     * are rejected so that ambiguous text is left untouched.
     *
     * @param string $str the formula, with any trailing charge already removed.
     * @return ?array{segments: array, elementcount: int, hasdigit: bool, hasgroup: bool}
     */
    private static function parse_formula_body(string $str): ?array {
        $len = strlen($str);
        $state = new \stdClass();
        $state->pos = 0;
        $state->elementcount = 0;
        $state->hasdigit = false;
        $state->hasgroup = false;
        $state->segments = [];

        $consumedigits = function () use ($str, $len, $state): void {
            $start = $state->pos;
            while ($state->pos < $len && ctype_digit($str[$state->pos])) {
                $state->pos++;
            }
            if ($state->pos > $start) {
                $state->hasdigit = true;
                $state->segments[] = ['type' => 'sub', 'value' => substr($str, $start, $state->pos - $start)];
            }
        };

        $parseunits = function (?string $closing) use (&$parseunits, $str, $len, $state, $consumedigits): bool {
            $matchedany = false;
            while ($state->pos < $len && ($closing === null || $str[$state->pos] !== $closing)) {
                $ch = $str[$state->pos];
                if ($ch === '(' || $ch === '[') {
                    $close = $ch === '(' ? ')' : ']';
                    $state->segments[] = ['type' => 'text', 'value' => $ch];
                    $state->pos++;
                    $state->hasgroup = true;
                    if (!$parseunits($close) || $state->pos >= $len || $str[$state->pos] !== $close) {
                        return false;
                    }
                    $state->segments[] = ['type' => 'text', 'value' => $close];
                    $state->pos++;
                    $consumedigits();
                    $matchedany = true;
                    continue;
                }
                $element = self::match_element($str, $state->pos);
                if ($element === null) {
                    break;
                }
                $state->segments[] = ['type' => 'text', 'value' => $element];
                $state->pos += strlen($element);
                $state->elementcount++;
                $consumedigits();
                $matchedany = true;
            }
            return $matchedany;
        };

        if (!$parseunits(null) || $state->pos !== $len) {
            return null;
        }
        return [
            'segments' => $state->segments,
            'elementcount' => $state->elementcount,
            'hasdigit' => $state->hasdigit,
            'hasgroup' => $state->hasgroup,
        ];
    }

    /**
     * Check for isotope notation, e.g. "U-238" or "238-U", in either
     * order. The whole candidate span must match exactly.
     *
     * @param string $span
     * @return ?string the formatted isotope HTML, or null if not isotope notation.
     */
    private static function try_format_isotope(string $span): ?string {
        if (preg_match('/^([A-Z][a-z]?)-(\d+|\?)$/', $span, $match)) {
            if (self::is_plausible_mass_number($match[1], $match[2])) {
                return '<sup>' . $match[2] . '</sup>' . $match[1];
            }
        }
        if (preg_match('/^(\d+|\?)-([A-Z][a-z]?)$/', $span, $match)) {
            if (self::is_plausible_mass_number($match[2], $match[1])) {
                return '<sup>' . $match[1] . '</sup>' . $match[2];
            }
        }
        return null;
    }

    /**
     * Whether a mass number is physically possible for the given element:
     * the mass number (protons + neutrons) can never be less than the
     * element's atomic number (protons alone). Without this check, an
     * element symbol followed by a numeric ionic charge (e.g. "I-1", the
     * iodide ion with a -1 charge) is indistinguishable by shape alone
     * from isotope notation (e.g. "U-238") and would be wrongly claimed
     * as an impossible mass-number-1 isotope of iodine instead of being
     * left for the charge-parsing logic below to handle.
     *
     * @param string $symbol element symbol.
     * @param string $massnumber the candidate mass number, as digits.
     * @return bool
     */
    private static function is_plausible_mass_number(string $symbol, string $massnumber): bool {
        if ($symbol === self::UNKNOWN_ELEMENT_PLACEHOLDER) {
            // There is no atomic number to compare against for an unknown
            // element, so any mass number is accepted.
            return true;
        }
        if (!array_key_exists($symbol, self::ATOMIC_NUMBERS)) {
            return false;
        }
        if ($massnumber === '?') {
            // An unknown mass number can't be checked against the (known)
            // element's atomic number, so it is accepted unconditionally.
            return true;
        }
        return (int) $massnumber >= self::ATOMIC_NUMBERS[$symbol];
    }

    /**
     * Check for full nuclear symbol notation, e.g. "238/92U": mass number
     * (superscript) then atomic number (subscript), both to the left of
     * the element symbol. The whole candidate span must match exactly.
     *
     * @param string $span
     * @return ?string the formatted HTML, or null if not this notation.
     */
    private static function try_format_nuclear_symbol(string $span): ?string {
        if (preg_match('/^(\d+|\?)\/(\d+|\?)([A-Z][a-z]?)$/', $span, $match)) {
            $issymbol = $match[3] === self::UNKNOWN_ELEMENT_PLACEHOLDER
                || in_array($match[3], self::ELEMENTS_1, true)
                || in_array($match[3], self::ELEMENTS_2, true);
            if ($issymbol) {
                // The mass number and atomic number are wrapped together so
                // filter_chemformula's styles.css can stack them vertically
                // (one above the other) rather than the sequential
                // left-to-right placement plain <sup>/<sub> would give.
                return '<span class="filter-chemformula-nuclide"><sup>' . $match[1] . '</sup>' .
                    '<sub>' . $match[2] . '</sub></span>' . $match[3];
            }
        }
        return null;
    }

    /**
     * Render a successfully-parsed formula body plus its optional
     * trailing charge back out to HTML.
     *
     * @param array $parsed
     * @param string $charge
     * @return string
     */
    private static function render_formula(array $parsed, string $charge): string {
        $html = '';
        foreach ($parsed['segments'] as $segment) {
            $html .= $segment['type'] === 'sub' ? '<sub>' . $segment['value'] . '</sub>' : $segment['value'];
        }
        if ($charge !== '') {
            $html .= '<sup>' . $charge . '</sup>';
        }
        return $html;
    }

    /**
     * Try to fully validate and format a single candidate span as
     * chemistry. If it does not resolve completely and unambiguously,
     * the original text is returned unchanged (HTML-escaped).
     *
     * @param string $rawspan
     * @return string
     */
    private static function process_candidate_span(string $rawspan): string {
        if ($rawspan === '') {
            return self::escape_html($rawspan);
        }

        // The caret is only ever a visual hint before a charge, e.g. "SO4^2-",
        // so it is safe to strip for the leading-digit, isotope and
        // nuclear-symbol checks.
        $bareforisotopecheck = str_replace('^', '', $rawspan);
        $isnumberfirstisotope = (bool) preg_match('/^(?:\d+|\?)-[A-Z][a-z]?$/', $bareforisotopecheck);
        $iselementfirstisotope = (bool) preg_match('/^[A-Z][a-z]?-(?:\d+|\?)$/', $bareforisotopecheck);
        $isnuclearsymbol = (bool) preg_match('/^(?:\d+|\?)\/(?:\d+|\?)[A-Z][a-z]?$/', $bareforisotopecheck);
        $isrecognisedplaceholdershape = $isnumberfirstisotope || $iselementfirstisotope || $isnuclearsymbol;

        // A leading run of digits that is not itself isotope notation or a
        // nuclear symbol's mass number is a stoichiometric coefficient and
        // must never be treated as part of the formula token - it is left
        // untouched and the remainder re-processed.
        if (!$isrecognisedplaceholdershape && preg_match('/^\d/', $rawspan)) {
            preg_match('/^\d+/', $rawspan, $coefficientmatch);
            $coefficient = $coefficientmatch[0];
            $rest = substr($rawspan, strlen($coefficient));
            if ($rest === '') {
                return self::escape_html($rawspan);
            }
            return self::escape_html($coefficient) . self::process_candidate_span($rest);
        }

        // A "?" is only meaningful as the unknown-number placeholder inside
        // isotope/nuclear-symbol notation (e.g. "?-235", "235/?U"). Anywhere
        // else it is just punctuation that happens to be glued onto a
        // formula with no space (e.g. "What is H2O?") - such edge "?"
        // characters are peeled off and re-processed separately, the same
        // way a leading stoichiometric coefficient is, so they never block
        // recognition of the chemistry underneath.
        if (!$isrecognisedplaceholdershape && (str_starts_with($rawspan, '?') || str_ends_with($rawspan, '?'))) {
            $core = trim($rawspan, '?');
            if ($core === '') {
                return self::escape_html($rawspan);
            }
            $leading = str_repeat('?', strlen($rawspan) - strlen(ltrim($rawspan, '?')));
            $trailing = str_repeat('?', strlen($rawspan) - strlen(rtrim($rawspan, '?')));
            return self::escape_html($leading) . self::process_candidate_span($core) . self::escape_html($trailing);
        }

        $nuclearsymbol = self::try_format_nuclear_symbol($bareforisotopecheck);
        if ($nuclearsymbol !== null) {
            return $nuclearsymbol;
        }

        $isotope = self::try_format_isotope($bareforisotopecheck);
        if ($isotope !== null) {
            return $isotope;
        }

        // From here on, a caret (if present) only ever marks the charge boundary.
        $working = $rawspan;
        $workingbare = str_replace('^', '', $working);
        $statelabel = '';
        foreach (self::STATE_LABELS as $label) {
            if (strlen($workingbare) > strlen($label) && str_ends_with($workingbare, $label)) {
                $statelabel = $label;
                $working = substr($working, 0, strlen($working) - strlen($label));
                break;
            }
        }

        $base = $working;
        $charge = '';
        $caretindex = strpos($working, '^');
        if ($caretindex !== false) {
            // A caret unambiguously separates the base formula from its
            // charge, resolving cases a plain digit run can't (e.g.
            // "SO4^2-": without the caret, the subscript "4" and charge
            // "2" would merge into one indistinguishable digit run).
            $beforecaret = substr($working, 0, $caretindex);
            $aftercaret = substr($working, $caretindex + 1);
            if (preg_match('/^(?:\d+[+\-]|[+\-]\d*)$/', $aftercaret)) {
                $base = $beforecaret;
                $charge = $aftercaret;
            } else {
                return self::escape_html($rawspan);
            }
        } else {
            // Charges may be written magnitude-then-sign ("2+") or
            // sign-then-magnitude ("+2"); both are accepted here.
            if (
                preg_match('/^([\s\S]*?)(\d+[+\-]|[+\-]\d*)$/', $working, $chargematch)
                && strlen($chargematch[1]) > 0
            ) {
                $base = $chargematch[1];
                $charge = $chargematch[2];
            }
        }

        // Normalise a sign-first charge ("+2") to the conventional
        // magnitude-then-sign form ("2+") used in real chemical notation,
        // so the rendered output always looks the same regardless of
        // which order the author typed it in.
        if (preg_match('/^([+\-])(\d+)$/', $charge, $signfirst)) {
            $charge = $signfirst[2] . $signfirst[1];
        }

        $parsed = self::parse_formula_body($base);
        if ($parsed === null) {
            return self::escape_html($rawspan);
        }

        $isunambiguouschemistry = $parsed['elementcount'] >= 2 || $parsed['hasdigit'] || $parsed['hasgroup'] || $charge !== '';
        if (!$isunambiguouschemistry) {
            return self::escape_html($rawspan);
        }

        return self::render_formula($parsed, $charge) . self::escape_html($statelabel);
    }
}
