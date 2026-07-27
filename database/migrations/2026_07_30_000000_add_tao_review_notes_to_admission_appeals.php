<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Two review notes that the single `remarks` column could not hold at once.
 *
 * `remarks` is written by whoever last acted, so an OCA screening note was
 * being overwritten the moment TAO Central left one of its own — which made
 * "show OCA's prior remarks read-only above the reviewer's input" impossible
 * to satisfy from one column. Splitting them keeps each office's note as its
 * own record of what that office said:
 *
 * - tao_remarks: the TAO Central reviewer's evaluation note, entered while an
 *   appeal sits at Evaluation Stage / For Approval. Never touches `remarks`.
 * - presidential_decision_note: the note recorded when the final Approve or
 *   Reject is entered on behalf of the University President (Art. IV Sec.
 *   11-C). Required at that step, and kept in its own column so a later edit
 *   to `remarks` cannot rewrite what was recorded at decision time.
 *
 * `remarks` keeps its existing meaning and is left alone here, so the public
 * tracker and notifyAppealStatus() need no changes.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(
            "ALTER TABLE `admission_appeals`
               ADD COLUMN `tao_remarks` text DEFAULT NULL
                 COMMENT 'TAO Central reviewer evaluation note (Art. IV Sec. 11-C); distinct from OCA remarks'
                 AFTER `remarks`,
               ADD COLUMN `presidential_decision_note` text DEFAULT NULL
                 COMMENT 'Required audit note recorded with the final decision on behalf of the University President'
                 AFTER `tao_remarks`"
        );
    }

    public function down(): void
    {
        Schema::table('admission_appeals', function ($table) {
            $table->dropColumn(['tao_remarks', 'presidential_decision_note']);
        });
    }
};
