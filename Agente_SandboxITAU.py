import PyKCS11
import requests
import time
from urllib3.util import ssl_

# --- CONFIGURAÇÕES ---
LIB_PKCS11 = '/usr/lib/x86_64-linux-gnu/opensc-pkcs11.so'
TOKEN_LABEL = 'Mickey'  # O nome que apareceu no seu pkcs11-tool
CLIENT_ID = '287c0549-b649-34cc-8f43-0b1072dbe8eb'
CLIENT_SECRET = 'bd6c1adf-4e3c-4078-a369-f7c378896062'
TXID = '7978c0c97ea847e78e8849634473c1f1'
URL_ESP32 = 'http://192.168.100.49/view_seed?id=0'

# Endpoints Sandbox
URL_AUTH = "https://sts.itau.com.br/as/token.oauth2"
URL_PIX = f"https://sandbox.devportal.itau.com.br/itau-ep9-gtw-pix-recebimentos-conciliacoes-v2-ext/v2/cob/{TXID}"

def obter_token():
    print("🔑 Autenticando com Client ID e Certificado...")
    headers = {
        'Content-Type': 'application/x-www-form-urlencoded',
        'Accept': 'application/json'
    }
    payload = {
        'grant_type': 'client_credentials',
        'client_id': CLIENT_ID,
        'client_secret': CLIENT_SECRET
    }
    
    try:
        # Passamos o certificado extraído da YubiKey
        res = requests.post(URL_AUTH, data=payload, headers=headers, cert='itau_sandbox.pem')
        
        if res.status_code == 200:
            return res.json().get('access_token')
        else:
            print(f"❌ Erro {res.status_code}: {res.text}")
            return None
    except Exception as e:
        print(f"❌ Erro de SSL: {e}")
        return None

def monitorar():
    print(f"🚀 Iniciando monitoramento do Pix ({TXID})...")
    token = obter_token()
    if not token:
        print("❌ Falha ao obter Token.")
        return

    headers = {'Authorization': f'Bearer {token}'}
    
    # IMPORTANTE: Para usar a YubiKey no requests com PyKCS11 puro, 
    # o jeito mais fácil no Sandbox é passar o certificado que você baixou do Itaú.
    # Se você quer que o certificado venha REALMENTE da YubiKey, o Python precisa
    # acessar o slot PIV.
    
    while True:
        try:
            # Consulta a API do Itaú
            res = requests.get(URL_PIX, headers=headers)
            
            if res.status_code == 200:
                status = res.json().get('status')
                print(f"🕒 [{time.strftime('%H:%M:%S')}] Status: {status}")
                
                if status == 'CONCLUIDA':
                    print("💰 PAGO! Notificando ESP32...")
                    requests.get(URL_ESP32)
                    break
            elif res.status_code == 401:
                token = obter_token()
                headers['Authorization'] = f'Bearer {token}'
            
            time.sleep(5)
        except Exception as e:
            print(f"Erro: {e}")
            time.sleep(10)

if __name__ == "__main__":
    monitorar()
