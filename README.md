🛠️ Tutorial de Configuração: API Pix Itaú (mTLS)
1. Preparação do Ambiente Linux
2. <img width="734" height="408" alt="Screenshot-2026-01-16-04:27:25" src="https://github.com/user-attachments/assets/e18417a0-0d4e-43e4-97cf-acff9930a199" />

<img width="141" height="76" alt="Captura de tela de 2026-01-15 20-24-42" src="https://github.com/user-attachments/assets/d1155b15-e481-4f61-9944-51314491658a" />
<img width="926" height="600" alt="Captura de tela de 2026-01-16 04-31-04" src="https://github.com/user-attachments/assets/cf5d6a0f-1809-498a-a14c-93b3e8fbb807" />

Primeiro, instalamos as ferramentas de sistema necessárias para lidar com a YubiKey e criptografia.
Bash

sudo apt update
sudo apt install openssl yubico-piv-tool libengine-pkcs11-openssl python3-venv python3-pip

2. Criação do Ambiente Virtual (VENV)

Para não "sujar" o Python do sistema, criamos o ambiente isolado que você mencionou:
Bash

# Criar a pasta do projeto
mkdir projeto-pix && cd projeto-pix

# Criar o ambiente virtual
python3 -m venv pix-itau

# Ativar o ambiente (Sempre faça isso antes de programar)
source pix-itau/bin/activate
<img width="548" height="287" alt="Screenshot-2026-01-16-04:41:25" src="https://github.com/user-attachments/assets/0ad70a66-2104-477f-b789-d684092748a8" />

3. Instalação das Bibliotecas Python

Dentro do ambiente ativado (pix-itau), instalamos o que é necessário para as requisições HTTP seguras:
Bash

pip install requests
# O requests-pkcs11 é usado quando chamamos a YubiKey direto pelo Python
pip install requests-pkcs11

🔑 Gestão de Certificados (O Caminho do OpenSSL)

Como a YubiKey deu conflito de memória, seguimos pelo Plano B (OpenSSL), que é mais estável para o seu uso pessoal e para o ESP32.
Passo 1: Gerar a Chave Privada
Bash

openssl ecparam -name prime256v1 -genkey -noout -out chave_itau_privada.key

Passo 2: Gerar o CSR (Para enviar ao Itaú)

Este comando cria o arquivo que você vai subir no portal do banco com seu nome:
Bash

openssl req -new -key chave_itau_privada.key -out pedido_itau.csr -subj "/CN=Amauri Bueno dos Santos/O=DESENVOLVIMENTO PESSOAL/"

Passo 3: Certificado Temporário (Para testes)

Enquanto o Itaú não assina o seu, usamos este para validar o código:
Bash

openssl req -key chave_itau_privada.key -new -x509 -days 365 -out certificado_temporario.pem -subj "/CN=Amauri Bueno dos Santos/"

🐍 O Script Python Final (obter_token_itau.py)

Este é o resumo do código que você validou e que retornou o erro 401 (Sucesso de conexão!):
Python

import requests
import json

# Arquivos gerados no passo anterior
CERT = "certificado_temporario.pem" 
KEY = "chave_itau_privada.key"

URL = "https://sts.itau.com.br/api/oauth/token"

payload = {
    'grant_type': 'client_credentials',
    'client_id': '287c0549-b649-34cc-8f43-0b1072dbe8eb',
    'client_secret': 'bd6c1adf-4e3c-4078-a369-f7c378896062'
}

headers = {
    'Content-Type': 'application/x-www-form-urlencoded',
    'x-itau-flowID': "1",
    'x-itau-correlationID': "2"
}

try:
    response = requests.post(URL, headers=headers, data=payload, cert=(CERT, KEY))
    print(f"Status: {response.status_code}")
    print(response.json())
except Exception as e:
    print(f"Erro: {e}")

📋 Resumo de Arquivos Importantes
Arquivo	Função
chave_itau_privada.key	Sua Chave Mestra. Não perca e não mostre a ninguém.
pedido_itau.csr	O arquivo que você vai colar no portal do Itaú Developers.
certificado_temporario.pem	Usado para testar o código enquanto o banco não libera o oficial.
itau_oficial.pem	(Futuro) O arquivo que o banco vai te dar para baixar.
🚀 Próximos Passos (Quando o Sandbox voltar)

    Ativar o VENV: source pix-itau/bin/activate

    Enviar o CSR: Pegar o texto do pedido_itau.csr e enviar no portal.

    Substituir: Quando o Itaú te der o certificado, salve-o e aponte o script Python para ele.
# Banco Central que você conecta diretamente para monitorar qualquer conta de qualquer banco. O Banco Central criou o padrão (as regras e os nomes dos endpoints), mas cada banco roda sua própria "instância" dessa API.
 O seu ESP32 não precisa entender nada de YubiKey ou JSON complexo do Itaú. Ele só precisa de um servidor Web bem simples (WebServer) que fica ouvindo:

existe um padrão obrigatório estabelecido pelo Banco Central do Brasil chamado API Pix.

Todas as instituições financeiras (bancos e fintechs) que oferecem contas para empresas (PJ) devem seguir essa padronização. Isso permite que você consulte se um pagamento caiu de duas formas principais:
1. Como consultar o recebimento via API

Para saber se um Pix foi pago, você geralmente usa dois caminhos técnicos:

    Webhook (Recomendado): O banco envia uma notificação automática para o seu servidor no exato segundo em que o dinheiro cai na conta. É a forma mais eficiente e rápida.

    Endpoint de Consulta (GET /pix): Você faz uma chamada para a API do banco perguntando sobre um pagamento específico (usando o txid ou o e2eid).

        Endpoint comum: GET /pix/{e2eid} ou GET /pix?inicio=...&fim=...

2. Principais Bancos que oferecem a API

A maioria dos grandes bancos e bancos digitais possui portais exclusivos para desenvolvedores onde você pode gerar as credenciais (Client ID, Secret e Certificado TLS):
Banco	Portal do Desenvolvedor
Itaú	Itaú Developers
Banco do Brasil	Developers BB
Santander	Santander Developers
Inter	Inter Empresas (API Pix)
Efí Bank (Gerencianet)	Efí Pay
Nubank	Nubank For Business
3. Requisitos para usar

Para implementar essa consulta, você precisará de:

    Conta PJ: A maioria dos bancos só libera a API para contas jurídicas.

    Certificado Digital (mTLS): O Banco Central exige um certificado padrão ICP-Brasil (ou o fornecido pelo banco) para autenticar a conexão.

    Escopos de leitura: Na hora de configurar a API, você deve habilitar o escopo pix.read para poder consultar os recebimentos.

https://devportal.itau.com.br/baas/#/dashboard/b2b-operational

não basta a YubiKey (que faz o papel do certificado mTLS). Você precisa enviar o Token de Acesso (Bearer Token) junto na requisição.

O fluxo que você postou nos JSONs mostra exatamente as duas partes do processo. Vou organizar o que está acontecendo ali para o seu script Python:
1. A Identificação (Quem é você)

Quando você faz o session.get usando a YubiKey, o banco valida o Certificado Digital. Mas o banco também exige o Authorization: Bearer seu_token.

    Como conseguir o token: Antes de consultar o Pix, seu script precisa fazer um POST para o endpoint de autenticação do Itaú (/as/token.oauth2), também usando a YubiKey, enviando seu client_id e client_secret. O banco te devolve um "token" que vale por 60 minutos.

    Se receber /aguardando: Mostra o QR Code (que você gerou com o Base64).

    Se receber /aprovado: Toca o buzzer e mostra o "OK" na tela.
Observações Técnicas Importantes

    URL de Sandbox vs Produção: Como você está usando as credenciais de Sandbox, certifique-se de usar o Path de Sandbox que você listou no seu script até estar pronto para ir para produção.

    O "id=0" no ESP32: No seu código do ESP32, certifique-se de que a rota /view_seed está preparada para receber essa requisição GET e mudar o que é exibido na tela (ou acionar o buzzer).

    Segurança da YubiKey: Para esse código funcionar, sua YubiKey precisa estar com o certificado do Itaú importado no slot 9a (Padrão PIV) e o PIN da chave pode ser solicitado pelo sistema operacional na primeira conexão.

Próximo Passo

Você já conseguiu carregar o certificado do Itaú para dentro da sua YubiKey usando o yubico-piv-tool ou precisa de ajuda com os comandos para "esconder" o certificado dentro dela?

Instalar as dependências: No terminal do seu servidor/PC, rode:

pip install requests requests-pkcs11

Verificar a YubiKey: Certifique-se de que o certificado do Itaú está no slot de autenticação da chave. O comando pkcs11-tool -O deve listar o seu certificado.

Configurar o ESP32: Certifique-se de que o código do ESP32 tem uma rota para /view_seed que aceita o parâmetro id=0. Se o ESP32 for apenas mostrar a tela, ele não precisa de segurança mTLS, facilitando o seu trabalho.
