<?php
header('Content-Type: application/json; charset=utf-8');

// Sadece kendi sitemizden gelen istekleri kabul et (basit bir koruma)
header('Access-Control-Allow-Origin: https://tbtk.org');

$adminEmail = 'bvhyapim@gmail.com';
$fromEmail  = 'noreply@tbtk.org';

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'invalid_payload']);
    exit;
}

function clean($v) {
    $v = is_string($v) ? $v : '';
    // e-posta basliklarina zarar verecek satir sonlarini temizle
    return trim(str_replace(["\r", "\n"], ' ', $v));
}

$kind    = clean($data['kind'] ?? '');
$name    = clean($data['full_name'] ?? '');
$email   = clean($data['email'] ?? '');
$phone   = clean($data['phone'] ?? '');
$message = is_string($data['message'] ?? '') ? $data['message'] : '';
$subject_field = clean($data['subject'] ?? '');

$titles = [
    'uyelik'    => 'Yeni Üyelik Başvurusu',
    'bagis'     => 'Yeni Bağış Bildirimi',
    'iletisim'  => 'Yeni İletişim Mesajı',
];
$subject = '=?UTF-8?B?' . base64_encode(($titles[$kind] ?? 'TBTK Site Bildirimi') . ' — TBTK') . '?=';

$body  = ($titles[$kind] ?? 'Bildirim') . "\n\n";
$body .= "Ad Soyad: {$name}\n";
$body .= "E-posta: {$email}\n";
if ($phone !== '')          $body .= "Telefon: {$phone}\n";
if ($subject_field !== '')  $body .= "Konu: {$subject_field}\n";
if (trim($message) !== '')  $body .= "\nMesaj / Detay:\n{$message}\n";
$body .= "\n— tbtk.org üzerinden otomatik gönderilmiştir.";

$headers  = "From: TBTK Site <{$fromEmail}>\r\n";
if ($email !== '') {
    $headers .= "Reply-To: {$email}\r\n";
}
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

$sent = @mail($adminEmail, $subject, $body, $headers);

echo json_encode(['ok' => (bool) $sent]);
