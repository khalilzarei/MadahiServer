<?php
declare(strict_types=1);

require_once __DIR__ . '/../include/bootstrap.php';
require_method('POST');

$user      = require_auth();
$contentId = (int)($_POST['content_id'] ?? 0);

if ($contentId <= 0) {
    error_response('محتوا مشخص نشده است');
}

$db      = new DB_Functions();
$content = $db->getContentWithUserIdAndId((int)$user['id'], $contentId);

if ($content === null) {
    error_response('محتوا یافت نشد یا دسترسی ندارید');
}

if (!isset($_FILES['audio'])) {
    error_response('فایل صوتی ارسال نشده است');
}

// ترجمه خطاهای آپلود
$uploadErrors = [
    1 => 'حجم فایل از حد مجاز سرور بیشتر است',
    2 => 'حجم فایل از حد مجاز بیشتر است',
    3 => 'فایل ناقص آپلود شد',
    4 => 'فایل صوتی ارسال نشده است',
    6 => 'پوشه موقت سرور در دسترس نیست',
    7 => 'امکان نوشتن فایل روی سرور نیست',
];

if ($_FILES['audio']['error'] !== UPLOAD_ERR_OK) {
    error_response($uploadErrors[$_FILES['audio']['error']] ?? 'خطا در آپلود فایل');
}

$file    = $_FILES['audio'];
$maxSize = 10 * 1024 * 1024; // ۱۰ مگابایت

if ($file['size'] <= 0) {
    error_response('فایل خالی است');
}
if ($file['size'] > $maxSize) {
    error_response('حجم فایل نباید بیشتر از 10 مگابایت باشد');
}

// بررسی پسوند
$allowedExt = ['mp3', 'm4a', 'ogg', 'aac', 'wav', 'mp4'];
$ext        = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, $allowedExt, true)) {
    error_response('فرمت فایل مجاز نیست (mp3, m4a, ogg, aac, wav)');
}

// بررسی MIME واقعی فایل (نه فقط پسوند)
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime  = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

$isAudio = str_starts_with($mime, 'audio/')
    || ($ext === 'ogg' && $mime === 'application/ogg');

if (!$isAudio) {
    error_response('فایل ارسالی یک فایل صوتی معتبر نیست');
}

$uploadDir = dirname(__DIR__) . '/uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}
if (!is_writable($uploadDir)) {
    error_response('پوشه آپلود قابل نوشتن نیست');
}

// نام تصادفی و غیرقابل حدس
$newName  = 'audio_' . bin2hex(random_bytes(8)) . '_' . time() . '.' . $ext;
$destPath = $uploadDir . $newName;

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    error_response('خطا در ذخیره فایل');
}
@chmod($destPath, 0644);

$audioUrl = APP_BASE_URL . '/uploads/' . $newName;

// حذف فایل قبلی اگر سبک قبلی داشته
if (!empty($content['audio_url'])) {
    $db->deleteAudioFile($content['audio_url']);
}

$db->updateContentAudio($contentId, $audioUrl);
$updated = $db->getContentWithUserIdAndId((int)$user['id'], $contentId);

json_response([
    'error'     => false,
    'error_msg' => 'سبک با موفقیت ذخیره شد',
    'content'   => $updated,
]);
