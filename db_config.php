<?php
$host = 'localhost';
$user = 'root';
$pass = '00073007';
$dbname = 'sistema_yubikey';

// 1. Conecta sem banco para verificar se ele existe
$conn = new mysqli($host, $user, $pass);

if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// 2. Cria o banco de dados se não existir
$conn->query("CREATE DATABASE IF NOT EXISTS $dbname");
$conn->select_db($dbname);

// 3. Cria a tabela se não existir
$table_sql = "CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE,
    public_key TEXT, 
    credential_id TEXT,
    sign_count INT DEFAULT 0
)";

if (!$conn->query($table_sql)) {
    die("Erro ao criar tabela: " . $conn->error);
}
?>
