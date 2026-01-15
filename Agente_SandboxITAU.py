import requests
from requests_pkcs11 import Session
import time
import sys

# --- CONFIGURAÇÕES DE AMBIENTE ---
# No Linux: '/usr/lib/x86_64-linux-gnu/opensc-pkcs11.so'
# No Windows: 'C:\\Windows\\System32\\opensc-pkcs11.dll'
LIB_PKCS11 = '/usr/lib/x86_64-linux-gnu/opensc-pkcs11.so' 

CLIENT_ID = '2XXXXXXXXXXXXXXXXXXXXXXXXXXXX'
CLIENT_SECRET = 'bXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX62'
TXID = '79XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXf1' 

# Endereço do seu hardware ESP32
URL_ESP32 = 'http://192.168.100.49/view_seed?id=0'

# URLs do Itaú (Sandbox)
URL_AUTH = "https://sts.itau.com.br/as/token.oauth2"
URL_PIX = f"https://sandbox.devportal.itau.com.br/itau-ep9-gtw-pix-recebimentos-conciliacoes-v2-ext/v2/cob/{TXID}"

def obter_token(session):
    """Solicita o Access Token usando a YubiKey para o mTLS"""
    payload = {
        'grant_type': 'client_credentials',
        'client_id': CLIENT_ID,
        'client_secret': CLIENT_SECRET
    }
    try:
        response = session.post(URL_AUTH, data=payload)
        response.raise_for_status()
        return response.json().get('access_token')
    except Exception as e:
        print(f"❌ Erro ao obter token: {e}")
        return None

def monitorar_pagamento():
    print("--- Iniciando Agente de Monitoramento Itaú + YubiKey ---")
    
    try:
        # Abre a sessão mTLS com a YubiKey
        with Session(pkcs11_library=LIB_PKCS11) as session:
            token = obter_token(session)
            if not token:
                return

            headers = {'Authorization': f'Bearer {token}'}
            print(f"✅ Autenticado. Monitorando TXID: {TXID}")

            while True:
                res = session.get(URL_PIX, headers=headers)
                
                if res.status_code == 200:
                    dados = res.json()
                    status = dados.get('status')
                    print(f"🕒 [{time.strftime('%H:%M:%S')}] Status: {status}")

                    if status == 'CONCLUIDA':
                        print("\n💰 PAGAMENTO CONFIRMADO!")
                        print(f"Enviando sinal para o ESP32: {URL_ESP32}")
                        
                        # Notifica o hardware (requisição simples sem certificado)
                        requests.get(URL_ESP32, timeout=5)
                        print("🚀 Sucesso! Encerrando monitoramento.")
                        break
                
                elif res.status_code == 401:
                    print("⚠️ Token expirado. Renovando...")
                    token = obter_token(session)
                    headers['Authorization'] = f'Bearer {token}'
                
                else:
                    print(f"⚠️ Erro na API: {res.status_code}")

                time.sleep(5) # Intervalo entre consultas

    except ImportError:
        print("❌ Erro: Instale 'requests-pkcs11' e tenha o 'opensc' instalado no sistema.")
    except Exception as e:
        print(f"❌ Erro Crítico: {e}")

if __name__ == "__main__":
    monitorar_pagamento()
