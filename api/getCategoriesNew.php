<?php

require_once 'include/Config.php';
$connection = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_DATABASE);
$connection->set_charset("utf8");

$response = array();

if ($_SERVER['REQUEST_METHOD'] == 'GET') {

    $sql    = "SELECT * FROM `categories`";
    $result = mysqli_query($connection, $sql);
    $data   = array();
    while ($row = mysqli_fetch_assoc($result)){
        echo $row['id'];
        $data[] = $row;}
    $response["error"]     = ($data == null);
    $response["error_msg"] = ($data == null) ?  "اطلاعاتی یافت نشد"
  : "اطلاعات یافت شد";
    $response["data"]      = $data;

} else {
    $response["error"]     = TRUE;
    $response["error_msg"] = "REQUIRED PARAMETERS CREATOR ID IS MISSING!";
}
echo json_encode($response);

