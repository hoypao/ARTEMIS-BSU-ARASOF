<?php
/**
 * Training Specialist I-V qualification table (Art. VI Sec. 17-A) and the
 * cumulative-score-to-level mapping used by the Trainer Level Equivalency
 * Engine. The Manual defines the 5-criterion, 1-5 rubric itself (Sec. 17-B)
 * but leaves the score-to-level cutoffs to the evaluator's judgment; the
 * bands below split the 5-25 possible total evenly across the five levels
 * and are advisory only — final level assignment rests with the Head of
 * Culture and Arts and the Chancellor (Sec. 17-A).
 */

const ARTEMIS_TRAINER_RUBRIC_CRITERIA = [
    'experience'    => 'Relevant Experience',
    'recognition'   => 'Industry Recognition',
    'contributions' => 'Professional Contributions',
    'skills'        => 'Skills in Training Development',
    'credentials'   => 'Additional Credentials',
];

const ARTEMIS_TRAINER_LEVELS = [
    'Training Specialist I' => [
        'min_score' => 5, 'max_score' => 8, 'salary_grade' => 11,
        'education' => "Bachelor's Degree",
        'experience' => 'Less than two (2) years of experience in teaching or training in culture and arts.',
    ],
    'Training Specialist II' => [
        'min_score' => 9, 'max_score' => 12, 'salary_grade' => 15,
        'education' => "Bachelor's Degree",
        'experience' => 'At least two (2) but less than three (3) years of experience in teaching or training in the arts.',
    ],
    'Training Specialist III' => [
        'min_score' => 13, 'max_score' => 16, 'salary_grade' => 18,
        'education' => "Bachelor's Degree",
        'experience' => 'At least three (3) but less than five (5) years of experience in teaching and training.',
    ],
    'Training Specialist IV' => [
        'min_score' => 17, 'max_score' => 20, 'salary_grade' => 22,
        'education' => "Bachelor's Degree",
        'experience' => 'At least five (5) but less than seven (7) years of experience in education, training, or professional practice.',
    ],
    'Training Specialist V' => [
        'min_score' => 21, 'max_score' => 25, 'salary_grade' => 24,
        'education' => "Master's Degree",
        'experience' => 'At least seven (7) years of experience in advanced education, training, or professional practice.',
    ],
];

function compute_trainer_level(int $totalScore): array
{
    foreach (ARTEMIS_TRAINER_LEVELS as $name => $level) {
        if ($totalScore >= $level['min_score'] && $totalScore <= $level['max_score']) {
            return ['name' => $name] + $level;
        }
    }
    // Score is out of the 5-25 range (e.g. not all 5 criteria were rated yet) — clamp to the nearest end.
    $fallback = $totalScore > 25 ? 'Training Specialist V' : 'Training Specialist I';
    return ['name' => $fallback] + ARTEMIS_TRAINER_LEVELS[$fallback];
}
