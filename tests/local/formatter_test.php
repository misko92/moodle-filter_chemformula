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
 * Unit tests for the pure filter_chemformula formatter.
 *
 * Mirrors the case set that used to live in tiny_chemformula's jest
 * suite (tests/jest/formatter.test.js), since this class is a direct
 * PHP port of that plugin's amd/src/formatter.js.
 *
 * @package    filter_chemformula
 * @category   test
 * @copyright  2026 Moodle
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \filter_chemformula\local\formatter
 */
final class formatter_test extends \basic_testcase {
    public function test_simple_formulas(): void {
        $this->assertSame('H<sub>2</sub>O', formatter::format('H2O'));
        $this->assertSame('CO<sub>2</sub>', formatter::format('CO2'));
        $this->assertSame('O<sub>2</sub>', formatter::format('O2'));
        $this->assertSame('2H<sub>2</sub>O', formatter::format('2H2O'));
        $this->assertSame('12H<sub>2</sub>O', formatter::format('12H2O'));
    }

    public function test_complex_formulas_with_groups(): void {
        $this->assertSame('Fe<sub>2</sub>(SO<sub>4</sub>)<sub>3</sub>', formatter::format('Fe2(SO4)3'));
        $this->assertSame('Mg(OH)<sub>2</sub>', formatter::format('Mg(OH)2'));
        $this->assertSame('K<sub>4</sub>[Fe(CN)<sub>6</sub>]', formatter::format('K4[Fe(CN)6]'));
    }

    public function test_ionic_charges(): void {
        $this->assertSame('Ca<sup>2+</sup>', formatter::format('Ca2+'));
        $this->assertSame('Na<sup>+</sup>', formatter::format('Na+'));
        $this->assertSame('Cl<sup>-</sup>', formatter::format('Cl-'));
        $this->assertSame('SO<sub>4</sub><sup>2-</sup>', formatter::format('SO4^2-'));
        $this->assertStringNotContainsString('^', formatter::format('SO4^2-'));
    }

    public function test_sign_first_charges_are_recognised_and_normalised(): void {
        // Charges may be written magnitude-then-sign ("2+") or
        // sign-then-magnitude ("+2"); both must be recognised, and both
        // must render in the conventional magnitude-then-sign order.
        $this->assertSame('Mg<sup>2+</sup>', formatter::format('Mg+2'));
        $this->assertSame('Mg<sup>2+</sup>', formatter::format('Mg2+'));
        $this->assertSame('Cl<sup>+</sup>', formatter::format('Cl+'));
        $this->assertSame('H<sub>3</sub>O<sup>1+</sup>', formatter::format('H3O+1'));
        $this->assertSame('H<sub>3</sub>O<sup>1+</sup>', formatter::format('H3O1+'));
        $this->assertSame('SO<sub>4</sub><sup>2+</sup>', formatter::format('SO4^+2'));
        $this->assertSame('SO<sub>4</sub><sup>2+</sup>', formatter::format('SO4^2+'));
    }

    public function test_isotopes(): void {
        $this->assertSame('<sup>238</sup>U', formatter::format('U-238'));
        $this->assertSame('<sup>238</sup>U', formatter::format('238-U'));
        $this->assertSame('<sup>14</sup>C', formatter::format('C-14'));
    }

    public function test_element_digit_charges_are_not_mistaken_for_isotopes(): void {
        // Token "I-1" has the same "Element-digits" shape as isotope notation
        // like "U-238", but a mass number of 1 is physically impossible
        // for iodine (atomic number 53), so this must be recognised as
        // the iodide ion with a -1 charge, not a bogus isotope.
        $this->assertSame('I<sup>1-</sup>', formatter::format('I-1'));
    }

    public function test_full_nuclear_symbol_notation(): void {
        // Mass number (superscript) then atomic number (subscript), both
        // to the left of the element symbol, e.g. "238/92U". Both numbers
        // are wrapped in a span that styles.css stacks vertically.
        $this->assertSame(
            '<span class="filter-chemformula-nuclide"><sup>238</sup><sub>92</sub></span>U',
            formatter::format('238/92U')
        );
        $this->assertSame(
            '<span class="filter-chemformula-nuclide"><sup>14</sup><sub>6</sub></span>C',
            formatter::format('14/6C')
        );
        $this->assertSame(
            '<span class="filter-chemformula-nuclide"><sup>1</sup><sub>1</sub></span>H',
            formatter::format('1/1H')
        );
    }

    public function test_nuclear_symbol_does_not_break_ordinary_slashes(): void {
        $this->assertSame('10/25/2024', formatter::format('10/25/2024'));
        $this->assertSame('and/or', formatter::format('and/or'));
    }

    public function test_unknown_element_placeholder(): void {
        // Placeholder "X" stands in for an unknown element in "identify element X"
        // problems, e.g. given a mass number and atomic number, or given
        // an isotope notation. It is not a real element symbol, so it is
        // only recognised in these two notations, never in ordinary
        // formulas.
        $this->assertSame(
            '<span class="filter-chemformula-nuclide"><sup>235</sup><sub>92</sub></span>X',
            formatter::format('235/92X')
        );
        $this->assertSame('<sup>235</sup>X', formatter::format('X-235'));
        $this->assertSame('<sup>235</sup>X', formatter::format('235-X'));
    }

    public function test_unknown_number_placeholder(): void {
        // Placeholder "?" stands in for an unknown mass number and/or atomic number in
        // isotope and nuclear symbol notation, e.g. given an element,
        // identify its unknown mass number. Either or both numbers may be
        // "?", and it works together with the "X" unknown-element
        // placeholder too.
        $this->assertSame(
            '<span class="filter-chemformula-nuclide"><sup>?</sup><sub>92</sub></span>U',
            formatter::format('?/92U')
        );
        $this->assertSame(
            '<span class="filter-chemformula-nuclide"><sup>235</sup><sub>?</sub></span>U',
            formatter::format('235/?U')
        );
        $this->assertSame(
            '<span class="filter-chemformula-nuclide"><sup>?</sup><sub>?</sub></span>U',
            formatter::format('?/?U')
        );
        $this->assertSame(
            '<span class="filter-chemformula-nuclide"><sup>?</sup><sub>?</sub></span>X',
            formatter::format('?/?X')
        );
        $this->assertSame('<sup>?</sup>U', formatter::format('U-?'));
        $this->assertSame('<sup>?</sup>U', formatter::format('?-U'));
    }

    public function test_stray_question_mark_does_not_block_formula_detection(): void {
        // A "?" that is not part of isotope/nuclear-symbol notation is
        // ordinary punctuation that happens to be glued onto a formula
        // with no space (e.g. ending a sentence) - it must not prevent the
        // formula underneath from being recognised.
        $this->assertSame('H<sub>2</sub>O?', formatter::format('H2O?'));
        $this->assertSame(
            'What is H<sub>2</sub>O? A common substance.',
            formatter::format('What is H2O? A common substance.')
        );
        $this->assertSame('2H<sub>2</sub>O?', formatter::format('2H2O?'));
        $this->assertSame('<sup>238</sup>U?', formatter::format('U-238?'));
    }

    public function test_state_labels(): void {
        $this->assertSame('NaCl(aq)', formatter::format('NaCl(aq)'));
        $this->assertSame('H<sub>2</sub>O(l)', formatter::format('H2O(l)'));
        $this->assertSame('CO<sub>2</sub>(g)', formatter::format('CO2(g)'));
        $this->assertSame('NaCl(s)', formatter::format('NaCl(s)'));
        $this->assertSame('Fe<sub>2</sub>(SO<sub>4</sub>)<sub>3</sub>(aq)', formatter::format('Fe2(SO4)3(aq)'));
    }

    public function test_hydrate_dot_notation(): void {
        // A "." used as the hydrate separator becomes a proper middle dot, and
        // both the salt and the water are formatted.
        $this->assertSame('CuSO<sub>4</sub>·5H<sub>2</sub>O', formatter::format('CuSO4.5H2O'));
        $this->assertSame('MgSO<sub>4</sub>·7H<sub>2</sub>O', formatter::format('MgSO4.7H2O'));
        $this->assertSame('Na<sub>2</sub>CO<sub>3</sub>·10H<sub>2</sub>O', formatter::format('Na2CO3.10H2O'));
        $this->assertSame('Fe(NO<sub>3</sub>)<sub>3</sub>·9H<sub>2</sub>O', formatter::format('Fe(NO3)3.9H2O'));
        $this->assertSame('KAl(SO<sub>4</sub>)<sub>2</sub>·12H<sub>2</sub>O', formatter::format('KAl(SO4)2.12H2O'));

        // Monohydrate (no coefficient) and a trailing state label still work.
        $this->assertSame('CuSO<sub>4</sub>·H<sub>2</sub>O', formatter::format('CuSO4.H2O'));
        $this->assertSame('CoCl<sub>2</sub>·6H<sub>2</sub>O(s)', formatter::format('CoCl2.6H2O(s)'));

        // A middle dot the author already typed is left as-is.
        $this->assertSame('CuSO<sub>4</sub>·5H<sub>2</sub>O', formatter::format('CuSO4·5H2O'));

        // Spaces around the separator are normalised away.
        $this->assertSame('CuSO<sub>4</sub>·5H<sub>2</sub>O', formatter::format('CuSO4 . 5H2O'));
    }

    public function test_hydrate_dot_does_not_touch_ordinary_periods(): void {
        // Sentence-ending period after a formula.
        $this->assertSame(
            'The salt is CaCl<sub>2</sub>. It dissolves in H<sub>2</sub>O.',
            formatter::format('The salt is CaCl2. It dissolves in H2O.')
        );
        // A decimal that happens to abut "H2O": the water is still formatted,
        // but the "." is not turned into a hydrate dot.
        $this->assertStringNotContainsString('·', formatter::format('Add 2.5H2O of solution'));
        // A version number followed by "H2O" later in the sentence.
        $this->assertStringNotContainsString('·', formatter::format('Moodle 3.5 needs H2O'));
    }

    public function test_scientific_notation_e_form(): void {
        // The "E"/"e" form, with and without a decimal mantissa and with
        // either sign (or none) on the exponent. A leading "+" on the
        // exponent is dropped; a "-" is kept.
        $this->assertSame('6.02 × 10<sup>23</sup>', formatter::format('6.02E23'));
        $this->assertSame('6.02 × 10<sup>23</sup>', formatter::format('6.02e23'));
        $this->assertSame('1 × 10<sup>10</sup>', formatter::format('1E10'));
        $this->assertSame('1.6 × 10<sup>-19</sup>', formatter::format('1.6e-19'));
        $this->assertSame('3 × 10<sup>8</sup>', formatter::format('3e+8'));
        $this->assertStringNotContainsString('E', formatter::format('6.02E23'));
    }

    public function test_scientific_notation_explicit_times_form(): void {
        // The explicit form: "x", "*" or a real multiplication sign, with optional spaces.
        $this->assertSame('6.02 × 10<sup>23</sup>', formatter::format('6.02x10^23'));
        $this->assertSame('6.02 × 10<sup>23</sup>', formatter::format('6.02 x 10^23'));
        $this->assertSame('6.02 × 10<sup>23</sup>', formatter::format('6.02*10^23'));
        $this->assertSame('6.02 × 10<sup>23</sup>', formatter::format('6.02 × 10^23'));
        $this->assertSame('9.11 × 10<sup>-31</sup>', formatter::format('9.11x10^-31'));
        $this->assertStringNotContainsString('^', formatter::format('6.02x10^23'));
    }

    public function test_scientific_notation_bare_power_of_ten(): void {
        $this->assertSame('10<sup>23</sup>', formatter::format('10^23'));
        $this->assertSame('10<sup>-3</sup>', formatter::format('10^-3'));
        $this->assertSame(
            'There are about 10<sup>23</sup> molecules per mole.',
            formatter::format('There are about 10^23 molecules per mole.')
        );
    }

    public function test_scientific_notation_in_a_sentence_and_next_to_chemistry(): void {
        $this->assertSame(
            'One mole contains 6.02 × 10<sup>23</sup> particles.',
            formatter::format('One mole contains 6.02E23 particles.')
        );
        $this->assertSame(
            'K = 1.8 × 10<sup>-5</sup> for CH<sub>3</sub>COOH',
            formatter::format('K = 1.8x10^-5 for CH3COOH')
        );
    }

    public function test_scientific_notation_false_positives_are_left_untouched(): void {
        // An "e" glued to letters is not scientific notation - and neither
        // is an element symbol that merely contains an "e".
        $this->assertSame('cafe123', formatter::format('cafe123'));
        $this->assertSame('abc1e5xyz', formatter::format('abc1e5xyz'));
        $this->assertSame('Fe<sub>2</sub>O<sub>3</sub>', formatter::format('Fe2O3'));
        $this->assertSame('Ne<sub>2</sub>', formatter::format('Ne2'));
        // A trailing "." or extra digits break the match.
        $this->assertSame('version 1e2.3 build', formatter::format('version 1e2.3 build'));
        // An "x10" with no mantissa is left alone.
        $this->assertSame('buy 3 get x10 points', formatter::format('buy 3 get x10 points'));
        // A caret that is not a power of ten.
        $this->assertSame('a^b and 2^n', formatter::format('a^b and 2^n'));
        $this->assertTrue(formatter::has_changes('6.02E23', formatter::format('6.02E23')));
        $this->assertFalse(formatter::has_changes('a^b and 2^n', formatter::format('a^b and 2^n')));
    }

    public function test_full_equations_with_arrows(): void {
        $this->assertSame(
            'H<sub>2</sub> + O<sub>2</sub> → H<sub>2</sub>O',
            formatter::format('H2 + O2 -> H2O')
        );
        $this->assertSame(
            'H<sub>2</sub> + O<sub>2</sub> → H<sub>2</sub>O',
            formatter::format('H2 + O2 --> H2O')
        );
        $this->assertSame(
            'N<sub>2</sub> + 3H<sub>2</sub> ⇌ 2NH<sub>3</sub>',
            formatter::format('N2 + 3H2 <=> 2NH3')
        );
        $this->assertSame(
            'N<sub>2</sub> + 3H<sub>2</sub> ⇌ 2NH<sub>3</sub>',
            formatter::format('N2 + 3H2 <-> 2NH3')
        );
        $this->assertSame(
            '2NaCl(s) + H<sub>2</sub>SO<sub>4</sub>(aq) → Na<sub>2</sub>SO<sub>4</sub>(aq) + 2HCl(g)',
            formatter::format('2NaCl(s) + H2SO4(aq) -> Na2SO4(aq) + 2HCl(g)')
        );
    }

    public function test_false_positives_are_left_untouched(): void {
        $this->assertSame('In 2024', formatter::format('In 2024'));
        $this->assertSame('In', formatter::format('In'));
        $this->assertSame('As', formatter::format('As'));
        $this->assertSame('At', formatter::format('At'));
        $this->assertSame('US', formatter::format('US'));
        $this->assertSame('COVID19', formatter::format('COVID19'));
        $this->assertSame('NASA', formatter::format('NASA'));
        $this->assertSame(
            'A quick note about H<sub>2</sub>O and CO<sub>2</sub> levels.',
            formatter::format('A quick note about H2O and CO2 levels.')
        );
    }

    public function test_html_escaping(): void {
        $this->assertSame(
            '&lt;script&gt;alert(1)&lt;/script&gt;',
            formatter::format('<script>alert(1)</script>')
        );
    }

    public function test_empty_input(): void {
        $this->assertSame('', formatter::format(''));
    }

    public function test_has_changes(): void {
        $this->assertTrue(formatter::has_changes('H2O', formatter::format('H2O')));
        $this->assertFalse(formatter::has_changes('In 2024', formatter::format('In 2024')));

        // A pure arrow conversion has no <sub>/<sup> at all, but it is
        // still a real change and must be reported as one.
        $this->assertTrue(formatter::has_changes('A -> B', formatter::format('A -> B')));
    }

    public function test_parse_overrides(): void {
        $raw = "IT = I<sub>2</sub>T\n# a comment line\n\nNaOH=NaOH\n  Cl2  =  weird spacing \n";
        $this->assertSame([
            'IT' => 'I<sub>2</sub>T',
            'NaOH' => 'NaOH',
            'Cl2' => 'weird spacing',
        ], formatter::parse_overrides($raw));
    }

    public function test_parse_overrides_ignores_malformed_lines(): void {
        $this->assertSame([], formatter::parse_overrides("no equals sign here\n=noleftside\n   \n"));
    }

    public function test_override_forces_a_specific_rendering(): void {
        $this->assertSame(
            'I<sub>2</sub>T',
            formatter::format('IT', ['IT' => 'I<sub>2</sub>T'])
        );
    }

    public function test_override_can_exempt_text_from_automatic_conversion(): void {
        // Mapping a token to itself suppresses what the automatic rules
        // would otherwise have done to it.
        $this->assertSame('H2O', formatter::format('H2O', ['H2O' => 'H2O']));
    }

    public function test_override_wins_over_scientific_notation(): void {
        // An exact-match override on a scientific-notation token takes
        // precedence over the automatic conversion, and mapping it to
        // itself exempts it entirely.
        $this->assertSame(
            '10<sup>23</sup> (Avogadro)',
            formatter::format('10^23', ['10^23' => '10<sup>23</sup> (Avogadro)'])
        );
        $this->assertSame('10^23', formatter::format('10^23', ['10^23' => '10^23']));
    }

    public function test_override_does_not_affect_unrelated_text(): void {
        $this->assertSame(
            'Water is H<sub>2</sub>O.',
            formatter::format('Water is H2O.', ['IT' => 'I<sub>2</sub>T'])
        );
    }

    public function test_override_matches_the_whole_candidate_span_only(): void {
        // Token "H2O" embedded in a longer run of letters/digits is scanned as
        // one candidate span ("SuperH2OThing"), which does not equal the
        // override token "H2O" - so it is left completely alone, same as
        // any other unrecognised word.
        $this->assertSame(
            'SuperH2OThing',
            formatter::format('SuperH2OThing', ['H2O' => 'OVERRIDDEN'])
        );

        // A leading stoichiometric coefficient makes the scanned candidate
        // span "2H2O", not "H2O", so the override does not apply here
        // either - the automatic rules format it normally instead.
        $this->assertSame(
            '2H<sub>2</sub>O',
            formatter::format('2H2O', ['H2O' => 'OVERRIDDEN'])
        );
    }
}
