<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; }
  .page { width: 100%; padding: 20px; }
  .header { border-bottom: 2px solid #1565c0; padding-bottom: 10px; margin-bottom: 16px; }
  .header h1 { font-size: 18px; color: #1565c0; }
  .header .subtitle { font-size: 10px; color: #555; margin-top: 2px; }
  .section-title { font-size: 12px; font-weight: bold; background: #e3f0fc; padding: 4px 8px; margin: 14px 0 6px; }
  .info-grid { display: table; width: 100%; border-collapse: collapse; }
  .info-row { display: table-row; }
  .info-cell { display: table-cell; padding: 3px 6px; width: 50%; }
  .info-label { font-size: 9px; color: #777; text-transform: uppercase; letter-spacing: 0.5px; }
  .info-value { font-size: 11px; font-weight: bold; }
  table { width: 100%; border-collapse: collapse; margin-top: 6px; }
  th { background: #1565c0; color: #fff; text-align: left; padding: 5px 8px; font-size: 10px; }
  td { padding: 5px 8px; border-bottom: 1px solid #eee; font-size: 11px; }
  tr:nth-child(even) td { background: #f5f9ff; }
  .badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 9px; font-weight: bold; text-transform: uppercase; }
  .badge-normal  { background: #e8f5e9; color: #2e7d32; }
  .badge-baixo   { background: #e3f2fd; color: #1565c0; }
  .badge-alto    { background: #ffebee; color: #c62828; }
  .badge-critico { background: #f3e5f5; color: #6a1b9a; }
  .badge-indefinido { background: #f5f5f5; color: #777; }
  .exame-nome { font-size: 12px; font-weight: bold; color: #1565c0; margin: 12px 0 4px; }
  .footer { margin-top: 20px; border-top: 1px solid #ccc; padding-top: 8px; font-size: 9px; color: #888; }
  .footer .protocolo { font-size: 11px; font-weight: bold; color: #333; }
</style>
</head>
<body>
<div class="page">
  <div class="header">
    <h1>Laudo de Exame Laboratorial</h1>
    <div class="subtitle">Documento gerado em {{ now()->format('d/m/Y H:i') }}</div>
  </div>

  <div class="section-title">Dados do Paciente</div>
  <div class="info-grid">
    <div class="info-row">
      <div class="info-cell">
        <div class="info-label">Nome</div>
        <div class="info-value">{{ $resultado->pedido->cliente->name ?? '—' }}</div>
      </div>
      <div class="info-cell">
        <div class="info-label">Data de Nascimento</div>
        <div class="info-value">{{ $resultado->pedido->cliente->born_date ? \Carbon\Carbon::parse($resultado->pedido->cliente->born_date)->format('d/m/Y') : '—' }}</div>
      </div>
    </div>
    <div class="info-row">
      <div class="info-cell">
        <div class="info-label">Médico Solicitante</div>
        <div class="info-value">{{ $resultado->pedido->medico_solicitante ?? '—' }}</div>
      </div>
      <div class="info-cell">
        <div class="info-label">Data do Pedido</div>
        <div class="info-value">{{ \Carbon\Carbon::parse($resultado->pedido->data_pedido)->format('d/m/Y') }}</div>
      </div>
    </div>
  </div>

  <div class="section-title">Resultados</div>

  @foreach($camposPorExame as $exameId => $campos)
    @php $exame = $campos->first()->campo->exame ?? null; @endphp
    <div class="exame-nome">{{ $exame->nome ?? 'Exame #'.$exameId }}</div>
    <table>
      <thead>
        <tr>
          <th>Campo</th>
          <th>Valor</th>
          <th>Unidade</th>
          <th>Referência</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        @foreach($campos as $rc)
          @php
            $campo = $rc->campo;
            $valor = $rc->valor_numerico ?? $rc->valor_texto ?? '—';
            $ref = '—';
            if ($campo && $campo->referencias->isNotEmpty()) {
                $r = $campo->referencias->first();
                if ($r->valor_min !== null && $r->valor_max !== null) {
                    $ref = number_format($r->valor_min, 2) . ' – ' . number_format($r->valor_max, 2);
                } elseif ($r->valor_texto) {
                    $ref = $r->valor_texto;
                }
            }
          @endphp
          <tr>
            <td>{{ $campo->nome ?? '—' }}</td>
            <td>{{ $valor }}</td>
            <td>{{ $campo->unidade ?? '—' }}</td>
            <td>{{ $ref }}</td>
            <td><span class="badge badge-{{ $rc->status_referencia }}">{{ $rc->status_referencia }}</span></td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endforeach

  <div class="footer">
    <div>Liberado em: {{ $resultado->data_liberacao ? \Carbon\Carbon::parse($resultado->data_liberacao)->format('d/m/Y H:i') : '—' }}
      &nbsp;|&nbsp; Válido até: {{ $resultado->data_validade ? \Carbon\Carbon::parse($resultado->data_validade)->format('d/m/Y') : '—' }}
    </div>
    <div class="protocolo">Protocolo: {{ $resultado->protocolo }}</div>
  </div>
</div>
</body>
</html>
