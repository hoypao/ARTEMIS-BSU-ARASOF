<?php
/** Sliding-window login lockout, keyed by email. Blocks credential
 * checking outright once the threshold is hit — the attacker never
 * learns whether the password they just tried was right. */

const LOGIN_MAX_ATTEMPTS    = 5;
const LOGIN_WINDOW_MINUTES  = 15;
const LOGIN_LOCKOUT_MINUTES = 15;

function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Minutes remaining before this email may try again, or null if not locked.
 * Computed entirely inside MySQL (COUNT, MAX, and the remaining-time delta
 * all against MySQL's own NOW()) — the app server's PHP timezone doesn't
 * have to match the DB server's for this to be correct.
 */
function login_lockout_minutes_remaining(PDO $pdo, string $email): ?int
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) AS cnt,
                TIMESTAMPDIFF(SECOND, NOW(), DATE_ADD(MAX(attempted_at), INTERVAL :lockout MINUTE)) AS seconds_remaining
         FROM login_attempts
         WHERE email = :email AND attempted_at > (NOW() - INTERVAL :window MINUTE)'
    );
    $stmt->bindValue(':email', $email);
    $stmt->bindValue(':window', LOGIN_WINDOW_MINUTES, PDO::PARAM_INT);
    $stmt->bindValue(':lockout', LOGIN_LOCKOUT_MINUTES, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch();

    if (!$row || (int) $row['cnt'] < LOGIN_MAX_ATTEMPTS) {
        return null;
    }

    $remaining = (int) ceil(((int) $row['seconds_remaining']) / 60);
    return $remaining > 0 ? $remaining : null;
}

function login_attempt_record(PDO $pdo, string $email): void
{
    $stmt = $pdo->prepare('INSERT INTO login_attempts (email, ip_address) VALUES (:email, :ip)');
    $stmt->execute(['email' => $email, 'ip' => client_ip()]);
}

/** Failed attempts recorded for this email within the current lockout window. */
function login_attempts_count(PDO $pdo, string $email): int
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM login_attempts WHERE email = :email AND attempted_at > (NOW() - INTERVAL :window MINUTE)'
    );
    $stmt->bindValue(':email', $email);
    $stmt->bindValue(':window', LOGIN_WINDOW_MINUTES, PDO::PARAM_INT);
    $stmt->execute();
    return (int) $stmt->fetchColumn();
}

function login_attempts_clear(PDO $pdo, string $email): void
{
    $stmt = $pdo->prepare('DELETE FROM login_attempts WHERE email = :email');
    $stmt->execute(['email' => $email]);
}
