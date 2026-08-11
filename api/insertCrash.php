<?php

require_once '../include/DB_Functions.php';
$db = new DB_Functions();

$response = array();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $exception = $_POST['exception'];
    $android_version = $_POST['android_version'];
    $app_version = $_POST['app_version'];

    $response = $db->insertCrash($exception, $android_version, $app_version);
} else {
    $response["error"] = TRUE;
    $response["error_msg"] = "Required parameters email or password is missing!";
}
echo json_encode($response);

