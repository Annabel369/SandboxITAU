<?php
require_once 'db_config.php';
session_start();

// Recebe os dados do AJAX
$postData = file_get_contents('php://input');
$credential = json_decode($postData, true);

if (!$credential) {
    http_response_code(400);
    echo json_encode(["error" => "Dados inválidos"]);
    exit;
}

// Simulando a lógica de verificação WebAuthn
// Na prática, aqui você usaria uma lib para validar a assinatura (signature)
// contra a public_key guardada no banco.
$username = $_SESSION['auth_username_temp'] ?? ''; 

$stmt = $conn->prepare("SELECT id, public_key FROM usuarios WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user) {
    // SE A ASSINATURA DA YUBIKEY FOR VÁLIDA:
    // Aqui entra a função: webauthn->verify($credential, $user['public_key'])
    
    $_SESSION['usuario_logado'] = true;
    $_SESSION['usuario_id'] = $user['id'];
    $_SESSION['usuario_nome'] = $username;

    echo json_encode(["status" => "success"]);
} else {
    http_response_code(401);
    echo json_encode(["error" => "Usuário não encontrado ou chave inválida"]);
}
?>
