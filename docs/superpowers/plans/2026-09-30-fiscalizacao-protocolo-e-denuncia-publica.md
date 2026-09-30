# Fiscalização: protocolo, movimentação e denúncia pública — plano de implementação

> **Para quem executa:** `superpowers:executing-plans` (modo nativo, já escolhido pelo usuário). Passos com checkbox; TDD em cada tarefa; commit por tarefa.

**Goal:** dar número de protocolo e histórico de movimentação às fiscalizações (com PDF) e abrir uma denúncia pública com consulta por protocolo + senha (com PDF).

**Architecture:** a denúncia é uma linha de `fiscalizacoes` (`origem=denuncia`, `resultado=Pendente de apuração`). Toda mudança gera linha em `fiscalizacao_movimentacoes` (flag `publico`). Endpoints internos exigem a página `/fiscalizacoes`; endpoints públicos ficam sob `throttle` próprio, com campo isca e consulta por hash de senha (padrão do `ConsultaPublicaController`). PDFs no front (`pdfmake`), com funções puras testáveis.

**Tech Stack:** Laravel 10 / MySQL / PHPUnit; Next 12 / MUI v5 / pdfmake / Jest.

**Spec:** `docs/superpowers/specs/2026-09-30-fiscalizacao-protocolo-e-denuncia-publica-design.md`

## Global Constraints
- Backend em `sysdoc_back/`, frontend em `sysdoc_front/`; validação só em `FormRequest`; textos em português com acento; arquivos com acento criados pela ferramenta de escrita.
- Protocolo: `FIS-AAAA-NNNNNN` (ano de `created_at` + `id` com 6 dígitos), único.
- Senha da denúncia: 8 caracteres do alfabeto `ABCDEFGHJKLMNPQRSTUVWXYZ23456789`; guardada só como hash; devolvida uma única vez; nunca em log/auditoria/resposta interna.
- Denunciante só vê movimentações `publico = true`; nunca nome de fiscal nem notas internas.
- Denúncia: 5 por hora por IP; consulta: 10/min por IP e bloqueio de 15 min após 5 senhas erradas por protocolo; até 5 arquivos de 10 MB (`jpg,jpeg,png,webp,pdf`).
- Cada tarefa fecha com: teste focado → suítes que dependem dos arquivos alterados (`--filter 'Fiscaliza|Audit|Estabelecimento'`) → suíte completa antes do commit da última tarefa de cada parte.

## Review Focus
1. Denúncia sem nenhum campo de identificação e sem estabelecimento → cria normalmente.
2. Campo isca preenchido → resposta de sucesso falsa, nada gravado.
3. Protocolo existente com senha errada e protocolo inexistente → resposta idêntica (não revela existência).
4. Fiscalização antiga (sem protocolo) após a migration → protocolo preenchido e único.
5. Movimentação interna nunca aparece na consulta pública; `senha_consulta_hash` nunca aparece em JSON.

## Arquivos
| Arquivo | Ação |
|---|---|
| `database/migrations/2026_09_30_100000_add_protocolo_e_denuncia_to_fiscalizacoes.php` | criar (colunas, backfill, nullable) |
| `database/migrations/2026_09_30_100001_create_fiscalizacao_movimentacoes_table.php` | criar |
| `database/migrations/2026_09_30_100002_add_origem_to_fiscalizacao_attachments.php` | criar |
| `app/Models/Fiscalizacao.php`, `FiscalizacaoAttachment.php` | modificar |
| `app/Models/FiscalizacaoMovimentacao.php` | criar |
| `app/Services/Fiscalizacao/FiscalizacaoProtocolo.php`, `FiscalizacaoTimeline.php` | criar |
| `app/Http/Controllers/FiscalizacaoController.php`, `Resources/FiscalizacaoResource.php`, `Requests/{Store,Update}FiscalizacaoRequest.php` | modificar |
| `app/Http/Controllers/FiscalizacaoHistoricoController.php` | criar |
| `app/Http/Controllers/DenunciaPublicaController.php`, `Requests/StoreDenunciaRequest.php`, `ConsultaDenunciaRequest.php` | criar |
| `app/Providers/RouteServiceProvider.php`, `routes/api.php`, `app/Services/AuditService.php` | modificar |
| front `src/components/fiscalizacoes/index.js`, `src/components/modal/fiscalizacao/index.js` | modificar |
| front `src/components/fiscalizacoes/FiscalizacaoHistorico.js`, `src/reports/fiscalizacao/index.js` | criar |
| front `pages/denuncia.js`, `pages/denuncia/consulta.js`, `pages/_app.js`, `src/constants/publicPaths.js` | criar/modificar |

---

# Parte A — T7: protocolo, histórico e PDF interno

### Task A1: banco, models e protocolo

**Files:** criar as 3 migrations, `FiscalizacaoMovimentacao.php`, `FiscalizacaoProtocolo.php`, `tests/Feature/FiscalizacaoProtocoloTest.php`; modificar `Fiscalizacao.php`, `FiscalizacaoAttachment.php`, `FiscalizacaoResource.php`, `UpdateFiscalizacaoRequest.php`.

**Interfaces — Produces:** `FiscalizacaoProtocolo::for(int $id, \DateTimeInterface $createdAt): string`; `Fiscalizacao::$hidden = ['senha_consulta_hash']`; `Fiscalizacao::movimentacoes()`; resource com `protocolo`, `origem`, `assunto`, `local_endereco`, `estabelecimento_nome_informado`, `denunciante_*` (só quando origem = denuncia).

- [ ] **Step 1 (RED):** `FiscalizacaoProtocoloTest`:
  - `for(123, 2026-05-01)` → `FIS-2026-000123`;
  - criar fiscalização via `POST /api/fiscalizacoes` (admin) → resposta traz `protocolo` no formato, `origem = interna`;
  - migration de backfill: inserir fiscalização sem `protocolo` (via `DB::table`), executar `up()` → `protocolo` = `FIS-{ano created_at}-{id 6d}`; rodar duas vezes não altera;
  - `estabelecimento_id` e `data_visita` aceitam `NULL` no banco (`DB::table('fiscalizacoes')->insert` sem eles funciona);
  - `Fiscalizacao::first()->toArray()` não contém `senha_consulta_hash`.
- [ ] **Step 2:** `./vendor/bin/phpunit tests/Feature/FiscalizacaoProtocoloTest.php` → FAIL.
- [ ] **Step 3: migrations.** `..._100000`: `ALTER TABLE fiscalizacoes MODIFY estabelecimento_id BIGINT UNSIGNED NULL, MODIFY data_visita DATE NULL` (via `DB::statement`, só `mysql`); `Schema::table` adiciona `protocolo` (string 20, unique, nullable), `origem` (string 20, default `interna`, index), `assunto` (200), `descricao_denuncia` (text), `local_endereco` (255), `estabelecimento_nome_informado` (200), `denunciante_nome` (150), `denunciante_contato` (150), `senha_consulta_hash` (255) — todos nullable; em seguida preenche `protocolo` das antigas em lotes: `UPDATE fiscalizacoes SET protocolo = CONCAT('FIS-', YEAR(created_at), '-', LPAD(id, 6, '0')) WHERE protocolo IS NULL`. `..._100001`: cria `fiscalizacao_movimentacoes` (`id`, `fiscalizacao_id` FK cascade, `user_id` nullable FK nullOnDelete, `acao` string 40, `descricao` text null, `dados` json null, `publico` boolean default false, `created_at` useCurrent; index `(fiscalizacao_id, id)`). `..._100002`: `origem` string 20 default `interno` em `fiscalizacao_attachments`.
- [ ] **Step 4: código.**
```php
final class FiscalizacaoProtocolo
{
    public static function for(int $id, \DateTimeInterface $createdAt): string
    {
        return sprintf('FIS-%s-%06d', $createdAt->format('Y'), $id);
    }
}
```
`Fiscalizacao`: novos campos no `$fillable` (exceto `senha_consulta_hash`, atribuído explicitamente), `$hidden = ['senha_consulta_hash']`, `movimentacoes()` hasMany ordenado por `id`; `FiscalizacaoMovimentacao` (sem timestamps, `$casts = ['publico'=>'boolean','dados'=>'array','created_at'=>'datetime']`). `FiscalizacaoAttachment`: `'origem'` no `$fillable`. `FiscalizacaoResource`: acrescenta `protocolo`, `origem`, e os campos de denúncia quando `origem === 'denuncia'`; `estabelecimento.nome_estabelecimento` cai para `estabelecimento_nome_informado` quando não há cadastro. `UpdateFiscalizacaoRequest`: `resultado` aceita também `Pendente de apuração`; novos campos opcionais `visivel_ao_denunciante` (boolean) e `mensagem_publica` (string ≤ 1000).
- [ ] **Step 5:** teste → PASS; `--filter 'Fiscaliza|Estabelecimento|Audit'`.
- [ ] **Step 6: commit** `feat(fiscalizacao): protocolo, campos de denúncia e tabela de movimentações`.

### Task A2: `FiscalizacaoTimeline` e integração no controller

**Files:** criar `app/Services/Fiscalizacao/FiscalizacaoTimeline.php`, `tests/Feature/FiscalizacaoTimelineTest.php`; modificar `FiscalizacaoController.php`, `AuditService.php` (`senha_consulta_hash`, `senha_hash` na lista de sensíveis).

**Interfaces — Produces:** `FiscalizacaoTimeline::registrar(Fiscalizacao $f, string $acao, ?string $descricao = null, bool $publico = false, ?int $userId = null, ?array $dados = null): FiscalizacaoMovimentacao`.

- [ ] **Step 1 (RED):** `FiscalizacaoTimelineTest`: (a) `POST /fiscalizacoes` gera protocolo e 1 movimentação `criada` interna, `user_id` = fiscal; (b) `PUT` mudando `resultado` gera `situacao_alterada` com `dados.de/para` e `descricao` “Situação: Pendente de apuração → Conforme”; (c) `PUT` com `visivel_ao_denunciante=true` e `mensagem_publica` gera movimentação `publico=true` com a mensagem; sem a flag, todas internas; (d) `PUT` sem mudança de situação não gera `situacao_alterada`; (e) exclusão não quebra.
- [ ] **Step 2:** FAIL. **Step 3:** implementar:
```php
public function registrar(Fiscalizacao $f, string $acao, ?string $descricao = null, bool $publico = false, ?int $userId = null, ?array $dados = null): FiscalizacaoMovimentacao
{
    return $f->movimentacoes()->create([
        'user_id' => $userId, 'acao' => $acao, 'descricao' => $descricao, 'publico' => $publico, 'dados' => $dados,
    ]);
}
```
No controller: em `store`, depois de `create`, gravar `protocolo` (`FiscalizacaoProtocolo::for($f->id, $f->created_at)`) e chamar `registrar($f, 'criada', 'Fiscalização criada', false, $user->id)`; em `update`, comparar `resultado` original/novo antes do `update` e registrar `situacao_alterada`; se `mensagem_publica` ou `visivel_ao_denunciante`, registrar também `mensagem_publica` com `publico = true`.
- [ ] **Step 4:** PASS + regressão `--filter 'Fiscaliza|Audit'`. **Step 5: commit** `feat(fiscalizacao): movimentações registradas na criação e edição`.

### Task A3: endpoints do histórico interno

**Files:** criar `FiscalizacaoHistoricoController.php`, `tests/Feature/FiscalizacaoHistoricoTest.php`; modificar `routes/api.php` (dentro do grupo `page.permission:/fiscalizacoes`).

- [ ] **Step 1 (RED):** (a) `GET /fiscalizacoes/{id}/historico` devolve todas (internas e públicas), mais recente primeiro, com `titulo`, `detalhe`, `usuario`, `data`, `publico`; (b) `POST /fiscalizacoes/{id}/movimentacoes` `{descricao, publico}` cria `acao=observacao` e devolve 201; `descricao` obrigatória (422); (c) 403 sem a página `/fiscalizacoes`; 404 para id inexistente.
- [ ] **Step 2:** FAIL. **Step 3:** rotas `Route::get('/fiscalizacoes/{fiscalizacao}/historico', ...)` e `Route::post('/fiscalizacoes/{fiscalizacao}/movimentacoes', ...)`; o controller mapeia cada movimentação para `{id, titulo, detalhe, usuario, data, publico}` (título por `acao`: `criada` → “Fiscalização criada”, `situacao_alterada` → “Situação alterada”, `mensagem_publica` → “Mensagem ao denunciante”, `denuncia_recebida` → “Denúncia recebida”, `observacao` → “Observação”, `anexo_adicionado` → “Anexo adicionado”; usuário nulo → “Denunciante” se `acao = denuncia_recebida`, senão “Sistema”). Validação em `StoreFiscalizacaoMovimentacaoRequest`.
- [ ] **Step 4:** PASS. **Step 5: commit** `feat(fiscalizacao): endpoints do histórico de movimentação`.

### Task A4: tela — protocolo, origem, histórico

**Files:** modificar `src/components/fiscalizacoes/index.js`, `src/components/modal/fiscalizacao/index.js`; criar `src/components/fiscalizacoes/FiscalizacaoHistorico.js`, `tests/fiscalizacoes/fiscalizacoes.test.js`.

- [ ] **Step 1 (RED):** lista mostra coluna “Protocolo” (`FIS-2026-000123`) e chip “Denúncia” quando `origem = denuncia`; filtro “Origem”; botão “Histórico” abre o `HistoryDrawer` com `GET /fiscalizacoes/{id}/historico`, e o formulário “Nova movimentação” (texto + “Visível ao denunciante”) faz `POST` e recarrega; modal de edição mostra o protocolo e, em denúncias, o campo “Informar ao denunciante” (mensagem pública).
- [ ] **Step 2:** `npx jest tests/fiscalizacoes` → FAIL. **Step 3:** implementar seguindo os padrões do arquivo (Redux `getAllFiscalizacoes` recebe `origem`). `FiscalizacaoHistorico` reutiliza `HistoryDrawer` (`items` já ordenados pelo servidor; marca “Público” com `Chip`).
- [ ] **Step 4:** PASS + `npx jest tests/history tests/protocolo`. **Step 5: commit** `feat(fiscalizacao): protocolo, origem e histórico na tela`.

### Task A5: PDF interno

**Files:** criar `src/reports/fiscalizacao/index.js`, `tests/fiscalizacoes/pdfFiscalizacao.test.js`; modificar a tela (botão “Imprimir PDF”).

**Interfaces — Produces:** `buildFiscalizacaoDocDefinition({ fiscalizacao, movimentacoes, modo }): object` (função pura, `modo` = `'interno' | 'publico'`); `printFiscalizacaoPdf(args)` (usa `pdfMake.createPdf(...).open()`).

- [ ] **Step 1 (RED):** a função pura, no modo interno, contém o protocolo, situação, estabelecimento, fiscal e **todas** as movimentações (com autor e data/hora, mais recente primeiro); no modo público não contém fiscal nem movimentações não públicas; textos com acento preservados.
- [ ] **Step 2:** FAIL. **Step 3:** implementar com `pdfmake` (vfs fonts como em `src/reports/protocol/index.js`); botão “Imprimir PDF” no drawer e na linha da fiscalização.
- [ ] **Step 4:** PASS; suíte completa do front e do back. **Step 5:** atualizar `STATE.md` (T7 concluída) e commit `feat(fiscalizacao): PDF do histórico`.

---

# Parte B — T8: denúncia pública, consulta e PDF do cidadão

### Task B1: registrar denúncia (público)

**Files:** criar `DenunciaPublicaController.php`, `StoreDenunciaRequest.php`, `tests/Feature/DenunciaPublicaTest.php`; modificar `RouteServiceProvider.php` (limitador `denuncia-create`), `routes/api.php`.

**Interfaces — Produces:** `POST /api/public/denuncias` → 201 `{protocolo, senha, url_consulta}`.

- [ ] **Step 1 (RED):** (a) payload mínimo `{assunto, descricao_denuncia, local_endereco}` cria fiscalização `origem=denuncia`, `resultado=Pendente de apuração`, `fiscal_id=null`, `protocolo` válido, `senha_consulta_hash` que confere com a `senha` devolvida (8 caracteres do alfabeto), movimentação `denuncia_recebida` com `publico=true`; (b) com identificação, estabelecimento informado e 2 arquivos → anexos salvos no disco `private` com `origem=denunciante`; (c) campo `website` preenchido → 201 com dados falsos e **nenhuma** linha criada; (d) 6ª chamada na mesma hora e IP → 429; (e) arquivo `.exe`, 6 arquivos ou > 10 MB → 422; (f) `senha` não aparece em `audit_logs`, em `GET /api/fiscalizacoes` nem no JSON interno; (g) `url_consulta` = `config('app.frontend_url')` + `/denuncia/consulta?protocolo=…` sem a senha.
- [ ] **Step 2:** FAIL. **Step 3:** limitador `RateLimiter::for('denuncia-create', fn ($r) => Limit::perHour(5)->by($r->ip()))`; rota `Route::post('/public/denuncias', ...)->middleware('throttle:denuncia-create')`; `StoreDenunciaRequest` (FormRequest, `authorize` true) com `assunto` required ≤ 200, `descricao_denuncia` required ≤ 4000, `local_endereco` required ≤ 255, demais opcionais, `files.*` `mimes:jpg,jpeg,png,webp,pdf` + `mimetypes`, `max:10240`, `files` `max:5`; controller cria numa transação, gera protocolo, senha (`random_int` sobre o alfabeto), grava anexos e a movimentação; adicionar `frontend_url` em `config/app.php` (`env('FRONTEND_URL', 'https://sysvendas.vercel.app')`). Adicionar `senha_consulta_hash` e `senha_hash` aos segredos do `AuditService`.
- [ ] **Step 4:** PASS + `--filter 'Fiscaliza|Audit'`. **Step 5: commit** `feat(denuncia): registro público com protocolo e senha`.

### Task B2: consulta pública

**Files:** modificar `DenunciaPublicaController.php`, `routes/api.php`, `RouteServiceProvider.php` (`denuncia-consulta`); criar `ConsultaDenunciaRequest.php`, `tests/Feature/DenunciaConsultaTest.php`.

**Interfaces — Produces:** `POST /api/public/denuncias/consulta` `{protocolo, senha}` → 200 `{protocolo, situacao, assunto, local_endereco, registrada_em, movimentacoes:[{titulo, descricao, data}]}`.

- [ ] **Step 1 (RED):** (a) senha certa → só movimentações `publico=true`, sem `usuario`/`user_id`, sem observações internas; (b) senha errada e protocolo inexistente → 404 com o **mesmo** corpo; (c) 5 senhas erradas para o mesmo protocolo → 429 por 15 min (mesmo com a senha certa) e volta depois; (d) protocolo em minúsculas/com espaços é normalizado; (e) fiscalização de origem `interna` nunca é consultável.
- [ ] **Step 2:** FAIL. **Step 3:** limitador `denuncia-consulta` (10/min por IP); contador de falhas em `Cache` (`denuncia-falhas:{protocolo}`, 15 min); `Hash::check` contra `senha_consulta_hash`; resposta sem dados de fiscais.
- [ ] **Step 4:** PASS. **Step 5: commit** `feat(denuncia): consulta pública por protocolo e senha`.

### Task B3: páginas públicas

**Files:** criar `pages/denuncia.js`, `pages/denuncia/consulta.js`, `tests/denuncia/denuncia.test.js`; modificar `pages/_app.js` e `src/constants/publicPaths.js` (`/denuncia`, `/denuncia/consulta`).

- [ ] **Step 1 (RED):** formulário com assunto, descrição, endereço, estabelecimento, nome/contato (opcionais), fotos/arquivos e campo isca invisível; enviar sem campos de identificação funciona; sucesso mostra protocolo, senha, endereço de consulta com botões “Copiar” e “Imprimir”; erro 429/422 mostra mensagem clara; consulta com protocolo (pré-preenchido pela URL) + senha mostra situação e timeline; falha mostra “Protocolo ou senha inválidos”.
- [ ] **Step 2:** FAIL. **Step 3:** implementar com MUI usando `axios` público (sem token) via `api` existente sem cabeçalho de auth quando não há cookie; páginas sem layout autenticado (seguir `pages/consulta-exame.js`).
- [ ] **Step 4:** PASS + `npx jest tests/protocolo tests/queue tests/history`. **Step 5: commit** `feat(denuncia): páginas públicas de denúncia e consulta`.

### Task B4: PDF do cidadão e encerramento

**Files:** modificar `src/reports/fiscalizacao/index.js` (já tem `modo: 'publico'`), `pages/denuncia/consulta.js` (botão “Imprimir PDF”), testes.

- [ ] **Step 1 (RED):** botão gera o documento com protocolo, situação, assunto, local e apenas as movimentações públicas; sem nomes de fiscais.
- [ ] **Step 2:** FAIL → **Step 3:** ligar o botão a `printFiscalizacaoPdf({ modo: 'publico' })` → **Step 4:** PASS.
- [ ] **Step 5:** suítes completas (`./vendor/bin/phpunit` e `npx jest --watchAll=false`); atualizar `STATE.md` (T7/T8 concluídas, migrations a rodar `2026_09_30_100000/1/2`, variável `FRONTEND_URL`); commit `feat(denuncia): PDF do cidadão e fechamento do lote`.

## Autorrevisão
- **Cobertura do spec:** protocolo (A1), timeline (A2), histórico interno (A3), tela (A4), PDF interno (A5), denúncia pública (B1), consulta (B2), páginas públicas (B3), PDF do cidadão (B4), segurança (B1/B2), testes por seção.
- **Tipos consistentes:** `FiscalizacaoProtocolo::for`, `FiscalizacaoTimeline::registrar`, `buildFiscalizacaoDocDefinition`, campos `protocolo/origem/senha_consulta_hash/publico` iguais em todas as tarefas.
- **Review Focus:** itens 1–5 cobertos por B1(a,c), B1(c), B2(b), A1, B2(a)/A1.
