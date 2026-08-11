<?php

header('Content-Type: application/json');

echo json_encode([
    'method' => $_SERVER['REQUEST_METHOD'],
    'uri' => $_SERVER['REQUEST_URI'],
    'post' => $_POST,
    'raw' => file_get_contents('php://input'),
    'headers' => getallheaders()
], JSON_PRETTY_PRINT);