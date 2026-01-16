<?php
$conn = new mysqli('localhost', 'root', '00073007');
$conn->query("CREATE DATABASE IF NOT EXISTS sistema_yubikey");
$conn->select_db("sistema_yubikey");
$conn->query("CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE,
    credential_id TEXT,
    public_key TEXT,
    sign_count INT
)");
?>
