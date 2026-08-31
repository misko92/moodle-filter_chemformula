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

/**
 * Unit tests for the filter_chemformula DOM-based text filter.
 *
 * @package    filter_chemformula
 * @category   test
 * @copyright  2026 Moodle
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \filter_chemformula\text_filter
 */
final class text_filter_test extends \advanced_testcase {
    /**
     * Build a filter instance. The filter never reads $this->context or
     * $this->localconfig, so a null context is sufficient here and lets
     * this suite run without any Moodle DB/session fixtures.
     *
     * @return text_filter
     */
    private function get_filter(): text_filter {
        return new text_filter(null, []);
    }

    public function test_converts_formula_inside_html(): void {
        $filter = $this->get_filter();
        $this->assertSame(
            '<p>Water is H<sub>2</sub>O.</p>',
            $filter->filter('<p>Water is H2O.</p>')
        );
    }

    public function test_converts_full_equation_inside_nested_markup(): void {
        $filter = $this->get_filter();
        $this->assertSame(
            '<p class="note" data-x="1">Reaction: <strong>H<sub>2</sub> + O<sub>2</sub> → H<sub>2</sub>O</strong></p>',
            $filter->filter('<p class="note" data-x="1">Reaction: <strong>H2 + O2 -> H2O</strong></p>')
        );
    }

    public function test_pre_content_is_untouched(): void {
        $filter = $this->get_filter();
        $this->assertSame(
            '<p>H<sub>2</sub>O</p><pre>raw H2O text</pre>',
            $filter->filter('<p>H2O</p><pre>raw H2O text</pre>')
        );
    }

    public function test_code_content_is_untouched(): void {
        $filter = $this->get_filter();
        $this->assertSame(
            '<p>See <code>H2O</code> in code.</p>',
            $filter->filter('<p>See <code>H2O</code> in code.</p>')
        );
    }

    public function test_script_content_is_untouched(): void {
        $filter = $this->get_filter();
        $this->assertSame(
            '<script>var x = "H2O";</script><p>H<sub>2</sub>O</p>',
            $filter->filter('<script>var x = "H2O";</script><p>H2O</p>')
        );
    }

    public function test_style_content_is_untouched(): void {
        $filter = $this->get_filter();
        $this->assertSame(
            '<style>.h2o::before { content: "H2O"; }</style><p>H<sub>2</sub>O</p>',
            $filter->filter('<style>.h2o::before { content: "H2O"; }</style><p>H2O</p>')
        );
    }

    public function test_content_without_chemistry_is_returned_byte_identical(): void {
        $filter = $this->get_filter();
        $html = '<p>hello world</p>';
        $this->assertSame($html, $filter->filter($html));
    }

    public function test_empty_string_returns_empty_string(): void {
        $this->assertSame('', $this->get_filter()->filter(''));
    }

    public function test_non_string_input_is_returned_unchanged(): void {
        $filter = $this->get_filter();
        $this->assertNull($filter->filter(null));
    }

    public function test_filter_is_deterministic_for_the_same_input_and_config(): void {
        // Same input, same configuration, must always produce the same
        // output.
        $filter = $this->get_filter();
        $text = '<p>H2O and Ca2+ react with NaCl(aq).</p>';
        $this->assertSame($filter->filter($text), $filter->filter($text));
    }

    public function test_arrow_only_conversion_is_applied(): void {
        // Regression test: a pure arrow conversion with no accompanying
        // formula has no <sub>/<sup> at all, so the "should this node be
        // replaced" check must not rely on tag presence, or this change
        // is silently dropped.
        $filter = $this->get_filter();
        $this->assertSame('<p>A → B</p>', $filter->filter('<p>A -> B</p>'));
    }

    public function test_scientific_notation_is_converted_inside_html(): void {
        $filter = $this->get_filter();
        $this->assertSame(
            '<p>Avogadro: 6.02 × 10<sup>23</sup> mol.</p>',
            $filter->filter('<p>Avogadro: 6.02x10^23 mol.</p>')
        );
        $this->assertSame(
            '<p>Planck is 6.63 × 10<sup>-34</sup> J s.</p>',
            $filter->filter('<p>Planck is 6.63E-34 J s.</p>')
        );
    }

    public function test_lowercase_only_scientific_notation_still_passes_the_shortcut(): void {
        // Text like "6.02e23" and "10^23" contains no uppercase letter, so
        // the early-out in filter() has to recognise it by shape or the
        // conversion is silently skipped.
        $filter = $this->get_filter();
        $this->assertSame('<p>6.02 × 10<sup>23</sup></p>', $filter->filter('<p>6.02e23</p>'));
        $this->assertSame('<p>about 10<sup>23</sup> atoms</p>', $filter->filter('<p>about 10^23 atoms</p>'));
    }

    public function test_scientific_notation_inside_code_is_untouched(): void {
        $filter = $this->get_filter();
        $this->assertSame(
            '<p>6.02 × 10<sup>23</sup></p><pre><code>x = 6.02e23</code></pre>',
            $filter->filter('<p>6.02x10^23</p><pre><code>x = 6.02e23</code></pre>')
        );
    }

    public function test_override_forces_a_specific_rendering(): void {
        $this->resetAfterTest(true);
        set_config('overrides', 'IT = I<sub>2</sub>T', 'filter_chemformula');

        $filter = $this->get_filter();
        $this->assertSame(
            '<p>The compound I<sub>2</sub>T is unusual.</p>',
            $filter->filter('<p>The compound IT is unusual.</p>')
        );
    }

    public function test_override_can_exempt_text_from_automatic_conversion(): void {
        $this->resetAfterTest(true);
        set_config('overrides', 'H2O = H2O', 'filter_chemformula');

        $filter = $this->get_filter();
        $this->assertSame(
            '<p>Testing H2O and CO<sub>2</sub>.</p>',
            $filter->filter('<p>Testing H2O and CO2.</p>')
        );
    }

    public function test_malformed_override_html_fails_safe(): void {
        $this->resetAfterTest(true);
        set_config('overrides', 'Bad = <sub>unclosed', 'filter_chemformula');

        $filter = $this->get_filter();
        $this->assertSame(
            '<p>Bad thing here.</p>',
            $filter->filter('<p>Bad thing here.</p>')
        );
    }

    public function test_isotope_notation_survives_xml_import_style_content(): void {
        // Content that never passed through the editor (e.g. an
        // XML-imported question bank) must be handled identically.
        $filter = $this->get_filter();
        $this->assertSame(
            '<div class="qtext"><p>What is the mass number of <sup>238</sup>U?</p></div>',
            $filter->filter('<div class="qtext"><p>What is the mass number of U-238?</p></div>')
        );
    }
}
