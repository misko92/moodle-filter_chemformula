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
 * Shared filter/highlighter parity cases.
 *
 * tests/fixtures/parity_cases.json is a list of {text, html} pairs: how
 * this filter renders each input. tiny_chemformula keeps an identical copy
 * and its jest suite checks that the editor highlighter's previews agree
 * with the same html, so the PHP and JS detectors can't silently drift
 * apart. Add a case here whenever detection behaviour changes, and copy
 * the file to tiny_chemformula's tests/fixtures/.
 *
 * @package    filter_chemformula
 * @category   test
 * @copyright  2026 Moodle
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(formatter::class)]
final class parity_test extends \basic_testcase {
    /** @var string The canonical parity fixture. */
    private const FIXTURE = __DIR__ . '/../fixtures/parity_cases.json';

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function parity_cases(): array {
        $cases = [];
        foreach (json_decode(file_get_contents(self::FIXTURE), true, 512, JSON_THROW_ON_ERROR) as $case) {
            $cases[$case['text']] = [$case['text'], $case['html']];
        }
        return $cases;
    }

    /**
     * @param string $text
     * @param string $html
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('parity_cases')]
    public function test_renders_parity_case(string $text, string $html): void {
        $this->assertSame($html, formatter::format($text));
    }

    public function test_tiny_copy_of_the_fixture_is_identical(): void {
        global $CFG;
        $tinycopy = $CFG->dirroot . '/lib/editor/tiny/plugins/chemformula/tests/fixtures/parity_cases.json';
        if (!file_exists($tinycopy)) {
            $this->markTestSkipped('tiny_chemformula (or its parity fixture) is not installed alongside.');
        }
        $this->assertFileEquals(self::FIXTURE, $tinycopy, 'Copy tests/fixtures/parity_cases.json to tiny_chemformula.');
    }
}
