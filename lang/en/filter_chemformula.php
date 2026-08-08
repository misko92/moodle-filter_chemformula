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

/**
 * Strings for component 'filter_chemformula', language 'en'.
 *
 * @package    filter_chemformula
 * @copyright  2026 Moodle
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['filtername'] = 'Chemical formula formatting';
$string['overrides'] = 'Manual overrides';
$string['overrides_desc'] = 'Force a specific rendering for a specific piece of text, or exempt it from automatic detection, overriding the built-in rules. One override per line, in the form:

<code>token = replacement HTML</code>

The token is matched exactly against a standalone piece of text (the same way an automatic formula is matched) - it will not match inside a longer word. The replacement is inserted as raw HTML, so you can use it to force specific &lt;sub&gt;/&lt;sup&gt; markup, e.g.:

<code>IT = I&lt;sub&gt;2&lt;/sub&gt;T</code>

To stop a piece of text being automatically converted at all, map it to itself, e.g.:

<code>NaOH = NaOH</code>

Blank lines and lines starting with # are ignored.';
$string['privacy:metadata'] = 'The Chemical formula formatting filter does not store any personal data.';
