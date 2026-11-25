<?php
declare(strict_types=1);

/**
 * جستجو در شعرهای کاربر (دفترچه) — در محتوای شعرها، نه دسته‌بندی‌ها
 *
 * ورودی:  q یا q_b64 (base64؛ عبور امن از فایروال هاست — الگوی بقیه APIها)
 * خروجی:  { error, error_msg, data: [...] } — همان ساختار getContentWithCategory
 *
 * فقط در شعرهای مال کاربرِ احرازشده جستجو می‌شود (user_id = خودِ کاربر).
 */
require_once __DIR__ . '/../include/bootstrap.php';
require_method('POST');

$user  = require_auth();
$query = trim(post_text('q'));

if ($query === '' || mb_strlen($query, 'UTF-8') < 2) {
    json_response([
        'error'     => false,
        'error_msg' => 'عبارت جستجو کوتاه است',
        'data'      => [],
    ]);
}

$db   = new DB_Functions();
$data = $db->searchContents((int)$user['id'], $query);

json_response([
    'error'     => false,
    'error_msg' => 'اطلاعات یافت شد',
    'data'      => $data,
]);
