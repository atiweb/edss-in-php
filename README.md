# EDSS Calculator for PHP

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)

PHP implementation of the **Expanded Disability Status Scale (EDSS)** calculator, based on the scoring table by Ludwig Kappos, MD (University Hospital Basel) and the Neurostatus-EDSS™ standard (Kurtzke, 1983).

This library calculates the EDSS score from 7 Functional System (FS) scores and an Ambulation score, used in the clinical assessment of Multiple Sclerosis.

Based on the [JavaScript reference implementation](https://github.com/atiweb/edss) (forked from [adobrasinovic/edss](https://github.com/adobrasinovic/edss)).

## Installation

```bash
composer require atiweb/edss-in-php
```

## Usage

### Direct calculation with individual scores

```php
use Atiweb\Edss\EdssCalculator;

$calculator = new EdssCalculator();

// Parameters (in Neurostatus-EDSS™ standard order):
//   Visual (raw 0-6), Brainstem (0-5), Pyramidal (0-6), Cerebellar (0-5),
//   Sensory (0-6), Bowel & Bladder (raw 0-6), Cerebral (0-5), Ambulation (0-16)

$edss = $calculator->calculate(
    visual: 1,
    brainstem: 2,
    pyramidal: 1,
    cerebellar: 3,
    sensory: 1,
    bowelBladder: 4,
    cerebral: 2,
    ambulation: 1,
);

echo $edss; // "4"
```

### From an associative array (e.g., REDCap data)

```php
$data = [
    'edss_func_visuais' => '1',
    'edss_cap_func_tronco_cereb' => '2',
    'edss_cap_func_pirad' => '1',
    'edss_cap_func_cereb' => '3',
    'edss_cap_func_sensitivas' => '1',
    'edss_func_vesicais_e_instestinais' => '4',
    'edss_func_cerebrais' => '2',
    'edss_func_demabulacao_incapacidade' => '1',
];

$edss = $calculator->calculateFromArray($data);
echo $edss; // "4"

// Supports suffixed keys (e.g., longitudinal data)
$edss = $calculator->calculateFromArray($longitudinalData, '_long');
```

### Score conversions

The Visual and Bowel & Bladder Functional Systems use a wider raw scale that is compressed for EDSS calculation:

```php
// Visual: raw 0-6 → converted 0-4
//   0→0, 1→1, 2-3→2, 4-5→3, 6→4
EdssCalculator::convertVisualScore(3);  // 2
EdssCalculator::convertVisualScore(5);  // 3

// Bowel & Bladder: raw 0-6 → converted 0-5
//   0→0, 1→1, 2→2, 3-4→3, 5→4, 6→5
EdssCalculator::convertBowelAndBladderScore(4);  // 3
EdssCalculator::convertBowelAndBladderScore(6);  // 5
```

## Functional Systems

| # | Functional System   | Raw Scale | Converted Scale |
|---|--------------------|-----------|-----------------|
| 1 | Visual (Optic)     | 0-6       | 0-4             |
| 2 | Brainstem          | 0-5       | —               |
| 3 | Pyramidal          | 0-6       | —               |
| 4 | Cerebellar         | 0-5       | —               |
| 5 | Sensory            | 0-6       | —               |
| 6 | Bowel & Bladder    | 0-6       | 0-5             |
| 7 | Cerebral (Mental)  | 0-5       | —               |
| 8 | Ambulation         | 0-16      | —               |

> **Note:** The Ambulation score (≥3) directly determines the EDSS score (≥5.0). When Ambulation is 0-2, the EDSS is determined by the combination of FS scores.

## Algorithm

The EDSS calculation follows a two-phase approach:

### Phase 1: Ambulation-driven (EDSS ≥ 5.0)
When the Ambulation score is ≥ 3, it directly maps to an EDSS value:

| Ambulation | EDSS | Description |
|------------|------|-------------|
| 3          | 5.0  | Walks 200-300m without help |
| 4          | 5.5  | Walks 100-200m without help |
| 5-7        | 6.0  | Assistance needed for walking |
| 8-9        | 6.5  | Bilateral/limited assistance |
| 10         | 7.0  | Wheelchair without help |
| 11         | 7.5  | Wheelchair with help |
| 12         | 8.0  | Restricted to bed/chair |
| 13         | 8.5  | Restricted to bed |
| 14         | 9.0  | Helpless bed patient |
| 15         | 9.5  | Totally helpless |
| 16         | 10.0 | Death due to MS |

### Phase 2: FS-driven (EDSS 0 – 5.0)
When Ambulation is 0-2, the EDSS is calculated from the combination of the 7 FS scores based on the Kappos scoring table, considering:
- The maximum FS score and how many systems have that score
- The second-highest FS score and its frequency
- The Ambulation score (0, 1, or 2)

## Testing

```bash
composer install
composer test
```

The test suite includes 70+ test cases covering:
- All EDSS ranges from 0 to 10
- Visual and Bowel/Bladder score conversions
- All ambulation-driven scores
- Edge cases for FS combinations
- The reference example from the JavaScript implementation

## References

- Kurtzke JF. Rating neurologic impairment in multiple sclerosis: an expanded disability status scale (EDSS). *Neurology*. 1983;33(11):1444-1452.
- [Neurostatus-EDSS™](https://www.neurostatus.net/)
- [JavaScript reference implementation](https://github.com/atiweb/edss)

## License

[MIT](LICENSE)
