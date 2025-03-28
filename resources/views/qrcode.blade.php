@section('title', 'Consulta Especialidades - SUS - Ilicínea')

<div class="container">
    <!-- Logo centralizado -->
    <div class="logo-container">
        <img src="{{ asset('files/brasao.png') }}" alt="Logo" class="logo">
    </div>

    <!-- Informações do SUS com espaçamento entre linhas reduzido -->
    <div class="info-sus">
        <p>SECRETARIA MUNICIPAL DE SAÚDE DE ILICÍNEA</p>
        <p>Rua 02 de Novembro, 96 - Centro TEL: 0800 035 1319</p>
        <p>saude@ilicinea.mg.gov.br</p>
    </div>

    @if ($queue)
        <header>
            <h1>PROTOCOLO Nº {{ $queue->id }}</h1>
        </header>
        <main>
            <div class="card">
                <p><strong>STATUS:</strong> {{ $queue->done == 0 ? 'EM ESPERA' : 'REALIZADO' }}</p>
                <p><strong>POSIÇÃO:</strong> {{ $queue->position }}</p>
                <p><strong>FILA:</strong> {{ $queue->urgency == 1 ? 'URGÊNCIA' : 'COMUM' }}</p>
                <p><strong>Nome do Cliente:</strong> Informação omitida em conformidade com a LGPD</p>
                <p><strong>Especialidade:</strong> {{ $queue->speciality->name ?? 'Não informado' }}</p>
                <p><strong>Entrou na fila em:</strong> {{ \Carbon\Carbon::parse($queue->created_at)->format('d/m/Y H:i') }}</p>
                <p><strong>Última movimentação:</strong> {{ \Carbon\Carbon::parse($queue->updated_at)->format('d/m/Y H:i') }}</p>
            </div>
        </main>
    @else
        <header>
            <h1>Dados não encontrados</h1>
        </header>
        <main>
            <p>Verifique o link ou tente novamente mais tarde.</p>
        </main>
    @endif
</div>

<style>
    :root {
        --primary-color: #005EA6; /* Azul SUS */
        --secondary-color: #009639; /* Verde SUS */
    }

    .container {
        min-height: 100vh;
        background-color: var(--background-color);
        padding: 20px;
        font-family: Arial, sans-serif;
        color: #000;
    }

    .logo-container {
        text-align: center;
        margin-bottom: 20px;
    }

    .logo {
        max-width: 150px;
        width: 100%;
        height: auto;
    }

    .info-sus {
        text-align: center;
        margin-bottom: 10px;
        font-size: 1.1rem;
        line-height: 0.5; /* Diminuindo o espaçamento entre linhas */
    }

    .info-sus p {
        margin-bottom: 0; /* Remove margem extra entre parágrafos */
    }

    header {
        padding: 20px;
        text-align: center;
        border-radius: 8px;
        margin-bottom: 20px;
    }

    header h1 {
        margin: 0;
        font-size: 2rem;
        color: #000;
    }

    main {
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .card {
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        max-width: 600px;
        width: 100%;
    }

    .card p {
        font-size: 1.1rem;
        margin-bottom: 10px;
        color: #000;
    }

    .card strong {
        color: #000;
    }
</style>
