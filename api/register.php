<?php
declare(strict_types=1);

require_once __DIR__ . '/../include/bootstrap.php';

require_method('POST');

$mobile = normalizeMobile((string)($_POST['data'] ?? ''));
$fullName = trim((string)($_POST['full_name'] ?? ''));

if ($mobile === '') {
    error_response('لطفا شماره موبایل را وارد کنید');
}

if ($fullName === '') {
    error_response('لطفا نام و نام خانوادگی را وارد کنید');
}

if (!preg_match('/^09\d{9}$/', $mobile)) {
    error_response('شماره موبایل معتبر نیست');
}

if (mb_strlen($fullName) > 150) {
    error_response('نام خیلی طولانی است');
}

rate_limit('register_mobile_' . $mobile, 5, 900);
rate_limit('register_ip_' . clientIp(), 20, 900);

$db = new DB_Functions();

$data = $db->register($mobile, $fullName);

if (!empty($data['error'])) {
    error_response($data['error_msg']);
}

json_response($data);