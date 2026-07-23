<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Ports of actions/applications/apply_action.php (student submission with
 * document uploads, all-or-nothing DB transaction) and
 * actions/applications/review_application_action.php (admin review JSON:
 * advance/approve/reject/remark/score/update_benefit + auto benefit grant
 * + status-update email).
 */
class ApplicationController extends Controller
{
    /** apply_action.php (multipart POST, role:student via route middleware) */
    public function apply(Request $request)
    {
        $user = current_user();
        $typeId = (int) $request->input('type_id', 0);
        $details = trim($request->input('details', ''));

        $pdo = getDB();

        $stmt = $pdo->prepare('SELECT type_id, code FROM application_types WHERE type_id = :id');
        $stmt->execute(['id' => $typeId]);
        $type = $stmt->fetch();
        if (!$type) {
            flash_set('error', 'Please select a valid application type.');
            return redirect()->route('student.dashboard');
        }
        $typeCode = $type['code'];

        $allowedExt = ['pdf', 'jpg', 'jpeg', 'png'];
        $maxSize = 10 * 1024 * 1024; // 10MB per file
        $uploadedFiles = [];

        // Named, catalog-backed required/optional document slots (doc_cor, doc_grades, ...)
        $requirements = application_document_requirements()[$typeCode] ?? [];
        $missingLabels = [];
        foreach ($requirements as $docKey => $isRequired) {
            $field = 'doc_' . $docKey;
            $label = ARTEMIS_DOCUMENT_CATALOG[$docKey] ?? $docKey;
            $hasFile = !empty($_FILES[$field]) && $_FILES[$field]['error'] !== UPLOAD_ERR_NO_FILE;

            if (!$hasFile) {
                if ($isRequired) {
                    $missingLabels[] = $label;
                }
                continue;
            }
            if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
                flash_set('error', "Failed to upload {$label}. Please try again.");
                return redirect()->route('student.dashboard');
            }
            if ($_FILES[$field]['size'] > $maxSize) {
                flash_set('error', "{$label} must be 10MB or smaller.");
                return redirect()->route('student.dashboard');
            }
            $originalName = $_FILES[$field]['name'];
            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExt, true)) {
                flash_set('error', "{$label} must be a PDF or image (jpg, png).");
                return redirect()->route('student.dashboard');
            }
            $uploadedFiles[] = [
                'tmp_name' => $_FILES[$field]['tmp_name'],
                'original' => $originalName,
                'ext' => $ext,
                'document_type' => $label,
            ];
        }

        if ($missingLabels) {
            flash_set('error', 'Please upload the required document(s): ' . implode(', ', $missingLabels) . '.');
            return redirect()->route('student.dashboard');
        }

        // Generic catch-all "Other Supporting Document" attachments
        if (!empty($_FILES['documents']) && is_array($_FILES['documents']['name'])) {
            $count = count($_FILES['documents']['name']);
            for ($i = 0; $i < $count; $i++) {
                if ($_FILES['documents']['error'][$i] === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                if ($_FILES['documents']['error'][$i] !== UPLOAD_ERR_OK) {
                    flash_set('error', 'One of the files failed to upload. Please try again.');
                    return redirect()->route('student.dashboard');
                }
                if ($_FILES['documents']['size'][$i] > $maxSize) {
                    flash_set('error', 'Each document must be 10MB or smaller.');
                    return redirect()->route('student.dashboard');
                }
                $originalName = $_FILES['documents']['name'][$i];
                $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                if (!in_array($ext, $allowedExt, true)) {
                    flash_set('error', 'Only PDF or image (jpg, png) documents are allowed.');
                    return redirect()->route('student.dashboard');
                }
                $uploadedFiles[] = [
                    'tmp_name' => $_FILES['documents']['tmp_name'][$i],
                    'original' => $originalName,
                    'ext' => $ext,
                    'document_type' => 'Other Supporting Document',
                ];
            }
        }

        $applicationCode = generate_application_code($pdo);

        $hoursClaimed = null;
        if ($typeCode === 'stipend' && trim((string) $request->input('hours_claimed', '')) !== '') {
            $hoursClaimed = max(0, (float) $request->input('hours_claimed'));
        }
        $bantogCategory = null;
        if ($typeCode === 'bantog_recognition') {
            $bantogCategory = trim($request->input('bantog_category', '')) !== '' ? trim($request->input('bantog_category')) : null;
        }

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO applications (application_code, user_id, type_id, status, current_stage, details, hours_claimed, bantog_category)
                 VALUES (:code, :user_id, :type_id, "Pending", 1, :details, :hours_claimed, :bantog_category)'
            );
            $stmt->execute([
                'code' => $applicationCode,
                'user_id' => $user['user_id'],
                'type_id' => $typeId,
                'details' => $details !== '' ? $details : null,
                'hours_claimed' => $hoursClaimed,
                'bantog_category' => $bantogCategory,
            ]);
            $applicationId = (int) $pdo->lastInsertId();

            $stmt = $pdo->prepare(
                'INSERT INTO application_status_history (application_id, stage, status, remarks, changed_by)
                 VALUES (:app_id, 1, "Pending", "Application submitted.", :user_id)'
            );
            $stmt->execute(['app_id' => $applicationId, 'user_id' => $user['user_id']]);

            if ($uploadedFiles) {
                $destDir = ARTEMIS_UPLOAD_PATH . '/' . $user['user_id'] . '/' . $applicationCode;
                if (!is_dir($destDir)) {
                    mkdir($destDir, 0775, true);
                }
                $docStmt = $pdo->prepare(
                    'INSERT INTO application_documents (application_id, document_type, file_name, file_path, file_type)
                     VALUES (:app_id, :document_type, :file_name, :file_path, :file_type)'
                );
                foreach ($uploadedFiles as $file) {
                    // Random filename on disk (original name is kept separately in the DB for
                    // display) — avoids path traversal / collisions from user-supplied filenames.
                    $safeName = bin2hex(random_bytes(8)) . '.' . $file['ext'];
                    $destPath = $destDir . '/' . $safeName;
                    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
                        throw new RuntimeException('Failed to move uploaded file.');
                    }
                    $relativePath = 'uploads/documents/' . $user['user_id'] . '/' . $applicationCode . '/' . $safeName;
                    $docStmt->execute([
                        'app_id' => $applicationId,
                        'document_type' => $file['document_type'],
                        'file_name' => $file['original'],
                        'file_path' => $relativePath,
                        'file_type' => $file['ext'],
                    ]);
                }
            }

            // Audition/Recruitment applications also declare (or update) the student's performer profile.
            if ($typeCode === 'audition_recruitment') {
                $categoryId = (int) $request->input('talent_category_id', 0);
                $proficiency = $request->input('proficiency_level', 'Intermediate');
                if (!in_array($proficiency, ['Beginner', 'Intermediate', 'Advanced', 'Expert'], true)) {
                    $proficiency = 'Intermediate';
                }
                $portfolioNote = trim($request->input('portfolio_note', ''));

                $stmt = $pdo->prepare('SELECT profile_id FROM performer_profiles WHERE user_id = :uid');
                $stmt->execute(['uid' => $user['user_id']]);
                $profileId = $stmt->fetchColumn();

                if (!$profileId) {
                    $stmt = $pdo->prepare('INSERT INTO performer_profiles (user_id, bio) VALUES (:uid, :bio)');
                    $stmt->execute(['uid' => $user['user_id'], 'bio' => $portfolioNote !== '' ? $portfolioNote : null]);
                    $profileId = (int) $pdo->lastInsertId();
                } elseif ($portfolioNote !== '') {
                    $stmt = $pdo->prepare('UPDATE performer_profiles SET bio = :bio WHERE profile_id = :pid');
                    $stmt->execute(['bio' => $portfolioNote, 'pid' => $profileId]);
                }

                if ($categoryId > 0) {
                    // Re-applying with the same category updates the proficiency level
                    // instead of creating a duplicate talent row.
                    $stmt = $pdo->prepare(
                        'INSERT INTO performer_talents (profile_id, category_id, proficiency_level)
                         VALUES (:pid, :cid, :level)
                         ON DUPLICATE KEY UPDATE proficiency_level = VALUES(proficiency_level)'
                    );
                    $stmt->execute(['pid' => $profileId, 'cid' => $categoryId, 'level' => $proficiency]);
                }
            }

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log('apply_action: ' . $e->getMessage());
            flash_set('error', 'Something went wrong while submitting your application. Please try again.');
            return redirect()->route('student.dashboard');
        }

        flash_set('success', "Application {$applicationCode} submitted successfully. You can track its progress on your dashboard.");
        return redirect()->route('student.dashboard');
    }

    /** review_application_action.php (JSON, role:admin via route middleware) */
    public function review(Request $request)
    {
        $input = $request->json()->all() ?: [];

        $applicationId = (int) ($input['application_id'] ?? 0);
        $action = $input['action'] ?? '';
        $remarks = trim($input['remarks'] ?? '');

        if (!in_array($action, ['advance', 'approve', 'reject', 'remark', 'score', 'update_benefit'], true)) {
            return response()->json(['error' => 'Invalid request.'], 400);
        }
        if ($action !== 'update_benefit' && $applicationId <= 0) {
            return response()->json(['error' => 'Invalid request.'], 400);
        }
        if ($action === 'reject' && $remarks === '') {
            return response()->json(['error' => 'Remarks are required to reject an application.'], 422);
        }
        if ($action === 'remark' && $remarks === '') {
            return response()->json(['error' => 'Remark text is required.'], 422);
        }

        $pdo = getDB();
        $admin = current_user();

        if ($action === 'update_benefit') {
            $benefitId = (int) ($input['benefit_id'] ?? 0);
            $amount = ($input['amount'] ?? '') !== '' ? round((float) $input['amount'], 2) : null;
            $grade = ($input['grade'] ?? '') !== '' ? round((float) $input['grade'], 2) : null;
            $semester = in_array($input['semester'] ?? '', ['1st', '2nd', 'Summer'], true) ? $input['semester'] : null;
            $status = in_array($input['status'] ?? '', ['Active', 'Expired', 'Revoked'], true) ? $input['status'] : 'Active';

            if ($benefitId <= 0) {
                return response()->json(['error' => 'Invalid benefit record.'], 400);
            }

            $stmt = $pdo->prepare(
                'UPDATE benefit_records SET amount = :amount, grade = :grade, semester = :semester, status = :status WHERE benefit_id = :id'
            );
            $stmt->execute(['amount' => $amount, 'grade' => $grade, 'semester' => $semester, 'status' => $status, 'id' => $benefitId]);
            return response()->json(['success' => true]);
        }

        $stmt = $pdo->prepare('SELECT * FROM applications WHERE application_id = :id');
        $stmt->execute(['id' => $applicationId]);
        $app = $stmt->fetch();

        if (!$app) {
            return response()->json(['error' => 'Application not found.'], 404);
        }

        if ($action === 'remark') {
            $stmt = $pdo->prepare('UPDATE applications SET remarks = :remarks WHERE application_id = :id');
            $stmt->execute(['remarks' => $remarks, 'id' => $applicationId]);

            $stmt = $pdo->prepare(
                'INSERT INTO application_status_history (application_id, stage, status, remarks, changed_by)
                 VALUES (:app_id, :stage, :status, :remarks, :changed_by)'
            );
            $stmt->execute([
                'app_id' => $applicationId,
                'stage' => $app['current_stage'],
                'status' => $app['status'],
                'remarks' => $remarks,
                'changed_by' => $admin['user_id'],
            ]);

            return response()->json(['success' => true, 'remarks' => $remarks]);
        }

        if ($action === 'score') {
            $training = (int) ($input['score_training'] ?? -1);
            $production = (int) ($input['score_production'] ?? -1);
            $award = (int) ($input['score_award'] ?? -1);
            if ($training < 0 || $training > 20 || $production < 0 || $production > 40 || $award < 0 || $award > 40) {
                return response()->json(['error' => 'Scores must be within range: Training/Seminar 0-20, Production/Presentation 0-40, Award Achieved 0-40.'], 422);
            }
            $stmt = $pdo->prepare(
                'UPDATE applications SET bantog_score_training = :training, bantog_score_production = :production, bantog_score_award = :award WHERE application_id = :id'
            );
            $stmt->execute(['training' => $training, 'production' => $production, 'award' => $award, 'id' => $applicationId]);
            return response()->json(['success' => true, 'total' => $training + $production + $award]);
        }

        if (in_array($app['status'], ['Approved', 'Rejected'], true)) {
            return response()->json(['error' => 'This application already has a final decision.'], 409);
        }

        $stageStatusMap = [2 => 'Under Review', 3 => 'Evaluation', 4 => 'Evaluation'];

        if ($action === 'advance') {
            if ($app['current_stage'] >= 4) {
                return response()->json(['error' => 'Application is already at the final review stage. Use Approve or Reject.'], 409);
            }
            $newStage = $app['current_stage'] + 1;
            $newStatus = $stageStatusMap[$newStage] ?? $app['status'];
            $decidedAt = null;
        } else {
            $newStage = 5;
            $newStatus = $action === 'approve' ? 'Approved' : 'Rejected';
            $decidedAt = date('Y-m-d H:i:s');
        }

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'UPDATE applications
                 SET status = :status, current_stage = :stage, reviewed_by = :reviewer,
                     remarks = :remarks, decided_at = :decided_at
                 WHERE application_id = :id'
            );
            $stmt->execute([
                'status' => $newStatus,
                'stage' => $newStage,
                'reviewer' => $admin['user_id'],
                'remarks' => $remarks !== '' ? $remarks : $app['remarks'],
                'decided_at' => $decidedAt,
                'id' => $applicationId,
            ]);

            $stmt = $pdo->prepare(
                'INSERT INTO application_status_history (application_id, stage, status, remarks, changed_by)
                 VALUES (:app_id, :stage, :status, :remarks, :changed_by)'
            );
            $stmt->execute([
                'app_id' => $applicationId,
                'stage' => $newStage,
                'status' => $newStatus,
                'remarks' => $remarks !== '' ? $remarks : null,
                'changed_by' => $admin['user_id'],
            ]);

            $createdBenefit = null;
            if ($action === 'approve') {
                $stmt = $pdo->prepare(
                    'SELECT name FROM application_types t JOIN applications a ON a.type_id = t.type_id WHERE a.application_id = :id'
                );
                $stmt->execute(['id' => $applicationId]);
                $typeName = $stmt->fetchColumn();

                $benefitMap = [
                    'Stipend Application' => 'Stipend',
                    'PATHFit Exemption' => 'PATHFit Exemption',
                    'BANTOG Recognition' => 'BANTOG Recognition',
                ];
                if (isset($benefitMap[$typeName])) {
                    // PH academic year starts around June — an approval in June-December
                    // belongs to "this year-next year"; Jan-May belongs to "last year-this year".
                    $month = (int) date('n');
                    $year = (int) date('Y');
                    $academicYear = $month >= 6 ? "{$year}-" . ($year + 1) : ($year - 1) . "-{$year}";

                    $benefitAmount = null;
                    $benefitGrade = null;
                    $benefitRemarks = 'Granted following approved application ' . $app['application_code'] . '.';

                    if ($typeName === 'Stipend Application' && $app['hours_claimed'] !== null) {
                        $benefitAmount = round(((float) $app['hours_claimed']) * 60.00, 2);
                        $benefitRemarks .= sprintf(' %s hour(s) @ Php 60.00/hour (Art. IX Sec. 29).', rtrim(rtrim(number_format((float) $app['hours_claimed'], 2), '0'), '.'));
                    } elseif ($typeName === 'PATHFit Exemption') {
                        $benefitGrade = 1.00;
                        $benefitRemarks .= ' Grade of 1.00 per Art. X Sec. 38.';
                    } elseif ($typeName === 'BANTOG Recognition' && $app['bantog_score_training'] !== null) {
                        $total = (int) $app['bantog_score_training'] + (int) $app['bantog_score_production'] + (int) $app['bantog_score_award'];
                        $benefitRemarks .= ' ' . ($app['bantog_category'] ?: 'BANTOG') . " — Score: {$total}/100 (Training {$app['bantog_score_training']}/20, Production {$app['bantog_score_production']}/40, Award {$app['bantog_score_award']}/40).";
                    }

                    $stmt = $pdo->prepare(
                        'INSERT INTO benefit_records (user_id, application_id, benefit_type, academic_year, amount, grade, status, remarks)
                         VALUES (:user_id, :app_id, :benefit_type, :academic_year, :amount, :grade, "Active", :remarks)'
                    );
                    $stmt->execute([
                        'user_id' => $app['user_id'],
                        'app_id' => $applicationId,
                        'benefit_type' => $benefitMap[$typeName],
                        'academic_year' => $academicYear,
                        'amount' => $benefitAmount,
                        'grade' => $benefitGrade,
                        'remarks' => $benefitRemarks,
                    ]);
                    $createdBenefit = [
                        'id' => (int) $pdo->lastInsertId(),
                        'type' => $benefitMap[$typeName],
                        'academicYear' => $academicYear,
                        'semester' => null,
                        'amount' => $benefitAmount,
                        'grade' => $benefitGrade,
                        'status' => 'Active',
                    ];
                }
            }

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log('review_application_action: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update the application. Please try again.'], 500);
        }

        $stmt = $pdo->prepare(
            'SELECT u.email, u.first_name, u.last_name, t.name AS type_name
             FROM applications a JOIN users u ON u.user_id = a.user_id JOIN application_types t ON t.type_id = a.type_id
             WHERE a.application_id = :id'
        );
        $stmt->execute(['id' => $applicationId]);
        if ($recipient = $stmt->fetch()) {
            $stageLabel = ARTEMIS_PROGRESS_STAGES[min($newStage, count(ARTEMIS_PROGRESS_STAGES)) - 1];
            $statusColor = $newStatus === 'Approved' ? '#15803D' : ($newStatus === 'Rejected' ? '#B91C1C' : '#B11226');
            $body = "<p>Your application <strong>{$app['application_code']}</strong> ({$recipient['type_name']}) has a status update.</p>"
                . "<p style=\"text-align:center; margin: 20px 0;\"><span style=\"display:inline-block; background:{$statusColor}1a; color:{$statusColor}; padding:8px 20px; border-radius:20px; font-weight:600; font-size:13px;\">{$newStatus} &middot; {$stageLabel}</span></p>"
                . ($remarks !== '' ? "<p><strong>Remarks:</strong> " . nl2br(e($remarks)) . "</p>" : '')
                . "<p style=\"font-size:12px; color:#9CA3AF;\">Log in to your ARTEMIS Student Dashboard to see the full progress tracker and any required next steps.</p>";
            send_email(
                $recipient['email'],
                $recipient['first_name'] . ' ' . $recipient['last_name'],
                "ARTEMIS: {$app['application_code']} is now {$newStatus}",
                email_layout('Application Status Update', $body)
            );
        }

        return response()->json(['success' => true, 'status' => $newStatus, 'stage' => $newStage, 'benefit' => $createdBenefit]);
    }
}
