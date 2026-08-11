<?php

require_once '../include/DB_Functions.php';
$db = new DB_Functions();

$response = array();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $category_id  = $_POST['category_id'];
    $user_id      = $_POST['user_id'];
    $answer       = $_POST['answer'];
    $content      = $_POST['content'];
    $subject      = $_POST['subject'];
    $content_type = $_POST['content_type'];

    $response = $db->insertContent($category_id, $user_id, $answer, $content, $subject, $content_type);
} else {
    $response["error"]     = TRUE;
    $response["error_msg"] = "Required parameters email or password is missing!";
}
echo json_encode($response);

