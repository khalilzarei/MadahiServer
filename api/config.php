<?php

$host = "localhost";
$user = "madahino_user";
$password = "@65877856.com";
$database = "madahino_madahi";

$connection = new mysqli($host, $user, $password, $database);
$connection->set_charset("utf8");
if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}

?>