import requests
import json

# --- CONFIGURAÇÕES DOS ARQUIVOS QUE VOCÊ GEROU ---
CERT_PUBLICO = "certificado_temporario.pem" 
CHAVE_PRIVADA = "chave_itau_privada.key"
CLIENT_ID = '287c0549-b649-34cc-8f43-0b1072dbe8eb'
CLIENT_SECRET = 'bd6c1adf-4e3c-4078-a369-f7c378896062'

URL_AUTH = "https://sts.itau.com.br/api/oauth/token"

def obter_token():
    print(f"🚀 Tentando conexão mTLS para: {URL_AUTH}")
    
    headers = {
        'Content-Type': 'application/x-www-form-urlencoded',
        'x-itau-flowID': "1",
        'x-itau-correlationID': "2"
    }
    
    payload = {
        'grant_type': 'client_credentials',
        'client_id': CLIENT_ID,
        'client_secret': CLIENT_SECRET
    }

    try:
        # Aqui o Python usa diretamente o par de arquivos para o handshake
        response = requests.post(
            URL_AUTH, 
            headers=headers, 
            data=payload, 
            cert=(CERT_PUBLICO, CHAVE_PRIVADA), # O pulo do gato está aqui
            verify=True
        )

        print(f"Status: {response.status_code}")
        print("Resposta do Banco:")
        print(json.dumps(response.json(), indent=4))

    except Exception as e:
        print(f"❌ Erro na conexão: {e}")

if __name__ == "__main__":
    obter_token()