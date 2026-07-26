<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Public-facing admission appeal for prospective students with cultural/
 * artistic excellence who did not initially qualify for admission (Art. IV
 * Sec. 11; BatStateU-WI-OCA-02). Unlike every other application type, the
 * appellant is not yet a BatStateU student — there is no user account to
 * attach this to — so it lives in its own table and is reachable without
 * logging in, exactly like the public /track page.
 */
class AdmissionAppealController extends Controller
{
    public function showForm()
    {
        $user = current_user();
        $portalUrl = route('login');
        if ($user) {
            $portalUrl = route(dashboard_route_for_role($user['role']));
        }

        $pageTitle = 'Admission Appeal';
        $error = flash_get('error');
        $success = flash_get('success');
        $referenceId = flash_get('reference_id');

        return view('pages.admission_appeal', compact('user', 'portalUrl', 'pageTitle', 'error', 'success', 'referenceId'));
    }

    public function submit(Request $request)
    {
        $back = redirect()->route('appeal.apply');

        $fullName = trim($request->input('full_name', ''));
        $email = trim($request->input('email', ''));
        $contactNumber = trim($request->input('contact_number', ''));
        $secondarySchool = trim($request->input('secondary_school', ''));
        $achievements = trim($request->input('achievements_summary', ''));
        $academicStanding = trim($request->input('academic_standing_note', ''));

        if ($fullName === '' || $email === '' || $secondarySchool === '' || $achievements === '') {
            flash_set('error', 'Please fill in your name, email, secondary school, and a summary of your cultural/artistic achievements.');
            return $back;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash_set('error', 'Please enter a valid email address.');
            return $back;
        }

        // Required documentary evidence (Sec. 11-B): certificates/awards, a
        // trainer's recommendation letter, and the secondary school's statement.
        $uploads = [];
        $fields = [
            'certificates' => 'Certificates, medals, or awards from competitions',
            'recommendation_letter' => 'Letter of recommendation from a trainer',
            'school_statement' => "Statement from your secondary school's culture and arts department",
        ];
        foreach ($fields as $field => $label) {
            if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
                flash_set('error', "Please attach: {$label}.");
                return $back;
            }
            if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
                flash_set('error', "Failed to upload \"{$label}\". Please try again.");
                return $back;
            }
            if ($_FILES[$field]['size'] > 10 * 1024 * 1024) {
                flash_set('error', "\"{$label}\" must be 10MB or smaller.");
                return $back;
            }
            $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'], true)) {
                flash_set('error', "\"{$label}\" must be a PDF or image (jpg, png).");
                return $back;
            }
            $uploads[$field] = ['tmp_name' => $_FILES[$field]['tmp_name'], 'ext' => $ext];
        }

        $pdo = getDB();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO admission_appeals (full_name, email, contact_number, secondary_school, achievements_summary, academic_standing_note)
                 VALUES (:name, :email, :contact, :school, :achievements, :standing)'
            );
            $stmt->execute([
                'name' => $fullName,
                'email' => $email,
                'contact' => $contactNumber !== '' ? $contactNumber : null,
                'school' => $secondarySchool,
                'achievements' => $achievements,
                'standing' => $academicStanding !== '' ? $academicStanding : null,
            ]);
            $appealId = (int) $pdo->lastInsertId();

            $destDir = ARTEMIS_UPLOAD_PATH . '/admission-appeals/' . $appealId;
            if (!is_dir($destDir)) {
                mkdir($destDir, 0775, true);
            }
            $paths = [];
            foreach ($uploads as $field => $file) {
                $safeName = bin2hex(random_bytes(8)) . '.' . $file['ext'];
                if (!move_uploaded_file($file['tmp_name'], $destDir . '/' . $safeName)) {
                    throw new \RuntimeException('Failed to move uploaded file.');
                }
                $paths[$field] = 'uploads/documents/admission-appeals/' . $appealId . '/' . $safeName;
            }
            $stmt = $pdo->prepare(
                'UPDATE admission_appeals SET certificates_path = :certificates, recommendation_letter_path = :recommendation, school_statement_path = :statement WHERE appeal_id = :id'
            );
            $stmt->execute([
                'certificates' => $paths['certificates'],
                'recommendation' => $paths['recommendation_letter'],
                'statement' => $paths['school_statement'],
                'id' => $appealId,
            ]);

            $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();
            error_log('admission_appeal submit: ' . $e->getMessage());
            flash_set('error', 'Something went wrong while submitting your appeal. Please try again.');
            return $back;
        }

        flash_set('success', 'Your admission appeal has been submitted. The Office of Culture and Arts will review it and contact you by email.');
        flash_set('reference_id', 'APPEAL-' . str_pad((string) $appealId, 5, '0', STR_PAD_LEFT));
        return $back;
    }
}
