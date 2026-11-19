# TFD e viagens

Viagens, agenda de viagens, pacientes e acompanhantes, veículos e rotas.

> Gerado a partir das rotas reais da aplicação (`php artisan route:list`). Autenticação: ver [Autenticação](../01-autenticacao.md). Cabeçalhos e convenções: ver [Convenções](../02-convencoes.md). O prefixo `/api` já está incluído nas URLs.

| Método | URL | Ação |
|---|---|---|
| `PATCH` | `/api/confirm-trip-client/{client_id}` | Confirm Trip Client |
| `GET` | `/api/routes` | Listar |
| `POST` | `/api/routes` | Criar |
| `DELETE` | `/api/routes/{route}` | Remover |
| `GET` | `/api/routes/{route}` | Detalhar |
| `PATCH` | `/api/routes/{route}` | Atualizar |
| `PUT` | `/api/routes/{route}` | Atualizar |
| `POST` | `/api/trip-clients` | Insert Trip Client |
| `DELETE` | `/api/trip-clients/{client_id}` | Delete Trip Client |
| `PUT` | `/api/trip-clients/{id}` | Edit Trip Client |
| `GET` | `/api/trip-schedule` | Listar |
| `POST` | `/api/trip-schedule` | Criar |
| `POST` | `/api/trip-schedule/send` | Send |
| `DELETE` | `/api/trip-schedule/{id}` | Remover |
| `PUT` | `/api/trip-schedule/{id}` | Atualizar |
| `GET` | `/api/trips` | Listar |
| `POST` | `/api/trips` | Criar |
| `GET` | `/api/trips/drivers-options` | Drivers |
| `DELETE` | `/api/trips/{trip}` | Remover |
| `GET` | `/api/trips/{trip}` | Detalhar |
| `PATCH` | `/api/trips/{trip}` | Atualizar |
| `PUT` | `/api/trips/{trip}` | Atualizar |
| `POST` | `/api/trips/{trip}/replicate` | Replicate |
| `PATCH` | `/api/unconfirm-trip-client/{client_id}` | Unconfirm Trip Client |
| `GET` | `/api/vehicles` | Listar |
| `POST` | `/api/vehicles` | Criar |
| `DELETE` | `/api/vehicles/{vehicle}` | Remover |
| `GET` | `/api/vehicles/{vehicle}` | Detalhar |
| `PATCH` | `/api/vehicles/{vehicle}` | Atualizar |
| `PUT` | `/api/vehicles/{vehicle}` | Atualizar |

---

## `PATCH /api/confirm-trip-client/{client_id}`

**Confirm Trip Client** · controller `TripController@confirmTripClient`

- **Acesso:** Token Sanctum; página em Perfis: `/trips`
- **Parâmetros de rota:** `client_id`

## `GET /api/routes`

**Listar** · controller `RouteController@index`

- **Acesso:** Token Sanctum; página em Perfis: `/routes` ou `/trips` ou `/trips/agenda`

## `POST /api/routes`

**Criar** · controller `RouteController@store`

- **Acesso:** Token Sanctum; página em Perfis: `/routes`
- **Corpo/validação** (`StoreRouteRequest`):

| Campo | Regras |
|---|---|
| `secretaria_id` | `nullable` |
| `origin` | `required \| string \| max:50` |
| `destination` | `required \| string \| max:50` |
| `origin_state` | `required \| string \| size:2 \| exists:states,code` |
| `destination_state` | `required \| string \| size:2 \| exists:states,code` |
| `distance` | `required \| numeric` |


## `DELETE /api/routes/{route}`

**Remover** · controller `RouteController@destroy`

- **Acesso:** Token Sanctum; página em Perfis: `/routes`
- **Parâmetros de rota:** `route`

## `GET /api/routes/{route}`

**Detalhar** · controller `RouteController@show`

- **Acesso:** Token Sanctum; página em Perfis: `/routes` ou `/trips` ou `/trips/agenda`
- **Parâmetros de rota:** `route`

## `PATCH /api/routes/{route}`

**Atualizar** · controller `RouteController@update`

- **Acesso:** Token Sanctum; página em Perfis: `/routes`
- **Parâmetros de rota:** `route`
- **Corpo/validação** (`StoreRouteRequest`):

| Campo | Regras |
|---|---|
| `secretaria_id` | `nullable` |
| `origin` | `required \| string \| max:50` |
| `destination` | `required \| string \| max:50` |
| `origin_state` | `required \| string \| size:2 \| exists:states,code` |
| `destination_state` | `required \| string \| size:2 \| exists:states,code` |
| `distance` | `required \| numeric` |


## `PUT /api/routes/{route}`

**Atualizar** · controller `RouteController@update`

- **Acesso:** Token Sanctum; página em Perfis: `/routes`
- **Parâmetros de rota:** `route`
- **Corpo/validação** (`StoreRouteRequest`):

| Campo | Regras |
|---|---|
| `secretaria_id` | `nullable` |
| `origin` | `required \| string \| max:50` |
| `destination` | `required \| string \| max:50` |
| `origin_state` | `required \| string \| size:2 \| exists:states,code` |
| `destination_state` | `required \| string \| size:2 \| exists:states,code` |
| `distance` | `required \| numeric` |


## `POST /api/trip-clients`

**Insert Trip Client** · controller `TripController@insertTripClient`

- **Acesso:** Token Sanctum; página em Perfis: `/trips`
- **Corpo/validação** (`TripClientRequest`):

| Campo | Regras |
|---|---|
| `id` | `nullable \| int` |
| `trip_id` | `required \| <Exists>` |
| `client_id` | `required \| exists:clients,id \| <Closure>` |
| `person_type` | `required \| in:passenger,companion` |
| `companion_of` | `nullable \| integer \| <Closure>` |
| `phone` | `nullable \| string \| max:20` |
| `departure_location` | `nullable \| string \| max:50` |
| `destination_location` | `nullable \| string \| max:50` |
| `time` | `required \| date_format:H:i` |


## `DELETE /api/trip-clients/{client_id}`

**Delete Trip Client** · controller `TripController@deleteTripClient`

- **Acesso:** Token Sanctum; página em Perfis: `/trips`
- **Parâmetros de rota:** `client_id`

## `PUT /api/trip-clients/{id}`

**Edit Trip Client** · controller `TripController@editTripClient`

- **Acesso:** Token Sanctum; página em Perfis: `/trips`
- **Parâmetros de rota:** `id`
- **Corpo/validação** (`TripClientRequest`):

| Campo | Regras |
|---|---|
| `id` | `nullable \| int` |
| `trip_id` | `required \| <Exists>` |
| `client_id` | `required \| exists:clients,id \| <Closure>` |
| `person_type` | `required \| in:passenger,companion` |
| `companion_of` | `nullable \| integer \| <Closure>` |
| `phone` | `nullable \| string \| max:20` |
| `departure_location` | `nullable \| string \| max:50` |
| `destination_location` | `nullable \| string \| max:50` |
| `time` | `required \| date_format:H:i` |


## `GET /api/trip-schedule`

**Listar** · controller `TripScheduleController@index`

- **Acesso:** Token Sanctum; página em Perfis: `/trips` ou `/trips/agenda`
- **Corpo/validação** (`IndexTripScheduleRequest`):

| Campo | Regras |
|---|---|
| `from` | `required \| date_format:Y-m-d` |
| `to` | `required \| date_format:Y-m-d \| after_or_equal:from` |


## `POST /api/trip-schedule`

**Criar** · controller `TripScheduleController@store`

- **Acesso:** Token Sanctum; página em Perfis: `/trips` ou `/trips/agenda`
- **Corpo/validação** (`StoreTripScheduleRequest`):

| Campo | Regras |
|---|---|
| `scheduled_date` | `required_without:companion_of \| nullable \| date_format:Y-m-d` |
| `client_id` | `required \| integer \| exists:clients,id` |
| `companion_of` | `nullable \| integer \| exists:trip_schedule_entries,id` |
| `phone` | `nullable \| string \| max:20` |
| `departure_location` | `nullable \| string \| max:50` |
| `destination_location` | `nullable \| string \| max:50` |
| `time` | `nullable \| date_format:H:i` |
| `obs` | `nullable \| string \| max:300` |
| `secretaria_id` | `nullable \| integer \| <Exists>` |
| `companions` | `nullable \| array \| max:10` |
| `companions.*.client_id` | `required \| integer \| exists:clients,id` |
| `companions.*.phone` | `nullable \| string \| max:20` |


## `POST /api/trip-schedule/send`

**Send** · controller `TripScheduleController@send`

- **Acesso:** Token Sanctum; página em Perfis: `/trips` ou `/trips/agenda`
- **Corpo/validação** (`SendTripScheduleRequest`):

| Campo | Regras |
|---|---|
| `entry_ids` | `required \| array \| min:1 \| max:100` |
| `entry_ids.*` | `integer` |
| `trip_id` | `nullable \| integer \| exists:trips,id` |
| `new_trip` | `nullable \| array` |
| `new_trip.route_id` | `required_with:new_trip \| integer \| exists:routes,id` |
| `new_trip.departure_date` | `required_with:new_trip \| date_format:Y-m-d` |
| `new_trip.departure_time` | `nullable \| date_format:H:i` |
| `new_trip.vehicle_id` | `nullable \| integer \| exists:vehicles,id` |
| `new_trip.driver_id` | `nullable \| integer \| exists:users,id` |


## `DELETE /api/trip-schedule/{id}`

**Remover** · controller `TripScheduleController@destroy`

- **Acesso:** Token Sanctum; página em Perfis: `/trips` ou `/trips/agenda`
- **Parâmetros de rota:** `id`

## `PUT /api/trip-schedule/{id}`

**Atualizar** · controller `TripScheduleController@update`

- **Acesso:** Token Sanctum; página em Perfis: `/trips` ou `/trips/agenda`
- **Parâmetros de rota:** `id`
- **Corpo/validação** (`UpdateTripScheduleRequest`):

| Campo | Regras |
|---|---|
| `scheduled_date` | `sometimes \| date_format:Y-m-d` |
| `phone` | `nullable \| string \| max:20` |
| `departure_location` | `nullable \| string \| max:50` |
| `destination_location` | `nullable \| string \| max:50` |
| `time` | `nullable \| date_format:H:i` |
| `status` | `sometimes \| <In>` |
| `obs` | `nullable \| string \| max:300` |


## `GET /api/trips`

**Listar** · controller `TripController@index`

- **Acesso:** Token Sanctum; página em Perfis: `/trips` ou `/trips/agenda` ou `/clients`
- **Outros parâmetros lidos pelo controller:** `year`, `month`, `day`, `date_begin`, `date_end`, `client_id`

## `POST /api/trips`

**Criar** · controller `TripController@store`

- **Acesso:** Token Sanctum; página em Perfis: `/trips` ou `/trips/agenda` ou `/clients`
- **Corpo/validação** (`TripRequest`; as regras dependem do contexto, texto copiado do código):

| Campo | Regras |
|---|---|
| `secretaria_id` | `$trip instanceof \App\Models\Trip ? ['nullable'] : \App\Support\EscopoAlmoxarifado::regraCampo()` |
| `user_id` | `'nullable', \App\Services\Secretarias\SecretariaMembership::regraUsuario($sid)` |
| `vehicle_id` | `'nullable', \App\Support\EscopoAlmoxarifado::existe('vehicles', $sid)` |
| `route_id` | `'required', \App\Support\EscopoAlmoxarifado::existe('routes', $sid)` |
| `departure_time` | `nullable` |
| `departure_date` | `required \| date` |
| `obs` | `nullable \| string \| max:300` |
| `driver_id` | `'nullable', \App\Services\Secretarias\SecretariaMembership::regraUsuario($sid)` |


## `GET /api/trips/drivers-options`

**Drivers** · controller `TripController@drivers`

- **Acesso:** Token Sanctum; página em Perfis: `/trips` ou `/trips/agenda` ou `/clients`

## `DELETE /api/trips/{trip}`

**Remover** · controller `TripController@destroy`

- **Acesso:** Token Sanctum; página em Perfis: `/trips` ou `/trips/agenda` ou `/clients`
- **Parâmetros de rota:** `trip`

## `GET /api/trips/{trip}`

**Detalhar** · controller `TripController@show`

- **Acesso:** Token Sanctum; página em Perfis: `/trips` ou `/trips/agenda` ou `/clients`
- **Parâmetros de rota:** `trip`

## `PATCH /api/trips/{trip}`

**Atualizar** · controller `TripController@update`

- **Acesso:** Token Sanctum; página em Perfis: `/trips` ou `/trips/agenda` ou `/clients`
- **Parâmetros de rota:** `trip`
- **Corpo/validação** (`TripRequest`; as regras dependem do contexto, texto copiado do código):

| Campo | Regras |
|---|---|
| `secretaria_id` | `$trip instanceof \App\Models\Trip ? ['nullable'] : \App\Support\EscopoAlmoxarifado::regraCampo()` |
| `user_id` | `'nullable', \App\Services\Secretarias\SecretariaMembership::regraUsuario($sid)` |
| `vehicle_id` | `'nullable', \App\Support\EscopoAlmoxarifado::existe('vehicles', $sid)` |
| `route_id` | `'required', \App\Support\EscopoAlmoxarifado::existe('routes', $sid)` |
| `departure_time` | `nullable` |
| `departure_date` | `required \| date` |
| `obs` | `nullable \| string \| max:300` |
| `driver_id` | `'nullable', \App\Services\Secretarias\SecretariaMembership::regraUsuario($sid)` |


## `PUT /api/trips/{trip}`

**Atualizar** · controller `TripController@update`

- **Acesso:** Token Sanctum; página em Perfis: `/trips` ou `/trips/agenda` ou `/clients`
- **Parâmetros de rota:** `trip`
- **Corpo/validação** (`TripRequest`; as regras dependem do contexto, texto copiado do código):

| Campo | Regras |
|---|---|
| `secretaria_id` | `$trip instanceof \App\Models\Trip ? ['nullable'] : \App\Support\EscopoAlmoxarifado::regraCampo()` |
| `user_id` | `'nullable', \App\Services\Secretarias\SecretariaMembership::regraUsuario($sid)` |
| `vehicle_id` | `'nullable', \App\Support\EscopoAlmoxarifado::existe('vehicles', $sid)` |
| `route_id` | `'required', \App\Support\EscopoAlmoxarifado::existe('routes', $sid)` |
| `departure_time` | `nullable` |
| `departure_date` | `required \| date` |
| `obs` | `nullable \| string \| max:300` |
| `driver_id` | `'nullable', \App\Services\Secretarias\SecretariaMembership::regraUsuario($sid)` |


## `POST /api/trips/{trip}/replicate`

**Replicate** · controller `TripController@replicate`

- **Acesso:** Token Sanctum; página em Perfis: `/trips` ou `/trips/agenda` ou `/clients`
- **Parâmetros de rota:** `trip`
- **Corpo/validação** (`ReplicateTripRequest`):

| Campo | Regras |
|---|---|
| `dates` | `required \| array \| min:1 \| max:60` |
| `dates.*` | `required \| date_format:Y-m-d \| after_or_equal:today` |


## `PATCH /api/unconfirm-trip-client/{client_id}`

**Unconfirm Trip Client** · controller `TripController@unconfirmTripClient`

- **Acesso:** Token Sanctum; página em Perfis: `/trips`
- **Parâmetros de rota:** `client_id`

## `GET /api/vehicles`

**Listar** · controller `VehicleController@index`

- **Acesso:** Token Sanctum; página em Perfis: `/vehicles` ou `/trips` ou `/trips/agenda`

## `POST /api/vehicles`

**Criar** · controller `VehicleController@store`

- **Acesso:** Token Sanctum; página em Perfis: `/vehicles`
- **Corpo/validação** (`StoreVehicleRequest`; as regras dependem do contexto, texto copiado do código):

| Campo | Regras |
|---|---|
| `secretaria_id` | `$editando ? ['nullable'] : \App\Support\EscopoAlmoxarifado::regraCampo()` |
| `brand` | `required \| string \| max:20` |
| `model` | `required \| string \| max:20` |
| `color` | `required \| string \| max:10` |
| `license_plate` | `'required', 'string', 'size:7', $unico('license_plate')` |
| `renavan` | `'required', 'string', 'size:11', $unico('renavan')` |
| `chassis` | `'required', 'string', 'size:17', $unico('chassis')` |
| `capacity` | `required \| integer \| max:999 \| min:1` |
| `year` | `required \| numeric \| digits:4` |


## `DELETE /api/vehicles/{vehicle}`

**Remover** · controller `VehicleController@destroy`

- **Acesso:** Token Sanctum; página em Perfis: `/vehicles`
- **Parâmetros de rota:** `vehicle`

## `GET /api/vehicles/{vehicle}`

**Detalhar** · controller `VehicleController@show`

- **Acesso:** Token Sanctum; página em Perfis: `/vehicles` ou `/trips` ou `/trips/agenda`
- **Parâmetros de rota:** `vehicle`

## `PATCH /api/vehicles/{vehicle}`

**Atualizar** · controller `VehicleController@update`

- **Acesso:** Token Sanctum; página em Perfis: `/vehicles`
- **Parâmetros de rota:** `vehicle`
- **Corpo/validação** (`StoreVehicleRequest`; as regras dependem do contexto, texto copiado do código):

| Campo | Regras |
|---|---|
| `secretaria_id` | `$editando ? ['nullable'] : \App\Support\EscopoAlmoxarifado::regraCampo()` |
| `brand` | `required \| string \| max:20` |
| `model` | `required \| string \| max:20` |
| `color` | `required \| string \| max:10` |
| `license_plate` | `'required', 'string', 'size:7', $unico('license_plate')` |
| `renavan` | `'required', 'string', 'size:11', $unico('renavan')` |
| `chassis` | `'required', 'string', 'size:17', $unico('chassis')` |
| `capacity` | `required \| integer \| max:999 \| min:1` |
| `year` | `required \| numeric \| digits:4` |


## `PUT /api/vehicles/{vehicle}`

**Atualizar** · controller `VehicleController@update`

- **Acesso:** Token Sanctum; página em Perfis: `/vehicles`
- **Parâmetros de rota:** `vehicle`
- **Corpo/validação** (`StoreVehicleRequest`; as regras dependem do contexto, texto copiado do código):

| Campo | Regras |
|---|---|
| `secretaria_id` | `$editando ? ['nullable'] : \App\Support\EscopoAlmoxarifado::regraCampo()` |
| `brand` | `required \| string \| max:20` |
| `model` | `required \| string \| max:20` |
| `color` | `required \| string \| max:10` |
| `license_plate` | `'required', 'string', 'size:7', $unico('license_plate')` |
| `renavan` | `'required', 'string', 'size:11', $unico('renavan')` |
| `chassis` | `'required', 'string', 'size:17', $unico('chassis')` |
| `capacity` | `required \| integer \| max:999 \| min:1` |
| `year` | `required \| numeric \| digits:4` |


