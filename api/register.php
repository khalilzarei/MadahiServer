<?php

require_once '../include/DB_Functions.php';
$db = new DB_Functions();

$response = array();

if (isset($_POST['data']) && isset($_POST['full_name'])) {
    $email     = $_POST['data'];
    $full_name = $_POST['full_name'];
    $response  = $db->insertUser($email, $full_name);
} else {
    $response["error"]     = TRUE;
    $response["error_msg"] = "لطفا اطلاعات کامل را وارد کنید";
}
echo json_encode($response);

