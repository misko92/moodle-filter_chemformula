# Chemistry Typing Standards for Moodle

How to type formulas, ions, equations and dimensional analysis so the site
formats them for you. Type plain keyboard text. Don't use the editor's
subscript or superscript buttons.

This covers two filters: `filter_chemformula` (sections 1–6 and 8) and
[`filter_dimanalysis`](https://github.com/misko92/moodle-filter_dimanalysis)
(section 7).

## The short version

1. Type formulas as plain text with normal digits: `H2O`, `Fe2(SO4)3`. The site adds the subscripts.
2. **Every charge gets a caret (`^`) before it:** `Mg^2+`, `Cl^-`, `SO4^2-`, `NH4^+`. Same rule for every ion.
3. Arrows: `->` gives →, and `<=>` gives ⇌.
4. Scientific notation: `6.02E23` or `6.02x10^23`. No space before the E.
5. Dimensional analysis: put every conversion factor in parentheses with a slash, and join terms with `x`: `5 L x (1 mol / 22.4 L)`.
6. Keep each dimensional analysis expression on one line. Don't press Enter partway through.
7. If something converts that shouldn't, wrap it in backticks: `` `PS5` ``.
8. Watch the editor highlight as you type. **Amber** means it will format as chemistry. **Blue** means it will format as dimensional analysis.

## 1. Chemical formulas

| You type | Students see | Notes |
|---|---|---|
| `H2O` | H<sub>2</sub>O | Digits after an element become subscripts. |
| `C6H12O6` | C<sub>6</sub>H<sub>12</sub>O<sub>6</sub> | Multi-digit subscripts work. |
| `Fe2(SO4)3` | Fe<sub>2</sub>(SO<sub>4</sub>)<sub>3</sub> | Parentheses and brackets are fine. |
| `CH3COOH` | CH<sub>3</sub>COOH | Condensed structural formulas work. |
| `2H2O` | 2H<sub>2</sub>O | A leading coefficient stays full size. `3 H2O` with a space also works. |
| `H2O(l)`  `NaCl(aq)` | H<sub>2</sub>O(l)  NaCl(aq) | State symbols (s), (l), (g), (aq) go straight after the formula, no space. |

Commas, full stops, question marks and brackets around a formula are fine:
`glucose (C6H12O6, molar mass 180.16 g/mol)` formats correctly.

## 2. Ions and charges

**The standard: type a caret (`^`) between the formula and the charge, for
every ion.** The caret means "superscript starts here". It's the same symbol
used for powers of ten (`10^23`), and it removes any doubt about where a
subscript ends and the charge begins.

| You type | Students see | Notes |
|---|---|---|
| `Mg^2+`  `Fe^3+`  `O^2-` | Mg<sup>2+</sup>  Fe<sup>3+</sup>  O<sup>2-</sup> | Monatomic ions |
| `Na^+`  `Cl^-`  `H^+` | Na<sup>+</sup>  Cl<sup>-</sup>  H<sup>+</sup> | Charge of 1: just the sign after the caret. |
| `SO4^2-`  `PO4^3-` | SO<sub>4</sub><sup>2-</sup>  PO<sub>4</sub><sup>3-</sup> | Polyatomic ions |
| `NO3^-`  `NH4^+`  `OH^-` | NO<sub>3</sub><sup>-</sup>  NH<sub>4</sub><sup>+</sup>  OH<sup>-</sup> | |
| `Cr2O7^2-` | Cr<sub>2</sub>O<sub>7</sub><sup>2-</sup> | |
| `[Cu(NH3)4]^2+` | [Cu(NH<sub>3</sub>)<sub>4</sub>]<sup>2+</sup> | Complex ions: caret after the closing bracket. |
| `Hg2^2+`  `O2^2-` | Hg<sub>2</sub><sup>2+</sup>  O<sub>2</sub><sup>2-</sup> | Here the caret is essential. Without it, `O22-` is read as a 22− charge. |
| `Cu^2+(aq)` | Cu<sup>2+</sup>(aq) | State symbol goes after the charge. |
| `Mg^+2` | Mg<sup>2+</sup> | Sign-first is accepted and shown number-first. |
| `CO3 ^2-` | CO<sub>3</sub> ^2- | ❌ No spaces anywhere in the ion. |

Older content typed without the caret (`Mg2+`, `SO42-`, `NH4+`) still
displays correctly, so existing pages don't need editing. Use the caret for
all new content.

## 3. Isotopes and nuclear notation

| You type | Students see | Notes |
|---|---|---|
| `U-238` or `238-U` | <sup>238</sup>U | Element symbol, hyphen, mass number. |
| `C-14` | <sup>14</sup>C | `carbon-14` written as a word stays as typed. |
| `238/92U` | <sup>238</sup><sub>92</sub>U | Mass / atomic number, then the symbol. No spaces. |
| `14/6C` | <sup>14</sup><sub>6</sub>C | |
| `0/-1e` | <sup>0</sup><sub>-1</sub>e | Beta particle / electron. Plain `e-` is not formatted. |
| `0/1e` | <sup>0</sup><sub>1</sub>e | Positron |
| `1/0n`  `1/1p` | <sup>1</sup><sub>0</sub>n  <sup>1</sup><sub>1</sub>p | Neutron, proton. Particle letters are lowercase. |
| `235/?U` | <sup>235</sup><sub>?</sub>U | Use `?` for a blank that students fill in. |

On the site, the mass and atomic numbers are stacked one above the other.

## 4. Equations and arrows

| You type | Students see | Notes |
|---|---|---|
| `->` or `-->` | → | Reaction arrow |
| `<=>` or `<->` | ⇌ | Equilibrium arrow |
| `2H2 + O2 -> 2H2O` | 2H<sub>2</sub> + O<sub>2</sub> → 2H<sub>2</sub>O | Spaces around `+` and the arrow. |
| `N2 + 3H2 <=> 2NH3` | N<sub>2</sub> + 3H<sub>2</sub> ⇌ 2NH<sub>3</sub> | |
| `=>`  `<-` | =>  <- | ❌ Not converted. Use the forms above. |

## 5. Hydrates

| You type | Students see | Notes |
|---|---|---|
| `CuSO4.5H2O` | CuSO<sub>4</sub>·5H<sub>2</sub>O | A full stop becomes the hydrate dot. |
| `CaSO4 . 2H2O` | CaSO<sub>4</sub>·2H<sub>2</sub>O | Spaced dot also works. |
| `Na2CO3.xH2O` | Na<sub>2</sub>CO<sub>3</sub>·xH<sub>2</sub>O | Use lowercase `x` for an unknown number. |
| `CO2. H2O is formed` | CO<sub>2</sub>. H<sub>2</sub>O is formed | A full stop followed by a space is treated as the end of a sentence. |

## 6. Scientific notation

| You type | Students see | Notes |
|---|---|---|
| `6.02E23` or `6.02e23` | 6.02 × 10<sup>23</sup> | |
| `6.02e-23` | 6.02 × 10<sup>-23</sup> | Negative exponents work. |
| `6.02x10^23` | 6.02 × 10<sup>23</sup> | `x`, `X`, `*` or `×` all work, with or without spaces. |
| `10^-3` | 10<sup>-3</sup> | A bare power of ten. |
| `6.02 E23` | 6.02 E23 | ❌ No space before the E. |

Unit powers like `cm^3` and `m/s^2` are **not** converted. Type `cm³` with
the character, or use the editor's superscript button for units.

## 7. Dimensional analysis

A chain of conversion factors is laid out as stacked fractions, with
cancelled units struck through.

### The pattern

```
GIVEN  x  (TOP / BOTTOM)  x  (TOP / BOTTOM) ...  = ANSWER
```

| You type | Result |
|---|---|
| `5 L x (1 mol / 22.4 L) x (46 g / 1 mol)` | ✅ Formats |
| `2 mol x (58.44 g / 1 mol) = 116.9 g` | ✅ Formats, with the answer after = |
| `25.0 g H2O x (1 mol H2O / 18.02 g H2O) x (6.02E23 molecules / 1 mol)` | ✅ Formats. H2O gets subscripts and 6.02E23 becomes 6.02 × 10<sup>23</sup>. |
| `3.0 mi x (1.609 km / 1 mi) x (1000 m / 1 km)` | ✅ Formats |
| `5 L × (1 mol / 22.4 L)` | ✅ Formats. `×`, `*` and `·` also work as the times sign. |

### Rules

1. Every conversion factor goes in **parentheses** with a **slash**: `(1 mol / 22.4 L)`.
2. Put a times sign between **every** pair of terms. An `x` needs a space or a parenthesis on both sides: `L x (` and `)x(` both work.
3. Write **number, then unit, then substance**: `2.4 g H2`, `1 mol NaCl`.
4. Keep the whole expression on **one line in one paragraph**. A line break in the middle stops everything after it from formatting.
5. Units are **case sensitive**: `mg` and `Mg` are different. Type units the same way every time.
6. The `= answer` part is optional and never cancels.
7. Parentheses nest only one level deep.

### How cancelling works

A unit on top cancels the same unit on the bottom. Substance labels must
match, or one side must have no label.

| Top | Bottom | Cancels? |
|---|---|---|
| `mol Na` | `mol Na` | ✅ Yes |
| `mol` | `mol Cu` | ✅ Yes: one side has no label |
| `mol Na` | `mol Cl` | ❌ No: different substances |
| `mg` | `Mg` | ❌ No: different case |

The site draws the working. It never checks the arithmetic.

### What won't format

| You type | Problem | Fix |
|---|---|---|
| `5 L x 1 mol / 22.4 L` | No parentheses around the factor | `5 L x (1 mol / 22.4 L)` |
| `5 L / (22.4 L / 1 mol)` | Dividing by a factor | Flip the factor and multiply |
| `5 L x (1 mol / 22.4 L) (46 g / 1 mol)` | Missing `x` between factors, so only the first part formats | Add `x` between them |
| `5 m/s x (3600 s / 1 hr)` | A slash in the starting quantity | Not supported. Rewrite the problem or leave it unformatted. |
| `5 box x (2 L / 1 box)` | A unit containing the letter x | Use a different word, or use `×` or `*` as the times sign |

### Forcing it on or off

| You type | Effect |
|---|---|
| `[da] 8.0 g NaOH x (1 mol / 40.00 g) [/da]` | Forces formatting for something the site misses, such as a single factor wrapped in parentheses. |
| `[da nocancel] 5 L x (1 mol / 22.4 L) [/da]` | Formats without striking through units. Useful when students should do the cancelling themselves. |

## 8. Stopping text from being converted

Some ordinary text looks like chemistry and gets formatted by mistake, for
example `K-12`, `N95`, `PS5`, `H1N1`, `B-52` and `F-16`.

| Method | When to use it |
|---|---|
| Backticks: `` `K-12` `` | A one-off. It shows exactly as typed, without the backticks. Keep both backticks on the same line. |
| Code or preformatted style in the editor | A whole block that should stay as typed. |
| Ask the site admin to add an override | Something that keeps coming up across the site. One override fixes it everywhere. |

Backticks also work when you want to show the raw input, for example when
teaching notation: `` `6.02x10^23` `` stays as typed.

## 9. Check before you save

- In the editor, recognised chemistry is highlighted **amber** and recognised dimensional analysis is highlighted **blue**. If there's no highlight, it won't format.
- The highlight never changes your text. It only shows what will happen.
- Highlighting needs Chrome, Edge, Safari, or Firefox 140 or later.
- After saving, preview the page once to confirm.
