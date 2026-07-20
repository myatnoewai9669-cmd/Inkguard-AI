<?php
// Dynamic database settings (fallbacks to local XAMPP)
$host = getenv('MYSQLHOST') ?: getenv('DB_HOST') ?: "localhost";
$user = getenv('MYSQLUSER') ?: getenv('DB_USER') ?: "root";
$pass = getenv('MYSQLPASSWORD') !== false ? getenv('MYSQLPASSWORD') : (getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : "");
$db   = getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: "inkguard_db";
$port = getenv('MYSQLPORT') ?: getenv('DB_PORT') ?: "3306";

$conn = mysqli_connect($host, $user, $pass, $db, intval($port));

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

/* Hugging Face */
$HF_TOKEN = getenv('HF_TOKEN') ?: "YOUR_NEW_TOKEN_HERE";
?>
