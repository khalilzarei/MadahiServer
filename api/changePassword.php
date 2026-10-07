<?php
declare(strict_types=1);

/**
 * تغییر رمز عبور کاربر احراز‌هویت‌شده.
 *
 * این تنها endpointی است که کاربرِ دارای پرچم «الزام تغییر رمز»
 * (رمز اولیه = شماره موبایل) اجازهٔ فراخوانی دارد؛ بقیهٔ endpointها
 * در require_auth() تا زمان تغییر رمز بسته می‌مانند.
 *
 * ورودی (POST فرم):
 *   current_password — رمز فعلی (برای کاربر جدید: همان شماره موبایل)
 *   new_password     — رمز جدید
 */

require_once __DIR__ . '/../include/bootstrap.php';
require_method('POST');

$user = require_auth();

$currentPassword = (string)($_POST['current_password'] ?? '');
$newPassword     = (string)($_POST['new_password'] ?? '');

if ($currentPassword === '') {
    error_response('لطفا رمز عبور فعلی را وارد کنید');
}
if ($newPassword === '') {
    error_response('لطفا رمز عبور جدید را وارد کنید');
}

$userId = (int)$user['id'];

// محدودیت تعداد تلاش (هم برای کاربر، هم برای IP)
rate_limit('change_password_user_' . $userId, 10, 900);
rate_limit('change_password_ip_' . clientIp(), 30, 900);

$db   = new DB_Functions();
$data = $db->changePassword($userId, $currentPassword, $newPassword);

if (!empty($data['error'])) {
    error_response($data['error_msg']);
}

json_response($data);
