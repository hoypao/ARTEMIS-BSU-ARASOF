<?php
/**
 * Culture & Arts disciplines (Art. IV Sec. 12 — Music, Dance, Theater Arts,
 * Film, Visual Arts) plus two fields BANTOG also recognizes even though
 * they aren't part of the RPAG training roster (Art. VIII Sec. 22-23 —
 * Architecture, Literature).
 *
 * Used on the public Admission Appeal form (Sec. 11) so an applicant states
 * their discipline explicitly instead of OCA having to infer it from the
 * free-text achievements summary, and surfaced back to reviewers in the
 * admin dashboard's Admission Appeals tab.
 */

const ARTEMIS_APPEAL_DISCIPLINES = [
    'Music', 'Dance', 'Theater Arts', 'Film', 'Visual Arts', 'Architecture', 'Literature',
];
