import requests
import time

# Agora apontamos para o TÚNEL LOCAL que o stunnel criou
URL_AUTH_LOCAL = "https://127.0.0.1:8443/as/token.oauth2"
CLIENT_ID = '287c0549-b649-34cc-8f43-0b1072dbe8eb'
CLIENT_SECRET = 'bd6c1adf-4e3c-4078-a369-f7c378896062'
URL_ESP32 = 'http://192.168.100.49/view_seed?id=0'

def obter_token():
    print("🚀 Chamando túnel mTLS (YubiKey)...")
    payload = {
        'grant_type': 'client_credentials',
        'client_id': CLIENT_ID,
        'client_secret': CLIENT_SECRET
    }
    
    try:
        # verify=False é necessário porque o certificado do túnel é local, 
        # mas a conexão entre o túnel e o Itaú é segura.
        res = requests.post(URL_AUTH_LOCAL, data=payload, verify=False, timeout=20)
        
        if res.status_code == 200:
            token = res.json().get('access_token')
            print(f"✅ TOKEN RECEBIDO: {token[:15]}...")
            return token
        else:
            print(f"❌ Erro: {res.status_code} - {res.text}")
    except Exception as e:
        print(f"❌ Erro ao falar com o túnel: {e}")

if __name__ == "__main__":
    token = obter_token()
    if token:
        # Se o token veio, o ESP32 pode ser avisado
        requests.get(URL_ESP32)