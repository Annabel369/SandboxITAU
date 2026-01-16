import requests
from requests_pkcs11 import PKCS11Adapter
import json

# --- DADOS DO HARDWARE ---
# O ID 01 é o seu PIV AUTH que vimos no pkcs11-tool
PKCS11_LIB = '/usr/lib/x86_64-linux-gnu/opensc-pkcs11.so'
URI_YUBI = f'pkcs11:module={PKCS11_LIB};token=Mickey;id=%01;pin-value=R7m2k9Xq'

# --- DADOS DO ITAÚ (SEUS) ---
CLIENT_ID = '287c0549-b649-34cc-8f43-0b1072dbe8eb'
CLIENT_SECRET = 'bd6c1adf-4e3c-4078-a369-f7c378896062'
URL = "https://sts.itau.com.br/api/oauth/token"

def solicitar_token():
    print("🚀 Iniciando conexão mTLS com YubiKey...")
    
    # Criamos uma sessão que entende PKCS11
    with requests.Session() as session:
        # Aqui está a mágica: o adaptador substitui o arquivo .key físico pelo hardware
        adapter = PKCS11Adapter(pkcs11_library=PKCS11_LIB)
        session.mount('https://', adapter)

        payload = {
            'grant_type': 'client_credentials',
            'client_id': CLIENT_ID,
            'client_secret': CLIENT_SECRET
        }
        
        headers = {
            'x-itau-flowID': "1",
            'x-itau-correlationID': "2",
            'Content-Type': 'application/x-www-form-urlencoded'
        }

        try:
            # Note que passamos a URI da YubiKey no lugar do caminho do arquivo
            response = session.post(
                URL, 
                data=payload, 
                headers=headers, 
                cert=URI_YUBI, # A URI substitui o par (.crt, .key)
                timeout=30
            )

            if response.status_code == 200:
                print("✅ SUCESSO!")
                return response.json()
            else:
                print(f"❌ Erro {response.status_code}")
                print(response.text)
                return None
                
        except Exception as e:
            print(f"💥 Erro na comunicação: {e}")
            return None

if __name__ == "__main__":
    resultado = solicitar_token()
    if resultado:
        print(json.dumps(resultado, indent=4))
