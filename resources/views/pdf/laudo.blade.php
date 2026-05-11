<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<style>
  @page { margin: 1cm; }
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; }
  .page { width: 100%; }

  /* ── CABEÇALHO ── */
  .header-wrap {
    display: table; width: 100%;
    border: 0;
    margin-bottom: 10px;
  }
  .header-logo, .header-center, .header-brasao {
    display: table-cell; vertical-align: middle; padding: 8px;
  }
  .header-logo   { width: 162px; text-align: center; padding: 8px 10px 8px 28px; }
  .header-brasao { width: 110px; text-align: center; padding: 8px 28px 8px 10px; }
  .header-center { text-align: center; border-left: 0; border-right: 0; }
  .header-logo img  { width: 124px; height: 68px; }
  .header-brasao img { width: 68px; height: 68px; }
  .lab-nome     { font-size: 14px; font-weight: bold; color: #1565c0; }
  .lab-razao    { font-size: 11px; color: #444; margin-top: 2px; }
  .lab-endereco { font-size: 9px;  color: #666; margin-top: 3px; }
  .lab-contato  { font-size: 9px;  color: #666; margin-top: 1px; }

  /* ── DADOS DO PACIENTE ── */
  .paciente-box {
    border: 1px solid #b0bec5; border-radius: 4px;
    padding: 6px 10px; margin-bottom: 10px;
  }
  .paciente-title {
    font-size: 10px; font-weight: bold; color: #1565c0;
    text-transform: uppercase; letter-spacing: 0.5px;
    margin-bottom: 5px; border-bottom: 1px solid #e0e0e0; padding-bottom: 3px;
  }
  .paciente-grid { display: table; width: 100%; }
  .paciente-row  { display: table-row; }
  .paciente-cell { display: table-cell; width: 50%; padding: 2px 4px; }
  .p-label { font-size: 8px; color: #888; text-transform: uppercase; letter-spacing: 0.4px; }
  .p-value { font-size: 10px; font-weight: bold; }

  /* ── RESULTADOS ── */
  .section-title {
    font-size: 11px; font-weight: bold; background: #e3f0fc;
    padding: 4px 8px; margin: 12px 0 5px;
    border-left: 3px solid #1565c0;
  }
  .exame-nome {
    font-size: 11px; font-weight: bold; color: #1565c0;
    margin: 10px 0 3px; padding: 2px 0;
    border-bottom: 1px dashed #90caf9;
  }
  table { width: 100%; border-collapse: collapse; margin-top: 4px; }
  th { background: #1565c0; color: #fff; text-align: left; padding: 4px 7px; font-size: 9px; }
  td { padding: 4px 7px; border-bottom: 1px solid #eee; font-size: 10px; }
  tr:nth-child(even) td { background: #f5f9ff; }
  .badge {
    display: inline-block; padding: 1px 7px; border-radius: 8px;
    font-size: 8px; font-weight: bold; text-transform: uppercase;
  }
  .badge-normal    { background: #e8f5e9; color: #2e7d32; }
  .badge-baixo     { background: #e3f2fd; color: #1565c0; }
  .badge-alto      { background: #ffebee; color: #c62828; }
  .badge-critico   { background: #f3e5f5; color: #6a1b9a; }
  .badge-indefinido{ background: #f5f5f5; color: #777; }

  /* ── ASSINATURA ── */
  .assinatura-block { margin-top: 85px; text-align: center; }
  .assinatura-line  { border-top: 1px solid #555; width: 220px; margin: 0 auto 4px; }
  .assinatura-label { font-size: 9px; color: #555; text-transform: uppercase; letter-spacing: 0.5px; }

  /* ── LIBERAÇÃO ── */
  .liberacao {
    margin-top: 14px; padding: 6px 10px;
    border: 1px solid #e0e0e0; border-radius: 4px;
    font-size: 9px; color: #666;
  }
  .liberacao .protocolo { font-size: 11px; font-weight: bold; color: #333; margin-top: 2px; }

  /* ── RODAPÉ ── */
  .footer {
    margin-top: 16px; border-top: 1px solid #ccc;
    padding-top: 6px; font-size: 8px; color: #555;
  }
  .footer .rodape1 { font-style: italic; font-weight: bold; margin-bottom: 3px; }
  .footer .rodape2 { color: #777; }
</style>
</head>
<body>
<div class="page">

  {{-- ══ CABEÇALHO INSTITUCIONAL ══ --}}
  <div class="header-wrap">
    {{-- Logo SUS --}}
    <div class="header-logo">
      @if($logoSusB64)
        <img src="{{ $logoSusB64 }}" alt="SUS" style="width:124px;height:68px;">
      @endif
    </div>

    {{-- Dados do Laboratório --}}
    <div class="header-center">
      <div class="lab-nome">{{ $config->nome_estabelecimento ?: 'Laboratório Municipal' }}</div>
      @if($config->razao_social)
        <div class="lab-razao">{{ $config->razao_social }}</div>
      @endif
      @php
        $endereco = collect([
          $config->endereco_rua,
          $config->endereco_numero ? 'nº ' . $config->endereco_numero : null,
          $config->endereco_bairro,
          $config->endereco_cep ? 'CEP ' . $config->endereco_cep : null,
        ])->filter()->implode(', ');
      @endphp
      @if($endereco)
        <div class="lab-endereco">{{ $endereco }}</div>
      @endif
      @php
        $contato = collect([
          $config->telefone ? 'Tel: ' . $config->telefone : null,
          $config->cnpj ? 'CNPJ: ' . $config->cnpj : null,
          $config->email_lab,
        ])->filter()->implode(' | ');
      @endphp
      @if($contato)
        <div class="lab-contato">{{ $contato }}</div>
      @endif
    </div>

    {{-- Brasão --}}
    <div class="header-brasao">
      @if($brasaoB64)
        <img src="{{ $brasaoB64 }}" alt="Brasão" style="width:68px;height:68px;">
      @endif
    </div>
  </div>

  {{-- ══ DADOS DO PACIENTE ══ --}}
  @php
    $cliente   = $resultado->pedido->cliente;
    $pedido    = $resultado->pedido;
    $medico    = $pedido->medicoSolicitante;

    $idade = '—';
    if ($cliente->born_date) {
        $anos = \Carbon\Carbon::parse($cliente->born_date)->age;
        $idade = $anos . ' ' . ($anos === 1 ? 'ano' : 'anos');
    }

    $sexoLabel = match(strtolower($cliente->sexo ?? '')) {
        'm', 'masculino', 'masculine' => 'Masculino',
        'f', 'feminino',  'feminine'  => 'Feminino',
        default                       => $cliente->sexo ?? '—',
    };

    $dataColeta = $pedido->data_coleta
        ? \Carbon\Carbon::parse($pedido->data_coleta)->format('d/m/Y')
        : ($pedido->data_pedido ? \Carbon\Carbon::parse($pedido->data_pedido)->format('d/m/Y') : '—');
  @endphp

  <div class="paciente-box">
    <div class="paciente-title">Dados do Paciente</div>
    <div class="paciente-grid">
      <div class="paciente-row">
        <div class="paciente-cell">
          <div class="p-label">Nome Sr(a)</div>
          <div class="p-value">{{ $cliente->name ?? '—' }}</div>
        </div>
        <div class="paciente-cell">
          <div class="p-label">Idade</div>
          <div class="p-value">{{ $idade }}</div>
        </div>
      </div>
      <div class="paciente-row">
        <div class="paciente-cell">
          <div class="p-label">Sexo</div>
          <div class="p-value">{{ $sexoLabel }}</div>
        </div>
        <div class="paciente-cell">
          <div class="p-label">Telefone</div>
          <div class="p-value">{{ $cliente->phone ?? '—' }}</div>
        </div>
      </div>
      <div class="paciente-row">
        <div class="paciente-cell">
          <div class="p-label">Data da Coleta</div>
          <div class="p-value">{{ $dataColeta }}</div>
        </div>
        <div class="paciente-cell">
          <div class="p-label">CPF</div>
          <div class="p-value">{{ $cliente->cpf ?? '—' }}</div>
        </div>
      </div>
      <div class="paciente-row">
        <div class="paciente-cell">
          <div class="p-label">Médico(a) Solicitante</div>
          <div class="p-value">{{ $medico?->nome ?? '—' }}</div>
        </div>
        <div class="paciente-cell">
          <div class="p-label">Data do Pedido</div>
          <div class="p-value">{{ \Carbon\Carbon::parse($pedido->data_pedido)->format('d/m/Y') }}</div>
        </div>
      </div>
    </div>
  </div>

  {{-- ══ RESULTADOS ══ --}}
  <div class="section-title">Resultados dos Exames</div>

  @foreach($camposPorExame as $exameId => $campos)
    @php $exame = $campos->first()->campo->exame ?? null; @endphp
    <div class="exame-nome">{{ $exame->nome ?? 'Exame #' . $exameId }}</div>
    <table>
      <thead>
        <tr>
          <th>Campo</th>
          <th>Valor</th>
          <th>Unidade</th>
          <th>Referência</th>
        </tr>
      </thead>
      <tbody>
        @foreach($campos as $rc)
          @php
            $campo = $rc->campo;
            $valor = $rc->valor_numerico ?? $rc->valor_texto ?? '—';
            $ref   = '—';
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
          </tr>
        @endforeach
      </tbody>
    </table>
  @endforeach

  {{-- ══ ASSINATURA DO RESPONSÁVEL ══ --}}
  <div class="assinatura-block">
    <div class="assinatura-line"></div>
    <div class="assinatura-label">Assinatura do Responsável</div>
  </div>

  {{-- ══ DADOS DE LIBERAÇÃO ══ --}}
  <div class="liberacao">
    <div>
      Liberado em: {{ $resultado->data_liberacao ? \Carbon\Carbon::parse($resultado->data_liberacao)->format('d/m/Y H:i') : '—' }}
      &nbsp;|&nbsp;
      Válido até: {{ $resultado->data_validade ? \Carbon\Carbon::parse($resultado->data_validade)->format('d/m/Y') : '—' }}
    </div>
    <div class="protocolo">Protocolo: {{ $resultado->protocolo }}</div>
  </div>

  {{-- ══ RODAPÉ ══ --}}
  @if($config->rodape1 || $config->rodape2)
    <div class="footer">
      @if($config->rodape1)
        <div class="rodape1">{{ $config->rodape1 }}</div>
      @endif
      @if($config->rodape2)
        <div class="rodape2">{{ $config->rodape2 }}</div>
      @endif
    </div>
  @endif

</div>
</body>
</html>
