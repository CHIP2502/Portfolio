<?php
/**
 * contact.php — handle portfolio contact form submissions.
 * Accepts POST (form) or POST JSON; replies JSON for AJAX.
 */
declare(strict_types=1);

session_start([
    'cookie_lifetime' => 0,
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
]);

header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');

$wantsJson = (
    (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest')
    || str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json')
);

function reply(int $status, string $message, bool $json, string $redirect = '/'): void
{
    if ($json) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => $status < 400, 'message' => $message], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $flag = $status < 400 ? 'sent' : 'error';
    $url = $redirect . '?contact=' . $flag . '#contact';
    header('Location: ' . $url, true, 303);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    reply(405, 'Method not allowed', $wantsJson);
}

// Honeypot: silent success
if (!empty($_POST['website'] ?? '')) {
    reply(200, 'OK', $wantsJson);
}

// CSRF
$csrfPosted = (string) ($_POST['csrf'] ?? '');
$csrfStored = (string) ($_SESSION['mh_csrf'] ?? '');
if ($csrfStored === '' || !hash_equals($csrfStored, $csrfPosted)) {
    reply(403, 'Token không hợp lệ. Vui lòng tải lại trang.', $wantsJson);
}

// Rate limit: 1 message / 30s / session
$now = time();
$lastSent = (int) ($_SESSION['mh_contact_last'] ?? 0);
if ($lastSent && ($now - $lastSent) < 30) {
    reply(429, 'Bạn gửi quá nhanh. Thử lại sau ít giây nhé.', $wantsJson);
}

$name    = trim((string) ($_POST['name']    ?? ''));
$email   = trim((string) ($_POST['email']   ?? ''));
$message = trim((string) ($_POST['message'] ?? ''));

if ($name === '' || $email === '' || $message === '') {
    reply(400, 'Vui lòng điền đầy đủ họ tên, email và nội dung.', $wantsJson);
}
if (mb_strlen($name) > 80 || mb_strlen($email) > 120 || mb_strlen($message) > 2000) {
    reply(400, 'Nội dung quá dài.', $wantsJson);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    reply(400, 'Email không hợp lệ.', $wantsJson);
}
// Block header injection
if (preg_match('/[\r\n]/', $name) || preg_match('/[\r\n]/', $email)) {
    reply(400, 'Dữ liệu chứa ký tự không hợp lệ.', $wantsJson);
}

$to = 'minhhuy@nqminkhuy.com';
$subject = '[Portfolio] Tin nhắn mới từ ' . $name;
$bodyLines = [
    'Họ tên : ' . $name,
    'Email  : ' . $email,
    'IP     : ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'),
    'UA     : ' . substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 200),
    'Time   : ' . date('Y-m-d H:i:s'),
    str_repeat('-', 40),
    $message,
];
$body = implode("\r\n", $bodyLines);

$safeEmail = preg_replace('/[\r\n]/', '', $email);
$headers = [
    'From: MinhHuyDev Portfolio <no-reply@nqminkhuy.com>',
    'Reply-To: ' . $safeEmail,
    'X-Mailer: PHP/' . PHP_VERSION,
    'Content-Type: text/plain; charset=UTF-8',
];

// Always log locally
$logDir = __DIR__ . '/AntiBot-Requests';
if (!is_dir($logDir)) { @mkdir($logDir, 0775, true); }
@file_put_contents(
    $logDir . '/contact-' . date('Ym') . '.log',
    '[' . date('c') . '] ' . $email . ' | ' . str_replace(["\r", "\n"], ' / ', $message) . PHP_EOL,
    FILE_APPEND | LOCK_EX
);

$sent = @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, implode("\r\n", $headers));

$_SESSION['mh_contact_last'] = $now;

if (!$sent) {
    // Mail might fail in local dev, but log is preserved
    reply(202, 'Đã ghi nhận tin nhắn. Mình sẽ phản hồi sớm nhất có thể.', $wantsJson);
}

reply(200, 'Cảm ơn bạn! Tin nhắn đã được gửi thành công.', $wantsJson);
