# 03 — Permissões e secretarias

Entender este modelo evita a maior parte dos `403` e dos "por que a lista veio vazia?". A API decide **o que você pode chamar** (perfil e páginas) e **quais dados você enxerga** (secretaria ativa e escopo).

## Visão geral

```
usuário ──┬─ perfil de acesso (slug) ──► páginas liberadas ──┐
          │                                                   ├─► endpoints permitidos
          └─ secretarias vinculadas ──► páginas da secretaria ┘      (interseção)
                      │
                      └─► secretaria ativa (X-Secretaria-Id) ──► dados visíveis
```

Acesso final a uma funcionalidade = **páginas do perfil ∩ páginas liberadas na secretaria ativa**. O administrador passa por cima das duas travas.

## 1. Perfil de acesso

Cada usuário tem um perfil (`profile` no login) — por exemplo `admin`, `gestor`, `tecnico`, `user`, além de perfis criados pelo administrador. O perfil define:

- **Páginas liberadas** (por exemplo `/ordem-servico`, `/clients`, `/laboratorio/pedidos`). Cada página corresponde a uma tela do sistema e a um conjunto de endpoints.
- **Permissões operacionais** (`capabilities` em `GET /api/auth/my-permissions`): chat, ações do almoxarifado (criar, aprovar, entregar), ver viagens e relatório/histórico do cidadão, encaminhar ordens de serviço a outra secretaria ou técnico, regras de protocolo (tipos, status e visibilidade que o perfil pode cadastrar) e outras.
- **Permissões por especialidade da fila** (ver, editar, inserir paciente), definidas por usuário.

### Como a API aplica as páginas

Existe uma tabela central de regras (`config/route_permissions.php`) que liga **prefixos de URL a páginas**. Para cada chamada autenticada, a API procura a primeira regra que casa com a URL e o método; o usuário precisa ter **ao menos uma** das páginas da regra. Exemplos:

| Prefixo da URL | Páginas que liberam |
|---|---|
| `laboratorio` | `/laboratorio*` ou `/auditoria` |
| `attendance` | `/attendance*` |
| `almoxarifado` | `/almoxarifado*` |
| `kanban` | `/kanban` |
| `mensagens-enviadas` | somente administrador |

- `*` ao final significa "qualquer página abaixo deste caminho".
- Rotas sem regra exigem apenas login; várias têm checagem própria no controller.
- A referência de cada endpoint mostra o item **Acesso** com as páginas exigidas.

Quando a regra falha: `403 {"message":"Usuário sem permissão para esta página."}`.

### Outras travas por rota

- **Somente administrador** (`403 Acesso restrito a administradores.`): configurações sensíveis, bloqueio de IPs, auditoria etc.
- **Gates de dashboard** (`can:dashboard-...`): cada aba do dashboard tem sua permissão.
- **Somente Saúde** (`403 Recurso disponível apenas para a secretaria de Saúde.`): Monitor APS, painel e-SUS e conformidade do cidadão.
- **Equipe APS**: no Monitor APS, usuários responsáveis técnicos só veem os dados das equipes a que estão vinculados (salvo permissão para todas as equipes).
- **Chat**: só para perfis com o chat liberado (com exceção individual possível por usuário).

## 2. Secretarias

O sistema é **multissecretaria**: Saúde, Educação, Cultura, Agricultura, Meio Ambiente, Obras etc. (o administrador cadastra as secretarias). Um usuário pode estar vinculado a uma ou mais secretarias.

- Cada secretaria tem suas **páginas liberadas**: um módulo só aparece/funciona para uma secretaria se o administrador o liberou para ela.
- Os dados de módulos por secretaria (protocolos, documentos, ordens de serviço, almoxarifado, entre outros) são filtrados pela **secretaria ativa**.

### Secretaria ativa — cabeçalho `X-Secretaria-Id`

| Situação | Resultado |
|---|---|
| Sem o cabeçalho, usuário comum | Vale a **primeira** secretaria ativa dele (menor id) |
| `X-Secretaria-Id: 3` e o usuário está vinculado à 3 | Vale a secretaria 3 |
| `X-Secretaria-Id: 3` e o usuário **não** está vinculado | `403 {"message":"Secretaria não vinculada ao usuário."}` |
| Administrador, sem o cabeçalho | **Todas** as secretarias |
| Administrador, `X-Secretaria-Id: all` | **Todas** as secretarias |
| Usuário comum com `all` | `403 {"message":"Secretaria não permitida."}` |

Descubra as opções do usuário com `GET /api/auth/secretarias` (`{"secretarias":[{"id","nome"}], "pode_todas": bool}`).

Regras que dependem da secretaria:

- **Ordem de serviço:** o usuário comum só abre OS para as **próprias** secretarias. O perfil com a permissão `os_encaminhar_enabled` (e o administrador) pode abrir para qualquer secretaria e **encaminhar** uma OS a outra secretaria ou a outro técnico (`POST /api/ordens-servico/{id}/encaminhar`).
- **Técnico de OS:** é um usuário com perfil `tecnico` vinculado à secretaria; não existe cadastro separado de técnicos.
- **Listas vazias:** se a secretaria ativa não tem dados ou o módulo não foi liberado para ela, a lista volta vazia ou `403`. Confira primeiro `X-Secretaria-Id` e `GET /api/auth/my-permissions`.

## 3. Perfis especiais

| Perfil | O que muda |
|---|---|
| `admin` | Acesso total. Enxerga todas as secretarias. Único que acessa rotas "somente administrador" |
| `gestor` | Gestor de uma secretaria: lista os usuários da **sua** secretaria, aprova ou recusa pedidos de cadastro dela e atua dentro do escopo dela |
| `tecnico` | Atende ordens de serviço: vê, atende e conclui OS; precisa estar vinculado a uma secretaria |
| demais | Conforme as páginas liberadas pelo administrador |

## 4. Visibilidade de protocolos

Protocolos têm visibilidade `public` ou `private` (padrão: privado):

- **Privado:** só quem participa (criador, responsável, unidade) enxerga.
- **Público:** visível a todos os usuários, **somente leitura** (para agir é preciso ter acesso normal).
- O perfil define **quais tipos e status** o usuário pode cadastrar e **se pode mudar a visibilidade**. O administrador enxerga tudo.

## 5. Mapa de respostas de acesso

| Resposta | Provável causa | O que fazer |
|---|---|---|
| `401` | Token ausente, errado ou expirado | Refazer login (`POST /api/login`) |
| `403 ...sem permissão para esta página` | O perfil não tem a página exigida | Pedir ao administrador para liberar a página no perfil |
| `403 ...Secretaria não vinculada...` | `X-Secretaria-Id` de secretaria que o usuário não tem | Usar uma secretaria de `GET /api/auth/secretarias` |
| `403 ...restrito a administradores` | Rota somente de administrador | Usar um usuário administrador |
| `404` em um id que existe | O registro é de outra secretaria ou fora do seu escopo | Trocar a secretaria ativa ou revisar o escopo |
| Lista vazia inesperada | Secretaria ativa sem dados ou módulo não liberado nela | Conferir `X-Secretaria-Id` e `my-permissions` |

## 6. Boas práticas para integrações

- Crie um **usuário dedicado** para a integração, com um perfil que tenha **somente** as páginas necessárias (princípio do menor privilégio) e vinculado à secretaria certa.
- Fixe `X-Secretaria-Id` explicitamente em vez de depender do padrão.
- Não use um usuário administrador para integrações de leitura.
- Trate `401` renovando o token e `403` como erro de configuração de perfil (não repita em laço).
