<?php

$host = "localhost";
$user = "ucuvhcnpfcavh";
$password = "Concordi26-project";
$database = "dby41clch96chw";

$conn = new mysqli($host, $user, $password, $database);

if($conn->connect_error) {
    die("Connection failed: ". $conn->connect_error);
}

?>
