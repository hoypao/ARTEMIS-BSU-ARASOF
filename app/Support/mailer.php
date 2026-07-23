<?php
/** Outbound email via Gmail SMTP (PHPMailer). Every call is best-effort —
 * a mail failure is logged, never thrown back at the caller, so a down
 * SMTP connection can't block a login or a password reset. */




use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;

function mail_is_configured(): bool
{
    return MAIL_USERNAME !== '' && MAIL_PASSWORD !== '';
}

const MAIL_LOGO_CID = 'artemislogo';

function send_email(string $toEmail, string $toName, string $subject, string $htmlBody): bool
{
    if (!mail_is_configured()) {
        error_log("ARTEMIS mail: MAIL_USERNAME/MAIL_PASSWORD not set, skipped '{$subject}' to {$toEmail}");
        return false;
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int) MAIL_PORT;
        $mail->Timeout    = 10;

        $mail->setFrom(MAIL_USERNAME, MAIL_FROM_NAME);
        $mail->addAddress($toEmail, $toName);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = trim(strip_tags(preg_replace('/<br\s*\/?>/i', "\n", $htmlBody)));

        // Embed the logo as a CID attachment rather than linking to APP_URL —
        // APP_URL is localhost during development, which would be a broken
        // image for anyone opening the email on a different machine. An
        // embedded image works in every inbox regardless of hosting.
        $logoPath = ARTEMIS_ROOT . '/assets/images/bsulogo.jpg';
        if (str_contains($htmlBody, 'cid:' . MAIL_LOGO_CID) && is_file($logoPath)) {
            $mail->addEmbeddedImage($logoPath, MAIL_LOGO_CID, 'bsulogo.jpg');
        }

        $mail->send();
        return true;
    } catch (PHPMailerException $e) {
        error_log('ARTEMIS mail failed to ' . $toEmail . ': ' . $mail->ErrorInfo);
        return false;
    }
}

function email_layout(string $title, string $bodyHtml): string
{
    $appName = e(APP_NAME);
    $titleEsc = e($title);
    $cid = MAIL_LOGO_CID;
    return <<<HTML
<div style="font-family: Arial, Helvetica, sans-serif; background: #f4f3f0; padding: 32px 12px; margin: 0;">
  <div style="max-width: 480px; margin: 0 auto;">
    <div style="background: linear-gradient(135deg, #B11226 0%, #7a0d1a 100%); padding: 30px 28px 26px; border-radius: 16px 16px 0 0; text-align: center;">
      <div style="width: 56px; height: 56px; border-radius: 50%; background: #ffffff; margin: 0 auto 14px; overflow: hidden; border: 2px solid rgba(255,255,255,0.5);">
        <img src="cid:{$cid}" width="56" height="56" alt="BatStateU OCA Logo" style="display: block; width: 56px; height: 56px; object-fit: cover; border-radius: 50%;">
      </div>
      <div style="color: #ffffff; font-weight: bold; font-size: 20px; letter-spacing: 0.08em;">{$appName}</div>
      <div style="color: #f3c8c8; font-size: 12px; margin-top: 3px;">BatStateU ARASOF-Nasugbu &middot; Culture and Arts Office</div>
    </div>
    <div style="background: #ffffff; padding: 30px 28px 8px;">
      <h2 style="margin: 0 0 14px; font-size: 19px; color: #1a1a2e;">{$titleEsc}</h2>
      <div style="font-size: 14px; line-height: 1.65; color: #374151;">
        {$bodyHtml}
      </div>
    </div>
    <div style="background: #ffffff; padding: 12px 28px 0;">
      <div style="height: 1px; background: linear-gradient(90deg, rgba(244,243,240,1), #D4AF37 45%, rgba(244,243,240,1));"></div>
    </div>
    <div style="background: #ffffff; border-radius: 0 0 16px 16px; padding: 18px 28px 28px; text-align: center;">
      <p style="color: #9CA3AF; font-size: 11px; margin: 0; line-height: 1.5;">This is an automated message from {$appName}. Please do not reply to this email.</p>
      <p style="color: #c3c2b7; font-size: 10px; margin: 8px 0 0;">&copy; 2026 ARTEMIS &mdash; Culture and Arts Office, BatStateU ARASOF-Nasugbu</p>
    </div>
  </div>
</div>
HTML;
}
