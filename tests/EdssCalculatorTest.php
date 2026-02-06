<?php

declare(strict_types=1);

namespace Atiweb\Edss\Tests;

use Atiweb\Edss\EdssCalculator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class EdssCalculatorTest extends TestCase
{
    private EdssCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new EdssCalculator();
    }

    // ─── Visual Score Conversion ─────────────────────────────────

    public function testVisualScoreConversion(): void
    {
        // 0 → 0, 1 → 1, 2-3 → 2, 4-5 → 3, 6 → 4
        $this->assertSame(0, EdssCalculator::convertVisualScore(0));
        $this->assertSame(1, EdssCalculator::convertVisualScore(1));
        $this->assertSame(2, EdssCalculator::convertVisualScore(2));
        $this->assertSame(2, EdssCalculator::convertVisualScore(3));
        $this->assertSame(3, EdssCalculator::convertVisualScore(4));
        $this->assertSame(3, EdssCalculator::convertVisualScore(5));
        $this->assertSame(4, EdssCalculator::convertVisualScore(6));
    }

    // ─── Bowel & Bladder Score Conversion ────────────────────────

    public function testBowelBladderScoreConversion(): void
    {
        // 0 → 0, 1 → 1, 2 → 2, 3-4 → 3, 5 → 4, 6 → 5
        $this->assertSame(0, EdssCalculator::convertBowelAndBladderScore(0));
        $this->assertSame(1, EdssCalculator::convertBowelAndBladderScore(1));
        $this->assertSame(2, EdssCalculator::convertBowelAndBladderScore(2));
        $this->assertSame(3, EdssCalculator::convertBowelAndBladderScore(3));
        $this->assertSame(3, EdssCalculator::convertBowelAndBladderScore(4));
        $this->assertSame(4, EdssCalculator::convertBowelAndBladderScore(5));
        $this->assertSame(5, EdssCalculator::convertBowelAndBladderScore(6));
    }

    // ─── Helper Functions ────────────────────────────────────────

    public function testFindMaxAndCount(): void
    {
        $this->assertSame([3, 2], EdssCalculator::findMaxAndCount([1, 3, 2, 3, 0]));
        $this->assertSame([5, 1], EdssCalculator::findMaxAndCount([1, 5, 2, 3, 0]));
        $this->assertSame([0, 7], EdssCalculator::findMaxAndCount([0, 0, 0, 0, 0, 0, 0]));
    }

    public function testFindSecondMaxAndCount(): void
    {
        $this->assertSame([2, 1], EdssCalculator::findSecondMaxAndCount([1, 3, 2, 3, 0], 3));
        $this->assertSame([3, 2], EdssCalculator::findSecondMaxAndCount([1, 5, 3, 3, 0], 5));
        $this->assertSame([0, 0], EdssCalculator::findSecondMaxAndCount([3, 3, 3], 3));
    }

    // ─── calculateFromArray with default English fields ─────────

    public function testCalculateFromArrayReturnsNullOnIncompleteData(): void
    {
        $this->assertNull($this->calculator->calculateFromArray([]));
        $this->assertNull($this->calculator->calculateFromArray([
            'visual_functions_score' => '0',
            'brainstem_functions_score' => '0',
            // missing fields
        ]));
    }

    public function testCalculateFromArrayWithDefaultEnglishFields(): void
    {
        $data = [
            'visual_functions_score' => '1',
            'brainstem_functions_score' => '2',
            'pyramidal_functions_score' => '1',
            'cerebellar_functions_score' => '3',
            'sensory_functions_score' => '1',
            'bowel_and_bladder_functions_score' => '4',
            'cerebral_functions_score' => '2',
            'ambulation_score' => '1',
        ];

        // Same as reference example: calculateEDSS(1,2,1,3,1,4,2,1) → 4
        $this->assertSame('4', $this->calculator->calculateFromArray($data));
    }

    public function testCalculateFromArrayWithRedcapPortugueseFields(): void
    {
        $data = [
            'edss_func_visuais' => '1',                       // Visual Functions Score
            'edss_cap_func_tronco_cereb' => '2',              // Brainstem Functions Score
            'edss_cap_func_pirad' => '1',                     // Pyramidal Functions Score
            'edss_cap_func_cereb' => '3',                     // Cerebellar Functions Score
            'edss_cap_func_sensitivas' => '1',                // Sensory Functions Score
            'edss_func_vesicais_e_instestinais' => '4',       // Bowel & Bladder Functions Score
            'edss_func_cerebrais' => '2',                     // Cerebral Functions Score
            'edss_func_demabulacao_incapacidade' => '1',      // Ambulation Score
        ];

        // Same as reference example: calculateEDSS(1,2,1,3,1,4,2,1) → 4
        $this->assertSame('4', $this->calculator->calculateFromArray($data, EdssCalculator::FIELDS_REDCAP_PT));
    }

    public function testCalculateFromArrayWithSuffix(): void
    {
        // Using default English fields with a suffix
        $data = [
            'visual_functions_score_long' => '0',
            'brainstem_functions_score_long' => '0',
            'pyramidal_functions_score_long' => '0',
            'cerebellar_functions_score_long' => '0',
            'sensory_functions_score_long' => '0',
            'bowel_and_bladder_functions_score_long' => '0',
            'cerebral_functions_score_long' => '0',
            'ambulation_score_long' => '0',
        ];

        $this->assertSame('0', $this->calculator->calculateFromArray($data, suffix: '_long'));
    }

    public function testCalculateFromArrayWithRedcapSuffix(): void
    {
        // Using REDCap Portuguese fields with a suffix
        $data = [
            'edss_func_visuais_long' => '0',
            'edss_cap_func_tronco_cereb_long' => '0',
            'edss_cap_func_pirad_long' => '0',
            'edss_cap_func_cereb_long' => '0',
            'edss_cap_func_sensitivas_long' => '0',
            'edss_func_vesicais_e_instestinais_long' => '0',
            'edss_func_cerebrais_long' => '0',
            'edss_func_demabulacao_incapacidade_long' => '0',
        ];

        $this->assertSame('0', $this->calculator->calculateFromArray($data, EdssCalculator::FIELDS_REDCAP_PT, '_long'));
    }

    public function testCalculateFromArrayWithCustomFieldMap(): void
    {
        // Users can define their own field mappings
        $customFields = [
            'visual'       => 'fs_visual',
            'brainstem'    => 'fs_brainstem',
            'pyramidal'    => 'fs_pyramidal',
            'cerebellar'   => 'fs_cerebellar',
            'sensory'      => 'fs_sensory',
            'bowelBladder' => 'fs_bowel_bladder',
            'cerebral'     => 'fs_cerebral',
            'ambulation'   => 'fs_ambulation',
        ];

        $data = [
            'fs_visual' => '0',
            'fs_brainstem' => '0',
            'fs_pyramidal' => '2',
            'fs_cerebellar' => '0',
            'fs_sensory' => '0',
            'fs_bowel_bladder' => '0',
            'fs_cerebral' => '2',
            'fs_ambulation' => '0',
        ];

        // Two FS=2 → EDSS 2.5
        $this->assertSame('2.5', $this->calculator->calculateFromArray($data, $customFields));
    }

    // ─── EDSS Calculation (parametrized) ─────────────────────────

    /**
     * @return array<string, array{int, int, int, int, int, int, int, int, string}>
     */
    public static function edssDataProvider(): array
    {
        // Format: [Visual(raw), Brainstem, Pyramidal, Cerebellar, Sensory, BowelBladder(raw), Cerebral, Ambulation, Expected]
        return [
            // ── EDSS 0 ──
            'All zeros → 0'                           => [0, 0, 0, 0, 0, 0, 0,  0, '0'],

            // ── EDSS 1.0 ──
            'One FS=1 (Pyramidal) → 1'                 => [0, 0, 1, 0, 0, 0, 0,  0, '1'],
            'One FS=1 (BB raw=1) → 1'                  => [0, 0, 0, 0, 0, 1, 0,  0, '1'],
            'One FS=1 (Cerebellar) → 1'                => [0, 0, 0, 1, 0, 0, 0,  0, '1'],
            'Visual raw=1 → conv=1 → 1'                => [1, 0, 0, 0, 0, 0, 0,  0, '1'],

            // ── EDSS 1.5 ──
            'Two FS=1 → 1.5'                           => [0, 1, 1, 0, 0, 0, 0,  0, '1.5'],
            'Seven FS=1 → 1.5'                         => [1, 1, 1, 1, 1, 1, 1,  0, '1.5'],

            // ── EDSS 2.0 ──
            'One FS=2 → 2'                             => [0, 0, 2, 0, 0, 0, 0,  0, '2'],
            'Amb=1 all FS≤1 → 2'                       => [0, 0, 0, 0, 0, 0, 0,  1, '2'],
            'Amb=1 seven FS=1 → 2'                     => [1, 1, 1, 1, 1, 1, 1,  1, '2'],
            'Visual raw=2 → conv=2 → 2'                => [2, 0, 0, 0, 0, 0, 0,  0, '2'],
            'Visual raw=3 → conv=2 → 2'                => [3, 0, 0, 0, 0, 0, 0,  0, '2'],
            'BB raw=2 → conv=2 → 2'                    => [0, 0, 0, 0, 0, 2, 0,  0, '2'],

            // ── EDSS 2.5 ──
            'Two FS=2 → 2.5'                           => [0, 0, 2, 2, 0, 0, 0,  0, '2.5'],
            'Vis raw=2 + BS=2 → 2.5'                   => [2, 2, 0, 0, 0, 0, 0,  0, '2.5'],

            // ── EDSS 3.0 ──
            'Three FS=2 → 3'                           => [0, 0, 2, 2, 2, 0, 0,  0, '3'],
            'Four FS=2 → 3'                            => [0, 0, 2, 2, 2, 2, 0,  0, '3'],
            'One FS=3 → 3'                             => [0, 3, 0, 0, 0, 0, 0,  0, '3'],
            'One FS=3 rest=1 → 3'                      => [1, 3, 1, 1, 1, 1, 1,  0, '3'],
            'BB raw=3 → conv=3 → 3'                    => [0, 0, 0, 0, 0, 3, 0,  0, '3'],
            'BB raw=4 → conv=3 → 3'                    => [0, 0, 0, 0, 0, 4, 0,  0, '3'],
            'Visual raw=4 → conv=3 → 3'                => [4, 0, 0, 0, 0, 0, 0,  0, '3'],
            'Visual raw=5 → conv=3 → 3'                => [5, 0, 0, 0, 0, 0, 0,  0, '3'],

            // ── EDSS 3.5 ──
            'Five FS=2 → 3.5'                          => [2, 2, 2, 2, 2, 0, 0,  0, '3.5'],
            '1×FS=3 + 1×FS=2 → 3.5'                   => [0, 0, 3, 2, 0, 0, 0,  0, '3.5'],
            '1×FS=3 + Vis raw=2 → 3.5'                 => [2, 0, 0, 0, 0, 0, 3,  0, '3.5'],
            '2×FS=3 secondMax≤1 → 3.5'                 => [0, 0, 0, 3, 0, 0, 3,  0, '3.5'],
            '2×FS=3 + 1×FS=1 → 3.5'                   => [0, 0, 0, 3, 1, 0, 3,  0, '3.5'],

            // ── EDSS 4.0 ──
            'Six FS=2 → 4'                             => [2, 2, 2, 2, 2, 2, 0,  0, '4'],
            'Seven FS=2 → 4'                           => [2, 2, 2, 2, 2, 2, 2,  0, '4'],
            '1×FS=4 rest=0 → 4'                        => [0, 0, 4, 0, 0, 0, 0,  0, '4'],
            '1×FS=4 rest=1 → 4'                        => [1, 1, 1, 1, 1, 1, 4,  0, '4'],
            '2×FS=3 + 2×FS=2 → 4'                     => [0, 0, 0, 3, 2, 0, 3,  0, '4'],
            '4×FS=3 → 4'                               => [2, 3, 3, 3, 2, 2, 3,  0, '4'],
            '1×FS=3 + 3×FS=2 → 4'                     => [2, 2, 2, 0, 0, 0, 3,  0, '4'],
            'Visual raw=6 → conv=4 → 4'                => [6, 0, 0, 0, 0, 0, 0,  0, '4'],
            'BB raw=5 → conv=4 → 4'                    => [0, 0, 0, 0, 0, 5, 0,  0, '4'],

            // ── EDSS 4.5 ──
            'Amb=2 → 4.5'                              => [0, 0, 0, 0, 0, 0, 0,  2, '4.5'],
            '1×FS=4 + 1×FS=3 → 4.5'                   => [0, 0, 4, 3, 0, 0, 0,  0, '4.5'],
            '1×FS=4 + 1×FS=2 → 4.5'                   => [0, 2, 0, 0, 0, 0, 4,  0, '4.5'],
            '1×FS=4 + 2×FS=3 → 4.5'                   => [0, 4, 3, 3, 0, 0, 0,  0, '4.5'],
            '5×FS=3 → 4.5'                             => [1, 3, 3, 3, 3, 1, 3,  0, '4.5'],
            'Vis raw=5 conv=3 Amb=2 → 4.5'             => [5, 1, 1, 1, 1, 1, 1,  2, '4.5'],
            '1×FS=4 Amb=2 → 4.5'                      => [0, 0, 0, 0, 0, 0, 4,  2, '4.5'],

            // ── EDSS 5.0 ──
            'Amb=3 → 5'                                => [0, 0, 0, 0, 0, 0, 0,  3, '5'],
            '2×FS=4 → 5'                               => [0, 0, 4, 4, 0, 0, 0,  0, '5'],
            'BS=5 → 5'                                 => [0, 5, 0, 0, 0, 0, 0,  0, '5'],
            'Pyr=5 → 5'                                => [0, 0, 5, 0, 0, 0, 0,  0, '5'],
            'Sen=5 → 5'                                => [0, 0, 0, 0, 5, 0, 0,  0, '5'],
            'BB raw=6 → conv=5 → 5'                    => [0, 0, 0, 0, 0, 6, 0,  0, '5'],
            '6×FS=3 → 5'                               => [4, 3, 3, 3, 3, 3, 3,  0, '5'],
            '1×FS=4 + 3×FS=3 → 5'                     => [1, 4, 3, 3, 3, 1, 1,  0, '5'],

            // ── EDSS 5.5 ──
            'Amb=4 → 5.5'                              => [0, 0, 0, 0, 0, 0, 0,  4, '5.5'],

            // ── EDSS 6.0 ──
            'Amb=5 → 6'                                => [0, 0, 0, 0, 0, 0, 0,  5, '6'],
            'Amb=6 → 6'                                => [0, 0, 0, 0, 0, 0, 0,  6, '6'],
            'Amb=7 → 6'                                => [0, 0, 0, 0, 0, 0, 0,  7, '6'],

            // ── EDSS 6.5 ──
            'Amb=8 → 6.5'                              => [0, 0, 0, 0, 0, 0, 0,  8, '6.5'],
            'Amb=9 → 6.5'                              => [0, 0, 0, 0, 0, 0, 0,  9, '6.5'],

            // ── EDSS 7.0 – 10.0 ──
            'Amb=10 → 7'                               => [0, 0, 0, 0, 0, 0, 0, 10, '7'],
            'Amb=11 → 7.5'                             => [0, 0, 0, 0, 0, 0, 0, 11, '7.5'],
            'Amb=12 → 8'                               => [0, 0, 0, 0, 0, 0, 0, 12, '8'],
            'Amb=13 → 8.5'                             => [0, 0, 0, 0, 0, 0, 0, 13, '8.5'],
            'Amb=14 → 9'                               => [0, 0, 0, 0, 0, 0, 0, 14, '9'],
            'Amb=15 → 9.5'                             => [0, 0, 0, 0, 0, 0, 0, 15, '9.5'],
            'Amb=16 → 10'                              => [0, 0, 0, 0, 0, 0, 0, 16, '10'],

            // ── Reference example from JS repo ──
            'Ref: calculateEDSS(1,2,1,3,1,4,2,1) → 4' => [1, 2, 1, 3, 1, 4, 2,  1, '4'],
        ];
    }

    #[DataProvider('edssDataProvider')]
    public function testEdssCalculation(
        int $visualFunctionsScore,
        int $brainstemFunctionsScore,
        int $pyramidalFunctionsScore,
        int $cerebellarFunctionsScore,
        int $sensoryFunctionsScore,
        int $bowelAndBladderFunctionsScore,
        int $cerebralFunctionsScore,
        int $ambulationScore,
        string $expected,
    ): void {
        $result = $this->calculator->calculate(
            $visualFunctionsScore,
            $brainstemFunctionsScore,
            $pyramidalFunctionsScore,
            $cerebellarFunctionsScore,
            $sensoryFunctionsScore,
            $bowelAndBladderFunctionsScore,
            $cerebralFunctionsScore,
            $ambulationScore,
        );

        $this->assertSame(
            $expected,
            $result,
            sprintf(
                'EDSS mismatch for Visual=%d Brainstem=%d Pyramidal=%d Cerebellar=%d Sensory=%d BowelBladder=%d Cerebral=%d Ambulation=%d: expected %s, got %s',
                $visualFunctionsScore, $brainstemFunctionsScore, $pyramidalFunctionsScore,
                $cerebellarFunctionsScore, $sensoryFunctionsScore, $bowelAndBladderFunctionsScore,
                $cerebralFunctionsScore, $ambulationScore,
                $expected, $result,
            ),
        );
    }
}
