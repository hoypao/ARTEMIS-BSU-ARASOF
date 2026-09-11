# ARTEMIS Compliance Fix Plan

This branch applies the repository audit against the Culture and Arts Development Manual, the 2026 OCA QEO objectives, and the uploaded OCA Work Instructions.

## Priority order

1. **Eligibility evidence integrity**
   - Do not infer "no failing grades" from GWA.
   - Treat missing authoritative evidence as `Needs Verification`.
   - Keep the human OCA/authorized reviewer as the final decision-maker.
   - Do not treat a talent/profile record as proof of PATHFit-equivalent physical training.

2. **PATHFit workflow evidence**
   - Add/identify explicit verification for good standing and physical-intensity equivalence.
   - Base decisions on training/rehearsal evidence and faculty/OCA review.

3. **External Invitation workflow**
   - Re-model the process as an institutional invitation received by OCA, not primarily as a student benefit application.

4. **Document-requirement verification**
   - Reconcile each application checklist with its official WI/manual basis.

5. **Objective-to-system alignment**
   - Resolve the Chapter I event-recommendation objective (implement a defensible rule-based recommender or revise the objective).

6. **End-to-end workflow tests**
   - Audition
   - Stipend
   - PATHFit exemption
   - BANTOG
   - Admission appeal
   - External invitation
   - Faculty non-compliance

7. **CI and release hardening**
   - Add automated lint/test/build checks after test-database setup is made reproducible.
   - Remove public demo passwords from README before public deployment/demo.

8. **Thesis synchronization**
   - Update Chapters II/III to match the implemented Laravel/PHP/Blade/Tailwind/MySQL stack.

## First fix applied

`app/Support/eligibility_engine.php` now distinguishes verified evidence from missing evidence and returns an explicit verdict:

- `Eligible`
- `Not Eligible`
- `Needs Verification`

A GWA is no longer treated as proof that the student has no failing course. PATHFit physical equivalence also remains unverified until an authorized reviewer verifies supporting evidence.

Regression tests are in `tests/Unit/EligibilityEngineTest.php`.
