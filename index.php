<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Amauri Dev | YubiKey Auth</title>
    <style>
        body { background: #121212; color: #fff; font-family: 'Segoe UI', sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .box { background: #1e1e1e; padding: 30px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); width: 350px; text-align: center; border: 1px solid #333; }
        h2 { color: #00ff88; margin-bottom: 20px; }
        input { width: 100%; padding: 12px; margin: 10px 0; background: #2a2a2a; border: 1px solid #444; border-radius: 6px; color: #fff; box-sizing: border-box; }
        button { width: 100%; padding: 12px; margin-top: 10px; cursor: pointer; border: none; border-radius: 6px; font-weight: bold; transition: 0.3s; }
        .btn-reg { background: #333; color: #ccc; }
        .btn-login { background: #00ff88; color: #000; }
        button:hover { opacity: 0.8; }
        #msg { margin-top: 15px; font-size: 14px; color: #aaa; }
    </style>
</head>
<body>

<div class="box">
    <h2>YubiKey Access</h2>
    <input type="text" id="user" placeholder="Usuário">
    <button class="btn-login" onclick="auth('login')">ENTRAR COM YUBIKEY</button>
    <button class="btn-reg" onclick="auth('register')">Cadastrar Nova Chave</button>
    <div id="msg">Aguardando comando...</div>
</div>

<script>
// Funções auxiliares para converter Buffer para Base64 (O que o PHP entende)
const bufferEncode = (value) => btoa(String.fromCharCode.apply(null, new Uint8Array(value))).replace(/\+/g, "-").replace(/\//g, "_").replace(/=/g, "");
const bufferDecode = (value) => Uint8Array.from(atob(value.replace(/-/g, "+").replace(/_/g, "/")), c => c.charCodeAt(0));

async function auth(type) {
    const user = document.getElementById('user').value;
    const msg = document.getElementById('msg');
    if(!user) return msg.innerText = "Digite o usuário!";

    msg.innerText = "Comunicando com servidor...";

    // 1. Pega o desafio (Challenge) do PHP
    const res = await fetch(`process.php?action=${type}_begin&user=${user}`);
    const options = await res.json();

    // Ajusta os IDs de binário para Buffer
    if(type === 'login') {
        options.allowCredentials.forEach(c => c.id = bufferDecode(c.id));
    }
    options.challenge = bufferDecode(options.challenge);
    if(options.user) options.user.id = bufferDecode(options.user.id);

    try {
        msg.innerText = "Toque na sua YubiKey agora!";
        const credential = await (type === 'register' ? navigator.credentials.create({publicKey: options}) : navigator.credentials.get({publicKey: options}));

        // 2. Envia a resposta da YubiKey para o PHP validar
        const body = {
            id: credential.id,
            rawId: bufferEncode(credential.rawId),
            type: credential.type,
            response: {
                attestationObject: credential.response.attestationObject ? bufferEncode(credential.response.attestationObject) : null,
                clientDataJSON: bufferEncode(credential.response.clientDataJSON),
                authenticatorData: credential.response.authenticatorData ? bufferEncode(credential.response.authenticatorData) : null,
                signature: credential.response.signature ? bufferEncode(credential.response.signature) : null,
            }
        };

        const verify = await fetch(`process.php?action=${type}_finish&user=${user}`, {method: 'POST', body: JSON.stringify(body)});
        const result = await verify.json();

        if(result.success) {
            msg.style.color = "#00ff88";
            msg.innerText = "Sucesso! Redirecionando...";
            setTimeout(() => window.location.href = 'index.php', 1000);
        } else {
            msg.innerText = "Erro: " + result.error;
        }
    } catch (e) {
        msg.innerText = "Cancelado ou erro de hardware.";
    }
}
</script>
</body>
</html>
