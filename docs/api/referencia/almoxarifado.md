# Almoxarifado

Produtos, estoque, movimentações, requisições com aprovação, fornecedores e relatórios.

> Gerado a partir das rotas reais da aplicação (`php artisan route:list`). Autenticação: ver [Autenticação](../01-autenticacao.md). Cabeçalhos e convenções: ver [Convenções](../02-convencoes.md). O prefixo `/api` já está incluído nas URLs.

| Método | URL | Ação |
|---|---|---|
| `GET` | `/api/almoxarifado/catalogos/{type}` | Listar |
| `POST` | `/api/almoxarifado/catalogos/{type}` | Criar |
| `DELETE` | `/api/almoxarifado/catalogos/{type}/{id}` | Remover |
| `PUT` | `/api/almoxarifado/catalogos/{type}/{id}` | Atualizar |
| `GET` | `/api/almoxarifado/configuracoes` | Detalhar |
| `PUT` | `/api/almoxarifado/configuracoes` | Atualizar |
| `GET` | `/api/almoxarifado/estoque` | Listar |
| `POST` | `/api/almoxarifado/estoque/movimentar` | Movimentar |
| `GET` | `/api/almoxarifado/movimentacoes` | Listar |
| `GET` | `/api/almoxarifado/produtos` | Listar |
| `POST` | `/api/almoxarifado/produtos` | Criar |
| `GET` | `/api/almoxarifado/produtos/opcoes` | Options |
| `DELETE` | `/api/almoxarifado/produtos/{id}` | Remover |
| `GET` | `/api/almoxarifado/produtos/{id}` | Detalhar |
| `PUT` | `/api/almoxarifado/produtos/{id}` | Atualizar |
| `GET` | `/api/almoxarifado/requisicoes` | Listar |
| `POST` | `/api/almoxarifado/requisicoes` | Criar |
| `GET` | `/api/almoxarifado/requisicoes/{id}` | Detalhar |
| `GET` | `/api/almoxarifado/requisicoes/{id}/pdf` | Download Pdf |
| `PATCH` | `/api/almoxarifado/requisicoes/{id}/status` | Update Status |

---

## `GET /api/almoxarifado/catalogos/{type}`

**Listar** · controller `AlmoxarifadoCatalogController@index`

- **Acesso:** Token Sanctum; página em Perfis: `/almoxarifado*`
- **Parâmetros de rota:** `type`

## `POST /api/almoxarifado/catalogos/{type}`

**Criar** · controller `AlmoxarifadoCatalogController@store`

- **Acesso:** Token Sanctum; página em Perfis: `/almoxarifado*`; página em Perfis: `/almoxarifado/categorias,/almoxarifado/especies,/almoxarifado/unidades-medida,/almoxarifado/fornecedores,/almoxarifado/localizacoes,/almoxarifado/secretarias`
- **Parâmetros de rota:** `type`
- **Corpo/validação** (feita no controller):

| Campo | Regras |
|---|---|
| `secretaria_id` | `EscopoAlmoxarifado::regraCampo()` |


## `DELETE /api/almoxarifado/catalogos/{type}/{id}`

**Remover** · controller `AlmoxarifadoCatalogController@destroy`

- **Acesso:** Token Sanctum; página em Perfis: `/almoxarifado*`; página em Perfis: `/almoxarifado/categorias,/almoxarifado/especies,/almoxarifado/unidades-medida,/almoxarifado/fornecedores,/almoxarifado/localizacoes,/almoxarifado/secretarias`
- **Parâmetros de rota:** `type`, `id`

## `PUT /api/almoxarifado/catalogos/{type}/{id}`

**Atualizar** · controller `AlmoxarifadoCatalogController@update`

- **Acesso:** Token Sanctum; página em Perfis: `/almoxarifado*`; página em Perfis: `/almoxarifado/categorias,/almoxarifado/especies,/almoxarifado/unidades-medida,/almoxarifado/fornecedores,/almoxarifado/localizacoes,/almoxarifado/secretarias`
- **Parâmetros de rota:** `type`, `id`

## `GET /api/almoxarifado/configuracoes`

**Detalhar** · controller `AlmoxarifadoConfigController@show`

- **Acesso:** Token Sanctum; página em Perfis: `/almoxarifado*`
- **Corpo/validação** (`ShowAlmoxarifadoConfigRequest`):

| Campo | Regras |
|---|---|
| `secretaria_id` | `nullable` |


## `PUT /api/almoxarifado/configuracoes`

**Atualizar** · controller `AlmoxarifadoConfigController@update`

- **Acesso:** Token Sanctum; página em Perfis: `/almoxarifado*`; página em Perfis: `/almoxarifado/configuracoes`
- **Corpo/validação** (`UpdateAlmoxarifadoConfigRequest`):

| Campo | Regras |
|---|---|
| `secretaria_id` | `nullable` |
| `permitir_saida_sem_saldo` | `boolean` |
| `permitir_transferencia_entre_secretarias` | `boolean` |
| `exigir_justificativa_saida` | `boolean` |
| `exigir_localizacao_produto` | `boolean` |
| `notificar_estoque_minimo` | `boolean` |
| `estoque_minimo_alerta_percentual` | `nullable \| integer \| min:1 \| max:100` |
| `permite_produto_sem_validade` | `boolean` |
| `observacoes` | `nullable \| string` |


## `GET /api/almoxarifado/estoque`

**Listar** · controller `AlmoxarifadoEstoqueController@index`

- **Acesso:** Token Sanctum; página em Perfis: `/almoxarifado*`
- **Outros parâmetros lidos pelo controller:** `search`, `secretaria_id`, `produto_id`, `abaixo_minimo`, `per_page`, `page`

## `POST /api/almoxarifado/estoque/movimentar`

**Movimentar** · controller `AlmoxarifadoEstoqueController@movimentar`

- **Acesso:** Token Sanctum; página em Perfis: `/almoxarifado*`; página em Perfis: `/almoxarifado/estoque`
- **Corpo/validação** (`MovimentarAlmoxarifadoEstoqueRequest`; as regras dependem do contexto, texto copiado do código):

| Campo | Regras |
|---|---|
| `almoxarifado_produto_id` | `'required', 'integer', EscopoAlmoxarifado::existe('almoxarifado_produtos', $sid)` |
| `almoxarifado_secretaria_id` | `'nullable', 'integer', EscopoAlmoxarifado::existe('almoxarifado_secretarias', $sid)` |
| `tipo` | `required \| in:entrada,saida,ajuste,transferencia` |
| `quantidade` | `required \| numeric \| min:0.001` |
| `motivo` | `required \| string \| max:150` |
| `observacao` | `nullable \| string` |
| `secretaria_destino_id` | `'nullable', 'integer', EscopoAlmoxarifado::existe('almoxarifado_secretarias', $sid)` |


## `GET /api/almoxarifado/movimentacoes`

**Listar** · controller `AlmoxarifadoMovimentacaoController@index`

- **Acesso:** Token Sanctum; página em Perfis: `/almoxarifado*`
- **Outros parâmetros lidos pelo controller:** `search`, `tipo`, `per_page`, `page`

## `GET /api/almoxarifado/produtos`

**Listar** · controller `AlmoxarifadoProdutoController@index`

- **Acesso:** Token Sanctum; página em Perfis: `/almoxarifado*`
- **Outros parâmetros lidos pelo controller:** `search`, `categoria_id`, `ativo`, `per_page`, `page`

## `POST /api/almoxarifado/produtos`

**Criar** · controller `AlmoxarifadoProdutoController@store`

- **Acesso:** Token Sanctum; página em Perfis: `/almoxarifado*`; página em Perfis: `/almoxarifado/produtos`
- **Corpo/validação** (`StoreAlmoxarifadoProdutoRequest`; as regras dependem do contexto, texto copiado do código):

| Campo | Regras |
|---|---|
| `secretaria_id` | `EscopoAlmoxarifado::regraCampo()` |
| `nome` | `required \| string \| max:150` |
| `descricao` | `nullable \| string` |
| `codigo_interno` | `'nullable', 'string', 'max:60', EscopoAlmoxarifado::unico('almoxarifado_produtos', 'codigo_interno', $sid)` |
| `codigo_barras` | `nullable \| string \| max:80` |
| `qr_code` | `nullable \| string \| max:255` |
| `almoxarifado_categoria_id` | `'nullable', 'integer', EscopoAlmoxarifado::existe('almoxarifado_categorias', $sid)` |
| `almoxarifado_especie_id` | `'nullable', 'integer', EscopoAlmoxarifado::existe('almoxarifado_especies', $sid)` |
| `almoxarifado_unidade_medida_id` | `'nullable', 'integer', EscopoAlmoxarifado::existe('almoxarifado_unidades_medida', $sid)` |
| `almoxarifado_fornecedor_id` | `'nullable', 'integer', EscopoAlmoxarifado::existe('almoxarifado_fornecedores', $sid)` |
| `almoxarifado_localizacao_id` | `'nullable', 'integer', EscopoAlmoxarifado::existe('almoxarifado_localizacoes', $sid)` |
| `marca` | `nullable \| string \| max:120` |
| `modelo` | `nullable \| string \| max:120` |
| `fabricante` | `nullable \| string \| max:120` |
| `numero_serie` | `nullable \| string \| max:120` |
| `lote` | `nullable \| string \| max:80` |
| `validade` | `nullable \| date` |
| `estoque_minimo` | `nullable \| numeric \| min:0` |
| `estoque_maximo` | `nullable \| numeric \| min:0` |
| `almoxarifado` | `nullable \| string \| max:120` |
| `sala` | `nullable \| string \| max:80` |
| `corredor` | `nullable \| string \| max:80` |
| `estante` | `nullable \| string \| max:80` |
| `prateleira` | `nullable \| string \| max:80` |
| `gaveta` | `nullable \| string \| max:80` |
| `caixa` | `nullable \| string \| max:80` |
| `posicao` | `nullable \| string \| max:80` |
| `observacao_localizacao` | `nullable \| string` |
| `imagem_url` | `nullable \| string \| max:255` |
| `observacoes` | `nullable \| string` |
| `ativo` | `sometimes \| boolean` |


## `GET /api/almoxarifado/produtos/opcoes`

**Options** · controller `AlmoxarifadoProdutoController@options`

- **Acesso:** Token Sanctum; página em Perfis: `/almoxarifado*`

## `DELETE /api/almoxarifado/produtos/{id}`

**Remover** · controller `AlmoxarifadoProdutoController@destroy`

- **Acesso:** Token Sanctum; página em Perfis: `/almoxarifado*`; página em Perfis: `/almoxarifado/produtos`
- **Parâmetros de rota:** `id`

## `GET /api/almoxarifado/produtos/{id}`

**Detalhar** · controller `AlmoxarifadoProdutoController@show`

- **Acesso:** Token Sanctum; página em Perfis: `/almoxarifado*`
- **Parâmetros de rota:** `id`

## `PUT /api/almoxarifado/produtos/{id}`

**Atualizar** · controller `AlmoxarifadoProdutoController@update`

- **Acesso:** Token Sanctum; página em Perfis: `/almoxarifado*`; página em Perfis: `/almoxarifado/produtos`
- **Parâmetros de rota:** `id`
- **Corpo/validação** (`UpdateAlmoxarifadoProdutoRequest`; as regras dependem do contexto, texto copiado do código):

| Campo | Regras |
|---|---|
| `nome` | `sometimes \| required \| string \| max:150` |
| `descricao` | `nullable \| string` |
| `codigo_interno` | `'sometimes', 'nullable', 'string', 'max:60', EscopoAlmoxarifado::unico('almoxarifado_produtos', 'codigo_interno', $sid)->ignore($this->route('id'))` |
| `codigo_barras` | `nullable \| string \| max:80` |
| `qr_code` | `nullable \| string \| max:255` |
| `almoxarifado_categoria_id` | `'nullable', 'integer', EscopoAlmoxarifado::existe('almoxarifado_categorias', $sid)` |
| `almoxarifado_especie_id` | `'nullable', 'integer', EscopoAlmoxarifado::existe('almoxarifado_especies', $sid)` |
| `almoxarifado_unidade_medida_id` | `'nullable', 'integer', EscopoAlmoxarifado::existe('almoxarifado_unidades_medida', $sid)` |
| `almoxarifado_fornecedor_id` | `'nullable', 'integer', EscopoAlmoxarifado::existe('almoxarifado_fornecedores', $sid)` |
| `almoxarifado_localizacao_id` | `'nullable', 'integer', EscopoAlmoxarifado::existe('almoxarifado_localizacoes', $sid)` |
| `marca` | `nullable \| string \| max:120` |
| `modelo` | `nullable \| string \| max:120` |
| `fabricante` | `nullable \| string \| max:120` |
| `numero_serie` | `nullable \| string \| max:120` |
| `lote` | `nullable \| string \| max:80` |
| `validade` | `nullable \| date` |
| `estoque_minimo` | `nullable \| numeric \| min:0` |
| `estoque_maximo` | `nullable \| numeric \| min:0` |
| `almoxarifado` | `nullable \| string \| max:120` |
| `sala` | `nullable \| string \| max:80` |
| `corredor` | `nullable \| string \| max:80` |
| `estante` | `nullable \| string \| max:80` |
| `prateleira` | `nullable \| string \| max:80` |
| `gaveta` | `nullable \| string \| max:80` |
| `caixa` | `nullable \| string \| max:80` |
| `posicao` | `nullable \| string \| max:80` |
| `observacao_localizacao` | `nullable \| string` |
| `imagem_url` | `nullable \| string \| max:255` |
| `observacoes` | `nullable \| string` |
| `ativo` | `sometimes \| boolean` |


## `GET /api/almoxarifado/requisicoes`

**Listar** · controller `AlmoxarifadoRequisicaoController@index`

- **Acesso:** Token Sanctum; página em Perfis: `/almoxarifado*`
- **Outros parâmetros lidos pelo controller:** `search`, `status`, `per_page`, `page`

## `POST /api/almoxarifado/requisicoes`

**Criar** · controller `AlmoxarifadoRequisicaoController@store`

- **Acesso:** Token Sanctum; página em Perfis: `/almoxarifado*`; permissão do almoxarifado
- **Corpo/validação** (`StoreAlmoxarifadoRequisicaoRequest`; as regras dependem do contexto, texto copiado do código):

| Campo | Regras |
|---|---|
| `secretaria_id` | `EscopoAlmoxarifado::regraCampo()` |
| `almoxarifado_secretaria_id` | `'required', 'integer', EscopoAlmoxarifado::existe('almoxarifado_secretarias', $sid)` |
| `justificativa` | `nullable \| string` |
| `observacoes` | `nullable \| string` |
| `itens` | `required \| array \| min:1` |
| `itens.*.almoxarifado_produto_id` | `'required', 'integer', EscopoAlmoxarifado::existe('almoxarifado_produtos', $sid)` |
| `itens.*.quantidade_solicitada` | `required \| numeric \| min:0.001` |
| `itens.*.observacao` | `nullable \| string` |


## `GET /api/almoxarifado/requisicoes/{id}`

**Detalhar** · controller `AlmoxarifadoRequisicaoController@show`

- **Acesso:** Token Sanctum; página em Perfis: `/almoxarifado*`
- **Parâmetros de rota:** `id`

## `GET /api/almoxarifado/requisicoes/{id}/pdf`

**Download Pdf** · controller `AlmoxarifadoRequisicaoController@downloadPdf`

- **Acesso:** Token Sanctum; página em Perfis: `/almoxarifado*`
- **Parâmetros de rota:** `id`

## `PATCH /api/almoxarifado/requisicoes/{id}/status`

**Update Status** · controller `AlmoxarifadoRequisicaoController@updateStatus`

- **Acesso:** Token Sanctum; página em Perfis: `/almoxarifado*`
- **Parâmetros de rota:** `id`
- **Corpo/validação** (`UpdateStatusAlmoxarifadoRequisicaoRequest`):

| Campo | Regras |
|---|---|
| `status` | `required \| in:recebida,em_analise,aprovada,recusada,em_separacao,em_processo_de_entrega,entregue,cancelada` |
| `observacao` | `nullable \| string` |


