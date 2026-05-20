# Manual de Uso — Sysdoc
## Sistema de Gestão Municipal de Saúde

**Versão:** 1.0 — Maio 2026
**Público:** Usuários finais do sistema (gestores, profissionais de saúde, recepcionistas, motoristas)

---

## Sumário

1. [Introdução](#1-introdução)
2. [Acesso e Autenticação](#2-acesso-e-autenticação)
3. [Perfis de Acesso](#3-perfis-de-acesso)
4. [Cadastro de Pacientes](#4-cadastro-de-pacientes)
5. [Laboratório Clínico](#5-laboratório-clínico)
6. [Fila de Atendimento](#6-fila-de-atendimento)
7. [Farmácia Municipal](#7-farmácia-municipal)
8. [Vigilância Sanitária](#8-vigilância-sanitária)
9. [TFD — Transporte Fretado Delegado](#9-tfd--transporte-fretado-delegado)
10. [Documentos (Ofícios e Portarias)](#10-documentos-ofícios-e-portarias)
11. [Monitor APS](#11-monitor-aps)
12. [Dashboards](#12-dashboards)
13. [Auditoria e Logs](#13-auditoria-e-logs)
14. [Administração do Sistema](#14-administração-do-sistema)
15. [Consultas Públicas](#15-consultas-públicas)
16. [Solução de Problemas](#16-solução-de-problemas)
17. [Glossário](#17-glossário)

---

## 1. Introdução

O **Sysdoc** é um sistema de gestão municipal de saúde que centraliza os principais serviços da secretaria de saúde em uma única plataforma:

- Gestão laboratorial com laudos em PDF
- Farmácia municipal com transparência pública (Lei 2488)
- Fila de atendimento com painel TV em tempo real
- Vigilância sanitária e controle de alvarás
- TFD — Transporte Fretado Delegado
- Documentos administrativos gerados com Inteligência Artificial
- Indicadores APS do eSUS PEC
- Dashboards analíticos por módulo
- Auditoria completa de todas as ações

O sistema é acessado pelo navegador. A interface principal (frontend) está disponível em **sysvendas.vercel.app**.

---

## 2. Acesso e Autenticação

### 2.1 Como fazer login

1. Acesse o endereço do sistema no navegador.
2. Na tela de login, informe seu **CPF** (somente números) e **senha**.
3. Clique em **Entrar**.
4. O sistema valida suas credenciais e redireciona para o painel principal.

> **Importante:** O CPF deve ser digitado sem pontos ou traços.

### 2.2 Esqueceu a senha

1. Na tela de login, clique em **"Esqueci minha senha"**.
2. Informe seu e-mail cadastrado.
3. Você receberá um link de redefinição de senha por e-mail.
4. Clique no link, defina uma nova senha e confirme.

> O link de redefinição expira em 60 minutos.

### 2.3 Como fazer logout

- Clique no seu nome ou avatar no canto superior direito.
- Selecione **Sair**.
- O sistema encerra sua sessão e redireciona para a tela de login.

---

## 3. Perfis de Acesso

O Sysdoc possui 6 perfis pré-configurados. Cada perfil tem acesso apenas aos módulos pertinentes à sua função.

| Perfil | Descrição | Módulos principais |
|--------|-----------|-------------------|
| **admin** | Administrador total | Todos os módulos + configurações |
| **manager** | Gestor | Laboratório, Documentos, Dashboards |
| **user** | Usuário padrão | Clientes, Pedidos básicos |
| **tfd** | Equipe TFD | Viagens, Veículos, Rotas |
| **driver** | Motorista | Painel de viagens próprias |
| **partner** | Parceiro externo | Somente cadastro de clientes |

> O administrador pode criar perfis personalizados e configurar quais páginas cada perfil pode acessar.

---

## 4. Cadastro de Pacientes

O cadastro de pacientes (clientes) é a base central do sistema. Todos os módulos (laboratório, TFD, atendimento) referenciam o cadastro de pacientes.

### 4.1 Cadastrar novo paciente

1. No menu lateral, acesse **Clientes**.
2. Clique em **Novo Paciente**.
3. Preencha os campos obrigatórios:
   - Nome completo
   - Data de nascimento
   - CPF ou CNS (Cartão Nacional de Saúde)
   - Sexo
4. Preencha os campos opcionais: mãe, pai, e-mail, telefone, observações.
5. Adicione o **endereço** clicando em "Adicionar Endereço".
6. Clique em **Salvar**.

### 4.2 Buscar paciente

- Use a barra de busca em Clientes para pesquisar por **nome**, **CPF** ou **CNS**.
- Também é possível buscar diretamente pelo CPF ou CNS no campo de busca rápida.

### 4.3 Editar dados do paciente

1. Localize o paciente na listagem.
2. Clique em **Editar** (ícone de lápis).
3. Altere os campos necessários.
4. Clique em **Salvar**.

---

## 5. Laboratório Clínico

O módulo de laboratório gerencia todo o ciclo de um exame: do pedido ao laudo liberado.

### 5.1 Criar pedido de exame

1. Acesse **Laboratório > Pedidos**.
2. Clique em **Novo Pedido**.
3. Selecione o **paciente** (busca por nome/CPF).
4. Informe a **data do pedido** e o **médico solicitante**.
5. Adicione os **exames** desejados clicando em "+ Adicionar Exame".
6. Clique em **Salvar Pedido**.

O pedido é criado com status **"Solicitado"**.

### 5.2 Status do pedido (fluxo)

Os pedidos seguem um fluxo de status. Apenas transições válidas são permitidas:

```
Solicitado → Coletado → Em Análise → Liberado
           ↘ Cancelado ↗ Cancelado
```

Para avançar o status:
1. Abra o pedido.
2. Clique em **Atualizar Status**.
3. Selecione o novo status.
4. Confirme.

### 5.3 Registrar resultado

1. Com o pedido em status **"Em Análise"**, clique em **Registrar Resultado**.
2. Preencha os valores para cada campo do exame.
3. O sistema indica automaticamente se os valores estão dentro das faixas de referência.
4. Clique em **Salvar Resultado**.

### 5.4 Liberar resultado

1. Com o resultado registrado, clique em **Liberar**.
2. O sistema gera um **protocolo único** para o resultado.
3. O laudo em PDF fica disponível para download.
4. O paciente pode consultar o resultado pela internet usando o protocolo.

### 5.5 Download do laudo em PDF

- Na listagem de resultados, clique em **Baixar Laudo** (ícone PDF).
- O laudo é gerado com os dados do paciente, médico, exames, valores e faixas de referência.

### 5.6 Consulta pública de exame

O paciente pode consultar o resultado sem precisar de login:
1. Acesse a página pública de consulta.
2. Informe o **protocolo** informado na entrega do laudo.
3. Visualize ou baixe o laudo em PDF.

### 5.7 Agenda de coleta

- Acesse **Laboratório > Agenda de Coleta** para ver os pedidos agendados por data.

### 5.8 Médicos solicitantes

- Acesse **Laboratório > Médicos** para cadastrar, editar ou desativar médicos solicitantes.
- Campos: Nome, CRM, Especialidade.

---

## 6. Fila de Atendimento

Sistema de senhas digitais com painel TV em tempo real.

### 6.1 Emitir senha

1. Acesse **Atendimento > Emitir Senha**.
2. Selecione o **paciente**.
3. Selecione a **sala de atendimento** (se houver mais de uma).
4. Clique em **Emitir Senha**.
5. O sistema gera um número sequencial do dia (ex: A-001).

### 6.2 Chamar próximo na fila

1. Acesse **Atendimento > Fila**.
2. Clique em **Chamar Próximo**.
3. O sistema chama o próximo da fila e atualiza o painel TV.

### 6.3 Chamar paciente específico

1. Na lista da fila, localize a senha desejada.
2. Clique em **Chamar** na senha específica.

### 6.4 Iniciar e finalizar atendimento

1. Após chamar o paciente, clique em **Iniciar Atendimento**.
2. Durante o atendimento, registre **notas** no campo de observações.
3. Ao concluir, clique em **Finalizar Atendimento**.

### 6.5 Cancelar senha / não compareceu

- Clique em **Cancelar** para cancelar uma senha.
- Clique em **Não Compareceu** se o paciente foi chamado mas não atendeu.

### 6.6 Painel TV

O painel TV exibe a fila em tempo real e pode ser aberto em qualquer televisão ou monitor:
- Acesse a URL do painel TV público (fornecida pelo administrador).
- Não é necessário login.
- O painel atualiza automaticamente a cada poucos segundos.

### 6.7 Administrar salas

- Administradores acessam **Atendimento > Salas** para criar, editar ou desativar salas de atendimento.

---

## 7. Farmácia Municipal

### 7.1 Cadastrar medicamento

1. Acesse **Farmácia > Medicamentos**.
2. Clique em **Novo Medicamento**.
3. Preencha: código interno, princípio ativo, concentração, forma farmacêutica, apresentação e unidade.
4. Clique em **Salvar**.

### 7.2 Registrar disponibilidade diária

1. Acesse **Farmácia > Disponibilidade Diária**.
2. Para cada medicamento, marque como **Disponível** ou **Indisponível** na data atual.
3. Salve o registro.

> Este registro alimenta o painel público de transparência farmacêutica.

### 7.3 Registrar aquisição mensal

1. Acesse **Farmácia > Aquisições Mensais**.
2. Clique em **Nova Aquisição**.
3. Selecione o medicamento, mês de referência, quantidade adquirida, custo unitário e fonte (empenho, doação, etc.).
4. Informe o documento de origem.
5. Clique em **Salvar**.

### 7.4 Painel público de transparência

O painel público de farmácia está disponível sem login e exibe:
- Disponibilidade diária de cada medicamento.
- Aquisições mensais (quantidades e valores).

Atende à **Lei 2488** de transparência pública.

---

## 8. Vigilância Sanitária

### 8.1 Cadastrar estabelecimento

1. Acesse **Vigilância > Estabelecimentos**.
2. Clique em **Novo Estabelecimento**.
3. Preencha: nome, CNAE, responsável, endereço, telefone, e-mail.
4. Clique em **Salvar**.

### 8.2 Emitir alvará sanitário

1. Acesse **Vigilância > Alvarás**.
2. Clique em **Novo Alvará**.
3. Selecione o estabelecimento.
4. Informe: número do alvará, data de emissão, data de vencimento, status e nível de risco.
5. Clique em **Salvar**.

### 8.3 Download do alvará em PDF

- Na listagem de alvarás, clique em **Baixar PDF** para gerar o documento oficial do alvará.

### 8.4 Controlar vencimentos

- No **Dashboard de Vigilância**, você visualiza:
  - Alvarás vigentes, vencidos e a vencer
  - Alvarás vencendo nos próximos 30 dias
  - Lista de próximos vencimentos

---

## 9. TFD — Transporte Fretado Delegado

### 9.1 Cadastrar viagem

1. Acesse **TFD > Viagens**.
2. Clique em **Nova Viagem**.
3. Informe: data, destino, rota (opcional) e veículo/motorista.
4. Clique em **Salvar**.

### 9.2 Adicionar pacientes à viagem

1. Abra a viagem desejada.
2. Clique em **Adicionar Paciente**.
3. Busque o paciente por nome ou CPF.
4. Confirme a adição.

### 9.3 Confirmar presença de paciente

1. Na listagem de pacientes da viagem, clique em **Confirmar** ao lado do paciente.
2. Para remover um paciente, clique em **Remover**.

### 9.4 Cadastrar veículos e rotas

- **TFD > Veículos:** cadastre placa, modelo e motorista responsável.
- **TFD > Rotas:** cadastre rotas com origem, destino e distância em km.

---

## 10. Documentos (Ofícios e Portarias)

### 10.1 Criar ofício

1. Acesse **Documentos > Ofícios**.
2. Clique em **Novo Ofício**.
3. Preencha: assunto, remetente, destinatário, resumo e observações.
4. O número do ofício é gerado **automaticamente** (sequencial anual).
5. Clique em **Salvar**.

### 10.2 Criar ofício com Inteligência Artificial

1. Acesse **Documentos > Ofícios > Criar com IA**.
2. Descreva brevemente o assunto do ofício.
3. O sistema usa a IA para redigir o texto completo do documento.
4. Revise o conteúdo gerado.
5. Salve ou edite antes de salvar.

### 10.3 Gerenciar anexos

1. Abra o ofício ou portaria desejado.
2. Na seção de anexos, clique em **Adicionar Anexo**.
3. Selecione o arquivo do seu computador.
4. Para baixar um anexo, clique em **Download**.
5. Para excluir, clique em **Remover**.

### 10.4 Criar portaria

O processo é idêntico ao de ofícios:
1. Acesse **Documentos > Portarias**.
2. Clique em **Nova Portaria**.
3. Preencha os campos ou use **Criar com IA**.
4. Salve.

---

## 11. Monitor APS

O Monitor APS exibe os indicadores de desempenho da Atenção Primária à Saúde, extraídos diretamente do banco de dados do eSUS PEC municipal.

### 11.1 Dashboard principal

Acesse **Monitor APS** para visualizar o resumo geral dos indicadores do quadrimestre atual.

### 11.2 Indicadores de vínculo territorial

Exibe dados sobre vínculo e acompanhamento de famílias pelas equipes de saúde da família.

### 11.3 Indicadores de qualidade

15 indicadores da **Portaria GM/MS 6.907/2025**, incluindo:
- Proporção de gestantes com pré-natal no 1º trimestre
- Proporção de diabéticos com hemoglobina glicada
- Proporção de hipertensos com PA aferida
- E demais indicadores do Cofinanciamento Federal

Clique em cada indicador para ver o detalhamento por equipe.

### 11.4 Indicadores de repasse

Visualize os valores de repasse federal por quadrimestre, baseados no desempenho dos indicadores.

### 11.5 Configuração (somente admin)

Para conectar o Monitor APS ao eSUS PEC:
1. Acesse **Monitor APS > Configurações**.
2. Informe: Host (IP do servidor eSUS), Porta, Banco de dados, Usuário e Senha.
3. Clique em **Testar Conexão**.
4. Se bem-sucedido, clique em **Salvar**.

---

## 12. Dashboards

Cada módulo possui um dashboard com métricas e gráficos para apoio à decisão.

### 12.1 Dashboard Início

Visão geral do sistema:
- Total de clientes cadastrados
- Total de especialidades
- Total de ofícios e portarias

### 12.2 Dashboard Laboratório

- Exames cadastrados, pedidos totais, por status e por mês
- Top 10 exames mais solicitados
- Top 5 médicos solicitantes
- Resultados por status

### 12.3 Dashboard TFD

- Viagens no mês atual
- Pessoas transportadas
- km rodados
- Top 10 motoristas e rotas
- Viagens por mês/ano

### 12.4 Dashboard Farmácia

- Medicamentos ativos
- Taxa de disponibilidade do dia
- Disponíveis e indisponíveis
- Aquisições do mês (quantidade e valor)
- Top 10 medicamentos mais indisponíveis

### 12.5 Dashboard Vigilância Sanitária

- Estabelecimentos cadastrados
- Alvarás vigentes, vencidos e a vencer nos próximos 30 dias
- Alvarás por nível de risco
- Próximos vencimentos

### 12.6 Dashboard Logs

- Total de acessos via QR Code e link público
- Acessos por dia e por mês

---

## 13. Auditoria e Logs

O Sysdoc registra **todas as ações** realizadas no sistema. Este recurso é acessível apenas para administradores.

### 13.1 Consultar logs de auditoria

1. Acesse **Administração > Logs de Auditoria**.
2. Utilize os filtros disponíveis:
   - Por **usuário**
   - Por **tipo de ação** (LOGIN, CREATE, UPDATE, DELETE, VIEW)
   - Por **período** (data inicial e final)
   - Por **módulo** (tipo de entidade)
3. Cada registro exibe:
   - Usuário que realizou a ação
   - Data e hora
   - Endereço IP e dispositivo
   - Endpoint acessado
   - Valores anteriores e novos (para UPDATE e DELETE)

### 13.2 Logs de erro

- Acesse **Administração > Logs de Erro** para visualizar erros registrados pelo sistema.

---

## 14. Administração do Sistema

Acessível apenas para o perfil **admin**.

### 14.1 Gerenciar usuários

1. Acesse **Administração > Usuários**.
2. Para criar: clique em **Novo Usuário**, preencha nome, e-mail, CPF, senha e perfil.
3. Para editar: clique no usuário e altere os campos.
4. Para desativar: marque o usuário como inativo (o histórico é preservado).

### 14.2 Gerenciar perfis de acesso

1. Acesse **Administração > Perfis de Acesso**.
2. Crie novos perfis ou edite os existentes.
3. Para cada perfil, selecione quais **páginas do sistema** ele pode acessar.

### 14.3 Gerenciar páginas do sistema

- Acesse **Administração > Páginas** para visualizar e organizar as páginas cadastradas no sistema.

---

## 15. Consultas Públicas

As seguintes funcionalidades estão disponíveis **sem necessidade de login**:

### 15.1 Resultado de exame por protocolo

- Acesse a URL de consulta pública.
- Informe o **protocolo** do exame (fornecido ao paciente na entrega).
- Visualize ou baixe o laudo em PDF.

### 15.2 Disponibilidade de medicamentos

- Acesse o painel público da farmácia.
- Consulte quais medicamentos estão disponíveis no dia atual.

### 15.3 Aquisições mensais da farmácia

- Acesse o painel de aquisições públicas.
- Consulte as compras de medicamentos por mês, com quantidade e valor.

### 15.4 Painel TV de atendimento

- Acesse a URL do painel TV (exibido nas TVs da unidade).
- Mostra as últimas senhas chamadas em tempo real.

---

## 16. Solução de Problemas

### Não consigo fazer login
- Verifique se o CPF está sendo digitado sem pontos e traços.
- Certifique-se de que o Caps Lock não está ativado.
- Use "Esqueci minha senha" se necessário.
- Contate o administrador do sistema se o problema persistir.

### O pedido de exame não avança de status
- Verifique se a transição de status é válida (ex: não é possível ir de "Solicitado" direto para "Liberado").
- Apenas usuários com permissão de laboratório podem alterar o status.

### Não vejo o módulo X no menu
- Seu perfil pode não ter acesso a esse módulo. Contate o administrador.

### O Monitor APS não carrega dados
- A conexão com o eSUS PEC pode estar indisponível. Contate o administrador para verificar a configuração.

### O painel TV não atualiza
- Verifique a conexão de internet do dispositivo que exibe o painel.
- Atualize a página manualmente (F5) e aguarde a próxima atualização automática.

### Não recebi o e-mail de redefinição de senha
- Verifique a caixa de spam.
- Certifique-se de que o e-mail informado está cadastrado no sistema.
- Contate o administrador se o problema persistir.

---

## 17. Glossário

| Termo | Definição |
|-------|-----------|
| **Admin** | Perfil de administrador com acesso total ao sistema |
| **Alvará** | Documento oficial de autorização de funcionamento de estabelecimento |
| **APS** | Atenção Primária à Saúde |
| **CNS** | Cartão Nacional de Saúde — identificador único do paciente no SUS |
| **CNAE** | Classificação Nacional de Atividades Econômicas |
| **CRM** | Registro do médico no Conselho Regional de Medicina |
| **eSUS PEC** | Sistema de informação de APS do Ministério da Saúde (Prontuário Eletrônico do Cidadão) |
| **JWT** | JSON Web Token — mecanismo de autenticação segura |
| **Laudo** | Documento oficial com o resultado de um exame laboratorial |
| **Lei 2488** | Norma de transparência pública para farmácias municipais |
| **Portaria GM/MS 6.907/2025** | Portaria do Ministério da Saúde que define indicadores de cofinanciamento APS |
| **Protocolo** | Código único gerado para cada resultado de exame, usado na consulta pública |
| **RBAC** | Role-Based Access Control — controle de acesso baseado em perfis |
| **REMUME** | Relação Municipal de Medicamentos Essenciais |
| **State machine** | Lógica de controle que define quais transições de status são permitidas |
| **TFD** | Tratamento Fora do Domicílio — programa de transporte de pacientes para consultas em outras cidades |
| **Throttle** | Limite de requisições por minuto para proteger endpoints públicos |

---

*Manual gerado automaticamente com base nas funcionalidades implementadas no Sysdoc v1.0 — Maio 2026.*
