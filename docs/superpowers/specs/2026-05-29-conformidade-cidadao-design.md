# Conformidade de Cidadão — Design Spec

**Data:** 2026-05-29  
**Status:** Aprovado  
**Projeto:** Sysdoc (sysdoc_back + sysdoc_front)

---

## Objetivo

Criar uma página de conformidade que compara os registros de `clients` do Sysdoc com os cidadãos ativos do e-SUS PEC, atualizando dados desatualizados, processando óbitos e criando novos clientes para cidadãos ativos que ainda não existem no Sysdoc.

---

## Escopo

| Ponto | Decisão |
|---|---|
| Trigger | Manual (botão na página) |
| Escopo de cidadãos | Todos os cidadãos ativos no e-SUS (sem filtro por equipe) |
| Chave de match | CPF como primário; CNS como fallback se CPF nulo |
| Acesso | Permissão configurável: `conformidade-cidadao-sincronizar` |
| UX | Prévia → confirmar → aplicar |
| Processamento | Job em background com polling; fallback síncrono se `QUEUE_CONNECTION=sync` |

---

## Arquitetura Geral

```
Frontend (Next.js)
    └── Página /conformidade-cidadao
           │
           ├── POST /api/conformidade-cidadao/analisar
           │        → dispara SincronizacaoCidadaoJob (queue)
           │        ← retorna { job_id }
           │
           ├── GET /api/conformidade-cidadao/status/{job_id}
           │        ← retorna { status, progresso, preview? }
           │
           ├── POST /api/conformidade-cidadao/aplicar/{job_id}
           │        → aplica as mudanças armazenadas
           │        ← retorna { criados, atualizados, obitos, erros }
           │
           └── GET /api/conformidade-cidadao/historico
                    ← lista as sincronizações anteriores

Backend (Laravel)
    ├── ConformidadeCidadaoController   — orquestra os endpoints
    ├── ConformidadeCidadaoService      — lógica de match e comparação
    ├── SincronizacaoCidadaoJob         — análise em background
    └── AplicarSincronizacaoJob         — aplicação em background

Banco de dados
    ├── MySQL (Sysdoc)
    │     ├── clients + addresses      — lidos e atualizados
    │     ├── queues                   — atualizadas em caso de óbito
    │     └── sincronizacoes_cidadao   — nova: histórico + resultados
    │     └── sincronizacao_itens      — nova: itens divergentes por sync
    │
    └── PostgreSQL (e-SUS, somente leitura)
          ├── tb_fat_cidadao_pec       — dados cadastrais atualizados
          └── tb_fat_cad_individual    — flag de óbito e data
```

---

## Banco de Dados — Novas Tabelas (MySQL Sysdoc)

### `sincronizacoes_cidadao`

| Coluna | Tipo | Descrição |
|---|---|---|
| `id` | PK | |
| `job_id` | string unique | Identificador do Laravel Job |
| `status` | enum | `pending`, `analyzing`, `preview_ready`, `applying`, `completed`, `failed` |
| `total_esus` | int | Total de cidadãos ativos no e-SUS |
| `total_sysdoc` | int | Total de clients no Sysdoc |
| `preview_criados` | int | A criar |
| `preview_atualizados` | int | Com divergências |
| `preview_obitos` | int | Óbitos a processar |
| `preview_sem_alteracao` | int | Sem divergência (apenas contagem) |
| `result_criados` | int nullable | Criados após aplicar |
| `result_atualizados` | int nullable | Atualizados após aplicar |
| `result_obitos` | int nullable | Óbitos processados |
| `result_erros` | int nullable | Erros na aplicação |
| `iniciado_por` | FK users.id | |
| `aplicado_por` | FK users.id nullable | |
| `iniciado_em` | timestamp | |
| `analisado_em` | timestamp nullable | |
| `aplicado_em` | timestamp nullable | |
| `erro_mensagem` | text nullable | |

### `sincronizacao_itens`

| Coluna | Tipo | Descrição |
|---|---|---|
| `id` | PK | |
| `sincronizacao_id` | FK cascade delete | |
| `acao` | enum | `criar`, `atualizar`, `obito` |
| `cpf` | string nullable | |
| `cns` | string nullable | |
| `nome_esus` | string | |
| `client_id` | int nullable | Nulo se ação = criar |
| `payload` | JSON | Campos a alterar com valor anterior e novo |
| `aplicado` | boolean default false | |
| `erro` | string nullable | |

**Exemplo de `payload`:**
```json
{
  "nome": { "de": "João Silva", "para": "João da Silva" },
  "telefone": { "de": "11999999999", "para": "11888888888" },
  "endereco": {
    "de": { "street": "Rua A", "number": "10" },
    "para": { "street": "Rua B", "number": "20" }
  }
}
```

---

## Backend

### Rotas

```php
// Protegidas por auth:sanctum + can:conformidade-cidadao-sincronizar
Route::prefix('conformidade-cidadao')->group(function () {
    Route::post('analisar', [ConformidadeCidadaoController::class, 'analisar']);
    Route::get('status/{job_id}', [ConformidadeCidadaoController::class, 'status']);
    Route::post('aplicar/{job_id}', [ConformidadeCidadaoController::class, 'aplicar']);
    Route::get('historico', [ConformidadeCidadaoController::class, 'historico']);
});
```

### `ConformidadeCidadaoController`

- **`analisar()`** — valida ausência de sync em andamento (HTTP 409 se existir); cria registro `sincronizacoes_cidadao` com status `pending`; dispara `SincronizacaoCidadaoJob`; retorna `{ job_id }`
- **`status()`** — retorna o registro com contagens e, se `preview_ready`, itens paginados de `sincronizacao_itens`
- **`aplicar()`** — valida que status é `preview_ready`; dispara `AplicarSincronizacaoJob`; retorna imediatamente
- **`historico()`** — lista `sincronizacoes_cidadao` paginado (sem itens)

### `ConformidadeCidadaoService`

**`analisar(SincronizacaoCidadao $sync)`:**
1. Carrega todos os cidadãos ativos do e-SUS em chunks de 500 (`tb_fat_cidadao_pec` + `tb_fat_cad_individual` para flag de óbito)
2. Carrega todos os `clients` do Sysdoc com `addresses` (eager load)
3. Indexa Sysdoc por CPF e por CNS (dois arrays em memória)
4. Para cada cidadão e-SUS:
   - Tenta match por CPF → fallback CNS
   - **Match encontrado:** se data de atualização do e-SUS for mais recente que `clients.updated_at`, detecta campos divergentes → gera item `atualizar` ou `obito`
   - **Sem match:** gera item `criar`
5. Persiste itens em lote (insert em chunks de 200)
6. Atualiza contagens e muda status para `preview_ready`

**`aplicar(SincronizacaoCidadao $sync)`:**
1. Processa itens em chunks de 100 dentro de transaction por chunk
2. **`atualizar`** → atualiza `clients` + `addresses` com os campos do payload
3. **`obito`** → `clients.active = 0`; para cada queue aberta do cliente: `done = true`, `obs` recebe append de "Baixa automática devido ao óbito ocorrido em {dd/mm/YYYY}"
4. **`criar`** → insere `client` + `address` (se endereço disponível)
5. Marca cada item como `aplicado = true` ou registra `erro`
6. Atualiza contagens finais e muda status para `completed`

### Jobs Laravel

| Job | Chama | Timeout |
|---|---|---|
| `SincronizacaoCidadaoJob` | `service->analisar()` | 10 min |
| `AplicarSincronizacaoJob` | `service->aplicar()` | 5 min |

Ambos atualizam `status = failed` + `erro_mensagem` em caso de exceção não tratada.

---

## Campos Sincronizados

### Atualização de client existente
- `name` (de `tb_fat_cidadao_pec`)
- `born_date`
- `phone`
- `active = 0` se óbito

### Atualização de address
- `street`, `number`, `complement`, `zip_code`, `district`, `city`

### Criação de novo client
- `name`, `cpf`, `cns`, `born_date`, `phone`
- `sexo` → mapeado de `co_sexo` (M→MASCULINE, F→FEMININE, demais→INDETERMINATE); se ausente → INDETERMINATE
- Address criado apenas se `logradouro` estiver preenchido no e-SUS

---

## Frontend

**Arquivo:** `sysdoc_front/src/pages/conformidade-cidadao/index.jsx`  
**Serviço:** `sysdoc_front/src/services/conformidadeCidadaoService.js`

### Estados da Página

**1. Idle**
- Botão "Analisar agora" (desabilitado sem permissão ou com sync em andamento)
- Tabela de histórico de sincronizações anteriores

**2. Analisando**
- Spinner + "Comparando cidadãos com e-SUS PEC..."
- Polling a cada 3 segundos em `/status/{job_id}`

**3. Prévia Pronta**
- 3 cards: `N novos para criar` / `N com dados diferentes` / `N óbitos a processar`
- Tabela paginada de `sincronizacao_itens`:
  - Colunas: Ação (chip), Nome (e-SUS), CPF/CNS, Campos que mudam
- Botões: **"Aplicar alterações"** + **"Descartar"**

**4. Aplicando**
- Spinner + "Aplicando alterações no Sysdoc..."
- Polling a cada 3 segundos

**5. Concluído**
- Cards de resultado: criados / atualizados / óbitos / erros
- Alerta se `result_erros > 0`
- Botão "Nova sincronização"

Estado gerenciado localmente no componente (sem Redux slice).

---

## Tratamento de Erros e Casos de Borda

| Situação | Comportamento |
|---|---|
| Análise já em andamento | HTTP 409; frontend desabilita botão |
| e-SUS indisponível | Job falha; status = `failed`; frontend mostra erro com opção de retry |
| CPF e CNS ambos nulos no e-SUS | Cidadão ignorado; contado em `preview_sem_alteracao` |
| CPF duplicado no Sysdoc | Usa o client com maior `updated_at` |
| `nome` ou `born_date` ausentes no e-SUS (criação) | Item marcado como erro; client não criado |
| Endereço ausente no e-SUS (criação) | Client criado sem address (sem erro) |
| `st_faleceu = true` mas `dt_obito` nulo | Inativa client e filas; obs registra "data não informada" |
| Erro em item individual durante aplicação | Registrado em `sincronizacao_itens.erro`; lote continua; status final = `completed` com alerta |

---

## Permissão

A permissão `conformidade-cidadao-sincronizar` será adicionada à lista de capacidades configuráveis do `AccessProfileController`, seguindo o padrão já existente no sistema.

---

## Correções e Melhorias — Fase 2

### Bug 1 — Óbitos retornando zero

**Causa provável:** nenhuma das colunas candidatas (`st_faleceu`, `in_falecido`, `st_obito`) existe na instalação local do e-SUS, fazendo `resolveEsusCols()` retornar `null` para `st_faleceu` e, consequentemente, `isTruthy(null) = false` para todos os registros.

**Solução:**
- Executar `SELECT column_name FROM information_schema.columns WHERE table_name = 'tb_fat_cad_individual'` para listar as colunas reais do banco e-SUS local
- Expandir a lista de candidatas em `resolveEsusCols()` com os nomes encontrados
- Adicionar log de diagnóstico no início de `analisar()` reportando qual coluna foi resolvida para cada campo crítico

### Bug 2 — 671 itens com erro: visualização e persistência

Os erros individuais já são gravados em `sincronizacao_itens.erro`, mas não há forma de visualizá-los.

**Solução:**
- Novo endpoint `GET /api/conformidade-cidadao/erros/{job_id}` — lista itens onde `erro IS NOT NULL`, paginado
- Após sincronização com `result_erros > 0`, exibir na tela tabela dos itens com erro (nome, CPF, mensagem)
- Persistir um resumo dos primeiros erros em `sincronizacoes_cidadao.erro_mensagem` (ex.: primeiros 5 erros concatenados) para acesso rápido no histórico
- Botão "Exportar erros (PDF)" disponível quando `result_erros > 0`

### Bug 3 — Endereço e data de nascimento não sendo atualizados

**`born_date`:** se `clients.born_date` é `null` no Sysdoc e o e-SUS tem data, a comparação `$esusDate !== $sysdocDate` deve detectar a diferença (null ≠ string). Verificar se o bloqueio ocorre porque o e-SUS local não retorna a coluna `dt_nascimento`/`dt_nasc` — mesmo investigação de colunas do Bug 1.

**Endereço:** o join com `tb_fat_cad_domiciliar` só ocorre se `hasTable('tb_fat_cad_domiciliar') = true`. Se a tabela não existe na instalação local, endereços nunca são comparados. Adicionar diagnóstico e documentar limitação.

**Cidade padrão:** quando `municipio` do e-SUS for `null` ou vazio (endereço presente mas sem município), gravar `city = 'Ilicínea'` tanto na criação quanto na atualização.

### Feature — Sincronizar todos os campos da tabela `clients`

Mapear todos os campos da tabela `clients` do Sysdoc para colunas correspondentes no e-SUS PEC, incluindo campos ainda não sincronizados:

| Campo Sysdoc | Coluna candidata e-SUS | Observação |
|---|---|---|
| `raca_cor` | `co_raca_cor` / `tp_raca_cor` | Mapear código → string |
| `sexo` | `co_sexo` | Já implementado em criar; adicionar em atualizar |
| `st_falecido` | `st_faleceu` (mesmo fix do Bug 1) | Bool |
| `escolaridade` | `co_nivel_escolaridade` | Se existir |
| `nacionalidade` | `co_nacionalidade` | Se existir |

Adicionar esses campos em `buildDiffPayload()`, `buildCreatePayload()` e `aplicarAtualizar()`.
