<?php

require_once '../include/DB_Functions.php';
$db = new DB_Functions();

$response = array();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // ✅ بررسی وجود پارامترها
    if (isset($_POST['user_id']) && isset($_POST['category_id'])) {
        $userId = $_POST['user_id'];
        $categoryId = $_POST['category_id'];
        $response = $db->deleteCategory($userId, $categoryId);
    } else {
        $response["error"] = true;
        $response["error_msg"] = "پارامترهای مورد نیاز ارسال نشده است";
    }

} else {
    $response["error"] = true;
    $response["error_msg"] = "روش درخواست باید POST باشد";
}
echo json_encode($response);