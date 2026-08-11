<?php
require 'config.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = $_POST['phone'];
    $password  = $_POST['password'];
    $query  = "SELECT * FROM `ali_users` WHERE phone='$phone' AND password='$password'";
    $result = mysqli_query($connection, $query);

    $data = array();
    $user = null;
    if ($result->num_rows == 1)
        $user = mysqli_fetch_assoc($result);
    $data["error"]     = ($user == null);
    $data["error_msg"] = ($user == null) ? "LOGIN UN SUCCESS FULL" : "LOGIN SUCCESS FULL";
    $data["user"]      = $user;
    echo json_encode($data);
}
