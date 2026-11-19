# Laboratório

Categorias, exames, campos e referências, pedidos, resultados, laudos em PDF, agenda de coleta e médicos solicitantes.

> Gerado a partir das rotas reais da aplicação (`php artisan route:list`). Autenticação: ver [Autenticação](../01-autenticacao.md). Cabeçalhos e convenções: ver [Convenções](../02-convencoes.md). O prefixo `/api` já está incluído nas URLs.

| Método | URL | Ação |
|---|---|---|
| `GET` | `/api/laboratorio/agenda` | Listar |
| `GET` | `/api/laboratorio/campos/{campo}/referencias` | Listar |
| `POST` | `/api/laboratorio/campos/{campo}/referencias` | Criar |
| `DELETE` | `/api/laboratorio/campos/{campo}/referencias/{referencia}` | Remover |
| `PUT` | `/api/laboratorio/campos/{campo}/referencias/{referencia}` | Atualizar |
| `GET` | `/api/laboratorio/categorias` | Listar |
| `POST` | `/api/laboratorio/categorias` | Criar |
| `DELETE` | `/api/laboratorio/categorias/{categoria}` | Remover |
| `PUT` | `/api/laboratorio/categorias/{categoria}` | Atualizar |
| `GET` | `/api/laboratorio/config` | Detalhar |
| `PUT` | `/api/laboratorio/config` | Atualizar |
| `GET` | `/api/laboratorio/exames` | Listar |
| `POST` | `/api/laboratorio/exames` | Criar |
| `DELETE` | `/api/laboratorio/exames/{exame}` | Remover |
| `GET` | `/api/laboratorio/exames/{exame}` | Detalhar |
| `PATCH` | `/api/laboratorio/exames/{exame}` | Atualizar |
| `PUT` | `/api/laboratorio/exames/{exame}` | Atualizar |
| `GET` | `/api/laboratorio/exames/{exame}/campos` | Listar |
| `POST` | `/api/laboratorio/exames/{exame}/campos` | Criar |
| `PATCH` | `/api/laboratorio/exames/{exame}/campos/reordenar` | Reordenar |
| `DELETE` | `/api/laboratorio/exames/{exame}/campos/{campo}` | Remover |
| `PUT` | `/api/laboratorio/exames/{exame}/campos/{campo}` | Atualizar |
| `GET` | `/api/laboratorio/medicos` | Listar |
| `POST` | `/api/laboratorio/medicos` | Criar |
| `DELETE` | `/api/laboratorio/medicos/{medico}` | Remover |
| `PUT` | `/api/laboratorio/medicos/{medico}` | Atualizar |
| `GET` | `/api/laboratorio/pedidos` | Listar |
| `POST` | `/api/laboratorio/pedidos` | Criar |
| `DELETE` | `/api/laboratorio/pedidos/{pedido}` | Remover |
| `GET` | `/api/laboratorio/pedidos/{pedido}` | Detalhar |
| `PATCH` | `/api/laboratorio/pedidos/{pedido}` | Atualizar |
| `PUT` | `/api/laboratorio/pedidos/{pedido}` | Atualizar |
| `POST` | `/api/laboratorio/pedidos/{pedido}/resultado` | Criar |
| `PATCH` | `/api/laboratorio/pedidos/{pedido}/status` | Atualizar Status |
| `GET` | `/api/laboratorio/resultados/{resultado}` | Detalhar |
| `POST` | `/api/laboratorio/resultados/{resultado}/campos` | Salvar Campos |
| `POST` | `/api/laboratorio/resultados/{resultado}/liberar` | Liberar |
| `GET` | `/api/laboratorio/resultados/{resultado}/pdf` | Download Pdf |

---

## `GET /api/laboratorio/agenda`

**Listar** · controller `AgendaColetaController@index`

- **Acesso:** Token Sanctum; página em Perfis: `/laboratorio*` ou `/auditoria`
- **Corpo/validação** (`IndexAgendaColetaRequest`):

| Campo | Regras |
|---|---|
| `data` | `nullable \| date` |
| `inicio` | `nullable \| date` |
| `fim` | `nullable \| date` |


## `GET /api/laboratorio/campos/{campo}/referencias`

**Listar** · controller `CampoReferenciaController@index`

- **Acesso:** Token Sanctum; página em Perfis: `/laboratorio*` ou `/auditoria`
- **Parâmetros de rota:** `campo`

## `POST /api/laboratorio/campos/{campo}/referencias`

**Criar** · controller `CampoReferenciaController@store`

- **Acesso:** Token Sanctum; página em Perfis: `/laboratorio*` ou `/auditoria`
- **Parâmetros de rota:** `campo`
- **Corpo/validação** (`StoreCampoReferenciaRequest`; as regras dependem do contexto, texto copiado do código):

| Campo | Regras |
|---|---|
| `perfil` | `[` |
| `sexo` | `nullable \| in:M,F` |
| `idade_min_dias` | `nullable \| integer \| min:0` |
| `idade_max_dias` | `nullable \| integer \| min:0 \| gte:idade_min_dias` |
| `valor_min` | `nullable \| numeric` |
| `valor_max` | `nullable \| numeric \| gte:valor_min` |
| `valor_texto` | `nullable \| string \| max:200` |
| `descricao` | `nullable \| string \| max:200` |

- **Outros parâmetros lidos pelo controller:** `perfil`, `valor_min`, `valor_max`, `valor_texto`, `descricao`

## `DELETE /api/laboratorio/campos/{campo}/referencias/{referencia}`

**Remover** · controller `CampoReferenciaController@destroy`

- **Acesso:** Token Sanctum; página em Perfis: `/laboratorio*` ou `/auditoria`
- **Parâmetros de rota:** `campo`, `referencia`

## `PUT /api/laboratorio/campos/{campo}/referencias/{referencia}`

**Atualizar** · controller `CampoReferenciaController@update`

- **Acesso:** Token Sanctum; página em Perfis: `/laboratorio*` ou `/auditoria`
- **Parâmetros de rota:** `campo`, `referencia`
- **Corpo/validação** (`StoreCampoReferenciaRequest`; as regras dependem do contexto, texto copiado do código):

| Campo | Regras |
|---|---|
| `perfil` | `[` |
| `sexo` | `nullable \| in:M,F` |
| `idade_min_dias` | `nullable \| integer \| min:0` |
| `idade_max_dias` | `nullable \| integer \| min:0 \| gte:idade_min_dias` |
| `valor_min` | `nullable \| numeric` |
| `valor_max` | `nullable \| numeric \| gte:valor_min` |
| `valor_texto` | `nullable \| string \| max:200` |
| `descricao` | `nullable \| string \| max:200` |

- **Outros parâmetros lidos pelo controller:** `perfil`, `valor_min`, `valor_max`, `valor_texto`, `descricao`

## `GET /api/laboratorio/categorias`

**Listar** · controller `CategoriaExameController@index`

- **Acesso:** Token Sanctum; página em Perfis: `/laboratorio*` ou `/auditoria`
- **Outros parâmetros lidos pelo controller:** `ativo`, `busca`, `all`, `per_page`

## `POST /api/laboratorio/categorias`

**Criar** · controller `CategoriaExameController@store`

- **Acesso:** Token Sanctum; página em Perfis: `/laboratorio*` ou `/auditoria`
- **Corpo/validação** (`StoreCategoriaExameRequest`; as regras dependem do contexto, texto copiado do código):

| Campo | Regras |
|---|---|
| `secretaria_id` | `EscopoAlmoxarifado::regraCampo()] : [])` |
| `nome` | `[` |
| `ativo` | `boolean` |

- **Outros parâmetros lidos pelo controller:** `secretaria_id`, `nome`, `ativo`

## `DELETE /api/laboratorio/categorias/{categoria}`

**Remover** · controller `CategoriaExameController@destroy`

- **Acesso:** Token Sanctum; página em Perfis: `/laboratorio*` ou `/auditoria`
- **Parâmetros de rota:** `categoria`

## `PUT /api/laboratorio/categorias/{categoria}`

**Atualizar** · controller `CategoriaExameController@update`

- **Acesso:** Token Sanctum; página em Perfis: `/laboratorio*` ou `/auditoria`
- **Parâmetros de rota:** `categoria`
- **Corpo/validação** (`StoreCategoriaExameRequest`; as regras dependem do contexto, texto copiado do código):

| Campo | Regras |
|---|---|
| `secretaria_id` | `EscopoAlmoxarifado::regraCampo()] : [])` |
| `nome` | `[` |
| `ativo` | `boolean` |

- **Outros parâmetros lidos pelo controller:** `nome`, `ativo`

## `GET /api/laboratorio/config`

**Detalhar** · controller `LabConfigController@show`

- **Acesso:** Token Sanctum; página em Perfis: `/laboratorio*` ou `/auditoria`
- **Corpo/validação** (`ShowLabConfigRequest`):

| Campo | Regras |
|---|---|
| `secretaria_id` | `nullable` |


## `PUT /api/laboratorio/config`

**Atualizar** · controller `LabConfigController@update`

- **Acesso:** Token Sanctum; somente administrador
- **Corpo/validação** (`UpdateLabConfigRequest`):

| Campo | Regras |
|---|---|
| `secretaria_id` | `nullable` |
| `email_habilitado` | `boolean` |
| `nome_estabelecimento` | `nullable \| string \| max:255` |
| `razao_social` | `nullable \| string \| max:255` |
| `endereco_rua` | `nullable \| string \| max:255` |
| `endereco_numero` | `nullable \| string \| max:20` |
| `endereco_bairro` | `nullable \| string \| max:100` |
| `endereco_cep` | `nullable \| string \| max:8` |
| `telefone` | `nullable \| string \| max:20` |
| `cnpj` | `nullable \| string \| max:14` |
| `email_lab` | `nullable \| email \| max:255` |
| `rodape1` | `nullable \| string` |
| `rodape2` | `nullable \| string` |
| `imprimir_rascunho_exame` | `boolean` |


## `GET /api/laboratorio/exames`

**Listar** · controller `ExameController@index`

- **Acesso:** Token Sanctum; página em Perfis: `/laboratorio*` ou `/auditoria`
- **Outros parâmetros lidos pelo controller:** `ativo`, `busca`, `categoria_exame_id`, `per_page`

## `POST /api/laboratorio/exames`

**Criar** · controller `ExameController@store`

- **Acesso:** Token Sanctum; página em Perfis: `/laboratorio*` ou `/auditoria`
- **Corpo/validação** (`StoreExameRequest`; as regras dependem do contexto, texto copiado do código):

| Campo | Regras |
|---|---|
| `secretaria_id` | `EscopoAlmoxarifado::regraCampo()] : [])` |
| `nome` | `required \| string \| max:100` |
| `codigo` | `required \| string \| max:30` |
| `categoria_exame_id` | `'nullable', EscopoAlmoxarifado::existe('categoria_exames', $sid)` |
| `descricao` | `nullable \| string` |
| `ativo` | `boolean` |

- **Outros parâmetros lidos pelo controller:** `secretaria_id`, `nome`, `codigo`, `categoria_exame_id`, `descricao`, `ativo`

## `DELETE /api/laboratorio/exames/{exame}`

**Remover** · controller `ExameController@destroy`

- **Acesso:** Token Sanctum; página em Perfis: `/laboratorio*` ou `/auditoria`
- **Parâmetros de rota:** `exame`

## `GET /api/laboratorio/exames/{exame}`

**Detalhar** · controller `ExameController@show`

- **Acesso:** Token Sanctum; página em Perfis: `/laboratorio*` ou `/auditoria`
- **Parâmetros de rota:** `exame`

## `PATCH /api/laboratorio/exames/{exame}`

**Atualizar** · controller `ExameController@update`

- **Acesso:** Token Sanctum; página em Perfis: `/laboratorio*` ou `/auditoria`
- **Parâmetros de rota:** `exame`
- **Corpo/validação** (`StoreExameRequest`; as regras dependem do contexto, texto copiado do código):

| Campo | Regras |
|---|---|
| `secretaria_id` | `EscopoAlmoxarifado::regraCampo()] : [])` |
| `nome` | `required \| string \| max:100` |
| `codigo` | `required \| string \| max:30` |
| `categoria_exame_id` | `'nullable', EscopoAlmoxarifado::existe('categoria_exames', $sid)` |
| `descricao` | `nullable \| string` |
| `ativo` | `boolean` |

- **Outros parâmetros lidos pelo controller:** `nome`, `codigo`, `categoria_exame_id`, `descricao`, `ativo`

## `PUT /api/laboratorio/exames/{exame}`

**Atualizar** · controller `ExameController@update`

- **Acesso:** Token Sanctum; página em Perfis: `/laboratorio*` ou `/auditoria`
- **Parâmetros de rota:** `exame`
- **Corpo/validação** (`StoreExameRequest`; as regras dependem do contexto, texto copiado do código):

| Campo | Regras |
|---|---|
| `secretaria_id` | `EscopoAlmoxarifado::regraCampo()] : [])` |
| `nome` | `required \| string \| max:100` |
| `codigo` | `required \| string \| max:30` |
| `categoria_exame_id` | `'nullable', EscopoAlmoxarifado::existe('categoria_exames', $sid)` |
| `descricao` | `nullable \| string` |
| `ativo` | `boolean` |

- **Outros parâmetros lidos pelo controller:** `nome`, `codigo`, `categoria_exame_id`, `descricao`, `ativo`

## `GET /api/laboratorio/exames/{exame}/campos`

**Listar** · controller `ExameCampoController@index`

- **Acesso:** Token Sanctum; página em Perfis: `/laboratorio*` ou `/auditoria`
- **Parâmetros de rota:** `exame`

## `POST /api/laboratorio/exames/{exame}/campos`

**Criar** · controller `ExameCampoController@store`

- **Acesso:** Token Sanctum; página em Perfis: `/laboratorio*` ou `/auditoria`
- **Parâmetros de rota:** `exame`
- **Corpo/validação** (`StoreExameCampoRequest`; as regras dependem do contexto, texto copiado do código):

| Campo | Regras |
|---|---|
| `nome` | `required \| string \| max:100` |
| `descricao` | `nullable \| string \| max:200` |
| `tipo_valor` | `required \| in:numerico,texto,booleano,selecao` |
| `unidade` | `nullable \| string \| max:30` |
| `opcoes_selecao` | `nullable \| array` |
| `opcoes_selecao.*` | `string` |
| `ordem` | `integer \| min:0` |
| `obrigatorio` | `boolean` |
| `ativo` | `boolean` |

- **Outros parâmetros lidos pelo controller:** `nome`, `descricao`, `tipo_valor`, `unidade`, `opcoes_selecao`, `ordem`, `obrigatorio`, `ativo`

## `PATCH /api/laboratorio/exames/{exame}/campos/reordenar`

**Reordenar** · controller `ExameCampoController@reordenar`

- **Acesso:** Token Sanctum; página em Perfis: `/laboratorio*` ou `/auditoria`
- **Parâmetros de rota:** `exame`
- **Corpo/validação** (`ReordenarExameCampoRequest`; as regras dependem do contexto, texto copiado do código):

| Campo | Regras |
|---|---|
| `ordem` | `required \| array` |
| `ordem.*` | `'integer', \App\Support\EscopoAlmoxarifado::existeCampoDeExame($sid)` |


## `DELETE /api/laboratorio/exames/{exame}/campos/{campo}`

**Remover** · controller `ExameCampoController@destroy`

- **Acesso:** Token Sanctum; página em Perfis: `/laboratorio*` ou `/auditoria`
- **Parâmetros de rota:** `exame`, `campo`

## `PUT /api/laboratorio/exames/{exame}/campos/{campo}`

**Atualizar** · controller `ExameCampoController@update`

- **Acesso:** Token Sanctum; página em Perfis: `/laboratorio*` ou `/auditoria`
- **Parâmetros de rota:** `exame`, `campo`
- **Corpo/validação** (`StoreExameCampoRequest`; as regras dependem do contexto, texto copiado do código):

| Campo | Regras |
|---|---|
| `nome` | `required \| string \| max:100` |
| `descricao` | `nullable \| string \| max:200` |
| `tipo_valor` | `required \| in:numerico,texto,booleano,selecao` |
| `unidade` | `nullable \| string \| max:30` |
| `opcoes_selecao` | `nullable \| array` |
| `opcoes_selecao.*` | `string` |
| `ordem` | `integer \| min:0` |
| `obrigatorio` | `boolean` |
| `ativo` | `boolean` |

- **Outros parâmetros lidos pelo controller:** `nome`, `descricao`, `tipo_valor`, `unidade`, `opcoes_selecao`, `obrigatorio`, `ativo`

## `GET /api/laboratorio/medicos`

**Listar** · controller `MedicoSolicitanteController@index`

- **Acesso:** Token Sanctum; página em Perfis: `/laboratorio*` ou `/auditoria`
- **Outros parâmetros lidos pelo controller:** `busca`, `ativo`, `all`, `per_page`

## `POST /api/laboratorio/medicos`

**Criar** · controller `MedicoSolicitanteController@store`

- **Acesso:** Token Sanctum; página em Perfis: `/laboratorio*` ou `/auditoria`
- **Corpo/validação** (`StoreMedicoSolicitanteRequest`; as regras dependem do contexto, texto copiado do código):

| Campo | Regras |
|---|---|
| `secretaria_id` | `EscopoAlmoxarifado::regraCampo()] : [])` |
| `nome` | `required \| string \| max:100` |
| `crm` | `[` |
| `uf_crm` | `nullable \| string \| size:2` |
| `especialidade` | `nullable \| string \| max:80` |
| `telefone` | `nullable \| string \| max:20` |
| `ativo` | `boolean` |


## `DELETE /api/laboratorio/medicos/{medico}`

**Remover** · controller `MedicoSolicitanteController@destroy`

- **Acesso:** Token Sanctum; página em Perfis: `/laboratorio*` ou `/auditoria`
- **Parâmetros de rota:** `medico`

## `PUT /api/laboratorio/medicos/{medico}`

**Atualizar** · controller `MedicoSolicitanteController@update`

- **Acesso:** Token Sanctum; página em Perfis: `/laboratorio*` ou `/auditoria`
- **Parâmetros de rota:** `medico`
- **Corpo/validação** (`StoreMedicoSolicitanteRequest`; as regras dependem do contexto, texto copiado do código):

| Campo | Regras |
|---|---|
| `secretaria_id` | `EscopoAlmoxarifado::regraCampo()] : [])` |
| `nome` | `required \| string \| max:100` |
| `crm` | `[` |
| `uf_crm` | `nullable \| string \| size:2` |
| `especialidade` | `nullable \| string \| max:80` |
| `telefone` | `nullable \| string \| max:20` |
| `ativo` | `boolean` |


## `GET /api/laboratorio/pedidos`

**Listar** · controller `PedidoExameController@index`

- **Acesso:** Token Sanctum; página em Perfis: `/laboratorio*` ou `/auditoria`
- **Outros parâmetros lidos pelo controller:** `status`, `client_id`, `data_de`, `data_ate`, `busca`, `per_page`

## `POST /api/laboratorio/pedidos`

**Criar** · controller `PedidoExameController@store`

- **Acesso:** Token Sanctum; página em Perfis: `/laboratorio*` ou `/auditoria`
- **Corpo/validação** (`StorePedidoExameRequest`; as regras dependem do contexto, texto copiado do código):

| Campo | Regras |
|---|---|
| `secretaria_id` | `EscopoAlmoxarifado::regraCampo()` |
| `client_id` | `required \| exists:clients,id` |
| `medico_solicitante_id` | `'nullable', EscopoAlmoxarifado::existe('medicos_solicitantes', $sid)` |
| `data_pedido` | `required \| date` |
| `data_coleta` | `nullable \| date` |
| `observacoes` | `nullable \| string` |
| `exames` | `required \| array \| min:1` |
| `exames.*` | `'required', 'integer', 'distinct', EscopoAlmoxarifado::existe('exames', $sid)` |

- **Outros parâmetros lidos pelo controller:** `secretaria_id`, `client_id`, `medico_solicitante_id`, `data_pedido`, `data_coleta`, `observacoes`, `exames`

## `DELETE /api/laboratorio/pedidos/{pedido}`

**Remover** · controller `PedidoExameController@destroy`

- **Acesso:** Token Sanctum; página em Perfis: `/laboratorio*` ou `/auditoria`
- **Parâmetros de rota:** `pedido`

## `GET /api/laboratorio/pedidos/{pedido}`

**Detalhar** · controller `PedidoExameController@show`

- **Acesso:** Token Sanctum; página em Perfis: `/laboratorio*` ou `/auditoria`
- **Parâmetros de rota:** `pedido`

## `PATCH /api/laboratorio/pedidos/{pedido}`

**Atualizar** · controller `PedidoExameController@update`

- **Acesso:** Token Sanctum; página em Perfis: `/laboratorio*` ou `/auditoria`
- **Parâmetros de rota:** `pedido`
- **Corpo/validação** (`UpdatePedidoExameRequest`):

| Campo | Regras |
|---|---|
| `medico_solicitante_id` | `nullable \| <Exists>` |
| `data_pedido` | `nullable \| date` |
| `data_coleta` | `nullable \| date` |
| `observacoes` | `nullable \| string \| max:1000` |
| `exames` | `nullable \| array \| min:1` |
| `exames.*` | `integer \| distinct \| <Exists>` |


## `PUT /api/laboratorio/pedidos/{pedido}`

**Atualizar** · controller `PedidoExameController@update`

- **Acesso:** Token Sanctum; página em Perfis: `/laboratorio*` ou `/auditoria`
- **Parâmetros de rota:** `pedido`
- **Corpo/validação** (`UpdatePedidoExameRequest`):

| Campo | Regras |
|---|---|
| `medico_solicitante_id` | `nullable \| <Exists>` |
| `data_pedido` | `nullable \| date` |
| `data_coleta` | `nullable \| date` |
| `observacoes` | `nullable \| string \| max:1000` |
| `exames` | `nullable \| array \| min:1` |
| `exames.*` | `integer \| distinct \| <Exists>` |


## `POST /api/laboratorio/pedidos/{pedido}/resultado`

**Criar** · controller `ResultadoExameController@store`

- **Acesso:** Token Sanctum; página em Perfis: `/laboratorio*` ou `/auditoria`
- **Parâmetros de rota:** `pedido`

## `PATCH /api/laboratorio/pedidos/{pedido}/status`

**Atualizar Status** · controller `PedidoExameController@atualizarStatus`

- **Acesso:** Token Sanctum; página em Perfis: `/laboratorio*` ou `/auditoria`
- **Parâmetros de rota:** `pedido`
- **Corpo/validação** (`AtualizarStatusPedidoExameRequest`):

| Campo | Regras |
|---|---|
| `status` | `required \| in:solicitado,coletado,em_analise,liberado,cancelado` |


## `GET /api/laboratorio/resultados/{resultado}`

**Detalhar** · controller `ResultadoExameController@show`

- **Acesso:** Token Sanctum; página em Perfis: `/laboratorio*` ou `/auditoria`
- **Parâmetros de rota:** `resultado`

## `POST /api/laboratorio/resultados/{resultado}/campos`

**Salvar Campos** · controller `ResultadoExameController@salvarCampos`

- **Acesso:** Token Sanctum; página em Perfis: `/laboratorio*` ou `/auditoria`
- **Parâmetros de rota:** `resultado`
- **Corpo/validação** (`SalvarCamposResultadoRequest`; as regras dependem do contexto, texto copiado do código):

| Campo | Regras |
|---|---|
| `campos` | `required \| array \| min:1` |
| `campos.*.exame_campo_id` | `'required', \App\Support\EscopoAlmoxarifado::existeCampoDeExame($sid)` |
| `campos.*.exame_id` | `'required', \App\Support\EscopoAlmoxarifado::existe('exames', $sid)` |
| `campos.*.valor_numerico` | `nullable \| numeric \| max:99999999999` |
| `campos.*.valor_texto` | `nullable \| string` |
| `campos.*.observacao` | `nullable \| string` |

- **Outros parâmetros lidos pelo controller:** `campos`

## `POST /api/laboratorio/resultados/{resultado}/liberar`

**Liberar** · controller `ResultadoExameController@liberar`

- **Acesso:** Token Sanctum; página em Perfis: `/laboratorio*` ou `/auditoria`
- **Parâmetros de rota:** `resultado`

## `GET /api/laboratorio/resultados/{resultado}/pdf`

**Download Pdf** · controller `ResultadoExameController@downloadPdf`

- **Acesso:** Token Sanctum; página em Perfis: `/laboratorio*` ou `/auditoria`
- **Parâmetros de rota:** `resultado`
- **Outros parâmetros lidos pelo controller:** `rascunho`

