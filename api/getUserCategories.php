<?php

require_once '../include/DB_Functions.php';
$db = new DB_Functions();

$response = array();

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $userId = $_GET['user_id'];

    $data                  = $db->getUserCategories($userId);
    $response["error"]     = ($data == null);
    $response["error_msg"] = ($data == null) ? "اطلاعاتی یافت نشد"
  : "اطلاعات یافت شد";
    $response["data"]      = $data;

} else {
    $response["error"]     = TRUE;
    $response["error_msg"] = "REQUIRED PARAMETERS CREATOR ID IS MISSING!";
}
echo json_encode($response);

