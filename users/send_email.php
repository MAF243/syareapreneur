<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendors/autoload.php';

/**
 * Ubah ke domain kamu (tanpa trailing slash).
 * Untuk lokal:
 *   http://localhost/syareapreneur
 */
const APP_BASE_URL = 'http://localhost/syareapreneur';

/** Kirim email reset password */
function send_reset_email($recipient_email, $token) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'lia.farhan.dila@gmail.com';
        $mail->Password   = 'iutk ahbo nfgm tjca'; // App Password Gmail (16 digit)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('lia.farhan.dila@gmail.com', 'Syareapreneur');
        $mail->addAddress($recipient_email);

        // PASTIKAN menuju reset_password.php (bukan reset-password.php)
        $resetLink = APP_BASE_URL . '/users/reset_password.php?token=' . urlencode($token);

        $mail->isHTML(true);
        $mail->Subject = 'Reset Password Anda';
        $mail->Body    = "Klik link berikut untuk reset password Anda:<br>
                          <a href='$resetLink'>$resetLink</a><br><br>
                          Link berlaku selama 1 jam.";
        return $mail->send();
    } catch (Exception $e) {
        error_log("Mailer Error (reset): {$mail->ErrorInfo}");
        return false;
    }
}

/** Kirim OTP verifikasi email (6 digit) */
function send_verification_email($recipient_email, $otp) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'lia.farhan.dila@gmail.com';
        $mail->Password   = 'iutk ahbo nfgm tjca';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('lia.farhan.dila@gmail.com', 'Syareapreneur');
        $mail->addAddress($recipient_email);

        $mail->isHTML(true);
        $mail->Subject = 'Kode Verifikasi Email';
        $mail->Body    = "
          <p>Terima kasih telah mendaftar di <strong>Syareapreneur</strong>.</p>
          <p>Kode verifikasi email Anda:</p>
          <h2 style='letter-spacing:4px;margin:10px 0;'>$otp</h2>
          <p>Kode berlaku selama <strong>15 menit</strong>.</p>
          <p>Jika Anda tidak merasa mendaftar, abaikan email ini.</p>
        ";
        return $mail->send();
    } catch (Exception $e) {
        error_log("Mailer Error (verify): {$mail->ErrorInfo}");
        return false;
    }
}