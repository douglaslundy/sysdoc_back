# Auditoria completa e histórico do cidadão — plano de implementação

> **Para quem executa:** usar `superpowers:executing-plans` (modo nativo) ou `superpowers:subagent-driven-development`.
> Os passos usam checkbox (`- [ ]`). Cada tarefa termina com testes + commit.

**Goal:** registrar toda escrita relevante do sistema em `audit_logs` sem custo perceptível e entregar o painel lateral “Histórico do cidadão”.

**Architecture:** `AuditService` passa a montar linhas em memória e gravá-las em **um INSERT em lote depois da resposta**; cada linha ganha `client_id` (resolvido por `AuditContext`). Um `AuditableObserver` genérico, configurado em `config/audit.php`, cobre os models sem auditoria. O histórico é uma consulta indexada por `client_id`, descrita em português por `AuditDescriber`, exibida por um `HistoryDrawer` reutilizável (também usado na Fiscalização, T7).

**Tech Stack:** Laravel 10 / PHP 8.2 / MySQL / PHPUnit; Next 12 / React 17 / MUI v5 / Jest + Testing Library.

**Spec:** `docs/superpowers/specs/2026-09-29-auditoria-e-historico-do-cidadao-design.md`

## Global Constraints
- Backend em `sysdoc_back/`, frontend em `sysdoc_front/` (nunca na raiz do workspace).
- Validação em `FormRequest` (`App\Http\Requests\*Request`), nunca inline em controller.
- Textos de tela em português, com acentos; arquivos com acento editados por ferramenta de escrita, nunca por heredoc de shell.
- Falha de auditoria **nunca** derruba a requisição (`try/catch` + `Log::error`).
- Sem soft delete; nada de novos `env()` fora de `config/`.
- Cada tarefa: teste focado → módulos que dependem dos arquivos alterados → suíte completa antes do commit final da tarefa.
- Permissão nova por perfil: coluna `client_history_view_enabled` em `access_profiles`; admin sempre vê.
- Visualização do cadastro do cidadão: 1 registro / 10 min / (usuário, cidadão).

## Review Focus
1. Duas ações do mesmo cidadão na mesma requisição → 2 linhas, ambas com `client_id`, 1 único INSERT.
2. Model sem vínculo com cidadão (ex.: `Vehicle`) → `client_id = NULL`, nunca erro.
3. Atualização que só muda `updated_at` → nenhuma linha.
4. Segredos aninhados (`configuracao.smtp_password`, `app_secret`) → mascarados, nunca em texto puro.
5. Histórico de cidadão sem eventos → lista vazia com 200 (não 404); cidadão inexistente → 404; sem permissão → 403.

---

## Estrutura de arquivos
| Arquivo | Ação | Responsabilidade |
|---|---|---|
| `database/migrations/2026_09_30_000001_add_client_id_to_audit_logs.php` | criar | coluna + índice |
| `database/migrations/2026_09_30_000002_add_client_history_permission_to_access_profiles.php` | criar | permissão por perfil |
| `database/migrations/2026_09_30_000003_backfill_audit_logs_client_id.php` | criar | vínculo retroativo |
| `app/Services/Audit/AuditContext.php` | criar | `client_id` de um model |
| `app/Services/Audit/AuditDescriber.php` | criar | texto para humanos |
| `app/Services/AuditService.php` | modificar | buffer/lote, `client_id`, máscara, `recordViewOncePer` |
| `app/Models/AuditLog.php` | modificar | `client_id` fillable |
| `config/audit.php` | criar | models auditados + campos ignorados |
| `app/Observers/AuditableObserver.php` | criar | CRUD genérico |
| `app/Providers/AppServiceProvider.php` | modificar | registra observers de `config('audit.models')` |
| `app/Http/Controllers/ClientHistoryController.php` | criar | `GET /clients/{client}/historico` |
| `app/Http/Controllers/ClientController.php` | modificar | VIEW deduplicado |
| `app/Http/Controllers/TripController.php` | modificar | passageiros auditados |
| `app/Http/Controllers/AccessProfileController.php`, `app/Models/AccessProfile.php`, `app/Models/User.php` | modificar | permissão nova |
| `routes/api.php` | modificar | rota do histórico |
| front `src/components/history/HistoryDrawer.js` | criar | painel lateral genérico |
| front `src/components/clients/ClientHistoryDrawer.js` | criar | busca e exibe o histórico |
| front `src/components/clients/index.js`, `src/components/perfis/index.js`, `src/contexts/AuthContext.js` | modificar | botão, switch, capability |

---

### Task 1: `client_id` em `audit_logs` e `AuditContext`

**Files:** criar `2026_09_30_000001_add_client_id_to_audit_logs.php`, `app/Services/Audit/AuditContext.php`, `tests/Unit/AuditContextTest.php` (usar `Tests\TestCase` + `RefreshDatabase`); modificar `app/Models/AuditLog.php`.

**Interfaces — Produces:** `AuditContext::clientIdFor(?Model $model): ?int`.

- [ ] **Step 1: teste falhando** — `AuditContextTest`:
```php
public function test_resolve_o_cidadao_de_cada_tipo_de_model(): void
{
    $client = Client::create(['name'=>'A','mother'=>'M','cpf'=>'1','born_date'=>'1990-01-01','active'=>true]);
    $admin  = User::factory()->create(['profile'=>'admin','active'=>true]);
    $spec   = Speciality::create(['id_user'=>$admin->id,'name'=>'Fisio']);
    $queue  = Queue::create(['id_client'=>$client->id,'id_specialities'=>$spec->id,'id_user'=>$admin->id,'done'=>false,'urgency'=>false]);
    $vehicle = new Vehicle();

    $this->assertSame($client->id, AuditContext::clientIdFor($client));
    $this->assertSame($client->id, AuditContext::clientIdFor($queue));
    $this->assertNull(AuditContext::clientIdFor($vehicle));
    $this->assertNull(AuditContext::clientIdFor(null));
}
```
- [ ] **Step 2:** `./vendor/bin/phpunit tests/Unit/AuditContextTest.php` → FAIL (classe inexistente).
- [ ] **Step 3: migration**
```php
Schema::table('audit_logs', function (Blueprint $t) {
    $t->unsignedBigInteger('client_id')->nullable()->after('model_id');
    $t->index(['client_id', 'id'], 'audit_logs_client_id_index');
});
```
(`down`: `dropIndex` + `dropColumn`.) Em `AuditLog::$fillable` acrescentar `'client_id'`.
- [ ] **Step 4: `AuditContext`**
```php
final class AuditContext
{
    public static function clientIdFor(?Model $model): ?int
    {
        if ($model === null) { return null; }
        if (class_basename($model) === 'Client') { return (int) $model->getKey(); }
        foreach (['client_id', 'id_client'] as $attribute) {
            $value = $model->getAttribute($attribute);
            if ($value) { return (int) $value; }
        }
        return match (class_basename($model)) {
            'ResultadoExame'        => self::id($model->pedido?->client_id),
            'QueueAttachment'       => self::id($model->queue?->id_client),
            'QueueTreatmentSession' => self::id($model->plan?->client_id),
            default                 => null,
        };
    }

    private static function id(mixed $value): ?int { return $value ? (int) $value : null; }
}
```
- [ ] **Step 5:** rodar o teste → PASS. Rodar `php artisan migrate` no banco de teste é automático (RefreshDatabase).
- [ ] **Step 6: commit** `feat(auditoria): client_id em audit_logs e AuditContext`.

### Task 2: `AuditService` — lote, `client_id`, máscara, visualização deduplicada

**Files:** modificar `app/Services/AuditService.php`; criar `tests/Feature/AuditServiceBatchTest.php`.

**Interfaces — Consumes:** `AuditContext::clientIdFor`, `App\Support\AfterResponse::run(Closure)`. **Produces:** `AuditService::record(string $action, ?Model $model=null, ?array $old=null, ?array $new=null, ?User $actingUser=null, ?int $clientId=null): void`; `AuditService::flush(): void`; `AuditService::recordViewOncePer(string $action, Model $model, ?array $new=null, int $seconds=600): void`.

- [ ] **Step 1: testes falhando**
```php
public function test_varias_acoes_na_mesma_requisicao_viram_um_unico_insert(): void
{
    config(['chat.defer_broadcast' => true]);          // comportamento web
    AuditService::flush();
    $client = $this->makeClient();
    DB::enableQueryLog();
    AuditService::record('UPDATE', $client, ['name'=>'A'], ['name'=>'B']);
    AuditService::record('VIEW', $client);
    $this->assertSame(0, AuditLog::count());           // ainda no buffer
    $this->app->terminate();
    $inserts = collect(DB::getQueryLog())->filter(fn ($q) => str_starts_with($q['query'], 'insert into `audit_logs`'));
    $this->assertCount(1, $inserts);
    $this->assertSame(2, AuditLog::where('client_id', $client->id)->count());
}
public function test_segredos_aninhados_sao_mascarados(): void
{
    AuditService::record('UPDATE', null, null, ['configuracao' => ['smtp_password' => 'x', 'smtp_host' => 'h'], 'api_key' => 'k']);
    $new = AuditLog::latest('id')->first()->new_values;
    $this->assertSame('[mascarado]', $new['configuracao']['smtp_password']);
    $this->assertSame('h', $new['configuracao']['smtp_host']);
    $this->assertSame('[mascarado]', $new['api_key']);
}
public function test_visualizacao_e_gravada_uma_vez_a_cada_10_minutos_por_usuario_e_cidadao(): void { /* 3 chamadas -> 1 linha; outro usuário -> +1; Carbon +601s -> +1 */ }
public function test_falha_ao_gravar_nao_quebra(): void { /* Schema::drop('audit_logs') ; record() não lança */ }
```
- [ ] **Step 2:** rodar → FAIL.
- [ ] **Step 3: implementação** (substitui `record`/`sanitize`):
```php
private static array $buffer = [];
private static bool $scheduled = false;
private static array $sensitiveKey = ['password','remember_token','token','secret','api_key','apikey','authorization','smtp_password','app_secret'];

public static function record(string $action, ?Model $model = null, ?array $old = null, ?array $new = null, ?User $actingUser = null, ?int $clientId = null): void
{
    try {
        $user = $actingUser ?? Auth::user();
        self::$buffer[] = [
            'user_id' => $user?->id, 'user_name' => $user?->name ?? 'Sistema', 'action' => $action,
            'model_type' => $model ? class_basename($model) : null, 'model_id' => $model?->getKey(),
            'client_id' => $clientId ?? AuditContext::clientIdFor($model),
            'endpoint' => request()->path(), 'method' => request()->method(), 'ip_address' => request()->ip() ?? '0.0.0.0',
            'user_agent' => substr(request()->userAgent() ?? '', 0, 255),
            'old_values' => $old ? json_encode(self::sanitize($old)) : null,
            'new_values' => $new ? json_encode(self::sanitize($new)) : null,
            'created_at' => now(),
        ];
        if (! self::$scheduled) { self::$scheduled = true; AfterResponse::run(fn () => self::flush()); }
    } catch (\Throwable $e) { Log::error('Falha ao gravar auditoria.', ['action' => $action, 'error' => $e->getMessage()]); }
}
public static function flush(): void
{
    $rows = self::$buffer; self::$buffer = []; self::$scheduled = false;
    if ($rows === []) { return; }
    try { AuditLog::insert($rows); } catch (\Throwable $e) { Log::error('Falha ao gravar auditoria em lote.', ['rows' => count($rows), 'error' => $e->getMessage()]); }
}
public static function recordViewOncePer(string $action, Model $model, ?array $new = null, int $seconds = 600): void
{
    $key = 'audit-view:'.(Auth::id() ?? 'anon').':'.$action.':'.class_basename($model).':'.$model->getKey();
    if (Cache::add($key, 1, $seconds)) { self::record($action, $model, null, $new); }
}
private static function sanitize(array $data): array
{
    $clean = [];
    foreach ($data as $key => $value) {
        if (is_string($key) && in_array(strtolower($key), self::$sensitiveKey, true)) { $clean[$key] = '[mascarado]'; continue; }
        $clean[$key] = is_array($value) ? self::sanitize($value) : $value;
    }
    return $clean;
}
```
(Manter `recordOncePerVisitor` da T2 chamando `record`.)
- [ ] **Step 4:** rodar `AuditServiceBatchTest`, depois **todos os testes de auditoria existentes**: `--filter 'Auditoria|Audit'` (ajustar os que esperavam remoção em vez de máscara de `password`).
- [ ] **Step 5: commit** `feat(auditoria): gravação em lote depois da resposta, máscara recursiva e client_id`.

### Task 3: Observer genérico e cobertura das áreas sem auditoria

**Files:** criar `config/audit.php`, `app/Observers/AuditableObserver.php`, `tests/Feature/AuditableObserverTest.php`; modificar `AppServiceProvider::boot`.

- [ ] **Step 1: teste falhando** — `ProtocolType` criar/editar/excluir gera CREATE/UPDATE/DELETE com diff; alterar só `updated_at` não gera linha; `NotificationChannelConfig` com `configuracao.smtp_password` sai mascarado.
- [ ] **Step 2:** rodar → FAIL.
- [ ] **Step 3:** `config/audit.php`:
```php
return [
    'ignore_fields' => ['created_at', 'updated_at', 'remember_token'],
    // Models auditados pelo observer genérico. Models que já têm observer próprio ou
    // auditoria explícita no controller NÃO entram aqui (evita linha duplicada).
    'models' => [
        \App\Models\ProtocolType::class, \App\Models\ProtocolOrganizationalUnit::class,
        \App\Models\ProtocolAlert::class, \App\Models\ProtocolConfig::class,
        \App\Models\DocumentType::class, \App\Models\KanbanTask::class, \App\Models\SystemNotice::class,
        \App\Models\NotificationChannelConfig::class, \App\Models\MedicinePublication::class,
        \App\Models\PharmacyMedicinePanelSetting::class, \App\Models\PharmacyAcquisitionSource::class,
        \App\Models\PharmacyPharmaceuticalForm::class, \App\Models\PharmacyPresentation::class, \App\Models\PharmacyUnit::class,
        \App\Models\AlmoxarifadoProduto::class, \App\Models\AlmoxarifadoCategoria::class, \App\Models\AlmoxarifadoEspecie::class,
        \App\Models\AlmoxarifadoFornecedor::class, \App\Models\AlmoxarifadoLocalizacao::class, \App\Models\AlmoxarifadoSecretaria::class,
        \App\Models\AlmoxarifadoUnidadeMedida::class, \App\Models\AlmoxarifadoConfig::class,
        \App\Models\AlmoxarifadoRequisicao::class, \App\Models\AlmoxarifadoMovimentacao::class,
    ],
];
```
`AuditableObserver`:
```php
class AuditableObserver
{
    public function created(Model $m): void { AuditService::record('CREATE', $m, null, $this->visible($m->getAttributes())); }
    public function updated(Model $m): void
    {
        $changes = $this->visible($m->getChanges());
        if ($changes === []) { return; }
        AuditService::record('UPDATE', $m, array_intersect_key($m->getOriginal(), $changes), $changes);
    }
    public function deleted(Model $m): void { AuditService::record('DELETE', $m, $this->visible($m->getAttributes()), null); }
    private function visible(array $attributes): array { return array_diff_key($attributes, array_flip(config('audit.ignore_fields', []))); }
}
```
`AppServiceProvider::boot`: `foreach (config('audit.models', []) as $class) { $class::observe(AuditableObserver::class); }`.
- [ ] **Step 4:** rodar teste → PASS; conferir com `grep` que nenhum model de `config/audit.php` também tem `::observe` próprio ou `AuditService::record` no controller (senão removê-lo da lista).
- [ ] **Step 5:** rodar as suítes de Protocolo, Documentos, Almoxarifado, Farmácia, Kanban (`--filter 'Protocol|Document|Almoxarifado|Pharmacy|Kanban'`).
- [ ] **Step 6: commit** `feat(auditoria): observer genérico cobre áreas sem registro`.

### Task 4: Ações especiais ligadas ao cidadão

**Files:** modificar `ClientController.php` (linha do `VIEW` → `recordViewOncePer`), `TripController.php` (`insertTripClient`, `editTripClient`, `deleteTripClient`, `confirmTripClient`), `ClientController` (`VIEW_REPORT` já usa `$client` → herda `client_id`); teste `tests/Feature/ClientAuditTrailTest.php`.

- [ ] **Step 1: testes falhando:** abrir `GET /api/clients/{id}` 3× → 1 `VIEW` com `client_id`; `POST /api/trip-clients` → linha `TRIP_CLIENT_ADDED` com `client_id` do passageiro; `DELETE /api/trip-clients/{id}` → `TRIP_CLIENT_REMOVED` (o `client_id` é lido **antes** de apagar); `PATCH confirm-trip-client/{id}` → `TRIP_CLIENT_CONFIRMED`; baixa na fila → linha `UPDATE` de `Queue` com `client_id`.
- [ ] **Step 2:** rodar → FAIL.
- [ ] **Step 3:** trocar `AuditService::record('VIEW', $client, ...)` por `AuditService::recordViewOncePer('VIEW', $client, [...])`. Nos métodos de viagem, chamar `AuditService::record('TRIP_CLIENT_ADDED', $trip, null, ['trip_id' => ..., 'client_id' => ...], null, $clientId)` (idem `EDITED/REMOVED/CONFIRMED`), sempre passando o `clientId` explícito.
- [ ] **Step 4:** testes do passo 1 → PASS; rodar `--filter 'Client|Trip|Queue'`.
- [ ] **Step 5: commit** `feat(auditoria): visualização deduplicada e ações de viagem ligadas ao cidadão`.

### Task 5: `AuditDescriber`

**Files:** criar `app/Services/Audit/AuditDescriber.php`, `tests/Unit/AuditDescriberTest.php`.

**Interfaces — Produces:** `AuditDescriber::describe(AuditLog $log): array{titulo:string, detalhe:?string}`.

- [ ] **Step 1: testes falhando** (tabela de casos):
  - `VIEW`+`Client` → “Visualizou o cadastro”;
  - `UPDATE`+`Client` com `old={name:A}`/`new={name:B}` → título “Editou o cadastro”, detalhe “name: A → B”;
  - `CREATE`+`Queue` → “Inseriu na fila”; `UPDATE`+`Queue` com `done` 0→1 → “Deu baixa na fila”; `DELETE`+`Queue` → “Removeu da fila”;
  - `TRIP_CLIENT_ADDED` → “Inseriu em viagem”; `VIEW_REPORT` → “Consultou o relatório do cidadão”;
  - ação desconhecida → título = ação legível (`snake_case` → “Snake case”), sem erro;
  - `__audit_subject_name` e campos ignorados não aparecem no detalhe.
- [ ] **Step 2:** rodar → FAIL.
- [ ] **Step 3:** implementar `describe()` com `match` por `(action, model_type)`; helper `diff(array $old, array $new): string` limitando a 6 campos e valores a 60 caracteres; rótulos PT-BR de campo em um array `FIELD_LABELS` (`name`→“Nome”, `cpf`→“CPF”, `cns`→“CNS”, `phone`→“Telefone”, `done`→“Baixa”, `obs`→“Observação”, `urgency`→“Urgência”…), campo desconhecido usa a chave.
- [ ] **Step 4:** rodar → PASS. **Step 5: commit** `feat(auditoria): descrição em português dos eventos`.

### Task 6: Endpoint do histórico e permissão por perfil

**Files:** criar `ClientHistoryController.php`, `2026_09_30_000002_...`, `tests/Feature/ClientHistoryTest.php`; modificar `AccessProfile.php` (fillable/cast), `AccessProfileController.php` (validação, store/update/listagem e `capabilities.client_history_view` em `myPermissions`), `User.php` (`canViewClientHistory()` seguindo `canViewClientReport()`), `routes/api.php`.

**Interfaces — Produces:** `GET /api/clients/{client}/historico?page=n` → `{data:[{id,titulo,detalhe,usuario,acao,data}],current_page,last_page,total}`; `User::canViewClientHistory(): bool`.

- [ ] **Step 1: testes falhando:** (a) admin recebe eventos do cidadão, mais recente primeiro, `usuario`/`data`/`titulo` preenchidos; (b) perfil sem a permissão → 403; perfil com a permissão → 200; (c) cidadão inexistente → 404; (d) cidadão sem eventos → 200 e `data=[]`; (e) eventos de **outro** cidadão nunca aparecem; (f) paginação de 30.
- [ ] **Step 2:** rodar → FAIL.
- [ ] **Step 3:** migration (`boolean client_history_view_enabled default false after client_report_view_enabled`); rota em `routes/api.php` dentro do grupo autenticado: `Route::get('/clients/{client}/historico', [ClientHistoryController::class, 'index'])->whereNumber('client');` (declarar **antes** de `apiResource('clients')` se houver conflito). Controller:
```php
public function index(Request $request, int $client): JsonResponse
{
    abort_unless($request->user()->canViewClientHistory(), 403, 'Sem permissão para ver o histórico do cidadão.');
    abort_unless(Client::whereKey($client)->exists(), 404, 'Cidadão não encontrado.');

    $page = AuditLog::query()->where('client_id', $client)->orderByDesc('id')->paginate(30);
    $page->getCollection()->transform(function (AuditLog $log) {
        $d = AuditDescriber::describe($log);
        return ['id'=>$log->id,'titulo'=>$d['titulo'],'detalhe'=>$d['detalhe'],'usuario'=>$log->user_name,'acao'=>$log->action,'data'=>$log->created_at?->toISOString()];
    });
    return response()->json($page);
}
```
(`orderByDesc('id')` usa o índice `(client_id,id)`.) `User::canViewClientHistory()`: admin → true; senão `accessProfile()->value('client_history_view_enabled')`.
- [ ] **Step 4:** testes → PASS; rodar `--filter 'AccessProfile|ClientView|Permission'`.
- [ ] **Step 5: commit** `feat(cidadao): endpoint do histórico e permissão por perfil`.

### Task 7: Preenchimento retroativo

**Files:** criar `2026_09_30_000003_backfill_audit_logs_client_id.php`, `tests/Feature/AuditBackfillTest.php`.

- [ ] **Step 1: teste falhando:** inserir linhas em `audit_logs` (`model_type` `Client`/`Queue`/`PedidoExame`, `client_id` nulo) e executar a migration (`(require path)->up()`); esperar `client_id` preenchido; linha de `Vehicle` continua nula.
- [ ] **Step 2:** rodar → FAIL.
- [ ] **Step 3:** SQL em lotes de 5.000 (`whereNull('client_id')`, `whereBetween('id')`):
```sql
UPDATE audit_logs a JOIN clients c ON c.id = a.model_id SET a.client_id = c.id WHERE a.model_type='Client' AND a.client_id IS NULL AND a.id BETWEEN ? AND ?;
UPDATE audit_logs a JOIN queue q ON q.id = a.model_id SET a.client_id = q.id_client WHERE a.model_type='Queue' AND a.client_id IS NULL AND a.id BETWEEN ? AND ?;
UPDATE audit_logs a JOIN pedidos_exame p ON p.id = a.model_id SET a.client_id = p.client_id WHERE a.model_type='PedidoExame' AND a.client_id IS NULL AND a.id BETWEEN ? AND ?;
```
(confirmar o nome real da tabela de `PedidoExame` em `app/Models/PedidoExame.php` antes de escrever.) `down()` vazio.
- [ ] **Step 4:** teste → PASS. **Step 5: commit** `feat(auditoria): vínculo retroativo com o cidadão`.

### Task 8: Frontend — `HistoryDrawer`, histórico do cidadão, permissão

**Files:** criar `src/components/history/HistoryDrawer.js`, `src/components/clients/ClientHistoryDrawer.js`, `tests/history/historyDrawer.test.js`, `tests/history/clientHistoryDrawer.test.js`; modificar `src/components/clients/index.js`, `src/components/perfis/index.js`, `src/contexts/AuthContext.js`.

**Interfaces — Produces:** `<HistoryDrawer open onClose title subtitle items loading error hasMore onLoadMore />` com `items: {id, titulo, detalhe, usuario, data}[]` (a ordem já vem do servidor); `<ClientHistoryDrawer open onClose client />`; contexto expõe `canViewClientHistory`.

- [ ] **Step 1: testes falhando:** `HistoryDrawer` renderiza itens na ordem recebida com usuário e `dd/MM/yyyy HH:mm`, estado vazio (“Nenhum registro.”), erro, e botão “Carregar mais” só com `hasMore`; `ClientHistoryDrawer` chama `GET /clients/{id}/historico?page=1` ao abrir e concatena a página 2; o botão “Histórico” só aparece com `canViewClientHistory`.
- [ ] **Step 2:** `npx jest tests/history` → FAIL.
- [ ] **Step 3:** `HistoryDrawer` copia o padrão do painel de Movimentação de `pages/protocolo/[...slug].js` (Drawer à direita, `Divider`, `Chip` de tipo, título em negrito, detalhe `whiteSpace: pre-wrap`). `ClientHistoryDrawer` usa `api.get` com paginação (`page`, `last_page`). Em `clients/index.js`, botão “Histórico” (ícone `clock` do feather) por linha, condicionado a `canViewClientHistory`. `perfis/index.js`: novo `Switch` “Ver histórico do cidadão” (`client_history_view_enabled`, iniciar `false`, carregar/enviar como os demais). `AuthContext`: `canViewClientHistory: Boolean(capabilities.client_history_view)`.
- [ ] **Step 4:** testes → PASS; rodar `npx jest tests/queue tests/protocolo tests/chat` e a suíte de `authGuard`/`perfis` se existir.
- [ ] **Step 5: commit** `feat(cidadao): painel lateral com o histórico do cidadão`.

### Task 9: Verificação final e encerramento

- [ ] **Step 1:** `./vendor/bin/phpunit` (suíte completa) — esperado: tudo verde, 2 pulados do e-SUS.
- [ ] **Step 2:** `npx jest --watchAll=false` — esperado: tudo verde.
- [ ] **Step 3:** conferir manualmente com dados: editar um cidadão, inserir na fila, dar baixa, abrir o Histórico (ordem, nomes, horários).
- [ ] **Step 4:** atualizar `docs/lote-2026-09-29/STATE.md` (T5 e T3 concluídas + migrations a rodar: `2026_09_30_000001/2/3`), commit dos dois repositórios.

## Autorrevisão
- **Cobertura do spec:** buffer/lote (T2), `client_id` (T1, T7), observer genérico e cobertura (T3), visualização 10 min (T2/T4), máscara (T2), descrição (T5), endpoint + permissão (T6), drawer + botão + Perfis (T8), retroativo (T7), testes e regressão (T9). Sem lacunas.
- **Tipos consistentes:** `AuditService::record(..., ?int $clientId)`, `AuditContext::clientIdFor`, `AuditDescriber::describe`, `User::canViewClientHistory`, capability `client_history_view` — mesmos nomes em todas as tarefas.
- **Riscos tratados:** linha duplicada (models com auditoria própria ficam fora de `config/audit.php`), `client_id` lido antes de excluir (T4), estado estático do buffer resetado em `flush()`.
