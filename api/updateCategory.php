<?php

require_once '../include/DB_Functions.php';
$db = new DB_Functions();

$response = array();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $user_id     = $_POST['user_id'];
    $category_id = $_POST['category_id'];
    $title       = $_POST['title'];
    $description = $_POST['description'];

    $response = $db->updateCategory($user_id, $category_id, $title, $description);
} else {
    $response["error"]     = TRUE;
    $response["error_msg"] = "Required parameters email or password is missing!";
}
echo json_encode($response);

