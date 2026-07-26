<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Ports of login.php, actions/auth/login_action.php, actions/auth/logout.php,
 * actions/auth/forgot_password_action.php, reset_password.php, and
 * actions/auth/reset_password_action.php. CSRF checks moved to middleware;
 * everything else keeps the legacy order: reCAPTCHA -> lockout -> credentials,
 * each returning early so a request never leaks more than the earliest check.
 */
class AuthController extends Controller
{
    /** login.php — renders the form; redirects away if already logged in. */
    public function showLogin()
    {
        if (is_logged_in()) {
            $user = current_user();
            return redirect()->route(dashboard_route_for_role($user['role']));
        }

        $pageTitle = 'Login';
        $error = flash_get('error');
        $success = flash_get('success');
        $oldEmail = session()->pull('old_email', $_COOKIE['artemis_remembered_email'] ?? '');
        $rememberChecked = !empty($_COOKIE['artemis_remembered_email']);

        return view('pages.login', compact('pageTitle', 'error', 'success', 'oldEmail', 'rememberChecked'));
    }

    /** actions/auth/login_action.php */
    public function login(Request $request)
    {
        $email = trim($request->input('email', ''));
        $password = (string) $request->input('password', '');
        $remember = (bool) $request->input('remember');

        if ($email === '' || $password === '') {
            flash_set('error', 'Please enter both email and password.');
            session(['old_email' => $email]);
            return redirect()->route('login');
        }

        if (!recaptcha_verify($request->input('g-recaptcha-response'))) {
            flash_set('error', 'Please complete the "I\'m not a robot" check.');
            session(['old_email' => $email]);
            return redirect()->route('login');
        }

        $pdo = getDB();

        $lockedMinutes = login_lockout_minutes_remaining($pdo, $email);
        if ($lockedMinutes !== null) {
            flash_set('error', "Too many failed attempts. Try again in {$lockedMinutes} minute" . ($lockedMinutes === 1 ? '' : 's') . '.');
            session(['old_email' => $email]);
            return redirect()->route('login');
        }

        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email AND status = "active" LIMIT 1');
        $stmt->execute(['email' => $email]);
        $userRow = $stmt->fetch();

        // Same generic error either way (unknown email vs. wrong password) — telling
        // an attacker which one it was would confirm whether that email has an account.
        if (!$userRow || !password_verify($password, $userRow['password_hash'])) {
            login_attempt_record($pdo, $email);
            $remainingAttempts = LOGIN_MAX_ATTEMPTS - login_attempts_count($pdo, $email);
            $message = 'Invalid email or password.';
            if ($remainingAttempts > 0 && $remainingAttempts <= 2) {
                $message .= " {$remainingAttempts} attempt" . ($remainingAttempts === 1 ? '' : 's') . ' remaining before your account is temporarily locked.';
            }
            flash_set('error', $message);
            session(['old_email' => $email]);
            return redirect()->route('login');
        }

        login_attempts_clear($pdo, $email);
        login_user($userRow);

        if ($remember) {
            setcookie('artemis_remembered_email', $email, time() + 60 * 60 * 24 * 30, '/');
        } else {
            setcookie('artemis_remembered_email', '', time() - 3600, '/');
        }

        if (mail_is_configured()) {
            $when = date('F j, Y \a\t g:i A');
            $ip = client_ip();
            $body = "<p>Hi {$userRow['first_name']},</p>"
                . "<p>We noticed a new sign-in to your ARTEMIS account.</p>"
                . "<table style=\"font-size:13px; color:#374151; margin: 12px 0;\">"
                . "<tr><td style=\"padding:2px 12px 2px 0; color:#9CA3AF;\">Time</td><td>{$when}</td></tr>"
                . "<tr><td style=\"padding:2px 12px 2px 0; color:#9CA3AF;\">IP address</td><td>{$ip}</td></tr>"
                . "</table>"
                . "<p>If this was you, no action is needed. If you don't recognize this sign-in, reset your password immediately from the login page.</p>";
            send_email(
                $userRow['email'],
                $userRow['first_name'] . ' ' . $userRow['last_name'],
                'New sign-in to your ARTEMIS account',
                email_layout('New sign-in detected', $body)
            );
        }

        return redirect()->route($userRow['role'] === 'student' ? 'home' : dashboard_route_for_role($userRow['role']));
    }

    /** actions/auth/logout.php */
    public function logout()
    {
        logout_user();
        return redirect()->route('home');
    }

    /** actions/auth/forgot_password_action.php (JSON) */
    public function forgotPassword(Request $request)
    {
        $email = trim($request->input('email', ''));

        // Always the same generic success whether or not the email is registered —
        // "no such account" is exactly the enumeration signal an attacker wants.
        $genericResponse = ['success' => true, 'message' => 'If that email is registered, a reset link is on its way.'];

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json($genericResponse);
        }

        $pdo = getDB();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email AND status = "active" LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if (!$user) {
            return response()->json($genericResponse);
        }

        // Throttle: skip issuing a new token (but still report success) if one was
        // already requested in the last 60 seconds. Runs against MySQL's own NOW().
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM password_resets WHERE user_id = :uid AND created_at > (NOW() - INTERVAL 60 SECOND)'
        );
        $stmt->execute(['uid' => $user['user_id']]);
        if ((int) $stmt->fetchColumn() > 0) {
            return response()->json($genericResponse);
        }

        // A user only ever has one live token — invalidate anything still unused.
        $stmt = $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE user_id = :uid AND used_at IS NULL');
        $stmt->execute(['uid' => $user['user_id']]);

        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);

        $stmt = $pdo->prepare(
            'INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (:uid, :hash, DATE_ADD(NOW(), INTERVAL 30 MINUTE))'
        );
        $stmt->execute(['uid' => $user['user_id'], 'hash' => $tokenHash]);

        $resetLink = APP_URL . '/reset-password?token=' . $token;
        $body = "<p>Hi {$user['first_name']},</p>"
            . "<p>We received a request to reset the password for your ARTEMIS account. This link expires in 30 minutes and can only be used once.</p>"
            . "<p style=\"text-align:center; margin: 24px 0;\">"
            . "<a href=\"{$resetLink}\" style=\"display:inline-block; background:#B11226; color:#fff; text-decoration:none; padding:12px 28px; border-radius:10px; font-weight:600; font-size:14px;\">Reset My Password</a>"
            . "</p>"
            . "<p style=\"font-size:12px; color:#9CA3AF;\">If you didn't request this, you can safely ignore this email — your password won't change.</p>";

        send_email(
            $user['email'],
            $user['first_name'] . ' ' . $user['last_name'],
            'Reset your ARTEMIS password',
            email_layout('Reset your password', $body)
        );

        return response()->json($genericResponse);
    }

    /** reset_password.php — token pre-check decides which view state renders. */
    public function showResetPassword(Request $request)
    {
        $token = trim($request->query('token', ''));
        $tokenValid = false;
        $resetId = null;

        if ($token !== '') {
            $pdo = getDB();
            $stmt = $pdo->prepare(
                'SELECT pr.reset_id, pr.expires_at, u.first_name
                 FROM password_resets pr
                 JOIN users u ON u.user_id = pr.user_id
                 WHERE pr.token_hash = :hash AND pr.used_at IS NULL AND pr.expires_at > NOW()
                 LIMIT 1'
            );
            $stmt->execute(['hash' => hash('sha256', $token)]);
            $reset = $stmt->fetch();
            if ($reset) {
                $tokenValid = true;
                $resetId = (int) $reset['reset_id'];
            }
        }

        $pageTitle = 'Reset Password';
        $error = flash_get('error');

        return view('pages.reset_password', compact('token', 'tokenValid', 'resetId', 'pageTitle', 'error'));
    }

    /** actions/auth/reset_password_action.php */
    public function resetPassword(Request $request)
    {
        $token = trim($request->input('token', ''));
        $password = (string) $request->input('password', '');
        $confirmPassword = (string) $request->input('confirm_password', '');

        if (strlen($password) < 8) {
            return $this->failReset($token, 'Password must be at least 8 characters.');
        }

        if ($password !== $confirmPassword) {
            return $this->failReset($token, "Passwords don't match.");
        }

        $pdo = getDB();
        $stmt = $pdo->prepare(
            'SELECT pr.reset_id, pr.user_id, u.email
             FROM password_resets pr
             JOIN users u ON u.user_id = pr.user_id
             WHERE pr.token_hash = :hash AND pr.used_at IS NULL AND pr.expires_at > NOW()
             LIMIT 1'
        );
        $stmt->execute(['hash' => hash('sha256', $token)]);
        $reset = $stmt->fetch();

        if (!$reset) {
            return $this->failReset($token, 'This reset link has expired or was already used.');
        }

        $newHash = password_hash($password, PASSWORD_DEFAULT);

        $pdo->prepare('UPDATE users SET password_hash = :hash WHERE user_id = :uid')
            ->execute(['hash' => $newHash, 'uid' => $reset['user_id']]);

        $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE reset_id = :id')
            ->execute(['id' => $reset['reset_id']]);

        // A password reset is also a good moment to clear any brute-force lockout
        // on the account — the owner just proved control of the mailbox.
        login_attempts_clear($pdo, $reset['email']);

        flash_set('success', 'Password reset successfully. You can now log in.');
        return redirect()->route('login');
    }

    private function failReset(string $token, string $message)
    {
        flash_set('error', $message);
        return redirect(route('password.reset') . '?token=' . urlencode($token));
    }
}
