import requests
from requests_pkcs11 import Session
import time

# --- Configurações ---
LIB_PKCS11 = '/usr/lib/x86_64-linux-gnu/opensc-pkcs11.so' # Caminho da YubiKey
CLIENT_ID = ''
CLIENT_SECRET = ''
TXID = '791f1' # O ID 
URL_ESP32 = 'http://192.168.100.49/view_seed?id=0'

def obter_token(session):
    # O Itaú exige autenticação para te dar o Bearer Token
    url_auth = "https://sts.itau.com.br/as/token.oauth2" # Ajustar para URL de sandbox se necessário
    payload = {
        'grant_type': 'client_credentials',
        'client_id': CLIENT_ID,
        'client_secret': CLIENT_SECRET
    }
    response = session.post(url_auth, data=payload)
    return response.json().get('access_token')

def monitorar_pagamento():
    # Iniciando sessão com suporte a hardware (YubiKey)
    with Session(pkcs11_library=LIB_PKCS11) as session:
        print("Obtendo token de acesso...")
        token = obter_token(session)
        headers = {'Authorization': f'Bearer {token}'}
        
        print(f"Monitorando TXID: {TXID}...")
        
        while True:
            # Consulta o status da cobrança
            url_consulta = f"https://sandbox.devportal.itau.com.br/itau-ep9-gtw-pix-recebimentos-conciliacoes-v2-ext/v2/cob/{TXID}"
            res = session.get(url_consulta, headers=headers)
            
            if res.status_code == 200:
                status = res.json().get('status')
                print(f"Status atual: {status}")
                
                if status == 'CONCLUIDA':
                    print("💰 Pagamento Aprovado! Notificando ESP32...")
                    # Envia o comando para o seu ESP32
                    requests.get(URL_ESP32)
                    break
            else:
                print("Erro na consulta. Tentando novamente...")
            
            time.sleep(5) # Verifica a cada 5 segundos

if __name__ == "__main__":
    monitorar_pagamento()
