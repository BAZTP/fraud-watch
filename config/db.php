<?php
$DB_HOST="localhost";
$DB_NAME="fraud_watch";
$DB_USER="root";
$DB_PASS="";

$mysqli = new mysqli($DB_HOST,$DB_USER,$DB_PASS,$DB_NAME);
if($mysqli->connect_error) die("DB error: ".$mysqli->connect_error);
$mysqli->set_charset("utf8mb4");
