<?php

declare(strict_types=1);

namespace Atiweb\Edss;

/**
 * EDSS (Expanded Disability Status Scale) Calculator.
 *
 * Calculates the EDSS score based on 7 Functional System (FS) scores and an Ambulation score,
 * following the scoring table by Ludwig Kappos, MD (University Hospital Basel) and the
 * Neurostatus-EDSS™ standard (Kurtzke, 1983).
 *
 * Functional Systems (in Neurostatus-EDSS™ standard order):
 *   1. Visual (Optic)         — raw 0-6, converted to 0-4 for EDSS calculation
 *   2. Brainstem              — 0-5
 *   3. Pyramidal              — 0-6
 *   4. Cerebellar             — 0-5
 *   5. Sensory                — 0-6
 *   6. Bowel & Bladder        — raw 0-6, converted to 0-5 for EDSS calculation
 *   7. Cerebral (Mental)      — 0-5
 *   8. Ambulation             — 0-16 (determines EDSS ≥ 5.0 directly)
 *
 * @see https://github.com/atiweb/edss          JavaScript reference implementation
 * @see https://www.neurostatus.net/             Neurostatus-EDSS™
 */
class EdssCalculator
{
    /**
     * Calculate the EDSS score.
     *
     * @param int $visual         Raw Visual (Optic) FS score (0-6)
     * @param int $brainstem      Brainstem FS score (0-5)
     * @param int $pyramidal      Pyramidal FS score (0-6)
     * @param int $cerebellar     Cerebellar FS score (0-5)
     * @param int $sensory        Sensory FS score (0-6)
     * @param int $bowelBladder   Raw Bowel & Bladder FS score (0-6)
     * @param int $cerebral       Cerebral (Mental) FS score (0-5)
     * @param int $ambulation     Ambulation score (0-16)
     *
     * @return string The calculated EDSS score (e.g., '0', '1.5', '4', '6.5', '10')
     */
    public function calculate(
        int $visual,
        int $brainstem,
        int $pyramidal,
        int $cerebellar,
        int $sensory,
        int $bowelBladder,
        int $cerebral,
        int $ambulation,
    ): string {
        // ─── Phase 1: Ambulation-driven EDSS (≥ 5.0) ───
        // Ambulation score directly determines EDSS for scores ≥ 3
        $ambulationEdss = $this->getAmbulationEdss($ambulation);
        if ($ambulationEdss !== null) {
            return $ambulationEdss;
        }

        // ─── Phase 2: FS-driven EDSS (0 – 5.0) ───
        // Convert Visual and Bowel/Bladder scores to their adjusted ranges
        $convertedVisual = self::convertVisualScore($visual);
        $convertedBowelBladder = self::convertBowelAndBladderScore($bowelBladder);

        // Build the 7 FS array
        $functionalSystems = [
            $convertedVisual,
            $brainstem,
            $pyramidal,
            $cerebellar,
            $sensory,
            $convertedBowelBladder,
            $cerebral,
        ];

        [$maxValue, $maxCount] = self::findMaxAndCount($functionalSystems);

        return $this->calculateFromFunctionalSystems(
            $functionalSystems,
            $maxValue,
            $maxCount,
            $ambulation,
        );
    }

    /**
     * Calculate EDSS from an associative array (e.g., from REDCap data).
     *
     * Expected keys:
     *   - edss_func_visuais{suffix}
     *   - edss_cap_func_tronco_cereb{suffix}
     *   - edss_cap_func_pirad{suffix}
     *   - edss_cap_func_cereb{suffix}
     *   - edss_cap_func_sensitivas{suffix}
     *   - edss_func_vesicais_e_instestinais{suffix}
     *   - edss_func_cerebrais{suffix}
     *   - edss_func_demabulacao_incapacidade{suffix}
     *
     * @param array<string, string|int> $data   Associative array with EDSS field values
     * @param string $suffix                     Optional suffix (e.g., '_long' for longitudinal)
     *
     * @return string|null The calculated EDSS score, or null if data is incomplete
     */
    public function calculateFromArray(array $data, string $suffix = ''): ?string
    {
        $fields = [
            'visual'       => 'edss_func_visuais' . $suffix,
            'brainstem'    => 'edss_cap_func_tronco_cereb' . $suffix,
            'pyramidal'    => 'edss_cap_func_pirad' . $suffix,
            'cerebellar'   => 'edss_cap_func_cereb' . $suffix,
            'sensory'      => 'edss_cap_func_sensitivas' . $suffix,
            'bowelBladder' => 'edss_func_vesicais_e_instestinais' . $suffix,
            'cerebral'     => 'edss_func_cerebrais' . $suffix,
            'ambulation'   => 'edss_func_demabulacao_incapacidade' . $suffix,
        ];

        $values = [];
        foreach ($fields as $key => $fieldName) {
            $value = $data[$fieldName] ?? '';
            if (strlen((string) $value) === 0) {
                return null; // Incomplete data
            }
            $values[$key] = (int) $value;
        }

        return $this->calculate(
            $values['visual'],
            $values['brainstem'],
            $values['pyramidal'],
            $values['cerebellar'],
            $values['sensory'],
            $values['bowelBladder'],
            $values['cerebral'],
            $values['ambulation'],
        );
    }

    /**
     * Convert the raw Visual (Optic) FS score to its adjusted value for EDSS.
     *
     * The Visual FS uses a 0-6 scale but is compressed for EDSS calculation:
     *   0 → 0, 1 → 1, 2-3 → 2, 4-5 → 3, 6 → 4
     */
    public static function convertVisualScore(int $rawScore): int
    {
        if ($rawScore === 6) {
            return 4;
        }
        if ($rawScore >= 4) {
            return 3;
        }
        if ($rawScore >= 2) {
            return 2;
        }

        return $rawScore; // 0 or 1
    }

    /**
     * Convert the raw Bowel & Bladder FS score to its adjusted value for EDSS.
     *
     * The Bowel & Bladder FS uses a 0-6 scale but is compressed for EDSS calculation:
     *   0 → 0, 1 → 1, 2 → 2, 3-4 → 3, 5 → 4, 6 → 5
     */
    public static function convertBowelAndBladderScore(int $rawScore): int
    {
        if ($rawScore === 6) {
            return 5;
        }
        if ($rawScore === 5) {
            return 4;
        }
        if ($rawScore >= 3) {
            return 3;
        }

        return $rawScore; // 0, 1, or 2
    }

    /**
     * Get the EDSS score determined directly by ambulation (for scores ≥ 3).
     *
     * @return string|null The EDSS score, or null if ambulation doesn't directly determine it
     */
    private function getAmbulationEdss(int $ambulation): ?string
    {
        return match (true) {
            $ambulation === 16 => '10',    // Death due to MS
            $ambulation === 15 => '9.5',   // Totally helpless bed patient
            $ambulation === 14 => '9',     // Helpless bed patient; can communicate and eat
            $ambulation === 13 => '8.5',   // Restricted to bed; some use of arm(s)
            $ambulation === 12 => '8',     // Restricted to bed/chair, out of bed most of day
            $ambulation === 11 => '7.5',   // Wheelchair with help
            $ambulation === 10 => '7',     // Wheelchair without help
            $ambulation === 9,
            $ambulation === 8  => '6.5',   // Bilateral assistance or limited walking
            $ambulation === 7,
            $ambulation === 6,
            $ambulation === 5  => '6',     // Unilateral/bilateral assistance ≥120m
            $ambulation === 4  => '5.5',   // Walks 100-200m without help
            $ambulation === 3  => '5',     // Walks 200-300m without help
            default => null,               // FS-driven EDSS (ambulation 0-2)
        };
    }

    /**
     * Calculate EDSS from FS scores when ambulation is 0-2 (Phase 2).
     *
     * @param array<int> $fs          The 7 functional system scores (already converted)
     * @param int        $maxValue    Maximum value among FS scores
     * @param int        $maxCount    Number of FS scores equal to maxValue
     * @param int        $ambulation  Ambulation score (0-2)
     */
    private function calculateFromFunctionalSystems(
        array $fs,
        int $maxValue,
        int $maxCount,
        int $ambulation,
    ): string {
        // ── EDSS 5.0: FS-based ──
        if ($maxValue >= 5) {
            return '5';
        }

        if ($maxValue === 4 && $maxCount >= 2) {
            return '5';
        }

        if ($maxValue === 4 && $maxCount === 1) {
            [$secondMax, $secondCount] = self::findSecondMaxAndCount($fs, $maxValue);

            if ($secondMax === 3 && $secondCount > 2) {
                return '5';
            }
            if ($secondMax === 3 || $secondMax === 2) {
                return '4.5';
            }
            if ($ambulation < 2 && $secondMax < 2) {
                return '4';
            }
        }

        // Check here because of ambulation score — the only case where it could go to 5
        if ($maxValue === 3 && $maxCount >= 6) {
            return '5';
        }

        // ── EDSS 4.5: Ambulation = 2 ──
        if ($ambulation === 2) {
            return '4.5';
        }

        // ── EDSS 3.0 – 4.5: maxValue = 3 ──
        if ($maxValue === 3) {
            if ($maxCount === 5) {
                return '4.5';
            }

            if ($maxCount >= 2) {
                if ($maxCount === 2) {
                    [$secondMax] = self::findSecondMaxAndCount($fs, $maxValue);
                    if ($secondMax <= 1) {
                        return '3.5';
                    }
                }

                return '4';
            }

            // maxCount is 1
            [$secondMax, $secondCount] = self::findSecondMaxAndCount($fs, $maxValue);

            if ($secondMax === 2) {
                if ($secondCount >= 3) {
                    return '4';
                }

                return '3.5';
            }

            // Second max is 0 or 1
            return '3';
        }

        // ── EDSS 2.0 – 4.0: maxValue = 2 ──
        if ($maxValue === 2) {
            if ($maxCount >= 6) {
                return '4';
            }
            if ($maxCount === 5) {
                return '3.5';
            }
            if ($maxCount === 3 || $maxCount === 4) {
                return '3';
            }
            if ($maxCount === 2) {
                return '2.5';
            }

            return '2';
        }

        // ── EDSS 2.0: Ambulation = 1 ──
        if ($ambulation === 1) {
            return '2';
        }

        // ── EDSS 1.0 – 1.5: maxValue = 1 ──
        if ($maxValue === 1) {
            if ($maxCount >= 2) {
                return '1.5';
            }

            return '1';
        }

        // ── EDSS 0.0: All scores are 0 ──
        return '0';
    }

    /**
     * Find the maximum value in an array and how many times it appears.
     *
     * @param array<int> $scores
     * @return array{int, int} [maxValue, count]
     */
    public static function findMaxAndCount(array $scores): array
    {
        $max = max(...$scores);
        $count = count(array_filter($scores, fn(int $v): bool => $v >= $max));

        return [$max, $count];
    }

    /**
     * Find the second-largest value in an array and how many times it appears.
     *
     * @param array<int> $scores
     * @param int        $max    The maximum value to exclude
     * @return array{int, int} [secondMaxValue, count]
     */
    public static function findSecondMaxAndCount(array $scores, int $max): array
    {
        $filtered = array_filter($scores, fn(int $v): bool => $v < $max);

        if (empty($filtered)) {
            return [0, 0];
        }

        $secondMax = max(...$filtered);
        $count = count(array_filter($filtered, fn(int $v): bool => $v >= $secondMax));

        return [$secondMax, $count];
    }
}
