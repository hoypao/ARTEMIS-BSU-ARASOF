<?php
/**
 * RPAG/Discipline Recommender. Ranks a performer's recorded talents
 * (performer_talents, against the RPAG disciplines of Art. IV Sec. 12) so
 * OCA staff can see which Resident Performing Arts Group a student best
 * fits, instead of relying on the single category picked at audition time.
 *
 * Score = proficiency weight, plus small bonuses for active membership and
 * tenure — reflecting the Manual's emphasis on sustained, active
 * participation (Art. IV Sec. 10) over a one-off audition result.
 */

const ARTEMIS_PROFICIENCY_WEIGHTS = [
    'Beginner'     => 1,
    'Intermediate' => 2,
    'Advanced'     => 3,
    'Expert'       => 4,
];

/**
 * @param array<int, array{category_name: string, proficiency_level: string}> $talents
 */
function recommend_rpag_placement(array $talents, ?array $profile): array
{
    if (empty($talents)) {
        return [];
    }

    // Same flat bonus applies to every talent — it rewards the performer's overall
    // standing, not any one discipline, so it doesn't change the relative ranking
    // between their own talents (only proficiency does).
    $activeBonus = ($profile !== null && (int) ($profile['active_member'] ?? 0) === 1) ? 0.5 : 0;
    $tenureBonus = ($profile !== null && (int) ($profile['years_active'] ?? 0) >= 2) ? 0.5 : 0;

    $ranked = array_map(function ($t) use ($activeBonus, $tenureBonus) {
        $weight = ARTEMIS_PROFICIENCY_WEIGHTS[$t['proficiency_level']] ?? 1;
        return [
            'category' => $t['category_name'],
            'proficiency' => $t['proficiency_level'],
            'score' => $weight + $activeBonus + $tenureBonus,
        ];
    }, $talents);

    usort($ranked, fn($a, $b) => $b['score'] <=> $a['score']);

    // Top-scoring talent is the suggested RPAG placement; the rest are shown as
    // secondary fits (e.g. for cross-training) rather than discarded.
    foreach ($ranked as $i => &$r) {
        $r['rank'] = $i + 1;
        $r['role'] = $i === 0 ? 'Primary recommended placement' : 'Secondary / cross-training fit';
    }
    unset($r);

    return $ranked;
}
