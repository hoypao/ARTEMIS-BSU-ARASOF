<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Article VII (Procurement and Management of Culture and Arts Materials and
 * Equipment) — previously had no representation anywhere in ARTEMIS. Adds:
 *
 * - equipment_items: the Property Custodian's inventory (Art. III Sec. 6.C.a-b
 *   — acquisition, condition, location/custodian records for every item).
 * - procurement_requests: the PPMP -> PR -> Approved -> Delivered pipeline
 *   (Art. VII Sec. 20-A), and the data source for QEO Objective 4 ("To
 *   enhance Culture and Arts facilities through acquisition of equipment and
 *   materials," BatStateU-QEO-OCA-03) once wired into app/Support/qeo_kpi.php.
 *
 * Written as a separate migration (not appended to the 2026-07-25 schema
 * snapshot) so a database that already ran that migration only needs this
 * one on top of it. Same raw-SQL-via-DB::unprepared() convention as the rest
 * of the schema, since the app talks to MySQL entirely through raw PDO.
 */
return new class extends Migration
{
    /** @var array<string,string> table name => CREATE TABLE statement */
    private array $tables = [
        'equipment_items' => <<<'SQL'
CREATE TABLE `equipment_items` (
  `equipment_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `item_name` varchar(200) NOT NULL,
  `category` varchar(100) DEFAULT NULL COMMENT 'Discipline, e.g. Music, Dance, Theater, Film, Visual Arts, General',
  `campus` varchar(100) NOT NULL DEFAULT 'ARASOF-Nasugbu',
  `condition_status` enum('Good','Needs Repair','Damaged','Lost') NOT NULL DEFAULT 'Good',
  `custodian_name` varchar(150) DEFAULT NULL,
  `location` varchar(150) DEFAULT NULL,
  `acquisition_date` date DEFAULT NULL,
  `acquisition_cost` decimal(10,2) DEFAULT NULL,
  `source_request_id` int(10) unsigned DEFAULT NULL COMMENT 'procurement_requests row this item was acquired through, if any',
  `notes` text DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`equipment_id`),
  KEY `fk_equipment_created_by` (`created_by`),
  KEY `fk_equipment_source_request` (`source_request_id`),
  CONSTRAINT `fk_equipment_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_equipment_source_request` FOREIGN KEY (`source_request_id`) REFERENCES `procurement_requests` (`request_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
SQL,

        'procurement_requests' => <<<'SQL'
CREATE TABLE `procurement_requests` (
  `request_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `item_name` varchar(200) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `quantity` int(10) unsigned NOT NULL DEFAULT 1,
  `estimated_cost` decimal(10,2) DEFAULT NULL,
  `ppmp_reference` varchar(100) DEFAULT NULL COMMENT 'PPMP document reference (Art. VII Sec. 20-A.1)',
  `justification` text DEFAULT NULL,
  `status` enum('Draft','PPMP Submitted','PR Prepared','Approved','Delivered','Cancelled') NOT NULL DEFAULT 'Draft',
  `requested_by` int(10) unsigned DEFAULT NULL,
  `approved_by` int(10) unsigned DEFAULT NULL,
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `decided_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`request_id`),
  KEY `fk_procurement_requested_by` (`requested_by`),
  KEY `fk_procurement_approved_by` (`approved_by`),
  CONSTRAINT `fk_procurement_requested_by` FOREIGN KEY (`requested_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_procurement_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
SQL,
    ];

    public function up(): void
    {
        DB::unprepared('SET FOREIGN_KEY_CHECKS=0');
        // procurement_requests first: equipment_items.source_request_id references it.
        DB::unprepared($this->tables['procurement_requests']);
        DB::unprepared($this->tables['equipment_items']);
        DB::unprepared('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(): void
    {
        DB::unprepared('SET FOREIGN_KEY_CHECKS=0');
        Schema::dropIfExists('equipment_items');
        Schema::dropIfExists('procurement_requests');
        DB::unprepared('SET FOREIGN_KEY_CHECKS=1');
    }
};
