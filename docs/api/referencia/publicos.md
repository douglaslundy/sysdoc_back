# Endpoints públicos

Rotas sem login: consulta de exame, denúncia/petição, transparência da farmácia, cadastro público e painel público.

> Gerado a partir das rotas reais da aplicação (`php artisan route:list`). Autenticação: ver [Autenticação](../01-autenticacao.md). Cabeçalhos e convenções: ver [Convenções](../02-convencoes.md). O prefixo `/api` já está incluído nas URLs.

| Método | URL | Ação |
|---|---|---|
| `POST` | `/api/consulta-exame` | Consultar |
| `POST` | `/api/consulta-exame/pdf/{protocolo}` | Download Pdf |
| `POST` | `/api/public/cadastro` | Criar |
| `GET` | `/api/public/cadastro/desafio` | Desafio |
| `GET` | `/api/public/cadastro/secretarias` | Secretarias |
| `POST` | `/api/public/denuncias` | Criar |
| `POST` | `/api/public/denuncias/consulta` | Consultar |
| `GET` | `/api/public/painel-esus/estado` | Estado |
| `GET` | `/api/public/painel-esus/validar-cnes` | Validar Cnes |
| `GET` | `/api/public/painel-publico` | Detalhar |
| `GET` | `/api/public/petition/reasons` | Public Index |
| `POST` | `/api/public/petitions` | Criar |
| `POST` | `/api/public/petitions/consulta` | Consultar |
| `GET` | `/api/public/pharmacy/medicines/daily` | Daily |
| `GET` | `/api/public/pharmacy/medicines/monthly-acquisitions` | Monthly |
| `GET` | `/api/public/pharmacy/medicines/panel` | Panel |

---

## `POST /api/consulta-exame`

**Consultar** · controller `ConsultaPublicaController@consultar`

- **Acesso:** Pública (sem login)
- **Limite de requisições:** 10 por minuto
- **Corpo/validação** (`ConsultarConsultaPublicaRequest`):

| Campo | Regras |
|---|---|
| `protocolo` | `required \| string` |
| `senha` | `required \| string` |


## `POST /api/consulta-exame/pdf/{protocolo}`

**Download Pdf** · controller `ConsultaPublicaController@downloadPdf`

- **Acesso:** Pública (sem login)
- **Limite de requisições:** 10 por minuto
- **Parâmetros de rota:** `protocolo`
- **Outros parâmetros lidos pelo controller:** `senha`

## `POST /api/public/cadastro`

**Criar** · controller `CadastroPublicoController@store`

- **Acesso:** Pública (sem login)
- **Limite de requisições:** 5 por hora por IP
- **Corpo/validação** (`CadastroPublicoRequest`):

| Campo | Regras |
|---|---|
| `website` | `prohibited` |
| `name` | `required \| string \| max:50` |
| `preferred_name` | `nullable \| string \| max:50` |
| `phone` | `nullable \| <PhoneValidation>` |
| `email` | `required \| string \| email \| max:100` |
| `cpf` | `required \| string \| max:18 \| <ValidCpf>` |
| `password` | `required \| string \| min:8 \| confirmed \| <Closure>` |
| `secretaria_id` | `required \| integer \| <Exists>` |
| `cargo` | `required \| string \| max:80` |
| `observacao` | `nullable \| string \| max:200` |
| `captcha_token` | `required \| string \| max:2000` |


## `GET /api/public/cadastro/desafio`

**Desafio** · controller `CadastroPublicoController@desafio`

- **Acesso:** Pública (sem login)
- **Limite de requisições:** 30 por minuto por IP

## `GET /api/public/cadastro/secretarias`

**Secretarias** · controller `CadastroPublicoController@secretarias`

- **Acesso:** Pública (sem login)
- **Limite de requisições:** 30 por minuto por IP

## `POST /api/public/denuncias`

**Criar** · controller `DenunciaPublicaController@store`

- **Acesso:** Pública (sem login)
- **Limite de requisições:** 5 por hora por IP
- **Corpo/validação** (`StoreDenunciaRequest`):

| Campo | Regras |
|---|---|
| `motivo_id` | `nullable \| integer \| <Exists>` |
| `assunto` | `required \| string \| max:200` |
| `descricao_denuncia` | `required \| string \| max:4000` |
| `local_endereco` | `required \| string \| max:255` |
| `estabelecimento_nome_informado` | `nullable \| string \| max:200` |
| `denunciante_nome` | `nullable \| string \| max:150` |
| `denunciante_contato` | `nullable \| string \| max:150` |
| `website` | `nullable \| string` |
| `files` | `nullable \| array \| max:5` |
| `files.*` | `file \| mimes:jpg,jpeg,png,webp,pdf \| mimetypes:image/jpeg,image/png,image/webp,application/pdf \| max:10240` |


## `POST /api/public/denuncias/consulta`

**Consultar** · controller `DenunciaPublicaController@consultar`

- **Acesso:** Pública (sem login)
- **Limite de requisições:** 10 por minuto por IP
- **Corpo/validação** (`ConsultaDenunciaRequest`):

| Campo | Regras |
|---|---|
| `protocolo` | `required \| string \| max:30` |
| `senha` | `required \| string \| max:30` |


## `GET /api/public/painel-esus/estado`

**Estado** · controller `PainelEsusController@estado`

- **Acesso:** Pública (sem login)
- **Limite de requisições:** 30 por minuto
- **Corpo/validação** (`EstadoPainelEsusRequest`):

| Campo | Regras |
|---|---|
| `cnes` | `required \| string \| max:20` |


## `GET /api/public/painel-esus/validar-cnes`

**Validar Cnes** · controller `PainelEsusController@validarCnes`

- **Acesso:** Pública (sem login)
- **Limite de requisições:** 30 por minuto
- **Corpo/validação** (`ValidarCnesPainelEsusRequest`):

| Campo | Regras |
|---|---|
| `cnes` | `required \| string \| max:20` |


## `GET /api/public/painel-publico`

**Detalhar** · controller `PainelPublicoController@show`

- **Acesso:** Pública (sem login)

## `GET /api/public/petition/reasons`

**Public Index** · controller `PeticaoMotivoController@publicIndex`

- **Acesso:** Pública (sem login)

## `POST /api/public/petitions`

**Criar** · controller `DenunciaPublicaController@store`

- **Acesso:** Pública (sem login)
- **Limite de requisições:** 5 por hora por IP
- **Corpo/validação** (`StoreDenunciaRequest`):

| Campo | Regras |
|---|---|
| `motivo_id` | `nullable \| integer \| <Exists>` |
| `assunto` | `required \| string \| max:200` |
| `descricao_denuncia` | `required \| string \| max:4000` |
| `local_endereco` | `required \| string \| max:255` |
| `estabelecimento_nome_informado` | `nullable \| string \| max:200` |
| `denunciante_nome` | `nullable \| string \| max:150` |
| `denunciante_contato` | `nullable \| string \| max:150` |
| `website` | `nullable \| string` |
| `files` | `nullable \| array \| max:5` |
| `files.*` | `file \| mimes:jpg,jpeg,png,webp,pdf \| mimetypes:image/jpeg,image/png,image/webp,application/pdf \| max:10240` |


## `POST /api/public/petitions/consulta`

**Consultar** · controller `DenunciaPublicaController@consultar`

- **Acesso:** Pública (sem login)
- **Limite de requisições:** 10 por minuto por IP
- **Corpo/validação** (`ConsultaDenunciaRequest`):

| Campo | Regras |
|---|---|
| `protocolo` | `required \| string \| max:30` |
| `senha` | `required \| string \| max:30` |


## `GET /api/public/pharmacy/medicines/daily`

**Daily** · controller `MedicineTransparencyPublicController@daily`

- **Acesso:** Pública (sem login)
- **Corpo/validação** (`PublicMedicineDailyRequest`):

| Campo | Regras |
|---|---|
| `date` | `nullable \| date` |


## `GET /api/public/pharmacy/medicines/monthly-acquisitions`

**Monthly** · controller `MedicineTransparencyPublicController@monthly`

- **Acesso:** Pública (sem login)
- **Corpo/validação** (`PublicMedicineMonthlyRequest`):

| Campo | Regras |
|---|---|
| `month` | `nullable \| regex:/^\d{4}\-(0[1-9] \| 1[0-2])$/` |


## `GET /api/public/pharmacy/medicines/panel`

**Panel** · controller `MedicineTransparencyPublicController@panel`

- **Acesso:** Pública (sem login)
- **Corpo/validação** (`PublicMedicineDailyRequest`):

| Campo | Regras |
|---|---|
| `date` | `nullable \| date` |


