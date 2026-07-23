<?php
/**
 * Canonical supporting-document catalog and which of them each application
 * type requires. Single source of truth for the student submission form,
 * apply_action.php validation, and the Ask Spartan FAQ copy.
 */

const ARTEMIS_DOCUMENT_CATALOG = [
    'cor'               => 'Certificate of Registration (COR)',
    'grades'            => 'Certified True Copy of Grades',
    'endorsement'       => "Trainer's Endorsement / Recommendation Letter",
    'waiver'            => "Parent's Waiver (BatStateU-FO-SOA-03)",
    'profile'           => 'Performer Profile / Portfolio',
    'membership'        => 'Certification of Active RPAG Membership',
    'performance_list'  => 'List of Performances (certified by Trainer & Head of OCA)',
    'consent'           => 'Consent Form',
    'training_cert'     => 'Certificate of Training',
    'performance_proof' => 'Proof of Involvement in Performances/Rehearsals',
    'awards'            => 'Awards / Certificates of Recognition',
    'school_statement'  => "Secondary School Culture & Arts Department Statement",
    'oath'              => 'Notarized Oath of Undertaking (from inviting institution)',
    'request_form'      => 'Request for Culture and Arts Presentation and Intermission Form (BatStateU-FO-OCA-04)',
];

/**
 * type code => [doc key => required]
 * Mirrors the documentary checklists in the Culture and Arts Development Manual
 * as operationalized by the current Work Instructions:
 * Stipend (Art. IX Sec. 31; BatStateU-WI-OCA-05 — adds the Performer's Profile Form
 * alongside the application form and list of performances; the Manual requires a
 * Consent Form while WI-OCA-05 names a Parent's Waiver (FO-SOA-03) — the Manual's
 * consent stays required and the Parent's Waiver is accepted as an optional slot),
 * External Invitation (Art. XI; BatStateU-WI-OCA-06 — the notarized Oath of
 * Undertaking and Request Form FO-OCA-04 come from the inviting institution after
 * approval, so they are optional at submission and attached as they arrive),
 * PATHFit (Art. X Sec. 37; BatStateU-WI-OCA-04),
 * BANTOG (Art. VIII Sec. 25; BatStateU-WI-OCA-10 — item (e) requires the trainer's
 * signed endorsement as part of the initial submission, not just a post-screening step),
 * Appeal Admission (Art. IV Sec. 11-B; BatStateU-WI-OCA-02).
 *
 * @return array<string, array<string, bool>>
 */
function application_document_requirements(): array
{
    return [
        'audition_recruitment' => ['cor' => true, 'grades' => true, 'profile' => true, 'endorsement' => false, 'waiver' => false],
        'stipend'              => ['performance_list' => true, 'consent' => true, 'grades' => true, 'membership' => true, 'profile' => true, 'cor' => false, 'endorsement' => false, 'waiver' => false],
        'pathfit_exemption'    => ['membership' => true, 'performance_proof' => true, 'awards' => false, 'endorsement' => false, 'waiver' => false, 'grades' => false, 'cor' => false, 'profile' => false],
        'bantog_recognition'   => ['cor' => true, 'membership' => true, 'grades' => true, 'training_cert' => true, 'endorsement' => true, 'waiver' => false, 'profile' => false],
        'external_invitation'  => ['endorsement' => true, 'oath' => false, 'request_form' => false, 'waiver' => false, 'grades' => false, 'cor' => false, 'profile' => false],
        'appeal_admission'     => ['awards' => true, 'endorsement' => true, 'school_statement' => true, 'grades' => false, 'cor' => false, 'waiver' => false, 'profile' => false],
    ];
}
