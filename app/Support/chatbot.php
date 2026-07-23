<?php
/**
 * "Ask Spartan" assistant — no external AI service. Two things make this
 * more than a plain keyword lookup:
 *
 * 1. Fuzzy topic matching: each topic is scored against the user's message
 *    by tokenizing both and fuzzy-comparing tokens (exact match, substring,
 *    or similar_text() percentage), so typos and rephrasing ("aplication
 *    status?", "wheres my stipend") still resolve to the right topic
 *    instead of falling through to "I'm not sure about that."
 *
 * 2. Context-awareness: when a logged-in student asks about their
 *    application, the reply is generated from their actual records
 *    (application code, live status, current stage) instead of generic
 *    instructions on how to check it themselves.
 */



/**
 * @return array<string, array{triggers: string[], response: string}>
 */
function spartan_topics(): array
{
    $docList = implode("\n", array_values(ARTEMIS_DOCUMENT_CATALOG));

    return [
        'greeting' => [
            'triggers' => ['hello', 'hi', 'hey', 'good morning', 'good afternoon', 'kumusta'],
            'response' => "Hello! I'm Spartan, your ARTEMIS assistant. I can help you with applications, OCA processes, and anything about the Culture and Arts Office. What can I help you with today?",
        ],
        'track_application' => [
            'triggers' => ['track application', 'track my application', 'wheres my application', 'application code', 'check my application', 'find my application'],
            'response' => "To track your application:\n1. Go to your Student Dashboard\n2. Click My Applications\n3. Or use the Track Application page with your application code (e.g. APP-2026-001).\nYou'll see the status and 5-stage progress tracker.",
        ],
        'stages' => [
            'triggers' => ['application stages', '5 stage', 'stages of application', 'how many stages', 'evaluation stage', 'approval process'],
            'response' => "Your application goes through 5 stages:\n1) Submitted\n2) Under Review (OCA)\n3) Evaluation Stage\n4) For Approval (VPAA/Chancellor)\n5) Approved / Rejected\n\nCheck your dashboard or the Track Application page to see the current stage.",
        ],
        'status_meaning' => [
            'triggers' => ['status', 'pending mean', 'under review mean', 'what does approved mean', 'rejected mean'],
            'response' => "Application statuses mean:\nPending — just submitted, awaiting OCA review\nUnder Review — OCA is checking your documents\nEvaluation — committee is scoring your application\nApproved — congratulations!\nRejected — check remarks for the reason",
        ],
        'audition' => [
            'triggers' => ['audition', 'recruitment', 'join rpag', 'join a group', 'try out'],
            'response' => "To apply for Audition / Recruitment:\n1. Log in to your Student Dashboard\n2. Click Submit Application\n3. Select Audition / Recruitment\n4. Fill in your performer profile\n5. Upload required documents\n6. Click Submit",
        ],
        'stipend' => [
            'triggers' => ['stipend', 'allowance', 'php 60', 'hourly rate', 'payment for training'],
            'response' => "The Stipend Application is for active cultural artists. Typical requirements:\nActive membership in a cultural troupe\nGood academic standing\nEndorsement from your OCA trainer\n\nStipend is Php 60.00 per hour of regular/special training and performances (Art. IX Sec. 29). Check with OCA for the current GWA and document requirements.",
        ],
        'pathfit' => [
            'triggers' => ['pathfit', 'pe exemption', 'physical education exemption', 'exempted from pe'],
            'response' => "PATHFit Exemption allows active cultural artists to be exempted from Physical Education subjects.\n\nTypical requirements:\nActive cultural troupe membership\nTrainer's endorsement\nParticipation records",
        ],
        'bantog' => [
            'triggers' => ['bantog', 'cultural excellence award', 'recognition program'],
            'response' => "BANTOG is the Cultural Excellence Recognition Program of BatStateU ARASOF-Nasugbu. It honors outstanding cultural artists who have represented the university in competitions.",
        ],
        'documents' => [
            'triggers' => ['documents', 'requirements', 'what to upload', 'attachments needed'],
            'response' => "Depending on what you're applying for, ARTEMIS may ask for:\n{$docList}\n\nWhich ones are required is shown on the application form once you pick a type — files must be PDF or image format, max 10MB each.",
        ],
        'login' => [
            'triggers' => ['login', 'log in', 'sign in', 'forgot password', 'cant login'],
            'response' => "To log in to ARTEMIS, use your BatStateU email and your account password, then select your role. Forgot your password? Use the Forgot Password link on the login page.",
        ],
        'register' => [
            'triggers' => ['register', 'sign up', 'create account', 'new account'],
            'response' => "Accounts aren't self-registered online. Visit the OCA Office at Room 201, Admin Building and fill out the physical application form — OCA will create your account and give you login credentials. See 'Apply Now' on the login page for the full steps.",
        ],
        'contact' => [
            'triggers' => ['contact', 'oca office', 'phone number', 'email address', 'office hours'],
            'response' => "oca.arasof@g.batstate-u.edu.ph\n(043) 425-0139\nBatStateU ARASOF-Nasugbu Campus, Nasugbu, Batangas\n\nOffice hours: Monday-Friday, 8AM-5PM",
        ],
        'events' => [
            'triggers' => ['events', 'cultural calendar', 'upcoming activities', 'festival', 'rsvp'],
            'response' => "Check the Events page for the current OCA cultural calendar — cultural nights, festivals, and awards ceremonies, with RSVP where available.",
        ],
        'help' => [
            'triggers' => ['help', 'what can you do', 'topics'],
            'response' => "Here's what I can help with:\nApplications — how to apply, requirements, status\nAuditions — registration, schedules\nStipend — eligibility, deadlines\nPATHFit — exemption process\nBANTOG — recognition program\nDocuments — what to upload\nContact — OCA contact info",
        ],
    ];
}

function spartan_tokenize(string $s): array
{
    $s = strtolower($s);
    $s = preg_replace('/[^a-z0-9\s]/', ' ', $s) ?? '';
    return array_values(array_filter(preg_split('/\s+/', trim($s))));
}

// Three escalating checks: exact word match, then substring (only for words of 4+
// letters, so short words like "pe" don't falsely match half of everything), then
// a >=75% character-similarity fallback that catches typos ("aplication" vs "application").
function spartan_token_fuzzy_match(string $token, string $target): bool
{
    if ($token === $target) {
        return true;
    }
    if (strlen($token) >= 4 && str_contains($target, $token)) {
        return true;
    }
    if (strlen($target) >= 4 && str_contains($token, $target)) {
        return true;
    }
    similar_text($token, $target, $percent);
    return $percent >= 75;
}

/** Best-matching topic id for a free-text message, or null if nothing scores high enough. */
function spartan_match_topic(string $input): ?string
{
    $inputTokens = spartan_tokenize($input);
    if (empty($inputTokens)) {
        return null;
    }

    $bestTopic = null;
    $bestScore = 0.0;

    foreach (spartan_topics() as $topicId => $topic) {
        foreach ($topic['triggers'] as $trigger) {
            // Word-boundary padded check: safe for short triggers (e.g. "hi") since it
            // requires the trigger to appear as whole word(s), not as a substring of an
            // unrelated word (e.g. "hi" inside "anything").
            $lowerInput = ' ' . strtolower($input) . ' ';
            if (str_contains($lowerInput, ' ' . strtolower($trigger) . ' ')) {
                return $topicId; // exact phrase match — highest confidence, short-circuit
            }

            $triggerTokens = spartan_tokenize($trigger);
            if (empty($triggerTokens)) {
                continue;
            }
            $hits = 0;
            foreach ($triggerTokens as $tt) {
                foreach ($inputTokens as $it) {
                    if (spartan_token_fuzzy_match($it, $tt)) {
                        $hits++;
                        break;
                    }
                }
            }
            // Score = fraction of this trigger's words found (fuzzily) in the message;
            // keep only the best-scoring trigger seen across every topic.
            $score = $hits / count($triggerTokens);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestTopic = $topicId;
            }
        }
    }

    // Below 60% confidence, admit we don't know rather than guess wrong.
    return $bestScore >= 0.6 ? $bestTopic : null;
}

/** Renders the student's own application records into the reply instead of generic instructions. */
function spartan_personalized_track_reply(array $user, PDO $pdo): ?string
{
    $stmt = $pdo->prepare(
        'SELECT a.application_code, a.status, a.current_stage, t.name AS type_name
         FROM applications a JOIN application_types t ON t.type_id = a.type_id
         WHERE a.user_id = :uid ORDER BY a.submitted_at DESC LIMIT 3'
    );
    $stmt->execute(['uid' => $user['user_id']]);
    $rows = $stmt->fetchAll();

    if (!$rows) {
        return "I don't see any applications on file for your account yet. You can submit one from your Student Dashboard under Submit Application.";
    }

    $lines = array_map(function ($r) {
        $stageLabel = ARTEMIS_PROGRESS_STAGES[min((int) $r['current_stage'], count(ARTEMIS_PROGRESS_STAGES)) - 1];
        return "{$r['application_code']} ({$r['type_name']}) — {$r['status']}, Stage {$r['current_stage']}/5: {$stageLabel}";
    }, $rows);

    return "Here's what's on file for your account:\n" . implode("\n", $lines) . "\n\nSee full details anytime in My Applications on your dashboard.";
}

function get_spartan_response(string $input, ?array $user = null, ?PDO $pdo = null): string
{
    if (trim($input) === '') {
        return "Type a question and I'll do my best to help — or type 'help' to see topics I can assist with.";
    }

    $topicId = spartan_match_topic($input);
    if ($topicId === null) {
        return "I'm not sure about that. Type 'help' to see all topics I can assist with, or contact OCA directly at oca.arasof@g.batstate-u.edu.ph.";
    }

    if ($topicId === 'track_application' && $user !== null && ($user['role'] ?? '') === 'student' && $pdo !== null) {
        $personalized = spartan_personalized_track_reply($user, $pdo);
        if ($personalized !== null) {
            return $personalized;
        }
    }

    return spartan_topics()[$topicId]['response'];
}
