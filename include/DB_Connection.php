<?php

class DB_Connection
{
    public function connect()
    {
        require_once 'Config.php';
        $connection = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_DATABASE);
        $connection->set_charset("utf8");
        // return database handler
        return $connection;
    }
}