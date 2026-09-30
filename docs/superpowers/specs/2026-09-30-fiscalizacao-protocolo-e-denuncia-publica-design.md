# Fiscalização: protocolo, movimentação e denúncia pública — desenho

Data: 2026-09-30 · Tarefas do lote: T7 (protocolo + histórico + PDF interno) e T8 (denúncia pública + consulta + PDF do cidadão)
Base: `docs/lote-2026-09-29/STATE.md` · Reaproveita o `HistoryDrawer` da T3.

## 1. Objetivo
1. Toda fiscalização passa a ter um **número de protocolo** exibido na listagem e na tela da fiscalização.
2. Cada fiscalização tem um **histórico de movimentação** (painel lateral, mais recente em cima: quem, quando, o quê), igual ao do cidadão.
3. Botão que imprime **PDF do histórico** na tela interna.
4. **Página pública** para qualquer pessoa registrar uma denúncia (sem cadastro; identificação opcional). A denúncia entra em `/fiscalizacoes` como fiscalização nova **“Pendente de apuração”**.
5. Ao registrar, o denunciante recebe **protocolo + senha aleatória + endereço de consulta**. Em uma **página pública de consulta** informa protocolo e senha e vê a movimentação e o desfecho, podendo **imprimir PDF**.

## 2. Decisões do usuário (2026-09-30)
| Tema | Decisão |
|------|---------|
| Onde guardar a denúncia | **Mesma tabela `fiscalizacoes`** (`origem = denuncia`, `resultado = Pendente de apuração`); estabelecimento passa a ser opcional |
| Formato do protocolo | **`FIS-2026-000123`** (ano + id com 6 dígitos) |
| O que o denunciante vê | **Só as movimentações que o fiscal marcar como públicas**; notas internas e nomes de fiscais nunca aparecem |
| Anti-abuso | **Limite por IP + campo isca + limites de arquivo** (sem captcha externo) |

## 3. Situação atual
`fiscalizacoes` = registro de visita: `estabelecimento_id` obrigatório, `data_visita` obrigatória, `resultado` (Conforme / Não conforme / Notificação / Auto de infração), `observacoes`, soft delete; anexos no disco `private` (`fiscalizacao_attachments`). Já existe o padrão de **consulta pública por protocolo + senha com hash** (resultado de exame: `ConsultaPublicaController`) e rotas públicas no front (`pages/_app.js` `PUBLIC_ROUTES`, `src/constants/publicPaths.js`). PDFs são gerados no front com `pdfmake` (`src/reports/*`).

## 4. Modelo de dados
**`fiscalizacoes` (alterações)**
| Coluna | Tipo | Observação |
|---|---|---|
| `estabelecimento_id` | passa a **nullable** | denúncia pode citar estabelecimento não cadastrado |
| `data_visita` | passa a **nullable** | denúncia ainda não apurada não tem visita |
| `protocolo` | varchar(20), único | `FIS-AAAA-NNNNNN`, gerado a partir do `id`; migration preenche as antigas (ano de `created_at`) |
| `origem` | varchar(20), padrão `interna` | `interna` \| `denuncia` |
| `assunto` | varchar(200) null | denúncia |
| `descricao_denuncia` | text null | denúncia (`observacoes` continua sendo nota interna) |
| `local_endereco` | varchar(255) null | onde ocorre |
| `estabelecimento_nome_informado` | varchar(200) null | nome dado pelo denunciante |
| `denunciante_nome`, `denunciante_contato` | varchar null | opcionais |
| `senha_consulta_hash` | varchar null | `Hash::make`; **oculto** na API; nunca logado |

`resultado` ganha o valor **`Pendente de apuração`**.

**`fiscalizacao_movimentacoes` (nova)**: `id`, `fiscalizacao_id` (FK cascade), `user_id` (null = sistema/denunciante), `acao` (varchar 40), `descricao` (text null), `dados` (json null), `publico` (bool, padrão false), `created_at`; índice `(fiscalizacao_id, id)`.

**`fiscalizacao_attachments` (alterações)**: `origem` (`interno` \| `denunciante`, padrão `interno`); `uploaded_by` já aceita nulo.

## 5. Componentes
| Unidade | Responsabilidade |
|---|---|
| `FiscalizacaoProtocolo::for(int $id, Carbon $date): string` | monta `FIS-AAAA-NNNNNN` |
| `FiscalizacaoTimeline` (service) | `registrar(Fiscalizacao, acao, descricao, publico, ?user, dados)`; usado pelos controllers e pelo fluxo público |
| `FiscalizacaoController` (alterações) | ao criar: gera protocolo + movimentação “Fiscalização criada”; ao editar: movimentação por mudança de situação/campos; aceita `visivel_ao_denunciante` |
| `FiscalizacaoHistoricoController` | `GET /fiscalizacoes/{id}/historico` (tudo), `POST /fiscalizacoes/{id}/movimentacoes` (`descricao`, `publico`) — exigem a permissão da página `/fiscalizacoes` |
| `DenunciaPublicaController` | `POST /api/public/denuncias` (cria) e `POST /api/public/denuncias/consulta` (protocolo + senha) |
| `StoreDenunciaRequest`, `ConsultaDenunciaRequest` | validação (FormRequest) |
| Front `HistoryDrawer` (existente) | histórico interno e público |
| Front `src/reports/fiscalizacao` | PDFs (interno completo e público) com `pdfmake` |
| Front páginas públicas `/denuncia` e `/denuncia/consulta` | formulário + confirmação; consulta + timeline + PDF |

## 6. Fluxos
**Fiscalização interna:** criar → protocolo gerado → movimentação “Fiscalização criada” (interna). Editar situação/campos → movimentação interna com o de→para; o fiscal pode marcar “visível ao denunciante” (padrão marcado apenas em denúncias) e escrever uma mensagem pública. Botão “Histórico” abre o painel; botão “Imprimir PDF” gera o histórico completo (dados + movimentações internas e públicas).

**Denúncia pública:** formulário → validação → cria fiscalização (`origem=denuncia`, `resultado=Pendente de apuração`, `fiscal_id=null`) + protocolo + senha aleatória de 8 caracteres (alfabeto sem ambiguidade `ABCDEFGHJKLMNPQRSTUVWXYZ23456789`) + movimentação pública “Denúncia recebida” + anexos (`origem=denunciante`). Resposta única: `{protocolo, senha, url_consulta}` (a senha só existe em texto puro nesta resposta). Tela de sucesso mostra os três, com copiar e imprimir. `url_consulta` = URL do sistema (front) + `/denuncia/consulta?protocolo=…` (a senha nunca vai na URL).

**Consulta pública:** protocolo + senha → situação atual, dados básicos que o próprio denunciante informou, e **somente** as movimentações `publico=true` (sem nome de fiscal, sem notas internas) → botão PDF.

## 7. Segurança e abuso
- Denúncia: **máx. 5 por hora por IP** (`throttle` nomeado), campo isca (`website`) — preenchido ⇒ resposta 201 falsa sem gravar —, no máximo **5 arquivos de 10 MB**, tipos `jpg,jpeg,png,webp,pdf` com checagem de MIME real.
- Consulta: `throttle` de 10/min por IP **e** bloqueio por protocolo após 5 senhas erradas em 15 min; mensagem única “Protocolo ou senha inválidos” (não revela se o protocolo existe).
- Senha e hash **nunca** em logs, auditoria (mascaradas em `AuditService`) ou resposta da API interna (`$hidden`).
- Rotas públicas fora do `auth`, mas com `FormRequest`; arquivos no disco `private`; nenhum download público de anexos.
- Campos de texto do denunciante escapados na exibição (React) e limitados em tamanho.

## 8. Testes
- Protocolo: formato, unicidade, backfill das antigas.
- Timeline: criar/editar geram movimentações; `publico` só quando marcado; POST manual.
- Denúncia pública: cria pendente com protocolo/senha/anexos; identificação opcional; campo isca não grava; limite de 5/h; arquivo inválido rejeitado; senha nunca aparece em resposta interna/auditoria.
- Consulta: senha certa mostra só o público; senha errada/protocolo inexistente ⇒ mesma mensagem; bloqueio após 5 erros.
- Fiscalizações existentes continuam funcionando (`FiscalizacaoTest`, `FiscalizacaoAttachmentTest`, `FiscalizacaoAuditTest`).
- Front: colunas de protocolo/origem, drawer, formulário público (campos opcionais, sucesso, erro), consulta e geração do PDF (função pura testada).

## 9. Fora de escopo (YAGNI)
Notificação por e-mail/WhatsApp ao denunciante; geolocalização/mapa; captcha externo; edição da denúncia pelo cidadão; dashboard de KPIs (é a tarefa T9, separada).

## 10. Divisão em entregas
1. **Plano A (T7):** migration + protocolo + timeline + endpoints internos + tela (colunas, drawer, formulário de movimentação) + PDF interno.
2. **Plano B (T8):** campos/tabela de denúncia + endpoints públicos + páginas públicas + PDF do cidadão.
