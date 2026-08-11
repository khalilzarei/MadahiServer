<?php

require_once '../include/DB_Functions.php';
$db = new DB_Functions();

$response = array();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $content_id = $_POST['content_id'];
    $user_id    = $_POST['user_id'];

    $response = $db->insertFavorite($content_id, $user_id);

} else {
    $response["error"]     = TRUE;
    $response["error_msg"] = "Required parameters email or password is missing!";
}
echo json_encode($response);

