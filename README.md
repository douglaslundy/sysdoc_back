# SYSDOC Backend (`sysdoc_back`)

API Laravel do sistema SYSDOC, com modulos de atendimento, laboratorio, vigilancia sanitaria, farmacia e auditoria.

## Requisitos
- PHP 8+
- Composer
- MySQL

## Setup local
```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan serve
```

## Testes
```bash
php artisan test
```

## Rotas principais (alto nivel)
- Auth/sessao, RBAC e perfis de acesso
- Dashboards: inicio, fila, tfd, laboratorio, farmacia, vigilancia, logs
- Laboratorio: exames, pedidos, resultados, consulta publica e configuracoes
- Vigilancia: estabelecimentos, alvaras e PDF
- Farmacia/transparencia: catalogos, status diario, aquisicoes mensais, publicacoes e compliance
- Anexos: filas, oficios e portarias
- Auditoria: logs e filtros administrativos

## Operacao em producao
```bash
php artisan migrate --force
php artisan db:seed --class=AccessProfileSeeder --force
```

## Observacao
`README` anterior do Laravel foi substituido por documentacao do projeto.