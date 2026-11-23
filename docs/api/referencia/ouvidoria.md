# Ouvidoria

Portal público para o cidadão registrar manifestações (reclamação, sugestão, elogio, denúncia, solicitação e pedido de informação) e área interna para responder, mudar o status e encaminhar.

> Autenticação: ver [Autenticação](../01-autenticacao.md). Cabeçalhos e convenções: ver [Convenções](../02-convencoes.md). O prefixo `/api` já está incluído nas URLs. Os campos e o `openapi.json` seguem as rotas reais da aplicação.

## Regras gerais

- As rotas `/api/public/ouvidoria*` não exigem login e `opcoes` e o registro só respondem (senão, 404) quando o administrador liberou ao menos uma secretaria ativa; a consulta de um protocolo existente continua sempre disponível.
- O portal só lista e só aceita secretarias com `ativa` e `ouvidoria_publica` verdadeiros (campo ajustado em `PUT /api/secretarias-admin/{id}`).
- Tipos: `reclamacao`, `sugestao`, `elogio`, `denuncia`, `solicitacao`, `informacao`.
- Status: `recebida`, `em_analise`, `encaminhada`, `respondida`, `encerrada`, `arquivada`. `encerrada` e `arquivada` só são reabertas pelo administrador.
- Prazo de resposta: 30 dias. O comando `ouvidoria:avisar-prazos` avisa o responsável 5 dias antes do vencimento.
- A área interna exige a página `/sistema/ouvidoria` no perfil. O usuário vê as manifestações da secretaria ativa (`X-Secretaria-Id`); o administrador em "todas" vê tudo. Depois de um encaminhamento a outra secretaria, a secretaria de origem continua vendo o caso em somente leitura (`pode_agir: false`).

## Portal público

| Método | URL | Ação | Limite |
|---|---|---|---|
| `GET` | `/api/public/ouvidoria/opcoes` | Secretarias liberadas, tipos, desafio (captcha) e `iniciado` (selo de abertura do formulário) | 30/min por IP |
| `POST` | `/api/public/ouvidoria` | Registrar manifestação (`multipart/form-data`) | 5/h por IP |
| `POST` | `/api/public/ouvidoria/consulta` | Consultar por protocolo e senha | 10/min por IP |

### `POST /api/public/ouvidoria`

Campos: `secretaria_id`, `tipo`, `assunto` (3 a 160), `descricao` (10 a 5000), `captcha_token`, `captcha_resposta`, `iniciado` (selo recebido em `opcoes`; envio antes de 3 s, ajustável por `OUVIDORIA_TEMPO_MINIMO`, é rejeitado), `anexos[]` (até 3 arquivos JPG, PNG ou PDF, 5 MB cada, tipo conferido pelo conteúdo) e `website` (isca: deve ficar vazio).

Identificação opcional: envie `identificar=1` junto com `nome` e ao menos um entre `email` e `phone`; `cpf` é opcional e validado. Sem `identificar`, nome, e-mail, telefone e CPF são ignorados e a manifestação é anônima.

Resposta `201`: `protocolo` (`OUV-AAAA-XXXXXXXX`), `senha` (mostrada só nesta resposta), `url_consulta`, `prazo_em`.

### `POST /api/public/ouvidoria/consulta`

Corpo: `protocolo`, `senha`. Resposta `200`: `protocolo`, `tipo`, `tipo_rotulo`, `assunto`, `status`, `created_at`, `resposta`, `respondida_em`. Protocolo inexistente, senha errada e protocolo bloqueado devolvem a mesma resposta `422` (`Protocolo ou senha inválidos.`); 5 erros bloqueiam o protocolo por 15 minutos.

## Área interna

| Método | URL | Ação |
|---|---|---|
| `GET` | `/api/ouvidoria` | Listar (filtros `status`, `tipo`, `vencidas=1`, `busca`, `secretaria_id` só para admin; 20 por página; sem dados pessoais) |
| `GET` | `/api/ouvidoria/destinatarios` | Usuários que podem receber um encaminhamento, com as secretarias em que podem recebê-lo |
| `GET` | `/api/ouvidoria/{id}` | Detalhar (cidadão, anexos, histórico, `pode_agir`) |
| `GET` | `/api/ouvidoria/{id}/anexos/{anexoId}` | Baixar anexo |
| `POST` | `/api/ouvidoria/{id}/responder` | Resposta oficial (`resposta`); uma nova resposta guarda a anterior no histórico |
| `POST` | `/api/ouvidoria/{id}/status` | Alterar status (`status`: `em_analise`, `encerrada` ou `arquivada`; `observacao`) |
| `POST` | `/api/ouvidoria/{id}/encaminhar` | Encaminhar (`user_id`, `secretaria_id`, `observacao`) |
| `POST` | `/api/ouvidoria/{id}/comentarios` | Comentário interno (`observacao`), invisível ao cidadão |
| `POST` | `/api/ouvidoria/{id}/dados-pessoais` | Revela nome, e-mail, telefone e CPF em claro e grava `OUVIDORIA_DADOS_REVELADOS` na auditoria (só a secretaria dona; `403` em somente leitura) |

O detalhe (`GET /api/ouvidoria/{id}`) devolve esses quatro campos mascarados (`dados_mascarados: true`).

Ações de escrita em manifestação em somente leitura devolvem `403`.
