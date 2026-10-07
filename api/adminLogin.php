<?php
declare(strict_types=1);

require_once __DIR__ . '/../include/bootstrap.php';
require_method('POST');

$mobile = normalizeMobile((string)($_POST['mobile'] ?? ''));
if ($mobile === '') {
    error_response('شماره موبایل را وارد کنید');
}

rate_limit('admin_login_ip_' . clientIp(), 10, 900);
rate_limit('admin_login_mobile_' . $mobile, 5, 900);
$db = new DB_Functions();
$user = $db->getUserByMobile($mobile);
if ($user === null || empty($user['is_admin'])) {
    error_response('این حساب دسترسی مدیر ندارد', 403);
}

// ⚠️ ورود مدیر عمداً بدون رمز عبور باقی مانده است (تصمیم محصول).
// این یعنی دانستن شمارهٔ مدیر برای گرفتن توکن مدیر کافی است؛
// اگر خواستید همین مسیر هم با رمز شود، کافی است رمز را بگیرید و
// loginByMobile($mobile, $password) را صدا بزنید.
$data = $db->loginAdminByMobile($mobile);

if (!empty($data['error'])) {
    error_response($data['error_msg']);
}

json_response($data);
