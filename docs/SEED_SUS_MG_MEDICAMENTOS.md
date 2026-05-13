# Seed SUS-MG de Medicamentos

## Fontes oficiais recomendadas

- REMEMG (SES-MG): `https://www.saude.mg.gov.br/wp-content/uploads/2026/03/REMEMG-SITE-MARCO_2026.pdf`
- CEAF alfabético (SES-MG): `https://www.saude.mg.gov.br/wp-content/uploads/2026/04/LISTA-DE-MEDICAMENTOS-DO-CEAF-POR-ORDEM-ALFABETICA-24-04-2026.pdf`
- CEAF por doença (SES-MG): `https://www.saude.mg.gov.br/wp-content/uploads/2025/01/LISTA-DE-MEDICAMENTOS-DO-CEAF-POR-DOENCA-17-01-2025.pdf`
- Controle especial (Anvisa): `https://www.gov.br/anvisa/pt-br/assuntos/medicamentos/controlados/lista-substancias`

## Como ampliar a base (cobertura total)

1. Copie o arquivo:
   `database/seeders/data/sus_mg_medicines.extend.example.json`
   para:
   `database/seeders/data/sus_mg_medicines.extend.json`
2. Preencha os itens faltantes do REMEMG/CEAF.
3. Marque `controlled: true` para medicamentos sujeitos a controle especial.
4. Use `component` com valores: `CBAF`, `CESAF` ou `CEAF`.
5. Garanta `code` único por item.

## Execução do seed

```bash
php artisan db:seed --class=SusMgMedicinesSeeder
```

## Regras validadas automaticamente

- Campos obrigatórios:
  `code`, `ingredient`, `concentration`, `form`, `presentation`, `unit`, `component`, `controlled`
- `component` inválido gera erro.
- `code` duplicado gera erro.
- JSON inválido interrompe a execução.

