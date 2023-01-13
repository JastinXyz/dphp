<?php

$conn = mysqli_connect('localhost', 'root', '');

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
} else {
    echo "Connected successfully";
}

$db = mysqli_select_db($conn, "db_anjani");

if ($db) {
    echo "\select database berhasil.\n";
} else {
    die("select failed");
}