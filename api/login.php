<?php
require_once '../include/DB_Functions.php';
$db = new DB_Functions();
$response = array();

if (isset($_POST['data'])) {

    // receiving the post params
    $email = $_POST['data'];

    // get the user by email and password
    $response = $db->getUserByEmailOrMobile($email);
//    $response["user"]      = $user;

} else {
    // required post params is missing
    $response["error"]     = TRUE;
    $response["error_msg"] = "لطفا شماره موبایل را وارد کنید";
}
echo json_encode($response, JSON_UNESCAPED_UNICODE);
