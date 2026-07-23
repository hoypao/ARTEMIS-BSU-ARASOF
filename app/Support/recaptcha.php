<?php
/** Google reCAPTCHA v2 ("I'm not a robot" checkbox). Verification is a
 * no-op success while unconfigured, so login keeps working during setup —
 * it only starts enforcing once both keys are filled in via .env. */


function recaptcha_enabled(): bool
{
    return RECAPTCHA_SITE_KEY !== '' && RECAPTCHA_SECRET_KEY !== '';
}

function recaptcha_verify(?string $token): bool
{
    if (!recaptcha_enabled()) {
        return true;
    }
    if (!$token) {
        return false;
    }

    $ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'secret'   => RECAPTCHA_SECRET_KEY,
            'response' => $token,
            'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 8,
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    if ($response === false) {
        return false;
    }
    $data = json_decode($response, true);
    return !empty($data['success']);
}
