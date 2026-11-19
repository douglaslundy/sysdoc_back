# 04 — Exemplos de consumo

Exemplos completos para os fluxos mais comuns. Substitua `BASE` por `https://dlsistemas.com.br/api` (produção) ou `http://localhost:8000/api` (local). Os nomes de campos vêm das regras reais de validação (ver [referência](referencia/)).

## 1. cURL

```bash
BASE=https://dlsistemas.com.br/api

# Login → guarda o token
TOKEN=$(curl -s -X POST "$BASE/login" \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"cpf":"12345678909","password":"sua-senha"}' | python -c "import sys,json; print(json.load(sys.stdin)['token'])")

# Quem sou eu e o que posso acessar
curl -s "$BASE/auth/my-permissions" -H "Accept: application/json" -H "Authorization: Bearer $TOKEN"
curl -s "$BASE/auth/secretarias"    -H "Accept: application/json" -H "Authorization: Bearer $TOKEN"

# Listar cidadãos com busca e paginação, na secretaria 1
curl -s "$BASE/clients?search=maria&per_page=20" \
  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" -H "X-Secretaria-Id: 1"

# Criar uma ordem de serviço (JSON)
curl -s -X POST "$BASE/ordens-servico" \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" -H "X-Secretaria-Id: 1" \
  -d '{"secretaria_id":1,"tipo_servico_id":3,"local":"Praça central","descricao":"Troca de lâmpada queimada","prioridade":"normal"}'

# Criar uma OS com fotos (multipart; até 6 fotos, 8 MB cada)
curl -s -X POST "$BASE/ordens-servico" \
  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" \
  -F secretaria_id=1 -F tipo_servico_id=3 -F local="Praça central" -F descricao="Poste caído" \
  -F "fotos[]=@foto1.jpg" -F "fotos[]=@foto2.jpg"

# Baixar o PDF de uma OS
curl -s "$BASE/ordens-servico/42/pdf" -H "Authorization: Bearer $TOKEN" -o os-42.pdf

# Logout (revoga todos os tokens do usuário)
curl -s -X POST "$BASE/logout" -H "Accept: application/json" -H "Authorization: Bearer $TOKEN"
```

> `prioridade` aceita `baixa`, `normal`, `alta` ou `urgente`. Os valores de outros campos de lista fechada estão no código do endpoint (ver `Rule::in(...)` na referência); confirme-os antes de enviar.

## 2. JavaScript (fetch) — cliente reutilizável

```js
const BASE = 'https://dlsistemas.com.br/api';

class SysdocApi {
  constructor() { this.token = null; this.secretaria = null; }

  async login(cpf, password) {
    const res = await fetch(`${BASE}/login`, {
      method: 'POST',
      headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
      body: JSON.stringify({ cpf, password }),
    });
    if (!res.ok) throw await this.#erro(res);
    const data = await res.json();
    this.token = data.token;
    return data.user;
  }

  async request(path, { method = 'GET', body, query } = {}) {
    const url = new URL(`${BASE}${path}`);
    Object.entries(query || {}).forEach(([k, v]) => v != null && url.searchParams.set(k, v));

    const headers = { Accept: 'application/json', Authorization: `Bearer ${this.token}` };
    if (this.secretaria) headers['X-Secretaria-Id'] = String(this.secretaria);
    const init = { method, headers };
    if (body instanceof FormData) init.body = body;                 // multipart: não defina Content-Type
    else if (body) { headers['Content-Type'] = 'application/json'; init.body = JSON.stringify(body); }

    const res = await fetch(url, init);
    if (res.status === 401) throw new Error('Sessão expirada: refaça o login.');
    if (res.status === 429) {
      const espera = Number(res.headers.get('Retry-After') || 1);
      await new Promise((r) => setTimeout(r, espera * 1000));       // backoff simples
      return this.request(path, { method, body, query });
    }
    if (!res.ok) throw await this.#erro(res);
    return res.status === 204 ? null : res.json();
  }

  async #erro(res) {
    const corpo = await res.json().catch(() => ({}));
    const erro = new Error(corpo.message || corpo.error || `HTTP ${res.status}`);
    erro.status = res.status;
    erro.errors = corpo.errors;      // 422: { campo: ['mensagem'] }
    return erro;
  }
}

// Uso
const api = new SysdocApi();
await api.login('12345678909', 'sua-senha');
const { secretarias } = await api.request('/auth/secretarias');
api.secretaria = secretarias[0].id;
const clientes = await api.request('/clients', { query: { search: 'maria', per_page: 20 } });
```

### Baixar PDF no navegador

```js
const res = await fetch(`${BASE}/ordens-servico/42/pdf`, {
  headers: { Authorization: `Bearer ${token}`, Accept: 'application/pdf' },
});
const blob = await res.blob();
window.open(URL.createObjectURL(blob));     // ou crie um <a download> e clique
```

### Percorrer todas as páginas

```js
async function todos(api, path, query = {}) {
  const itens = [];
  for (let page = 1; ; page++) {
    const r = await api.request(path, { query: { ...query, page, per_page: 100 } });
    const lista = Array.isArray(r) ? r : r.data;      // alguns endpoints devolvem lista simples
    itens.push(...lista);
    if (Array.isArray(r) || page >= r.last_page) break;
  }
  return itens;
}
```

## 3. Python (requests)

```python
import requests

BASE = "https://dlsistemas.com.br/api"
s = requests.Session()
s.headers.update({"Accept": "application/json"})

r = s.post(f"{BASE}/login", json={"cpf": "12345678909", "password": "sua-senha"})
r.raise_for_status()
s.headers["Authorization"] = f"Bearer {r.json()['token']}"
s.headers["X-Secretaria-Id"] = "1"

# Listar
r = s.get(f"{BASE}/clients", params={"search": "maria", "per_page": 20})
r.raise_for_status()
print(r.json())

# Criar com fotos (multipart: não defina Content-Type, o requests monta o boundary)
with open("foto1.jpg", "rb") as f:
    r = s.post(
        f"{BASE}/ordens-servico",
        data={"secretaria_id": 1, "tipo_servico_id": 3, "local": "Praça central", "descricao": "Poste caído"},
        files=[("fotos[]", ("foto1.jpg", f, "image/jpeg"))],
    )
if r.status_code == 422:
    print(r.json()["errors"])          # { campo: [mensagens] }

# PDF
pdf = s.get(f"{BASE}/ordens-servico/42/pdf")
open("os-42.pdf", "wb").write(pdf.content)
```

## 4. PHP (cURL puro)

```php
<?php
const BASE = 'https://dlsistemas.com.br/api';

function chamar(string $metodo, string $caminho, ?array $corpo = null, ?string $token = null, array $extra = []): array {
    $ch = curl_init(BASE . $caminho);
    $h = ['Accept: application/json'];
    if ($token) $h[] = "Authorization: Bearer $token";
    if ($corpo !== null) { $h[] = 'Content-Type: application/json'; curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($corpo)); }
    curl_setopt_array($ch, [CURLOPT_CUSTOMREQUEST => $metodo, CURLOPT_HTTPHEADER => array_merge($h, $extra), CURLOPT_RETURNTRANSFER => true]);
    $resp = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$status, json_decode($resp, true)];
}

[$st, $dados] = chamar('POST', '/login', ['cpf' => '12345678909', 'password' => 'sua-senha']);
$token = $dados['token'];

[$st, $clientes] = chamar('GET', '/clients?search=maria&per_page=20', null, $token, ['X-Secretaria-Id: 1']);
```

## 5. Endpoints públicos (sem token)

```bash
# Consulta pública de resultado de exame
curl -s -X POST "$BASE/consulta-exame" -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"protocolo":"<protocolo-do-laudo>","senha":"<senha-do-laudo>"}'

# Painel de transparência de medicamentos (data opcional)
curl -s "$BASE/public/pharmacy/medicines/panel?date=2026-11-19" -H "Accept: application/json"

# Motivos disponíveis para denúncia/petição pública
curl -s "$BASE/public/petition/reasons" -H "Accept: application/json"
```

Respeite os limites por IP (ver [Convenções](02-convencoes.md#limites-de-requisições)).

## 6. Tratamento de erros — receita

```text
401  → token expirado/ausente: refaça o login e repita a chamada uma vez.
403  → perfil/secretaria sem permissão: não repita; corrija a configuração (ver Permissões).
404  → registro inexistente ou fora do escopo da secretaria ativa.
422  → leia errors.<campo> e mostre ao usuário; não repita igual.
429  → espere Retry-After e tente de novo (backoff exponencial).
5xx  → tente de novo com atraso; se persistir, avise o suporte (o erro já foi registrado no servidor).
```

## 7. Importar no Postman, Insomnia ou Swagger UI

Use o arquivo [`openapi.json`](openapi.json): **Import → File**. Crie uma variável `token` e configure a autenticação *Bearer* com ela; os endpoints públicos já estão marcados sem autenticação.
