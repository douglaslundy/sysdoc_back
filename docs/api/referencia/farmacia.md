# Farmácia

Catálogo de medicamentos, disponibilidade diária, aquisições mensais, importação de estoque e transparência.

> Gerado a partir das rotas reais da aplicação (`php artisan route:list`). Autenticação: ver [Autenticação](../01-autenticacao.md). Cabeçalhos e convenções: ver [Convenções](../02-convencoes.md). O prefixo `/api` já está incluído nas URLs.

| Método | URL | Ação |
|---|---|---|
| `GET` | `/api/medicines` | Listar |
| `POST` | `/api/medicines` | Criar |
| `DELETE` | `/api/medicines/{medicine}` | Remover |
| `GET` | `/api/medicines/{medicine}` | Detalhar |
| `PATCH` | `/api/medicines/{medicine}` | Atualizar |
| `PUT` | `/api/medicines/{medicine}` | Atualizar |
| `GET` | `/api/pharmacy/catalogs` | Listar |
| `GET` | `/api/pharmacy/catalogs/{type}` | Listar |
| `POST` | `/api/pharmacy/catalogs/{type}` | Criar |
| `DELETE` | `/api/pharmacy/catalogs/{type}/{id}` | Remover |
| `PUT` | `/api/pharmacy/catalogs/{type}/{id}` | Atualizar |
| `GET` | `/api/pharmacy/medicines/compliance` | Listar |
| `GET` | `/api/pharmacy/medicines/daily-statuses` | Listar |
| `POST` | `/api/pharmacy/medicines/daily-statuses` | Criar |
| `DELETE` | `/api/pharmacy/medicines/daily-statuses/{id}` | Remover |
| `PUT` | `/api/pharmacy/medicines/daily-statuses/{id}` | Atualizar |
| `GET` | `/api/pharmacy/medicines/monthly-acquisitions` | Listar |
| `POST` | `/api/pharmacy/medicines/monthly-acquisitions` | Criar |
| `DELETE` | `/api/pharmacy/medicines/monthly-acquisitions/{id}` | Remover |
| `PUT` | `/api/pharmacy/medicines/monthly-acquisitions/{id}` | Atualizar |
| `GET` | `/api/pharmacy/medicines/panel-settings` | Detalhar |
| `PUT` | `/api/pharmacy/medicines/panel-settings` | Atualizar |
| `GET` | `/api/pharmacy/medicines/publications` | Listar |
| `POST` | `/api/pharmacy/medicines/publications` | Criar |
| `DELETE` | `/api/pharmacy/medicines/publications/{id}` | Remover |
| `GET` | `/api/pharmacy/medicines/select` | Select |
| `POST` | `/api/pharmacy/medicines/stock-import` | Criar |
| `GET` | `/api/pharmacy/medicines/stock-import/current-stock` | Download Current Stock |

---

## `GET /api/medicines`

**Listar** · controller `MedicineItemController@index`

- **Acesso:** Token Sanctum; página em Perfis: `/pharmacy*`
- **Corpo/validação** (`ListMedicineItemsRequest`):

| Campo | Regras |
|---|---|
| `search` | `nullable \| string \| max:120` |
| `active` | `nullable \| boolean` |
| `is_free_distribution` | `nullable \| boolean` |
| `is_controlled` | `nullable \| boolean` |
| `is_judicial_order` | `nullable \| boolean` |
| `is_high_cost` | `nullable \| boolean` |
| `availability_status` | `nullable \| in:available,unavailable` |
| `per_page` | `nullable \| integer \| min:1 \| max:200` |


## `POST /api/medicines`

**Criar** · controller `MedicineItemController@store`

- **Acesso:** Token Sanctum; página em Perfis: `/pharmacy*`
- **Corpo/validação** (`StoreMedicineItemRequest`; as regras dependem do contexto, texto copiado do código):

| Campo | Regras |
|---|---|
| `secretaria_id` | `EscopoAlmoxarifado::regraCampo()` |
| `internal_code` | `'required', 'string', 'max:80', EscopoAlmoxarifado::unico('medicine_items', 'internal_code', $sid)` |
| `brand_name` | `nullable \| string \| max:255` |
| `active_ingredient` | `required \| string \| max:255` |
| `concentration` | `required \| string \| max:100` |
| `pharmaceutical_form` | `'required', 'string', 'max:120', Rule::exists('pharmacy_pharmaceutical_forms', 'name')->where('active', true)->where('secretaria_id', $sid)` |
| `presentation` | `'required', 'string', 'max:120', Rule::exists('pharmacy_presentations', 'name')->where('active', true)->where('secretaria_id', $sid)` |
| `unit_measure` | `'required', 'string', 'max:20', Rule::exists('pharmacy_units', 'code')->where('active', true)->where('secretaria_id', $sid)` |
| `ean_code` | `nullable \| string \| max:50` |
| `is_free_distribution` | `required \| boolean` |
| `is_controlled` | `sometimes \| boolean` |
| `is_judicial_order` | `sometimes \| boolean` |
| `is_high_cost` | `sometimes \| boolean` |
| `active` | `sometimes \| boolean` |
| `technical_notes` | `nullable \| string \| max:4000` |


## `DELETE /api/medicines/{medicine}`

**Remover** · controller `MedicineItemController@destroy`

- **Acesso:** Token Sanctum; página em Perfis: `/pharmacy*`
- **Parâmetros de rota:** `medicine`

## `GET /api/medicines/{medicine}`

**Detalhar** · controller `MedicineItemController@show`

- **Acesso:** Token Sanctum; página em Perfis: `/pharmacy*`
- **Parâmetros de rota:** `medicine`

## `PATCH /api/medicines/{medicine}`

**Atualizar** · controller `MedicineItemController@update`

- **Acesso:** Token Sanctum; página em Perfis: `/pharmacy*`
- **Parâmetros de rota:** `medicine`
- **Corpo/validação** (`UpdateMedicineItemRequest`):

| Campo | Regras |
|---|---|
| `internal_code` | `sometimes \| required \| string \| max:80 \| <Unique>` |
| `brand_name` | `nullable \| string \| max:255` |
| `active_ingredient` | `sometimes \| required \| string \| max:255` |
| `concentration` | `sometimes \| required \| string \| max:100` |
| `pharmaceutical_form` | `sometimes \| required \| string \| max:120 \| <Exists>` |
| `presentation` | `sometimes \| required \| string \| max:120 \| <Exists>` |
| `unit_measure` | `sometimes \| required \| string \| max:20 \| <Exists>` |
| `ean_code` | `nullable \| string \| max:50` |
| `is_free_distribution` | `sometimes \| boolean` |
| `is_controlled` | `sometimes \| boolean` |
| `is_judicial_order` | `sometimes \| boolean` |
| `is_high_cost` | `sometimes \| boolean` |
| `active` | `sometimes \| boolean` |
| `technical_notes` | `nullable \| string \| max:4000` |


## `PUT /api/medicines/{medicine}`

**Atualizar** · controller `MedicineItemController@update`

- **Acesso:** Token Sanctum; página em Perfis: `/pharmacy*`
- **Parâmetros de rota:** `medicine`
- **Corpo/validação** (`UpdateMedicineItemRequest`):

| Campo | Regras |
|---|---|
| `internal_code` | `sometimes \| required \| string \| max:80 \| <Unique>` |
| `brand_name` | `nullable \| string \| max:255` |
| `active_ingredient` | `sometimes \| required \| string \| max:255` |
| `concentration` | `sometimes \| required \| string \| max:100` |
| `pharmaceutical_form` | `sometimes \| required \| string \| max:120 \| <Exists>` |
| `presentation` | `sometimes \| required \| string \| max:120 \| <Exists>` |
| `unit_measure` | `sometimes \| required \| string \| max:20 \| <Exists>` |
| `ean_code` | `nullable \| string \| max:50` |
| `is_free_distribution` | `sometimes \| boolean` |
| `is_controlled` | `sometimes \| boolean` |
| `is_judicial_order` | `sometimes \| boolean` |
| `is_high_cost` | `sometimes \| boolean` |
| `active` | `sometimes \| boolean` |
| `technical_notes` | `nullable \| string \| max:4000` |


## `GET /api/pharmacy/catalogs`

**Listar** · controller `PharmacyCatalogController@index`

- **Acesso:** Token Sanctum; página em Perfis: `/pharmacy*` ou `/auditoria`

## `GET /api/pharmacy/catalogs/{type}`

**Listar** · controller `PharmacyCatalogAdminController@index`

- **Acesso:** Token Sanctum; somente administrador
- **Parâmetros de rota:** `type`

## `POST /api/pharmacy/catalogs/{type}`

**Criar** · controller `PharmacyCatalogAdminController@store`

- **Acesso:** Token Sanctum; somente administrador
- **Parâmetros de rota:** `type`
- **Corpo/validação** (`PharmacyCatalogUpsertRequest`):

| Campo | Regras |
|---|---|
| `type` | `required \| <In>` |


## `DELETE /api/pharmacy/catalogs/{type}/{id}`

**Remover** · controller `PharmacyCatalogAdminController@destroy`

- **Acesso:** Token Sanctum; somente administrador
- **Parâmetros de rota:** `type`, `id`

## `PUT /api/pharmacy/catalogs/{type}/{id}`

**Atualizar** · controller `PharmacyCatalogAdminController@update`

- **Acesso:** Token Sanctum; somente administrador
- **Parâmetros de rota:** `type`, `id`
- **Corpo/validação** (`PharmacyCatalogUpsertRequest`):

| Campo | Regras |
|---|---|
| `type` | `required \| <In>` |


## `GET /api/pharmacy/medicines/compliance`

**Listar** · controller `MedicineComplianceController@index`

- **Acesso:** Token Sanctum; página em Perfis: `/pharmacy*` ou `/auditoria`

## `GET /api/pharmacy/medicines/daily-statuses`

**Listar** · controller `MedicineDailyStatusController@index`

- **Acesso:** Token Sanctum; página em Perfis: `/pharmacy*` ou `/auditoria`
- **Corpo/validação** (`ListMedicineDailyStatusesRequest`):

| Campo | Regras |
|---|---|
| `reference_date` | `nullable \| date` |
| `availability_status` | `nullable \| <In>` |
| `medicine_item_id` | `nullable \| integer \| <Exists>` |
| `search` | `nullable \| string \| max:120` |
| `include_all` | `nullable \| boolean` |
| `per_page` | `nullable \| integer \| min:1 \| max:200` |


## `POST /api/pharmacy/medicines/daily-statuses`

**Criar** · controller `MedicineDailyStatusController@store`

- **Acesso:** Token Sanctum; página em Perfis: `/pharmacy*` ou `/auditoria`
- **Corpo/validação** (`UpsertMedicineDailyStatusRequest`; as regras dependem do contexto, texto copiado do código):

| Campo | Regras |
|---|---|
| `secretaria_id` | `EscopoAlmoxarifado::regraCampo()] : [])` |
| `medicine_item_id` | `'required', 'integer', EscopoAlmoxarifado::existe('medicine_items', $sid)` |
| `reference_date` | `required \| date` |
| `availability_status` | `'required', Rule::in(['available', 'unavailable'])` |
| `available_quantity` | `nullable \| numeric \| min:0` |
| `restock_forecast_date` | `nullable \| date` |
| `public_note` | `nullable \| string \| max:1000` |


## `DELETE /api/pharmacy/medicines/daily-statuses/{id}`

**Remover** · controller `MedicineDailyStatusController@destroy`

- **Acesso:** Token Sanctum; página em Perfis: `/pharmacy*` ou `/auditoria`
- **Parâmetros de rota:** `id`

## `PUT /api/pharmacy/medicines/daily-statuses/{id}`

**Atualizar** · controller `MedicineDailyStatusController@update`

- **Acesso:** Token Sanctum; página em Perfis: `/pharmacy*` ou `/auditoria`
- **Parâmetros de rota:** `id`
- **Corpo/validação** (`UpsertMedicineDailyStatusRequest`; as regras dependem do contexto, texto copiado do código):

| Campo | Regras |
|---|---|
| `secretaria_id` | `EscopoAlmoxarifado::regraCampo()] : [])` |
| `medicine_item_id` | `'required', 'integer', EscopoAlmoxarifado::existe('medicine_items', $sid)` |
| `reference_date` | `required \| date` |
| `availability_status` | `'required', Rule::in(['available', 'unavailable'])` |
| `available_quantity` | `nullable \| numeric \| min:0` |
| `restock_forecast_date` | `nullable \| date` |
| `public_note` | `nullable \| string \| max:1000` |


## `GET /api/pharmacy/medicines/monthly-acquisitions`

**Listar** · controller `MedicineMonthlyAcquisitionController@index`

- **Acesso:** Token Sanctum; página em Perfis: `/pharmacy*` ou `/auditoria`
- **Corpo/validação** (`ListMedicineMonthlyAcquisitionsRequest`):

| Campo | Regras |
|---|---|
| `reference_month` | `nullable \| regex:/^\d{4}\-(0[1-9] \| 1[0-2])$/` |
| `medicine_item_id` | `nullable \| integer \| <Exists>` |
| `per_page` | `nullable \| integer \| min:1 \| max:200` |


## `POST /api/pharmacy/medicines/monthly-acquisitions`

**Criar** · controller `MedicineMonthlyAcquisitionController@store`

- **Acesso:** Token Sanctum; página em Perfis: `/pharmacy*` ou `/auditoria`
- **Corpo/validação** (`UpsertMedicineMonthlyAcquisitionRequest`; as regras dependem do contexto, texto copiado do código):

| Campo | Regras |
|---|---|
| `secretaria_id` | `EscopoAlmoxarifado::regraCampo()] : [])` |
| `medicine_item_id` | `'required', 'integer', EscopoAlmoxarifado::existe('medicine_items', $sid)` |
| `reference_month` | `'required', 'regex:/^\d{4}\-(0[1-9] \| 1[0-2])$/'` |
| `acquired_quantity` | `required \| numeric \| min:0` |
| `unit_measure` | `'required', 'string', 'max:20', Rule::exists('pharmacy_units', 'code')->where('active', true)->where('secretaria_id', $sid)` |
| `source_document` | `'nullable', 'string', 'max:255', Rule::exists('pharmacy_acquisition_sources', 'name')->where('active', true)->where('secretaria_id', $sid)` |
| `note` | `nullable \| string \| max:1000` |


## `DELETE /api/pharmacy/medicines/monthly-acquisitions/{id}`

**Remover** · controller `MedicineMonthlyAcquisitionController@destroy`

- **Acesso:** Token Sanctum; página em Perfis: `/pharmacy*` ou `/auditoria`
- **Parâmetros de rota:** `id`

## `PUT /api/pharmacy/medicines/monthly-acquisitions/{id}`

**Atualizar** · controller `MedicineMonthlyAcquisitionController@update`

- **Acesso:** Token Sanctum; página em Perfis: `/pharmacy*` ou `/auditoria`
- **Parâmetros de rota:** `id`
- **Corpo/validação** (`UpsertMedicineMonthlyAcquisitionRequest`; as regras dependem do contexto, texto copiado do código):

| Campo | Regras |
|---|---|
| `secretaria_id` | `EscopoAlmoxarifado::regraCampo()] : [])` |
| `medicine_item_id` | `'required', 'integer', EscopoAlmoxarifado::existe('medicine_items', $sid)` |
| `reference_month` | `'required', 'regex:/^\d{4}\-(0[1-9] \| 1[0-2])$/'` |
| `acquired_quantity` | `required \| numeric \| min:0` |
| `unit_measure` | `'required', 'string', 'max:20', Rule::exists('pharmacy_units', 'code')->where('active', true)->where('secretaria_id', $sid)` |
| `source_document` | `'nullable', 'string', 'max:255', Rule::exists('pharmacy_acquisition_sources', 'name')->where('active', true)->where('secretaria_id', $sid)` |
| `note` | `nullable \| string \| max:1000` |


## `GET /api/pharmacy/medicines/panel-settings`

**Detalhar** · controller `MedicinePanelSettingController@show`

- **Acesso:** Token Sanctum; página em Perfis: `/pharmacy*` ou `/auditoria`
- **Corpo/validação** (`ShowMedicinePanelSettingRequest`):

| Campo | Regras |
|---|---|
| `secretaria_id` | `nullable` |


## `PUT /api/pharmacy/medicines/panel-settings`

**Atualizar** · controller `MedicinePanelSettingController@update`

- **Acesso:** Token Sanctum; página em Perfis: `/pharmacy*` ou `/auditoria`
- **Corpo/validação** (`UpdateMedicinePanelSettingRequest`):

| Campo | Regras |
|---|---|
| `secretaria_id` | `nullable` |
| `filter_is_free_distribution` | `required \| boolean` |
| `filter_is_controlled` | `required \| boolean` |
| `filter_is_judicial_order` | `required \| boolean` |
| `filter_is_high_cost` | `required \| boolean` |
| `filter_active` | `required \| boolean` |
| `filter_show_all` | `required \| boolean` |


## `GET /api/pharmacy/medicines/publications`

**Listar** · controller `MedicinePublicationController@index`

- **Acesso:** Token Sanctum; página em Perfis: `/pharmacy*` ou `/auditoria`
- **Corpo/validação** (`ListMedicinePublicationsRequest`):

| Campo | Regras |
|---|---|
| `reference_type` | `nullable \| <In>` |
| `channel` | `nullable \| <In>` |
| `status` | `nullable \| <In>` |
| `per_page` | `nullable \| integer \| min:1 \| max:200` |


## `POST /api/pharmacy/medicines/publications`

**Criar** · controller `MedicinePublicationController@store`

- **Acesso:** Token Sanctum; página em Perfis: `/pharmacy*` ou `/auditoria`
- **Corpo/validação** (`StoreMedicinePublicationRequest`):

| Campo | Regras |
|---|---|
| `reference_type` | `required \| <In>` |
| `reference_id` | `required \| integer \| min:1` |
| `channel` | `required \| <In>` |
| `status` | `required \| <In>` |
| `payload_summary` | `nullable \| array` |
| `published_at` | `nullable \| date` |


## `DELETE /api/pharmacy/medicines/publications/{id}`

**Remover** · controller `MedicinePublicationController@destroy`

- **Acesso:** Token Sanctum; página em Perfis: `/pharmacy*` ou `/auditoria`
- **Parâmetros de rota:** `id`

## `GET /api/pharmacy/medicines/select`

**Select** · controller `MedicineItemController@select`

- **Acesso:** Token Sanctum; página em Perfis: `/pharmacy*` ou `/auditoria`
- **Corpo/validação** (`SelectMedicineItemsRequest`):

| Campo | Regras |
|---|---|
| `search` | `nullable \| string \| max:120` |
| `active` | `nullable \| boolean` |
| `limit` | `nullable \| integer \| min:1 \| max:500` |


## `POST /api/pharmacy/medicines/stock-import`

**Criar** · controller `MedicineStockImportController@store`

- **Acesso:** Token Sanctum; página em Perfis: `/pharmacy*` ou `/auditoria`
- **Corpo/validação** (`ImportMedicineStockCsvRequest`):

| Campo | Regras |
|---|---|
| `file` | `required \| file \| mimes:csv,txt \| max:30720` |


## `GET /api/pharmacy/medicines/stock-import/current-stock`

**Download Current Stock** · controller `MedicineStockImportController@downloadCurrentStock`

- **Acesso:** Token Sanctum; página em Perfis: `/pharmacy*` ou `/auditoria`

