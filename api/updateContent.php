<?php
declare(strict_types=1);

require_once __DIR__ . '/../include/bootstrap.php';
require_method('POST');

$user        = require_auth();
$contentId   = (int)($_POST['content_id'] ?? 0);
// ⚠️ فیلدهای متنی ممکن است base64 ارسال شده باشند (عبور از فایروال هاست)
$subject     = trim(post_text('subject'));
$answer      = trim(post_text('answer'));
$content     = trim(post_text('content'));
// ⚠️ رفع باگ: اگر content_type ارسال نشده، مقدار فعلی دیتابیس حفظ می‌شود.
// نسخه‌ی قبلی با «?? 0» نوع هر محتوای ویرایش‌شده را به ۰ (نوحه) ریست می‌کرد.
// -1 = بدون تغییر
$contentType = isset($_POST['content_type']) ? (int)$_POST['content_type'] : -1;

if ($contentId <= 0 || $subject === '' || $content === '') {
    error_response('اطلاعات ناقص است');
}

$db   = new DB_Functions();
$data = $db->updateContent((int)$user['id'], $contentId, $answer, $content, $subject, $contentType);

if (!empty($data['error'])) {
    error_response($data['error_msg']);
}

json_response($data);
