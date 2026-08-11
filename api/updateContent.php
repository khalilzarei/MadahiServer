<?php

require_once '../include/DB_Functions.php';
$db = new DB_Functions();

$response = array();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $user_id    = $_POST['user_id'];
    $content_id = $_POST['content_id'];
    $answer     = $_POST['answer'];
    $content    = $_POST['content'];
    $subject    = $_POST['subject'];

    $response = $db->updateContent($user_id, $content_id, $answer, $content, $subject);
} else {
    $response["error"]     = TRUE;
    $response["error_msg"] = "Required parameters email or password is missing!";
}
echo json_encode($response);

