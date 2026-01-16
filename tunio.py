import requests

# Desativamos o SSL do lado do Python pois o túnel já é seguro
res = requests.post(
    "http://127.0.0.1:8443/as/token.oauth2", # Nota: mudei para http se o stunnel estiver convertendo
    data={
        'grant_type': 'client_credentials',
        'client_id': '287c0549-b649-34cc-8f43-0b1072dbe8eb',
        'client_secret': 'bd6c1adf-4e3c-4078-a369-f7c378896062'
    },
    timeout=20
)
print(res.json())
