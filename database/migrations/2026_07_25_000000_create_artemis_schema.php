<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Recreates the full ARTEMIS schema exactly as it exists in the live
 * artemis_db database. The app talks to MySQL entirely through raw PDO
 * (see getDB() in app/Support/helpers.php) with no Eloquent models anywhere,
 * so this migration mirrors that convention instead of hand-translating 25
 * tables into the Schema builder DSL: it replays the real CREATE TABLE
 * statements (captured via `mysqldump --no-data` against the working dev
 * database) so a fresh clone can run `php artisan migrate` and end up with
 * byte-for-byte the same structure this project has been developed against.
 *
 * FOREIGN_KEY_CHECKS is disabled while creating so table order doesn't need
 * to satisfy every FK dependency up front.
 */
return new class extends Migration
{
    /** @var array<string,string> table name => CREATE TABLE statement */
    private array $tables = [
        'talent_categories' => <<<'SQL'
CREATE TABLE `talent_categories` (
  `category_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,

        'application_types' => <<<'SQL'
CREATE TABLE `application_types` (
  `type_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`type_id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,

        'users' => <<<'SQL'
CREATE TABLE `users` (
  `user_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `role` enum('student','admin','trainer','pathfit_faculty','college_dean') NOT NULL DEFAULT 'student',
  `id_number` varchar(20) DEFAULT NULL COMMENT 'Student number (e.g. 21-10234) or staff ID',
  `qr_token` varchar(64) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `course` varchar(100) DEFAULT NULL COMMENT 'Program/section, e.g. BSIT 3-A (students only)',
  `college` varchar(150) DEFAULT NULL,
  `year_level` tinyint(3) unsigned DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `id_number` (`id_number`),
  UNIQUE KEY `qr_token` (`qr_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,

        'settings' => <<<'SQL'
CREATE TABLE `settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
SQL,

        'login_attempts' => <<<'SQL'
CREATE TABLE `login_attempts` (
  `attempt_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(150) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `attempted_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`attempt_id`),
  KEY `idx_login_attempts_email_time` (`email`,`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,

        'password_resets' => <<<'SQL'
CREATE TABLE `password_resets` (
  `reset_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `token_hash` char(64) NOT NULL COMMENT 'sha256 of the token emailed to the user',
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`reset_id`),
  KEY `idx_password_resets_token` (`token_hash`),
  KEY `fk_reset_user` (`user_id`),
  CONSTRAINT `fk_reset_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,

        'announcements' => <<<'SQL'
CREATE TABLE `announcements` (
  `announcement_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `content` text NOT NULL,
  `tag` varchar(50) DEFAULT NULL COMMENT 'e.g. Audition, Stipend, Events, Action Required',
  `audience` varchar(100) DEFAULT 'All Students',
  `is_urgent` tinyint(1) NOT NULL DEFAULT 0,
  `posted_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`announcement_id`),
  KEY `fk_announcement_author` (`posted_by`),
  CONSTRAINT `fk_announcement_author` FOREIGN KEY (`posted_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,

        'events' => <<<'SQL'
CREATE TABLE `events` (
  `event_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `competition_result` text DEFAULT NULL,
  `event_type` varchar(100) DEFAULT NULL COMMENT 'e.g. Cultural Night, Festival, Awards Night',
  `event_date` date NOT NULL,
  `event_time` time DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `audience` varchar(150) DEFAULT NULL,
  `expected_attendees` int(10) unsigned DEFAULT NULL,
  `status` enum('Planning','Upcoming','Ongoing','Completed','Cancelled') NOT NULL DEFAULT 'Upcoming',
  `image_path` varchar(255) DEFAULT NULL,
  `color_hex` varchar(7) DEFAULT NULL,
  `requires_travel` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Off-campus/inter-campus event - RSVP requires the travel acknowledgment gate instead of a bare one-click RSVP',
  `requires_application_type_id` int(10) unsigned DEFAULT NULL COMMENT 'If set, RSVP requires an Approved application of this type',
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`event_id`),
  KEY `fk_event_creator` (`created_by`),
  KEY `fk_event_required_type` (`requires_application_type_id`),
  CONSTRAINT `fk_event_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_event_required_type` FOREIGN KEY (`requires_application_type_id`) REFERENCES `application_types` (`type_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,

        'event_attendance' => <<<'SQL'
CREATE TABLE `event_attendance` (
  `attendance_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `event_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `status` enum('Registered','Attended','Absent','Cancelled') NOT NULL DEFAULT 'Registered',
  `registered_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `marked_at` timestamp NULL DEFAULT NULL,
  `travel_acknowledged_at` timestamp NULL DEFAULT NULL COMMENT 'Set when the student accepted the off-campus travel/logistics terms at RSVP time',
  `conduct_acknowledged_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`attendance_id`),
  UNIQUE KEY `uq_event_user` (`event_id`,`user_id`),
  KEY `fk_attendance_user` (`user_id`),
  CONSTRAINT `fk_attendance_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_attendance_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,

        'performer_profiles' => <<<'SQL'
CREATE TABLE `performer_profiles` (
  `profile_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `troupe_name` varchar(150) DEFAULT NULL,
  `gwa` decimal(3,2) DEFAULT NULL COMMENT 'General weighted average, e.g. 2.35',
  `active_member` tinyint(1) NOT NULL DEFAULT 0,
  `probation_status` enum('None','Probation') NOT NULL DEFAULT 'None',
  `probation_reason` text DEFAULT NULL,
  `probation_started_at` timestamp NULL DEFAULT NULL,
  `trainer_name` varchar(150) DEFAULT NULL,
  `trainer_id` int(10) unsigned DEFAULT NULL,
  `years_active` tinyint(3) unsigned DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`profile_id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `fk_profile_trainer` (`trainer_id`),
  CONSTRAINT `fk_profile_trainer` FOREIGN KEY (`trainer_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_profile_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,

        'performer_talents' => <<<'SQL'
CREATE TABLE `performer_talents` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `profile_id` int(10) unsigned NOT NULL,
  `category_id` int(10) unsigned NOT NULL,
  `proficiency_level` enum('Beginner','Intermediate','Advanced','Expert') NOT NULL DEFAULT 'Intermediate',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_profile_category` (`profile_id`,`category_id`),
  KEY `fk_talent_category` (`category_id`),
  CONSTRAINT `fk_talent_category` FOREIGN KEY (`category_id`) REFERENCES `talent_categories` (`category_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_talent_profile` FOREIGN KEY (`profile_id`) REFERENCES `performer_profiles` (`profile_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,

        'applications' => <<<'SQL'
CREATE TABLE `applications` (
  `application_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `application_code` varchar(20) NOT NULL COMMENT 'e.g. APP-2026-001',
  `user_id` int(10) unsigned NOT NULL,
  `type_id` int(10) unsigned NOT NULL,
  `pathfit_faculty_id` int(10) unsigned DEFAULT NULL,
  `status` enum('Pending','Under Review','Evaluation','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  `current_stage` tinyint(3) unsigned NOT NULL DEFAULT 1 COMMENT '1=Submitted 2=OCA Review 3=Evaluation 4=For Approval 5=Final Decision',
  `details` text DEFAULT NULL COMMENT 'Free-form/JSON type-specific fields from the application form',
  `hours_claimed` decimal(6,2) DEFAULT NULL COMMENT 'Stipend: hours of training/performance claimed (Art. IX Sec. 29, Php 60.00/hour)',
  `bantog_category` varchar(100) DEFAULT NULL COMMENT 'BANTOG: discipline category applied for (Art. VIII Sec. 26)',
  `bantog_award_title` varchar(150) DEFAULT NULL,
  `bantog_score_training` tinyint(3) unsigned DEFAULT NULL COMMENT 'BANTOG evaluator score, Training/Seminar, max 20 (Art. VIII Sec. 23)',
  `bantog_score_production` tinyint(3) unsigned DEFAULT NULL COMMENT 'BANTOG evaluator score, Production/Presentation, max 40',
  `bantog_score_award` tinyint(3) unsigned DEFAULT NULL COMMENT 'BANTOG evaluator score, Award Achieved, max 40',
  `remarks` text DEFAULT NULL COMMENT 'Admin remarks, e.g. reason for rejection or missing documents',
  `reviewed_by` int(10) unsigned DEFAULT NULL COMMENT 'Admin user who last acted on this application',
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `decided_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`application_id`),
  UNIQUE KEY `application_code` (`application_code`),
  KEY `fk_app_user` (`user_id`),
  KEY `fk_app_type` (`type_id`),
  KEY `fk_app_reviewer` (`reviewed_by`),
  KEY `fk_app_pathfit_faculty` (`pathfit_faculty_id`),
  CONSTRAINT `fk_app_pathfit_faculty` FOREIGN KEY (`pathfit_faculty_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_app_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_app_type` FOREIGN KEY (`type_id`) REFERENCES `application_types` (`type_id`),
  CONSTRAINT `fk_app_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,

        'application_documents' => <<<'SQL'
CREATE TABLE `application_documents` (
  `document_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `application_id` int(10) unsigned NOT NULL,
  `document_type` varchar(80) DEFAULT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`document_id`),
  KEY `fk_doc_application` (`application_id`),
  CONSTRAINT `fk_doc_application` FOREIGN KEY (`application_id`) REFERENCES `applications` (`application_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,

        'application_endorsements' => <<<'SQL'
CREATE TABLE `application_endorsements` (
  `endorsement_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `application_id` int(10) unsigned NOT NULL,
  `trainer_user_id` int(10) unsigned NOT NULL,
  `note` text DEFAULT NULL,
  `endorsed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`endorsement_id`),
  UNIQUE KEY `uniq_app_trainer` (`application_id`,`trainer_user_id`),
  KEY `fk_endorsement_trainer` (`trainer_user_id`),
  CONSTRAINT `fk_endorsement_app` FOREIGN KEY (`application_id`) REFERENCES `applications` (`application_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_endorsement_trainer` FOREIGN KEY (`trainer_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
SQL,

        'application_status_history' => <<<'SQL'
CREATE TABLE `application_status_history` (
  `history_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `application_id` int(10) unsigned NOT NULL,
  `stage` tinyint(3) unsigned NOT NULL,
  `status` varchar(50) NOT NULL,
  `remarks` text DEFAULT NULL,
  `changed_by` int(10) unsigned DEFAULT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`history_id`),
  KEY `fk_hist_application` (`application_id`),
  KEY `fk_hist_user` (`changed_by`),
  CONSTRAINT `fk_hist_application` FOREIGN KEY (`application_id`) REFERENCES `applications` (`application_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_hist_user` FOREIGN KEY (`changed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,

        'audition_ratings' => <<<'SQL'
CREATE TABLE `audition_ratings` (
  `rating_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `application_id` int(10) unsigned NOT NULL,
  `trainer_id` int(10) unsigned NOT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `decision` enum('Pass','Fail') DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `rated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`rating_id`),
  UNIQUE KEY `uq_application_trainer` (`application_id`,`trainer_id`),
  KEY `fk_audition_ratings_trainer` (`trainer_id`),
  CONSTRAINT `fk_audition_ratings_app` FOREIGN KEY (`application_id`) REFERENCES `applications` (`application_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_audition_ratings_trainer` FOREIGN KEY (`trainer_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,

        'benefit_records' => <<<'SQL'
CREATE TABLE `benefit_records` (
  `benefit_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `application_id` int(10) unsigned DEFAULT NULL,
  `benefit_type` enum('Stipend','PATHFit Exemption','BANTOG Recognition') NOT NULL,
  `academic_year` varchar(20) NOT NULL COMMENT 'e.g. 2025-2026',
  `semester` enum('1st','2nd','Summer') DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL COMMENT 'Stipend amount, if applicable',
  `grade` decimal(3,2) DEFAULT NULL COMMENT 'PATHFit exemption grade, fixed at 1.00 (Art. X Sec. 38)',
  `status` enum('Active','Expired','Revoked') NOT NULL DEFAULT 'Active',
  `remarks` text DEFAULT NULL,
  `completion_report` text DEFAULT NULL,
  `completion_file_path` varchar(255) DEFAULT NULL,
  `completion_submitted_at` timestamp NULL DEFAULT NULL,
  `granted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`benefit_id`),
  KEY `fk_benefit_user` (`user_id`),
  KEY `fk_benefit_application` (`application_id`),
  CONSTRAINT `fk_benefit_application` FOREIGN KEY (`application_id`) REFERENCES `applications` (`application_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_benefit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,

        'trainer_evaluations' => <<<'SQL'
CREATE TABLE `trainer_evaluations` (
  `evaluation_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `trainer_name` varchar(150) NOT NULL,
  `trainer_user_id` int(10) unsigned DEFAULT NULL,
  `discipline` varchar(150) DEFAULT NULL,
  `score_experience` tinyint(3) unsigned NOT NULL,
  `score_recognition` tinyint(3) unsigned NOT NULL,
  `score_contributions` tinyint(3) unsigned NOT NULL,
  `score_skills` tinyint(3) unsigned NOT NULL,
  `score_credentials` tinyint(3) unsigned NOT NULL,
  `total_score` tinyint(3) unsigned NOT NULL,
  `recommended_level` varchar(50) NOT NULL,
  `recommended_salary_grade` tinyint(3) unsigned NOT NULL,
  `hourly_rate` decimal(8,2) DEFAULT NULL,
  `hours_rendered` decimal(6,2) DEFAULT NULL,
  `computed_honorarium` decimal(10,2) DEFAULT NULL COMMENT 'hourly_rate x hours_rendered (Art. VI Sec. 17-B.7, 9-10)',
  `evaluated_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`evaluation_id`),
  KEY `fk_trainer_eval_user` (`evaluated_by`),
  KEY `fk_eval_trainer_user` (`trainer_user_id`),
  CONSTRAINT `fk_eval_trainer_user` FOREIGN KEY (`trainer_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_trainer_eval_user` FOREIGN KEY (`evaluated_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,

        'academic_monitoring_reports' => <<<'SQL'
CREATE TABLE `academic_monitoring_reports` (
  `report_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `term` enum('Midterm','Final') NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `semester` enum('1st','2nd','Summer') NOT NULL,
  `has_failing_grade` tinyint(1) NOT NULL DEFAULT 0,
  `gwa` decimal(3,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`report_id`),
  KEY `fk_academic_report_user` (`user_id`),
  CONSTRAINT `fk_academic_report_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
SQL,

        'student_mentorships' => <<<'SQL'
CREATE TABLE `student_mentorships` (
  `mentorship_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `student_user_id` int(10) unsigned NOT NULL,
  `mentor_name` varchar(150) NOT NULL,
  `reason` enum('Voluntary Request','Failing Grade','Appeal Admission Recruit') NOT NULL,
  `status` enum('Active','Completed') NOT NULL DEFAULT 'Active',
  `assigned_by` int(10) unsigned DEFAULT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`mentorship_id`),
  KEY `fk_mentorship_student` (`student_user_id`),
  KEY `fk_mentorship_assigned_by` (`assigned_by`),
  CONSTRAINT `fk_mentorship_assigned_by` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_mentorship_student` FOREIGN KEY (`student_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
SQL,

        'bantog_evaluators' => <<<'SQL'
CREATE TABLE `bantog_evaluators` (
  `evaluator_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `position` varchar(150) NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`evaluator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
SQL,

        'faculty_noncompliance_complaints' => <<<'SQL'
CREATE TABLE `faculty_noncompliance_complaints` (
  `complaint_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `faculty_name` varchar(150) NOT NULL,
  `college` varchar(150) DEFAULT NULL,
  `rpag_group` varchar(150) DEFAULT NULL,
  `description` text NOT NULL,
  `escalation_level` enum('First Violation','Repeated Violation') NOT NULL DEFAULT 'First Violation',
  `status` enum('Submitted','Dean Review','Written Warning Issued','Grievance Board','Resolved') NOT NULL DEFAULT 'Submitted',
  `filed_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`complaint_id`),
  KEY `fk_complaint_filer` (`filed_by`),
  CONSTRAINT `fk_complaint_filer` FOREIGN KEY (`filed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,

        'partner_organizations' => <<<'SQL'
CREATE TABLE `partner_organizations` (
  `partner_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `org_type` enum('Government Cultural Agency','International Cultural Institution','NGO','Cultural Institution','Community/Indigenous Group','Other') NOT NULL DEFAULT 'Other',
  `contact_person` varchar(150) DEFAULT NULL,
  `contact_email` varchar(150) DEFAULT NULL,
  `contact_phone` varchar(30) DEFAULT NULL,
  `status` enum('Prospective','Proposal Submitted','Approved','Active MOA','Completed','Declined') NOT NULL DEFAULT 'Prospective',
  `proposal_summary` text DEFAULT NULL,
  `moa_signed_date` date DEFAULT NULL,
  `moa_expiry_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`partner_id`),
  KEY `fk_partner_org_created_by` (`created_by`),
  CONSTRAINT `fk_partner_org_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
SQL,

        'activity_honoraria' => <<<'SQL'
CREATE TABLE `activity_honoraria` (
  `honorarium_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `person_name` varchar(150) NOT NULL,
  `role` enum('Resource Person','Facilitator','Judge') NOT NULL,
  `activity_name` varchar(200) NOT NULL,
  `activity_date` date NOT NULL,
  `discipline` varchar(150) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `granted_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`honorarium_id`),
  KEY `idx_activity_date` (`activity_date`),
  KEY `fk_activity_honoraria_granted_by` (`granted_by`),
  CONSTRAINT `fk_activity_honoraria_granted_by` FOREIGN KEY (`granted_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
SQL,

        'admission_appeals' => <<<'SQL'
CREATE TABLE `admission_appeals` (
  `appeal_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `secondary_school` varchar(200) NOT NULL,
  `campus` varchar(100) NOT NULL DEFAULT 'ARASOF-Nasugbu',
  `achievements_summary` text NOT NULL,
  `academic_standing_note` text DEFAULT NULL,
  `certificates_path` varchar(255) DEFAULT NULL,
  `recommendation_letter_path` varchar(255) DEFAULT NULL,
  `school_statement_path` varchar(255) DEFAULT NULL,
  `status` enum('Submitted','Under Review','Endorsed to TAO Central','Approved','Rejected') NOT NULL DEFAULT 'Submitted',
  `remarks` text DEFAULT NULL,
  `reviewed_by` int(10) unsigned DEFAULT NULL,
  `linked_user_id` int(10) unsigned DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `decided_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`appeal_id`),
  KEY `fk_appeal_reviewed_by` (`reviewed_by`),
  KEY `fk_appeal_linked_user` (`linked_user_id`),
  CONSTRAINT `fk_appeal_linked_user` FOREIGN KEY (`linked_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_appeal_reviewed_by` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
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
