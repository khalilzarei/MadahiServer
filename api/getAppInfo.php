<?php

require_once '../include/DB_Functions.php';
$db = new DB_Functions();

$response = array();

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
     $data                  = $db->getAppInfo();
    $response["error"]     = ($data == null);
    $response["error_msg"] = ($data == null) ? "اطلاعاتی یافت نشد"
    : "اطلاعات یافت شد";
    $response["info"]      = $data;

} else {
    $response["error"]     = TRUE;
    $response["error_msg"] = "اطلاعاتی برای نمایش وجود ندارد";
}
echo json_encode($response);

