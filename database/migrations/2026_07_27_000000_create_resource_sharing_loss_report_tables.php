<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2 of the Article VII module (see the 2026-07-26 migration for
 * Phase 1 — equipment_items / procurement_requests). Adds:
 *
 * - resource_share_requests: the cross-campus lending/tracking system Sec.
 *   20-C explicitly calls for ("a centralized tracking system... to monitor
 *   the inventory, movement, and condition of shared resources").
 * - equipment_loss_reports: the operational core of the RLSDDP (Report of
 *   Lost, Stolen, Damaged or Destroyed Property) form referenced in Sec.
 *   20-C.2 ("any damage or malfunction must be reported immediately").
 *
 * Both hang off equipment_items (ON DELETE CASCADE — a share/loss record
 * about a since-deleted item has no independent meaning), same
 * users-reference convention (ON DELETE SET NULL) as every other table.
 */
return new class extends Migration
{
    private array $tables = [
        'resource_share_requests' => <<<'SQL'
CREATE TABLE `resource_share_requests` (
  `share_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `equipment_id` int(10) unsigned NOT NULL,
  `from_campus` varchar(100) NOT NULL DEFAULT 'ARASOF-Nasugbu',
  `to_campus` varchar(100) NOT NULL,
  `purpose` text DEFAULT NULL,
  `requested_start_date` date DEFAULT NULL,
  `requested_end_date` date DEFAULT NULL,
  `status` enum('Requested','Approved','In Transit','Returned','Declined') NOT NULL DEFAULT 'Requested',
  `condition_on_return` enum('Good','Needs Repair','Damaged','Lost') DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `requested_by` int(10) unsigned DEFAULT NULL,
  `approved_by` int(10) unsigned DEFAULT NULL,
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `returned_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`share_id`),
  KEY `fk_share_equipment` (`equipment_id`),
  KEY `fk_share_requested_by` (`requested_by`),
  KEY `fk_share_approved_by` (`approved_by`),
  CONSTRAINT `fk_share_equipment` FOREIGN KEY (`equipment_id`) REFERENCES `equipment_items` (`equipment_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_share_requested_by` FOREIGN KEY (`requested_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_share_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
SQL,

        'equipment_loss_reports' => <<<'SQL'
CREATE TABLE `equipment_loss_reports` (
  `report_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `equipment_id` int(10) unsigned NOT NULL,
  `report_type` enum('Lost','Stolen','Damaged','Destroyed') NOT NULL,
  `incident_date` date DEFAULT NULL,
  `description` text NOT NULL,
  `status` enum('Reported','Under Investigation','Resolved') NOT NULL DEFAULT 'Reported',
  `resolution_notes` text DEFAULT NULL,
  `reported_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`report_id`),
  KEY `fk_lossreport_equipment` (`equipment_id`),
  KEY `fk_lossreport_reported_by` (`reported_by`),
  CONSTRAINT `fk_lossreport_equipment` FOREIGN KEY (`equipment_id`) REFERENCES `equipment_items` (`equipment_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_lossreport_reported_by` FOREIGN KEY (`reported_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
SQL,
    ];

    public function up(): void
    {
        DB::unprepared('SET FOREIGN_KEY_CHECKS=0');
        foreach ($this->tables as $sql) {
            DB::unprepared($sql);
        }
        DB::unprepared('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(): void
    {
        DB::unprepared('SET FOREIGN_KEY_CHECKS=0');
        foreach (array_reverse(array_keys($this->tables)) as $table) {
            Schema::dropIfExists($table);
        }
        DB::unprepared('SET FOREIGN_KEY_CHECKS=1');
    }
};
