<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * TAO Central reviewer role + the full 5-stage Admission Appeal pipeline
 * (Art. IV Sec. 11-C).
 *
 * The appeal chain previously collapsed the Manual's review path into three
 * pre-decision steps (Submitted -> Under Review -> Endorsed to TAO Central).
 * The tracker component in app/Support/ui_helpers.php has always described the
 * real five, including the `appeal_admission` override naming stage 4 "For
 * Approval (President via TAO)" — so this migration brings the stored
 * vocabulary in line with the UI that was already built for it:
 *
 *     Submitted -> Under Review (OCA) -> Evaluation Stage
 *               -> For Approval (President via TAO) -> Approved / Rejected
 *
 * Enum widening is done in three steps rather than one ALTER: MySQL would
 * silently coerce any row whose current value isn't in the new list to '' (or
 * fail under strict mode), so the column first holds the union of the old and
 * new vocabularies, existing rows are remapped, and only then is the column
 * narrowed. 'Under Review' carries over to 'Under Review (OCA)' and 'Endorsed
 * to TAO Central' to 'For Approval (President via TAO)' — the step each one
 * actually represented.
 */
return new class extends Migration
{
    private const ROLE_WITH_TAO = "enum('student','admin','trainer','pathfit_faculty','college_dean','tao_central')";
    private const ROLE_ORIGINAL = "enum('student','admin','trainer','pathfit_faculty','college_dean')";

    private const STATUS_OLD = "enum('Submitted','Under Review','Endorsed to TAO Central','Approved','Rejected')";
    private const STATUS_NEW = "enum('Submitted','Under Review (OCA)','Evaluation Stage','For Approval (President via TAO)','Approved','Rejected')";
    private const STATUS_BOTH = "enum('Submitted','Under Review','Endorsed to TAO Central','Under Review (OCA)','Evaluation Stage','For Approval (President via TAO)','Approved','Rejected')";

    /** Demo reviewer, following the README's <role>123 convention. */
    private const TAO_EMAIL = 'tao.central@batstate-u.edu.ph';
    private const TAO_PASSWORD = 'tao123';

    public function up(): void
    {
        DB::unprepared('ALTER TABLE `users` MODIFY `role` ' . self::ROLE_WITH_TAO . " NOT NULL DEFAULT 'student'");

        DB::unprepared('ALTER TABLE `admission_appeals` MODIFY `status` ' . self::STATUS_BOTH . " NOT NULL DEFAULT 'Submitted'");
        DB::update("UPDATE admission_appeals SET status = 'Under Review (OCA)' WHERE status = 'Under Review'");
        DB::update("UPDATE admission_appeals SET status = 'For Approval (President via TAO)' WHERE status = 'Endorsed to TAO Central'");
        DB::unprepared('ALTER TABLE `admission_appeals` MODIFY `status` ' . self::STATUS_NEW . " NOT NULL DEFAULT 'Submitted'");

        $this->seedReviewer();
    }

    public function down(): void
    {
        DB::table('users')->where('email', self::TAO_EMAIL)->delete();

        DB::unprepared('ALTER TABLE `admission_appeals` MODIFY `status` ' . self::STATUS_BOTH . " NOT NULL DEFAULT 'Submitted'");
        // Both new intermediate stages fold back onto the single step the old
        // vocabulary had for "past OCA, not yet decided" — lossy by nature, so
        // rolling forward again cannot restore which of the two it had been.
        DB::update("UPDATE admission_appeals SET status = 'Under Review' WHERE status = 'Under Review (OCA)'");
        DB::update("UPDATE admission_appeals SET status = 'Endorsed to TAO Central' WHERE status IN ('Evaluation Stage','For Approval (President via TAO)')");
        DB::unprepared('ALTER TABLE `admission_appeals` MODIFY `status` ' . self::STATUS_OLD . " NOT NULL DEFAULT 'Submitted'");

        // Any account still on the role would violate the narrowed enum.
        DB::table('users')->where('role', 'tao_central')->update(['role' => 'admin']);
        DB::unprepared('ALTER TABLE `users` MODIFY `role` ' . self::ROLE_ORIGINAL . " NOT NULL DEFAULT 'student'");
    }

    /**
     * Idempotent: re-running `migrate:refresh` on a database where the account
     * survived shouldn't collide on the unique email.
     */
    private function seedReviewer(): void
    {
        $exists = DB::table('users')->where('email', self::TAO_EMAIL)->exists();
        if ($exists) {
            DB::table('users')->where('email', self::TAO_EMAIL)->update(['role' => 'tao_central', 'status' => 'active']);
            return;
        }

        DB::table('users')->insert([
            'role' => 'tao_central',
            'id_number' => 'TAO-0001',
            'email' => self::TAO_EMAIL,
            'password_hash' => password_hash(self::TAO_PASSWORD, PASSWORD_DEFAULT),
            'first_name' => 'Rosario',
            'last_name' => 'Magpantay',
            'college' => 'Testing and Admission Office - Central',
            'status' => 'active',
        ]);
    }
};
