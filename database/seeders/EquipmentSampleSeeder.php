<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Demo data for the Article VII module (Equipment & Materials Inventory,
 * Procurement, Cross-Campus Sharing, RLSDDP loss reports).
 *
 * Deliberately NOT wired into DatabaseSeeder: `php artisan migrate` remains the
 * whole setup step for a real install, and that class documents why it stays
 * empty. Run this one explicitly when you want the tab populated:
 *
 *     php artisan db:seed --class=EquipmentSampleSeeder
 *
 * Re-runnable. It deletes only the fixed IDs below before re-inserting, so
 * anything you've entered by hand through the dashboard survives.
 *
 * The content is drawn from the Culture and Arts Development Manual
 * (BatStateU-DOC-AA-33) rather than invented, so the module is exercised with
 * the shape of record it will actually hold:
 *
 *   - Art. VII Sec. 20-A  — the PPMP -> PR -> Approved -> Delivered pipeline,
 *                           with PPMP references in the office's own format.
 *   - Art. VII Sec. 20-C  — sharing between constituent campuses, "allocated
 *                           based on availability and need," with the return
 *                           condition recorded.
 *   - Art. VII Sec. 20-C.2 — "any damage or malfunction must be reported
 *                           immediately," i.e. the RLSDDP reports.
 *   - Art. III Sec. 6.C   — the Property Custodian's inventory: acquisition,
 *                           condition, maintenance history, and location.
 *   - Art. IV Sec. 12     — the five RPAG disciplines the items belong to.
 *
 * Edge cases are seeded on purpose (a legacy item with no acquisition record, a
 * name near the varchar(200) ceiling, a cancelled request, an item whose
 * condition was driven by a loss report) so layout and logic problems surface
 * instead of hiding behind uniformly tidy rows.
 */
class EquipmentSampleSeeder extends Seeder
{
    /** Seeded PKs, deleted before re-insert so the seeder is idempotent. */
    private const EQUIPMENT_IDS   = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13];
    private const REQUEST_IDS     = [1, 2, 3, 4, 5, 6, 7, 8];
    private const SHARE_IDS       = [1, 2, 3, 4, 5];
    private const LOSS_REPORT_IDS = [1, 2, 3, 4];

    /** OCA-Central admin (users.user_id = 1) stands in for the Property Custodian. */
    private const CUSTODIAN_USER_ID = 1;
    private const CUSTODIAN_NAME    = 'Ma. Teresa Alvarez';

    public function run(): void
    {
        $this->clearPreviouslySeeded();

        DB::table('procurement_requests')->insert($this->procurementRequests());
        DB::table('equipment_items')->insert($this->equipmentItems());
        DB::table('resource_share_requests')->insert($this->resourceShareRequests());
        DB::table('equipment_loss_reports')->insert($this->lossReports());

        $this->command?->info(sprintf(
            'Seeded %d equipment items, %d procurement requests, %d share requests, %d loss reports.',
            count(self::EQUIPMENT_IDS),
            count(self::REQUEST_IDS),
            count(self::SHARE_IDS),
            count(self::LOSS_REPORT_IDS)
        ));
    }

    /**
     * Children first — resource_share_requests and equipment_loss_reports are
     * FK'd to equipment_items with ON DELETE CASCADE, and equipment_items
     * references procurement_requests.
     */
    private function clearPreviouslySeeded(): void
    {
        DB::table('equipment_loss_reports')->whereIn('report_id', self::LOSS_REPORT_IDS)->delete();
        DB::table('resource_share_requests')->whereIn('share_id', self::SHARE_IDS)->delete();
        DB::table('equipment_items')->whereIn('equipment_id', self::EQUIPMENT_IDS)->delete();
        DB::table('procurement_requests')->whereIn('request_id', self::REQUEST_IDS)->delete();
    }

    /**
     * Sec. 20-A.1-2. Three Delivered against eight total is what QEO Objective 4
     * ("purchased equipment / required equipment x 100") will read as the
     * year's procurement rate — see qeo_kpi.php.
     */
    private function procurementRequests(): array
    {
        $rows = [
            [
                'request_id' => 1,
                'item_name' => 'Yamaha U1 Upright Piano (OCA Music Room rehabilitation)',
                'category' => 'Music',
                'quantity' => 1,
                'estimated_cost' => 285000.00,
                'ppmp_reference' => 'PPMP-OCA-2026-001',
                'justification' => 'Market scoping under Sec. 20-A.1.a established replacement cost for the 1998 unit declared beyond economical repair. Primary rehearsal instrument for the RPAG Chorale and the Music discipline of the training program (Art. IV Sec. 12).',
                'status' => 'Delivered',
                'requested_at' => '2026-01-14 09:12:00',
                'decided_at' => '2026-02-18 14:30:00',
                'notes' => 'Delivered and inspected 18 Feb 2026; Acknowledgement Receipt for Equipment issued to the Property Custodian.',
            ],
            [
                'request_id' => 2,
                'item_name' => '16-Channel Digital Audio Mixer with Stage Box',
                'category' => 'Music',
                'quantity' => 2,
                'estimated_cost' => 68500.00,
                'ppmp_reference' => 'PPMP-OCA-2026-002',
                'justification' => 'Existing analog mixer cannot support the channel count required for full RPAG Chorale and Rondalla configurations at University events.',
                'status' => 'Delivered',
                'requested_at' => '2026-01-14 09:40:00',
                'decided_at' => '2026-03-05 11:05:00',
                'notes' => null,
            ],
            [
                'request_id' => 3,
                'item_name' => 'Marley Dance Floor Roll, 10m x 1.5m',
                'category' => 'Dance',
                'quantity' => 6,
                'estimated_cost' => 42000.00,
                'ppmp_reference' => 'PPMP-OCA-2026-003',
                'justification' => 'Sprung-floor surface required for the Dance discipline; current studio flooring has caused recurring ankle injuries reported by the trainer.',
                'status' => 'Delivered',
                'requested_at' => '2026-02-03 08:55:00',
                'decided_at' => '2026-04-12 16:20:00',
                'notes' => 'Two rolls allocated to Studio A, four held in storage for cross-campus sharing under Sec. 20-C.',
            ],
            [
                'request_id' => 4,
                'item_name' => 'LED PAR Stage Light 54x3W with DMX Controller',
                'category' => 'Theater',
                'quantity' => 12,
                'estimated_cost' => 96000.00,
                'ppmp_reference' => 'PPMP-OCA-2026-004',
                'justification' => 'Theater Arts productions and the annual BANTOG awards night are currently rented lighting at recurring cost; acquisition breaks even within three stagings.',
                'status' => 'Approved',
                'requested_at' => '2026-03-11 10:15:00',
                'decided_at' => '2026-06-20 09:00:00',
                'notes' => 'PR approved by the Chancellor; awaiting supplier delivery schedule.',
            ],
            [
                'request_id' => 5,
                'item_name' => 'Mirrorless Camera Body with 24-70mm f/2.8 Lens',
                'category' => 'Film',
                'quantity' => 2,
                'estimated_cost' => 175000.00,
                'ppmp_reference' => 'PPMP-OCA-2026-005',
                'justification' => 'Film discipline training and documentation of culture and arts events; supports the Lakbay Kultura documentation requirement (Art. XI).',
                'status' => 'PR Prepared',
                'requested_at' => '2026-04-22 13:45:00',
                'decided_at' => null,
                'notes' => 'PR forwarded to the Procurement Office 30 Jun 2026.',
            ],
            [
                'request_id' => 6,
                'item_name' => 'Acrylic Paint Sets and Canvas Stretchers (Visual Arts studio replenishment)',
                'category' => 'Visual Arts',
                'quantity' => 30,
                'estimated_cost' => 28500.00,
                'ppmp_reference' => 'PPMP-OCA-2026-006',
                'justification' => 'Consumable materials for the Visual Arts discipline; quantity based on the needs assessment for AY 2026-2027 enrollment.',
                'status' => 'PPMP Submitted',
                'requested_at' => '2026-05-09 15:30:00',
                'decided_at' => null,
                'notes' => null,
            ],
            [
                'request_id' => 7,
                'item_name' => 'Portable Sound System with Wireless Microphones',
                'category' => 'General',
                'quantity' => 3,
                'estimated_cost' => 54000.00,
                'ppmp_reference' => null,
                'justification' => 'Requested by the Head of Culture and Arts for off-campus invitational performances (Art. XI).',
                'status' => 'Draft',
                'requested_at' => '2026-07-02 11:20:00',
                'decided_at' => null,
                'notes' => 'Pending market scoping before PPMP inclusion.',
            ],
            [
                'request_id' => 8,
                'item_name' => 'Steel Costume Storage Cabinet, 6-door',
                'category' => 'Theater',
                'quantity' => 4,
                'estimated_cost' => 38000.00,
                'ppmp_reference' => 'PPMP-OCA-2026-007',
                'justification' => 'Proper storage and handling to prevent deterioration of costume inventory (Art. III Sec. 6.C.b).',
                'status' => 'Cancelled',
                'requested_at' => '2026-02-27 14:05:00',
                'decided_at' => '2026-05-02 10:40:00',
                'notes' => 'Cancelled — requirement absorbed by the Alangilan campus consolidated procurement for the same item.',
            ],
        ];

        return array_map(function (array $row): array {
            $isFinal = in_array($row['status'], ['Approved', 'Delivered', 'Cancelled'], true);

            return $row + [
                'requested_by' => self::CUSTODIAN_USER_ID,
                'approved_by' => $isFinal ? self::CUSTODIAN_USER_ID : null,
                'updated_at' => $row['decided_at'] ?? $row['requested_at'],
            ];
        }, $rows);
    }

    /**
     * Art. III Sec. 6.C.a — "acquisition, condition, maintenance history, and
     * location" for every cultural property. Items 1-3 carry source_request_id
     * back to the Delivered request they came from.
     */
    private function equipmentItems(): array
    {
        $rows = [
            [
                'equipment_id' => 1,
                'item_name' => 'Yamaha U1 Upright Piano',
                'category' => 'Music',
                'campus' => 'ARASOF-Nasugbu',
                'condition_status' => 'Good',
                'location' => 'OCA Music Room, 2/F Student Center',
                'acquisition_date' => '2026-02-18',
                'acquisition_cost' => 285000.00,
                'source_request_id' => 1,
                'notes' => 'Tuning scheduled semi-annually. Not for cross-campus lending — transport risk.',
                'updated_at' => '2026-02-18 14:35:00',
            ],
            [
                'equipment_id' => 2,
                'item_name' => '16-Channel Digital Audio Mixer with Stage Box',
                'category' => 'Music',
                'campus' => 'ARASOF-Nasugbu',
                'condition_status' => 'Good',
                'location' => 'OCA Sound Booth, Gymnasium',
                'acquisition_date' => '2026-03-05',
                'acquisition_cost' => 34250.00,
                'source_request_id' => 2,
                'notes' => 'Unit 1 of 2.',
                'updated_at' => '2026-03-05 11:10:00',
            ],
            [
                'equipment_id' => 3,
                'item_name' => 'Marley Dance Floor Roll (Set A)',
                'category' => 'Dance',
                'campus' => 'ARASOF-Nasugbu',
                'condition_status' => 'Good',
                'location' => 'Dance Studio A, 3/F Student Center',
                'acquisition_date' => '2026-04-12',
                'acquisition_cost' => 7000.00,
                'source_request_id' => 3,
                'notes' => null,
                'updated_at' => '2026-04-12 16:25:00',
            ],
            [
                'equipment_id' => 4,
                'item_name' => 'Marley Dance Floor Roll (Set B)',
                'category' => 'Dance',
                'campus' => 'ARASOF-Nasugbu',
                'condition_status' => 'Damaged',
                'location' => 'OCA Storage Room, G/F',
                'acquisition_date' => '2026-04-12',
                'acquisition_cost' => 7000.00,
                'source_request_id' => 3,
                'notes' => 'Water damage along one edge; see RLSDDP report. Repair completed, pending re-inspection.',
                'updated_at' => '2026-06-14 09:20:00',
            ],
            [
                'equipment_id' => 5,
                'item_name' => 'LED PAR Stage Light 54x3W (Unit 01)',
                'category' => 'Theater',
                'campus' => 'ARASOF-Nasugbu',
                'condition_status' => 'Good',
                'location' => 'Little Theater, lighting grid',
                'acquisition_date' => '2026-06-25',
                'acquisition_cost' => 8000.00,
                'source_request_id' => null,
                'notes' => 'Advance delivery of 2 units ahead of the full PR under PPMP-OCA-2026-004.',
                'updated_at' => '2026-06-25 10:00:00',
            ],
            [
                'equipment_id' => 6,
                'item_name' => 'LED PAR Stage Light 54x3W (Unit 02)',
                'category' => 'Theater',
                'campus' => 'ARASOF-Nasugbu',
                'condition_status' => 'Damaged',
                'location' => 'On loan — Alangilan campus',
                'acquisition_date' => '2026-06-25',
                'acquisition_cost' => 8000.00,
                'source_request_id' => null,
                'notes' => 'Reported damaged in transit during cross-campus sharing.',
                'updated_at' => '2026-07-15 13:40:00',
            ],
            [
                'equipment_id' => 7,
                'item_name' => 'Mirrorless Camera Body with 24-70mm f/2.8 Lens',
                'category' => 'Film',
                'campus' => 'ARASOF-Nasugbu',
                'condition_status' => 'Good',
                'location' => 'OCA Equipment Cage (locked)',
                'acquisition_date' => '2025-08-19',
                'acquisition_cost' => 82000.00,
                'source_request_id' => null,
                'notes' => 'Acquired under the 2025 PPMP; retained for the Film discipline.',
                'updated_at' => '2026-05-30 08:15:00',
            ],
            [
                'equipment_id' => 8,
                'item_name' => 'Camera Tripod and 3-Axis Gimbal Stabilizer',
                'category' => 'Film',
                'campus' => 'ARASOF-Nasugbu',
                'condition_status' => 'Lost',
                'location' => 'Unaccounted',
                'acquisition_date' => '2025-08-19',
                'acquisition_cost' => 24500.00,
                'source_request_id' => null,
                'notes' => 'Unaccounted for after the Lakbay Kultura documentation shoot. RLSDDP filed.',
                'updated_at' => '2026-07-08 16:50:00',
            ],
            [
                'equipment_id' => 9,
                'item_name' => 'Acrylic Paint Set and Canvas Stretchers',
                'category' => 'Visual Arts',
                'campus' => 'ARASOF-Nasugbu',
                'condition_status' => 'Good',
                'location' => 'Visual Arts Studio, 1/F Arts Building',
                'acquisition_date' => '2026-06-30',
                'acquisition_cost' => 950.00,
                'source_request_id' => null,
                'notes' => 'Consumable — quantity tracked by the studio logbook, not per-unit here.',
                'updated_at' => '2026-06-30 14:00:00',
            ],
            [
                'equipment_id' => 10,
                'item_name' => 'Portable Sound System with Wireless Microphones',
                'category' => 'General',
                'campus' => 'ARASOF-Nasugbu',
                'condition_status' => 'Good',
                'location' => 'OCA Storage Room, G/F',
                'acquisition_date' => '2024-11-08',
                'acquisition_cost' => 18000.00,
                'source_request_id' => null,
                'notes' => 'Most frequently lent unit under Sec. 20-C.',
                'updated_at' => '2026-07-20 09:30:00',
            ],
            [
                'equipment_id' => 11,
                'item_name' => 'Rondalla Instrument Set (14 pieces: bandurria, laud, octavina, bajo de unas)',
                'category' => 'Music',
                'campus' => 'ARASOF-Nasugbu',
                'condition_status' => 'Needs Repair',
                'location' => 'OCA Music Room, 2/F Student Center',
                'acquisition_date' => null,
                'acquisition_cost' => null,
                'source_request_id' => null,
                'notes' => 'Legacy donation — no acquisition record on file. Four instruments need restringing and fretwork before the next semester.',
                'updated_at' => '2026-07-22 11:45:00',
            ],
            [
                'equipment_id' => 12,
                'item_name' => "Barong Tagalog and Baro't Saya Costume Set for the Resident Performing Arts Group Folk Dance Ensemble, complete with accessories, headpieces, and individual garment bags",
                'category' => 'Dance',
                'campus' => 'ARASOF-Nasugbu',
                'condition_status' => 'Good',
                'location' => 'Costume Room, 3/F Student Center',
                'acquisition_date' => '2025-09-15',
                'acquisition_cost' => 145000.00,
                'source_request_id' => null,
                'notes' => 'Stored in garment bags with desiccant; inspected each semester (Art. III Sec. 6.C.b).',
                'updated_at' => '2026-07-01 10:10:00',
            ],
            [
                'equipment_id' => 13,
                'item_name' => 'Handheld Wireless Microphone Unit (dual-channel receiver)',
                'category' => 'General',
                'campus' => 'ARASOF-Nasugbu',
                'condition_status' => 'Lost',
                'location' => 'Unaccounted',
                'acquisition_date' => '2024-11-08',
                'acquisition_cost' => 12500.00,
                'source_request_id' => null,
                'notes' => 'Reported stolen during an off-campus invitational performance. RLSDDP filed.',
                'updated_at' => '2026-06-05 17:25:00',
            ],
        ];

        return array_map(static fn (array $row): array => $row + [
            'custodian_name' => self::CUSTODIAN_NAME,
            'created_by' => self::CUSTODIAN_USER_ID,
        ], $rows);
    }

    /**
     * Sec. 20-C — cross-campus sharing. Share 2 is a completed round trip with
     * condition_on_return recorded, which is what the controller writes back
     * onto the item when a loan is closed out.
     */
    private function resourceShareRequests(): array
    {
        $rows = [
            [
                'share_id' => 1,
                'equipment_id' => 6,
                'to_campus' => 'Alangilan',
                'purpose' => 'Lighting support for the Alangilan campus Theater Arts production week.',
                'requested_start_date' => '2026-07-10',
                'requested_end_date' => '2026-07-31',
                'status' => 'In Transit',
                'condition_on_return' => null,
                'notes' => 'Damage reported in transit — see RLSDDP report; return inspection pending.',
                'requested_at' => '2026-07-02 09:00:00',
                'returned_at' => null,
                'updated_at' => '2026-07-15 13:40:00',
            ],
            [
                'share_id' => 2,
                'equipment_id' => 10,
                'to_campus' => 'Lipa',
                'purpose' => 'Sound reinforcement for the Lipa campus Buwan ng Wika culminating program.',
                'requested_start_date' => '2026-06-24',
                'requested_end_date' => '2026-07-18',
                'status' => 'Returned',
                'condition_on_return' => 'Good',
                'notes' => 'Returned complete and on time; inspected by the Property Custodian on receipt.',
                'requested_at' => '2026-06-16 14:20:00',
                'returned_at' => '2026-07-20 09:30:00',
                'updated_at' => '2026-07-20 09:30:00',
            ],
            [
                'share_id' => 3,
                'equipment_id' => 3,
                'to_campus' => 'Pablo Borbon',
                'purpose' => 'Dance floor for the Pablo Borbon campus RPAG folk dance rehearsals.',
                'requested_start_date' => '2026-08-03',
                'requested_end_date' => '2026-08-28',
                'status' => 'Approved',
                'condition_on_return' => null,
                'notes' => 'Approved subject to the borrowing campus arranging transport.',
                'requested_at' => '2026-07-14 10:35:00',
                'returned_at' => null,
                'updated_at' => '2026-07-21 15:00:00',
            ],
            [
                'share_id' => 4,
                'equipment_id' => 12,
                'to_campus' => 'Malvar',
                'purpose' => 'Costume set for the Malvar campus entry to the inter-campus folk dance competition (Art. XII).',
                'requested_start_date' => '2026-09-07',
                'requested_end_date' => '2026-09-20',
                'status' => 'Requested',
                'condition_on_return' => null,
                'notes' => null,
                'requested_at' => '2026-07-25 08:45:00',
                'returned_at' => null,
                'updated_at' => '2026-07-25 08:45:00',
            ],
            [
                'share_id' => 5,
                'equipment_id' => 7,
                'to_campus' => 'Alangilan',
                'purpose' => 'Documentation of the Alangilan campus founding anniversary.',
                'requested_start_date' => '2026-05-11',
                'requested_end_date' => '2026-05-15',
                'status' => 'Declined',
                'condition_on_return' => null,
                'notes' => 'Declined — unit already committed to the Film discipline workshop for the same dates (Sec. 20-C.1, allocation based on availability and need).',
                'requested_at' => '2026-04-28 16:10:00',
                'returned_at' => null,
                'updated_at' => '2026-05-02 09:15:00',
            ],
        ];

        return array_map(static fn (array $row): array => $row + [
            'from_campus' => 'ARASOF-Nasugbu',
            'requested_by' => self::CUSTODIAN_USER_ID,
            'approved_by' => in_array($row['status'], ['Approved', 'In Transit', 'Returned', 'Declined'], true)
                ? self::CUSTODIAN_USER_ID
                : null,
        ], $rows);
    }

    /**
     * Sec. 20-C.2 — the RLSDDP reports. Each item's condition_status matches
     * what equipment_condition_for_loss_report() would have set when the report
     * was saved through the dashboard (Damaged -> Damaged; Lost/Stolen/
     * Destroyed -> Lost), so the seeded rows are consistent with the app's own
     * write path rather than a state it could never produce.
     */
    private function lossReports(): array
    {
        $rows = [
            [
                'report_id' => 1,
                'equipment_id' => 8,
                'report_type' => 'Lost',
                'incident_date' => '2026-07-05',
                'description' => 'Tripod and gimbal did not return with the equipment set after the Lakbay Kultura documentation shoot in Taal. Last signed out to the Film discipline student crew; not recovered after a sweep of the venue and transport.',
                'status' => 'Under Investigation',
                'resolution_notes' => null,
                'created_at' => '2026-07-08 16:50:00',
                'updated_at' => '2026-07-08 16:50:00',
            ],
            [
                'report_id' => 2,
                'equipment_id' => 6,
                'report_type' => 'Damaged',
                'incident_date' => '2026-07-14',
                'description' => 'Housing cracked and one LED bank non-functional on arrival at the Alangilan campus. Damage occurred in transit; unit was inspected and confirmed in good condition before dispatch per Sec. 20-C.2.',
                'status' => 'Reported',
                'resolution_notes' => null,
                'created_at' => '2026-07-15 13:40:00',
                'updated_at' => '2026-07-15 13:40:00',
            ],
            [
                'report_id' => 3,
                'equipment_id' => 4,
                'report_type' => 'Damaged',
                'incident_date' => '2026-06-11',
                'description' => 'Water damage along one edge of the roll after a storage room leak during heavy rain. Approximately 1.2m of the edge affected.',
                'status' => 'Resolved',
                'resolution_notes' => 'Affected section trimmed and edge re-bound; roll returned to serviceable length of 8.8m. Storage room roof repaired by GSO 14 Jun 2026.',
                'created_at' => '2026-06-12 08:30:00',
                'updated_at' => '2026-06-14 09:20:00',
            ],
            [
                'report_id' => 4,
                'equipment_id' => 13,
                'report_type' => 'Stolen',
                'incident_date' => '2026-06-04',
                'description' => 'Receiver unit and one handheld microphone missing from the equipment table during an off-campus invitational performance. Reported to venue security the same evening; incident report filed with the Campus Security Office.',
                'status' => 'Under Investigation',
                'resolution_notes' => null,
                'created_at' => '2026-06-05 17:25:00',
                'updated_at' => '2026-06-05 17:25:00',
            ],
        ];

        return array_map(static fn (array $row): array => $row + [
            'reported_by' => self::CUSTODIAN_USER_ID,
        ], $rows);
    }
}
