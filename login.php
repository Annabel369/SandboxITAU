<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Login Seguro - Amauri Dev</title>
    <style>
        body { font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background: #f4f4f9; }
        .card { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 300px; text-align: center; }
        input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #0056b3; }
        .status { margin-top: 15px; font-size: 0.9rem; color: #666; }
    </style>
</head>
<body>
    <div class="card">
        <h3>Login YubiKey</h3>
        <input type="text" id="username" placeholder="Usuário">
        <button onclick="fazerLogin()">Entrar com YubiKey</button>
        <div id="status" class="status"></div>
    </div>

    <script>
        async function fazerLogin() {
            const status = document.getElementById('status');
            const username = document.getElementById('username').value;
            
            status.innerText = "Toque na sua YubiKey...";

            // 1. Busca desafio (challenge) do servidor via AJAX
            const resp = await fetch('auth_challenge.php?user=' + username);
            const options = await resp.json();

            try {
                // 2. Aciona o hardware (YubiKey)
                const credential = await navigator.credentials.get({ publicKey: options });
                
                // 3. Envia a assinatura de volta para o PHP validar
                const finalResp = await fetch('verify_login.php', {
                    method: 'POST',
                    body: JSON.stringify(credential)
                });

                if (finalResp.ok) {
                    window.location.href = 'index.php';
                } else {
                    status.innerText = "Falha na autenticação.";
                }
            } catch (err) {
                status.innerText = "Erro: " + err.message;
            }
        }
    </script>
</body>
</html>
