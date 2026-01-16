<?php
require_once 'db.php';
session_start();
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$user = $_GET['user'] ?? '';

// Função para limpar o Base64 que vem do JS
function decode_base64($data) {
    return base64_decode(str_replace(['-', '_'], ['+', '/'], $data));
}

if ($action == 'register_begin') {
    $challenge = random_bytes(32);
    $_SESSION['challenge'] = bin2hex($challenge);
    
    echo json_encode([
        'challenge' => base64_encode($challenge),
        'rp' => ['name' => 'Amauri Dev', 'id' => 'localhost'],
        'user' => [
            'id' => base64_encode($user), 
            'name' => $user, 
            'displayName' => $user
        ],
        'pubKeyCredParams' => [['type' => 'public-key', 'alg' => -7]],
        'timeout' => 60000,
        'attestation' => 'none'
    ]);
}

if ($action == 'register_finish') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Salvamos o ID da credencial e o objeto de atestação (chave pública bruta)
    $stmt = $conn->prepare("INSERT INTO usuarios (username, credential_id, public_key) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $user, $data['id'], $data['response']['attestationObject']);
    
    if($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erro ao salvar no banco']);
    }
}

if ($action == 'login_begin') {
    $stmt = $conn->prepare("SELECT credential_id FROM usuarios WHERE username = ?");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();

    if(!$res) {
        echo json_encode(['error' => 'Usuário não cadastrado']);
        exit;
    }

    $challenge = random_bytes(32);
    $_SESSION['challenge'] = bin2hex($challenge);

    echo json_encode([
        'challenge' => base64_encode($challenge),
        'allowCredentials' => [[
            'type' => 'public-key', 
            'id' => $res['credential_id'] // Já está em formato compatível
        ]],
        'timeout' => 60000
    ]);
}

if ($action == 'login_finish') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Aqui a mágica acontece: verificamos se o usuário existe
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE username = ? AND credential_id = ?");
    $stmt->bind_param("ss", $user, $data['id']);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0) {
        $_SESSION['usuario_logado'] = true;
        $_SESSION['usuario_nome'] = $user;
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Chave não reconhecida']);
    }
}
