<?php
/**
 * Faculty Non-Compliance Pattern Flag (BatStateU-WI-OCA-09). The work
 * instruction's escalation rule — a first violation gets a written warning
 * from the College Dean, a repeated violation goes to a Grievance Board
 * chaired by the Head of HRMO — is encoded here so a repeat offender is
 * flagged automatically instead of relying on OCA staff to recall or
 * manually tally prior complaints against the same faculty member.
 */

function determine_escalation_level(PDO $pdo, string $facultyName): string
{
    // Any earlier complaint on file for this faculty member (regardless of how it
    // was resolved) is enough to treat the new one as a repeat offense.
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM faculty_noncompliance_complaints WHERE faculty_name = :name');
    $stmt->execute(['name' => $facultyName]);
    $priorCount = (int) $stmt->fetchColumn();
    return $priorCount >= 1 ? 'Repeated Violation' : 'First Violation';
}

function escalation_recommended_action(string $level): string
{
    return $level === 'Repeated Violation'
        ? "Escalate to a Grievance Board chaired by the Head of HRMO, per the Chancellor's directive (WI-OCA-09; Art. V Sec. 16-A.2)."
        : 'Issue a written warning from the College Dean (WI-OCA-09; Art. V Sec. 16-A.1).';
}
