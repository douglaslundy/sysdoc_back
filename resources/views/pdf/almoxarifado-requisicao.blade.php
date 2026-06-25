<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 11px; }
        h1 { margin: 0 0 4px; font-size: 18px; }
        .muted { color: #6b7280; }
        .section { margin-top: 18px; }
        .grid { width: 100%; border-collapse: collapse; }
        .grid td, .grid th { padding: 7px; border: 1px solid #d1d5db; text-align: left; }
        .grid th { background: #f3f4f6; }
        .status { text-transform: uppercase; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Requisição de Almoxarifado</h1>
    <div class="muted">{{ $requisicao->numero }}</div>

    <table class="grid section">
        <tr>
            <th>Data</th>
            <td>{{ optional($requisicao->data_solicitacao)->format('d/m/Y') }}</td>
            <th>Status</th>
            <td class="status">{{ str_replace('_', ' ', $requisicao->status) }}</td>
        </tr>
        <tr>
            <th>Secretaria</th>
            <td>{{ $requisicao->secretaria?->nome ?? '-' }}</td>
            <th>Requisitante</th>
            <td>{{ $requisicao->requisitante?->name ?? $requisicao->solicitante }}</td>
        </tr>
    </table>

    <div class="section"><strong>Justificativa:</strong> {{ $requisicao->justificativa ?: '-' }}</div>
    <div><strong>Observações:</strong> {{ $requisicao->observacoes ?: '-' }}</div>

    <h3 class="section">Itens</h3>
    <table class="grid">
        <thead>
            <tr>
                <th>Código</th>
                <th>Produto</th>
                <th>Solicitado</th>
                <th>Atendido</th>
                <th>Entregue</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($requisicao->itens as $item)
                <tr>
                    <td>{{ $item->produto?->codigo_interno ?? '-' }}</td>
                    <td>{{ $item->produto?->nome ?? '-' }}</td>
                    <td>{{ number_format((float) $item->quantidade_solicitada, 3, ',', '.') }}</td>
                    <td>{{ number_format((float) $item->quantidade_atendida, 3, ',', '.') }}</td>
                    <td>{{ number_format((float) $item->quantidade_entregue, 3, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h3 class="section">Histórico</h3>
    <table class="grid">
        <thead>
            <tr><th>Data e hora</th><th>Ação</th><th>Usuário</th><th>Observação</th></tr>
        </thead>
        <tbody>
            @foreach ($requisicao->historicos->sortBy('created_at') as $historico)
                <tr>
                    <td>{{ optional($historico->created_at)->format('d/m/Y H:i') }}</td>
                    <td>{{ str_replace('_', ' ', $historico->novo_status) }}</td>
                    <td>{{ $historico->user?->name ?? '-' }}</td>
                    <td>{{ $historico->observacao ?: '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
