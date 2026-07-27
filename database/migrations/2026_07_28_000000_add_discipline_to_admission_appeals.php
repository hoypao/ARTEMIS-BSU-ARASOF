<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Admission Appeal applicants (Art. IV Sec. 11) previously only had a free-
 * text "Cultural/Artistic Achievements" field, leaving OCA reviewers to
 * infer which discipline (Art. IV Sec. 12) an appeal belongs to. Adds an
 * explicit `discipline` column so the applicant states it up front.
 *
 * Nullable (no default) rather than a guessed default, since appeals
 * submitted before this column existed genuinely have no recorded
 * discipline — the admin dashboard just omits the discipline badge for
 * those rows instead of mislabeling them.
 *
 * Separate migration on top of the 2026-07-25 schema snapshot, same
 * raw-SQL-via-DB::unprepared() convention as the rest of the app.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(
            'ALTER TABLE `admission_appeals` ADD COLUMN `discipline` varchar(50) DEFAULT NULL AFTER `achievements_summary`'
        );
    }

    public function down(): void
    {
        DB::unprepared('ALTER TABLE `admission_appeals` DROP COLUMN `discipline`');
    }
};
