<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 11px; }
        h1 { margin: 0 0 2px; font-size: 18px; }
        .muted { color: #6b7280; }
        .section { margin-top: 14px; }
        .grid { width: 100%; border-collapse: collapse; }
        .grid td, .grid th { padding: 6px; border: 1px solid #d1d5db; text-align: left; vertical-align: top; }
        .grid th { background: #f3f4f6; width: 22%; }
        .foto { width: 31%; max-height: 150px; margin: 0 1% 6px 0; }
        .assinatura { margin-top: 36px; width: 100%; }
        .assinatura td { width: 50%; text-align: center; padding-top: 6px; border-top: 1px solid #111; }
    </style>
</head>
<body>
    <h1>Ordem de Serviço {{ $os->numero }}</h1>
    <div class="muted">{{ $os->secretaria?->nome }} · {{ strtoupper(str_replace('_', ' ', $os->status)) }}</div>

    <table class="grid section">
        <tr><th>Tipo de serviço</th><td>{{ $os->tipo?->nome }}</td><th>Prioridade</th><td>{{ ucfirst($os->prioridade) }}</td></tr>
        <tr><th>Local</th><td>{{ $os->local }}</td><th>Prazo</th><td>{{ optional($os->prazo)->format('d/m/Y') ?? '-' }}</td></tr>
        <tr><th>Endereço</th><td colspan="3">{{ $os->endereco ?? '-' }}@if($os->latitude && $os->longitude) ({{ $os->latitude }}, {{ $os->longitude }})@endif</td></tr>
        <tr><th>Solicitante</th><td>{{ $os->solicitante?->name }}</td><th>Técnico</th><td>{{ $os->tecnico?->name ?? '-' }}</td></tr>
        <tr><th>Contato no local</th><td colspan="3">{{ $os->contato_nome ?? '-' }} {{ $os->contato_telefone }}</td></tr>
        <tr><th>O que deve ser feito</th><td colspan="3">{{ $os->descricao }}</td></tr>
        <tr><th>Problema constatado</th><td colspan="3">{{ $os->problema ?? '-' }}</td></tr>
    </table>

    @if($os->fechamento_relato)
    <table class="grid section">
        <tr><th>Relato do atendimento</th><td>{{ $os->fechamento_relato }}</td></tr>
        <tr><th>Materiais usados</th><td>{{ $os->materiais ?? '-' }}</td></tr>
        <tr><th>Tempo gasto</th><td>{{ $os->tempo_gasto_min !== null ? $os->tempo_gasto_min.' min' : '-' }}</td></tr>
    </table>
    @endif

    @if($fotos->isNotEmpty())
    <div class="section">
        @foreach($fotos as $f)
            <img class="foto" src="{{ $f['src'] }}" alt="foto {{ $f['fase'] }}">
        @endforeach
    </div>
    @endif

    <table class="assinatura">
        <tr><td>Técnico responsável</td><td>Responsável pelo local</td></tr>
    </table>
</body>
</html>
