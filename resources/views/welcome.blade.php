<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Sysdoc é o sistema de gestão municipal de saúde que integra laboratório, farmácia, vigilância sanitária, TFD, atendimento e documentos com IA em uma única plataforma auditada.">
    <meta name="keywords" content="sistema gestão municipal saúde, software prefeitura saúde, laboratório municipal, farmácia transparência pública, vigilância sanitária, TFD transporte fretado, Monitor APS, fila atendimento">
    <meta name="robots" content="index, follow">
    <meta property="og:title" content="Sysdoc — Sistema de Gestão Municipal de Saúde">
    <meta property="og:description" content="Integre laboratório, farmácia, vigilância sanitária, TFD, atendimento e documentos com IA em uma única plataforma auditada.">
    <meta property="og:type" content="website">
    <title>Sysdoc — Sistema de Gestão Municipal de Saúde</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --blue:    #1e3a5f;
            --blue-md: #2a5298;
            --blue-lt: #e8f0fe;
            --green:   #2d9e6b;
            --green-lt:#e6f7f0;
            --gray-50: #f8fafc;
            --gray-100:#f1f5f9;
            --gray-200:#e2e8f0;
            --gray-500:#64748b;
            --gray-700:#374151;
            --gray-900:#0f172a;
            --white:   #ffffff;
            --radius:  10px;
            --shadow:  0 4px 24px rgba(0,0,0,.08);
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            color: var(--gray-700);
            background: var(--white);
            line-height: 1.6;
        }

        /* ── NAV ── */
        nav {
            position: sticky; top: 0; z-index: 100;
            background: var(--white);
            border-bottom: 1px solid var(--gray-200);
            padding: 0 2rem;
            display: flex; align-items: center; justify-content: space-between;
            height: 64px;
        }
        .nav-brand { font-size: 1.4rem; font-weight: 800; color: var(--blue); letter-spacing: -.5px; }
        .nav-brand span { color: var(--green); }
        .nav-links { display: flex; gap: 2rem; list-style: none; }
        .nav-links a { text-decoration: none; color: var(--gray-700); font-size: .9rem; font-weight: 500; }
        .nav-links a:hover { color: var(--blue); }
        .nav-cta {
            background: var(--blue); color: var(--white);
            padding: .5rem 1.25rem; border-radius: var(--radius);
            text-decoration: none; font-size: .9rem; font-weight: 600;
        }
        .nav-cta:hover { background: var(--blue-md); }

        /* ── HERO ── */
        .hero {
            background: linear-gradient(135deg, var(--blue) 0%, #2a5298 60%, #1a7a50 100%);
            color: var(--white);
            padding: 6rem 2rem 5rem;
            text-align: center;
        }
        .hero-badge {
            display: inline-block;
            background: rgba(255,255,255,.15);
            border: 1px solid rgba(255,255,255,.3);
            color: var(--white);
            padding: .35rem 1rem; border-radius: 999px;
            font-size: .8rem; font-weight: 600; letter-spacing: .5px;
            text-transform: uppercase; margin-bottom: 1.5rem;
        }
        .hero h1 {
            font-size: clamp(2rem, 5vw, 3.2rem);
            font-weight: 800; line-height: 1.15;
            max-width: 800px; margin: 0 auto 1.25rem;
            letter-spacing: -.5px;
        }
        .hero h1 span { color: #7ee8b8; }
        .hero p {
            font-size: clamp(1rem, 2vw, 1.2rem);
            max-width: 620px; margin: 0 auto 2.5rem;
            opacity: .9;
        }
        .hero-actions { display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; }
        .btn-primary {
            background: var(--green); color: var(--white);
            padding: .85rem 2rem; border-radius: var(--radius);
            font-size: 1rem; font-weight: 700; text-decoration: none;
            transition: background .2s;
        }
        .btn-primary:hover { background: #23845a; }
        .btn-outline {
            background: transparent; color: var(--white);
            border: 2px solid rgba(255,255,255,.6);
            padding: .85rem 2rem; border-radius: var(--radius);
            font-size: 1rem; font-weight: 600; text-decoration: none;
            transition: border-color .2s;
        }
        .btn-outline:hover { border-color: var(--white); }

        /* ── STATS ── */
        .stats {
            background: var(--white);
            border-bottom: 1px solid var(--gray-200);
            padding: 2.5rem 2rem;
        }
        .stats-grid {
            max-width: 900px; margin: 0 auto;
            display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 2rem; text-align: center;
        }
        .stat-num { font-size: 2.2rem; font-weight: 800; color: var(--blue); }
        .stat-label { font-size: .85rem; color: var(--gray-500); margin-top: .25rem; }

        /* ── SECTION HELPERS ── */
        section { padding: 5rem 2rem; }
        .container { max-width: 1100px; margin: 0 auto; }
        .section-tag {
            display: inline-block;
            background: var(--blue-lt); color: var(--blue-md);
            padding: .3rem .9rem; border-radius: 999px;
            font-size: .78rem; font-weight: 700; letter-spacing: .5px;
            text-transform: uppercase; margin-bottom: 1rem;
        }
        .section-tag.green { background: var(--green-lt); color: var(--green); }
        h2 {
            font-size: clamp(1.5rem, 3vw, 2.2rem);
            font-weight: 800; color: var(--gray-900);
            line-height: 1.2; margin-bottom: 1rem;
        }
        .section-lead {
            font-size: 1.1rem; color: var(--gray-500);
            max-width: 600px; margin-bottom: 3rem;
        }

        /* ── MODULES GRID ── */
        .modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        .module-card {
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-radius: var(--radius);
            padding: 1.75rem;
            transition: box-shadow .2s, transform .2s;
        }
        .module-card:hover { box-shadow: var(--shadow); transform: translateY(-2px); }
        .module-icon {
            width: 48px; height: 48px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem; margin-bottom: 1rem;
        }
        .module-card h3 { font-size: 1.05rem; font-weight: 700; color: var(--gray-900); margin-bottom: .5rem; }
        .module-card p { font-size: .9rem; color: var(--gray-500); line-height: 1.55; }
        .module-card ul { margin-top: .75rem; padding-left: 1.1rem; }
        .module-card ul li { font-size: .85rem; color: var(--gray-500); margin-bottom: .25rem; }

        /* ── BENEFITS ── */
        .benefits { background: var(--gray-50); }
        .benefits-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 1.5rem;
        }
        .benefit-card {
            background: var(--white);
            border: 1px solid var(--gray-200);
            border-radius: var(--radius);
            padding: 1.5rem;
        }
        .benefit-card .icon { font-size: 1.8rem; margin-bottom: .75rem; }
        .benefit-card h3 { font-size: 1rem; font-weight: 700; color: var(--gray-900); margin-bottom: .5rem; }
        .benefit-card p { font-size: .88rem; color: var(--gray-500); }

        /* ── HOW IT WORKS ── */
        .steps { display: flex; flex-direction: column; gap: 2rem; max-width: 700px; }
        .step { display: flex; gap: 1.5rem; align-items: flex-start; }
        .step-num {
            flex-shrink: 0;
            width: 40px; height: 40px; border-radius: 50%;
            background: var(--blue); color: var(--white);
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: .95rem;
        }
        .step h3 { font-size: 1rem; font-weight: 700; color: var(--gray-900); margin-bottom: .3rem; }
        .step p { font-size: .9rem; color: var(--gray-500); }

        /* ── HIGHLIGHT (auditoria) ── */
        .highlight {
            background: linear-gradient(135deg, var(--blue) 0%, #1a4a7a 100%);
            color: var(--white); border-radius: 16px;
            padding: 3rem 2rem; text-align: center;
            max-width: 900px; margin: 0 auto;
        }
        .highlight h2 { color: var(--white); margin-bottom: 1rem; }
        .highlight p { opacity: .85; max-width: 560px; margin: 0 auto 2rem; }
        .highlight-list {
            display: flex; flex-wrap: wrap; gap: .75rem;
            justify-content: center; list-style: none;
            margin-bottom: 2.5rem;
        }
        .highlight-list li {
            background: rgba(255,255,255,.15);
            border: 1px solid rgba(255,255,255,.25);
            padding: .4rem 1rem; border-radius: 999px;
            font-size: .85rem; font-weight: 600;
        }

        /* ── FAQ ── */
        .faq { background: var(--gray-50); }
        .faq-list { max-width: 720px; display: flex; flex-direction: column; gap: 1rem; }
        details {
            background: var(--white);
            border: 1px solid var(--gray-200);
            border-radius: var(--radius);
            padding: 1.25rem 1.5rem;
        }
        details[open] { border-color: var(--blue-md); }
        summary {
            font-weight: 600; color: var(--gray-900);
            cursor: pointer; font-size: .95rem;
            list-style: none; display: flex; justify-content: space-between; align-items: center;
        }
        summary::after { content: '+'; font-size: 1.3rem; color: var(--blue-md); }
        details[open] summary::after { content: '−'; }
        details p { font-size: .9rem; color: var(--gray-500); margin-top: .75rem; line-height: 1.6; }

        /* ── CTA FINAL ── */
        .cta-final {
            background: var(--white);
            text-align: center; padding: 5rem 2rem;
        }
        .cta-box {
            background: var(--green-lt);
            border: 1px solid var(--green);
            border-radius: 16px;
            max-width: 680px; margin: 0 auto;
            padding: 3rem 2rem;
        }
        .cta-box h2 { color: var(--gray-900); margin-bottom: 1rem; }
        .cta-box p { color: var(--gray-500); margin-bottom: 2rem; }
        .cta-email {
            font-size: 1.1rem; font-weight: 700; color: var(--blue);
            text-decoration: none;
        }

        /* ── FOOTER ── */
        footer {
            background: var(--gray-900); color: var(--gray-200);
            padding: 3rem 2rem;
        }
        .footer-inner {
            max-width: 1100px; margin: 0 auto;
            display: flex; flex-wrap: wrap; gap: 2rem;
            justify-content: space-between; align-items: flex-start;
        }
        .footer-brand { font-size: 1.3rem; font-weight: 800; color: var(--white); }
        .footer-brand span { color: #7ee8b8; }
        .footer-desc { font-size: .85rem; color: #94a3b8; margin-top: .5rem; max-width: 280px; }
        .footer-col h4 { font-size: .85rem; font-weight: 700; color: var(--white); margin-bottom: .75rem; }
        .footer-col ul { list-style: none; }
        .footer-col ul li { font-size: .82rem; color: #94a3b8; margin-bottom: .4rem; }
        .footer-bottom {
            max-width: 1100px; margin: 2rem auto 0;
            padding-top: 1.5rem; border-top: 1px solid #1e293b;
            font-size: .8rem; color: #64748b;
        }

        /* ── RESPONSIVE ── */
        @media (max-width: 768px) {
            nav { padding: 0 1rem; }
            .nav-links { display: none; }
            .hero { padding: 4rem 1.25rem 3.5rem; }
            section { padding: 3.5rem 1.25rem; }
            .highlight { padding: 2rem 1.25rem; }
        }
    </style>
</head>
<body>

<!-- ── NAVEGAÇÃO ── -->
<nav aria-label="Menu principal">
    <div class="nav-brand">Sys<span>doc</span></div>
    <ul class="nav-links">
        <li><a href="#modulos">Módulos</a></li>
        <li><a href="#beneficios">Benefícios</a></li>
        <li><a href="#como-funciona">Como funciona</a></li>
        <li><a href="#faq">FAQ</a></li>
    </ul>
    <a href="#contato" class="nav-cta">Solicitar Demo</a>
</nav>

<!-- ── HERO ── -->
<header class="hero" role="banner">
    <div class="hero-badge">Gestão Pública de Saúde</div>
    <h1>Gestão Municipal de Saúde <span>Integrada e Auditada</span></h1>
    <p>Do laboratório à farmácia, do TFD à vigilância sanitária — tudo em uma única plataforma com rastreabilidade completa e transparência pública.</p>
    <div class="hero-actions">
        <a href="#modulos" class="btn-primary">Conhecer funcionalidades</a>
        <a href="#contato" class="btn-outline">Solicitar demonstração</a>
    </div>
</header>

<!-- ── STATS ── -->
<div class="stats" aria-label="Números do sistema">
    <div class="stats-grid">
        <div>
            <div class="stat-num">10+</div>
            <div class="stat-label">Módulos integrados</div>
        </div>
        <div>
            <div class="stat-num">120+</div>
            <div class="stat-label">Endpoints de API</div>
        </div>
        <div>
            <div class="stat-num">100%</div>
            <div class="stat-label">Ações auditadas</div>
        </div>
        <div>
            <div class="stat-num">Lei 2488</div>
            <div class="stat-label">Transparência farmacêutica</div>
        </div>
        <div>
            <div class="stat-num">IA</div>
            <div class="stat-label">Geração de documentos</div>
        </div>
    </div>
</div>

<!-- ── MÓDULOS ── -->
<section id="modulos" aria-labelledby="modulos-title">
    <div class="container">
        <span class="section-tag">Funcionalidades</span>
        <h2 id="modulos-title">Tudo que sua secretaria de saúde precisa</h2>
        <p class="section-lead">Cada módulo foi desenvolvido para o fluxo real do serviço público, com controles, validações e rastreabilidade integrados.</p>

        <div class="modules-grid">

            <article class="module-card">
                <div class="module-icon" style="background:#e8f0fe;">🔬</div>
                <h3>Laboratório Clínico</h3>
                <p>Gestão completa do ciclo laboratorial, do pedido ao laudo impresso.</p>
                <ul>
                    <li>Pedidos com rastreamento de status (solicitado → liberado)</li>
                    <li>Campos dinâmicos e faixas de referência por exame</li>
                    <li>Laudos em PDF com protocolo único</li>
                    <li>Consulta pública de resultados online</li>
                    <li>Agenda de coleta e médicos solicitantes</li>
                </ul>
            </article>

            <article class="module-card">
                <div class="module-icon" style="background:#e6f7f0;">💊</div>
                <h3>Farmácia Municipal</h3>
                <p>Controle de estoque, disponibilidade e conformidade pública (Lei 2488).</p>
                <ul>
                    <li>Cadastro de medicamentos com código interno e princípio ativo</li>
                    <li>Disponibilidade diária por medicamento</li>
                    <li>Aquisições mensais com fonte e custo</li>
                    <li>Painel público de transparência farmacêutica</li>
                    <li>Integração com REMUME e SUS-MG</li>
                </ul>
            </article>

            <article class="module-card">
                <div class="module-icon" style="background:#fff7ed;">🏥</div>
                <h3>Fila de Atendimento</h3>
                <p>Sistema de senhas digitais com painel TV em tempo real.</p>
                <ul>
                    <li>Emissão de senha com número sequencial diário</li>
                    <li>Fila ordenada por prioridade</li>
                    <li>Painel TV público sem necessidade de login</li>
                    <li>Controle de múltiplas salas de atendimento</li>
                    <li>Histórico completo por atendente e sala</li>
                </ul>
            </article>

            <article class="module-card">
                <div class="module-icon" style="background:#fce7f3;">🛡️</div>
                <h3>Vigilância Sanitária</h3>
                <p>Cadastro de estabelecimentos e controle de alvarás com PDF oficial.</p>
                <ul>
                    <li>Cadastro por CNAE, responsável e endereço</li>
                    <li>Emissão e vencimento de alvarás sanitários</li>
                    <li>Download do alvará em PDF</li>
                    <li>Controle por nível de risco</li>
                    <li>Dashboard com alvarás a vencer</li>
                </ul>
            </article>

            <article class="module-card">
                <div class="module-icon" style="background:#ede9fe;">🚌</div>
                <h3>TFD — Transporte Fretado</h3>
                <p>Organização de viagens e controle de presença de pacientes.</p>
                <ul>
                    <li>Cadastro de viagens com origem, destino e data</li>
                    <li>Pacientes confirmando presença individualmente</li>
                    <li>Controle de veículos e motoristas</li>
                    <li>Rotas predefinidas com distância</li>
                    <li>Dashboard de km rodados e pessoas transportadas</li>
                </ul>
            </article>

            <article class="module-card">
                <div class="module-icon" style="background:#fef3c7;">📄</div>
                <h3>Documentos com IA</h3>
                <p>Ofícios e portarias com numeração automática e geração por Inteligência Artificial.</p>
                <ul>
                    <li>Numeração anual automática de ofícios</li>
                    <li>Geração de conteúdo com IA (OpenAI)</li>
                    <li>Upload e download de anexos</li>
                    <li>Registro de expedição</li>
                    <li>Portarias com data e descrição</li>
                </ul>
            </article>

            <article class="module-card">
                <div class="module-icon" style="background:#ecfdf5;">📊</div>
                <h3>Monitor APS</h3>
                <p>Indicadores da Atenção Primária à Saúde direto do eSUS PEC.</p>
                <ul>
                    <li>15 indicadores de qualidade (Portaria GM/MS 6.907/2025)</li>
                    <li>Indicadores de vínculo territorial</li>
                    <li>Repasse federal por quadrimestre</li>
                    <li>Histórico de desempenho por equipe</li>
                    <li>Conexão read-only ao banco eSUS PEC</li>
                </ul>
            </article>

            <article class="module-card">
                <div class="module-icon" style="background:#e0f2fe;">📈</div>
                <h3>Dashboards Analíticos</h3>
                <p>Painéis gerenciais por módulo para tomada de decisão em tempo real.</p>
                <ul>
                    <li>Dashboard de laboratório com top exames e médicos</li>
                    <li>Dashboard de farmácia com disponibilidade</li>
                    <li>Dashboard de vigilância com vencimentos</li>
                    <li>Dashboard TFD com km e pessoas</li>
                    <li>Dashboard de logs e acessos</li>
                </ul>
            </article>

            <article class="module-card">
                <div class="module-icon" style="background:#fef2f2;">🔐</div>
                <h3>Controle de Acesso e Auditoria</h3>
                <p>Perfis, permissões granulares e rastreamento completo de todas as ações.</p>
                <ul>
                    <li>6 perfis de acesso pré-configurados</li>
                    <li>Permissões por página e por perfil</li>
                    <li>Auditoria automática de CREATE/UPDATE/DELETE</li>
                    <li>Registro de IP, user-agent e endpoint</li>
                    <li>Consulta de logs com filtros avançados</li>
                </ul>
            </article>

        </div>
    </div>
</section>

<!-- ── BENEFÍCIOS ── -->
<section id="beneficios" class="benefits" aria-labelledby="beneficios-title">
    <div class="container">
        <span class="section-tag green">Benefícios</span>
        <h2 id="beneficios-title">Por que o Sysdoc transforma a gestão municipal de saúde</h2>
        <p class="section-lead">Cada funcionalidade foi pensada para resolver problemas reais do serviço público.</p>

        <div class="benefits-grid">
            <div class="benefit-card">
                <div class="icon">🗂️</div>
                <h3>Elimina planilhas e sistemas isolados</h3>
                <p>Laboratório, farmácia, TFD e vigilância em um único sistema integrado, sem retrabalho ou duplicação de dados.</p>
            </div>
            <div class="benefit-card">
                <div class="icon">🔍</div>
                <h3>Rastreabilidade completa</h3>
                <p>Toda ação no sistema é auditada: quem fez, quando, de qual IP, com antes e depois registrados.</p>
            </div>
            <div class="benefit-card">
                <div class="icon">🌐</div>
                <h3>Transparência pública automática</h3>
                <p>Resultados de exames, disponibilidade de medicamentos e aquisições disponíveis para o cidadão sem burocracia.</p>
            </div>
            <div class="benefit-card">
                <div class="icon">⚖️</div>
                <h3>Conformidade legal</h3>
                <p>Atende à Lei 2488 (transparência farmacêutica) e à Portaria GM/MS 6.907/2025 (indicadores APS).</p>
            </div>
            <div class="benefit-card">
                <div class="icon">🤖</div>
                <h3>Inteligência Artificial integrada</h3>
                <p>Ofícios e portarias redigidos com IA em segundos, mantendo o padrão formal exigido pelo serviço público.</p>
            </div>
            <div class="benefit-card">
                <div class="icon">📡</div>
                <h3>Indicadores APS sem exportação manual</h3>
                <p>Dados do eSUS PEC consultados diretamente pelo sistema. Chega de exportar planilhas para calcular indicadores.</p>
            </div>
        </div>
    </div>
</section>

<!-- ── COMO FUNCIONA ── -->
<section id="como-funciona" aria-labelledby="como-title">
    <div class="container">
        <span class="section-tag">Como funciona</span>
        <h2 id="como-title">Fluxo simples para cada serviço</h2>
        <p class="section-lead">Cada módulo segue um fluxo claro que reflete o processo real da secretaria de saúde.</p>

        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 3rem; margin-top: 1rem;">

            <div>
                <h3 style="font-size:1rem; font-weight:700; color:var(--blue); margin-bottom:1.5rem;">🔬 Laboratório</h3>
                <div class="steps">
                    <div class="step"><div class="step-num">1</div><div><h3>Pedido de exame</h3><p>Profissional registra o pedido com exames selecionados e médico solicitante.</p></div></div>
                    <div class="step"><div class="step-num">2</div><div><h3>Coleta e análise</h3><p>Status avança de "solicitado" para "coletado" e "em análise".</p></div></div>
                    <div class="step"><div class="step-num">3</div><div><h3>Laudo e entrega</h3><p>Resultado liberado gera laudo em PDF com protocolo único para consulta pública.</p></div></div>
                </div>
            </div>

            <div>
                <h3 style="font-size:1rem; font-weight:700; color:var(--green); margin-bottom:1.5rem;">🏥 Atendimento</h3>
                <div class="steps">
                    <div class="step"><div class="step-num">1</div><div><h3>Emissão de senha</h3><p>Recepcionista emite senha digital com número sequencial diário.</p></div></div>
                    <div class="step"><div class="step-num">2</div><div><h3>Chamada na fila</h3><p>Atendente chama próximo cliente. Painel TV atualiza automaticamente.</p></div></div>
                    <div class="step"><div class="step-num">3</div><div><h3>Registro do atendimento</h3><p>Profissional registra notas e finaliza. Histórico completo salvo.</p></div></div>
                </div>
            </div>

            <div>
                <h3 style="font-size:1rem; font-weight:700; color:#9333ea; margin-bottom:1.5rem;">🚌 TFD</h3>
                <div class="steps">
                    <div class="step"><div class="step-num">1</div><div><h3>Cadastro da viagem</h3><p>Gestor cria viagem com rota, data, veículo e motorista.</p></div></div>
                    <div class="step"><div class="step-num">2</div><div><h3>Pacientes confirmados</h3><p>Pacientes são adicionados e confirmam presença individualmente.</p></div></div>
                    <div class="step"><div class="step-num">3</div><div><h3>Dashboard de resultados</h3><p>km rodados, pessoas transportadas e motoristas no dashboard TFD.</p></div></div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- ── AUDITORIA HIGHLIGHT ── -->
<section aria-labelledby="audit-title" style="background:var(--gray-50);">
    <div class="container">
        <div class="highlight">
            <h2 id="audit-title">Auditoria completa em cada ação</h2>
            <p>Cada criação, edição ou exclusão no sistema é registrada automaticamente com quem fez, quando, de qual dispositivo e o que mudou — antes e depois.</p>
            <ul class="highlight-list">
                <li>✓ Registro de LOGIN e LOGOUT</li>
                <li>✓ CREATE / UPDATE / DELETE auditados</li>
                <li>✓ IP e user-agent registrados</li>
                <li>✓ Antes e depois preservados</li>
                <li>✓ Dados sensíveis sanitizados</li>
                <li>✓ Consulta com filtros por usuário e ação</li>
            </ul>
            <a href="#contato" class="btn-primary">Solicitar demonstração</a>
        </div>
    </div>
</section>

<!-- ── FAQ ── -->
<section id="faq" class="faq" aria-labelledby="faq-title">
    <div class="container">
        <span class="section-tag">Dúvidas frequentes</span>
        <h2 id="faq-title">Perguntas frequentes</h2>
        <p class="section-lead">Tudo que você precisa saber antes de adotar o Sysdoc na sua secretaria.</p>

        <div class="faq-list">

            <details>
                <summary>O sistema funciona para municípios de qualquer porte?</summary>
                <p>Sim. O Sysdoc foi desenvolvido pensando em municípios de pequeno e médio porte, onde uma única plataforma precisa cobrir laboratório, farmácia, TFD, vigilância sanitária e documentação. O sistema de permissões granular permite configurar o acesso de cada colaborador exatamente ao que ele precisa.</p>
            </details>

            <details>
                <summary>O cidadão pode consultar o resultado do exame online?</summary>
                <p>Sim. O módulo de laboratório gera um protocolo único para cada resultado. O cidadão acessa a página pública de consulta, informa o protocolo e visualiza ou baixa o laudo em PDF — sem necessidade de login.</p>
            </details>

            <details>
                <summary>Como funciona a transparência pública da farmácia?</summary>
                <p>O Sysdoc publica automaticamente a disponibilidade diária de medicamentos e as aquisições mensais em painéis públicos, atendendo à Lei 2488. O cidadão acessa via link direto, sem login, e visualiza o status de cada medicamento e os valores pagos nas aquisições.</p>
            </details>

            <details>
                <summary>Preciso de licença do OpenAI para usar a geração de documentos com IA?</summary>
                <p>Sim. O módulo de geração de ofícios e portarias por Inteligência Artificial usa a API da OpenAI (GPT). É necessário configurar uma chave de API da OpenAI no ambiente do servidor. O Sysdoc faz a integração — você precisa apenas de uma conta OpenAI com créditos disponíveis.</p>
            </details>

            <details>
                <summary>O Monitor APS precisa de acesso ao eSUS PEC?</summary>
                <p>Sim. O Monitor APS conecta-se diretamente ao banco de dados PostgreSQL do eSUS PEC instalado no município, em modo somente leitura. É necessário que o servidor eSUS permita conexão externa da máquina do Sysdoc na porta configurada (padrão 5433).</p>
            </details>

            <details>
                <summary>O sistema tem controle de quem acessou o quê?</summary>
                <p>Sim. Toda ação autenticada é registrada automaticamente: login, logout, criação, edição e exclusão de qualquer registro. Os logs ficam disponíveis para consulta pelo administrador com filtros por usuário, tipo de ação, período e módulo.</p>
            </details>

            <details>
                <summary>Como são gerenciados os perfis de acesso?</summary>
                <p>O Sysdoc possui 6 perfis pré-configurados (admin, gestor, usuário, TFD, motorista e parceiro), cada um com acesso restrito aos módulos pertinentes. O administrador pode ainda criar perfis personalizados e definir quais páginas cada perfil pode acessar, com controle granular por página.</p>
            </details>

        </div>
    </div>
</section>

<!-- ── CTA FINAL ── -->
<section id="contato" class="cta-final" aria-labelledby="cta-title">
    <div class="cta-box">
        <h2 id="cta-title">Pronto para modernizar a gestão de saúde do seu município?</h2>
        <p>Entre em contato e agende uma demonstração gratuita. Mostre ao gestor como o Sysdoc funciona na prática — com os dados reais da sua secretaria.</p>
        <a href="mailto:contato@dlsistemas.com.br" class="cta-email">contato@dlsistemas.com.br</a>
        <br><br>
        <a href="/public/manual/manual.html" class="btn-primary" style="display:inline-block;">
            📖 Baixar manual de uso
        </a>
    </div>
</section>

<!-- ── FOOTER ── -->
<footer aria-label="Rodapé">
    <div class="footer-inner">
        <div>
            <div class="footer-brand">Sys<span>doc</span></div>
            <p class="footer-desc">Sistema de Gestão Municipal de Saúde — integrado, auditado e em conformidade com a legislação pública.</p>
        </div>
        <div class="footer-col">
            <h4>Módulos</h4>
            <ul>
                <li>Laboratório Clínico</li>
                <li>Farmácia Municipal</li>
                <li>Vigilância Sanitária</li>
                <li>TFD — Transporte Fretado</li>
                <li>Fila de Atendimento</li>
                <li>Documentos com IA</li>
                <li>Monitor APS</li>
            </ul>
        </div>
        <div class="footer-col">
            <h4>Recursos</h4>
            <ul>
                <li>Dashboards analíticos</li>
                <li>Auditoria completa</li>
                <li>Transparência pública (Lei 2488)</li>
                <li>Consulta pública de exames</li>
                <li>Painel TV de atendimento</li>
            </ul>
        </div>
        <div class="footer-col">
            <h4>Contato</h4>
            <ul>
                <li>contato@dlsistemas.com.br</li>
                <li>dlsistemas.com.br</li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">
        &copy; {{ date('Y') }} Sysdoc — DL Sistemas. Todos os direitos reservados.
    </div>
</footer>

</body>
</html>
