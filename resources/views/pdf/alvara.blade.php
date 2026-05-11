<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<style>
  @page { margin: 1.5cm 2cm 1.5cm 2cm; }
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; }

  /* ── CABEÇALHO ── */
  .header-wrap {
    display: table; width: 100%;
    border-bottom: 2px solid #1565c0;
    padding-bottom: 10px; margin-bottom: 12px;
  }
  .header-center, .header-brasao {
    display: table-cell; vertical-align: middle; padding: 4px 8px;
  }
  .header-brasao { width: 90px; text-align: center; }
  .header-center { text-align: center; }
  .header-brasao img { width: 70px; height: 70px; }
  .inst-uf       { font-size: 10px; color: #555; text-transform: uppercase; letter-spacing: 0.5px; }
  .inst-prefeitura { font-size: 13px; font-weight: bold; color: #1565c0; text-transform: uppercase; }
  .inst-secretaria { font-size: 11px; font-weight: bold; margin-top: 2px; text-transform: uppercase; }
  .inst-divisao  { font-size: 10px; color: #444; margin-top: 2px; text-transform: uppercase; }
  .inst-contato  { font-size: 9px; color: #666; margin-top: 4px; }

  /* ── TÍTULO ── */
  .titulo-bloco {
    text-align: center;
    border: 2px solid #1565c0;
    border-radius: 4px;
    padding: 12px;
    margin: 16px 0;
  }
  .titulo-principal {
    font-size: 16px; font-weight: bold; color: #1565c0;
    text-transform: uppercase; letter-spacing: 1px;
  }
  .titulo-numero {
    font-size: 13px; font-weight: bold; color: #333;
    margin-top: 6px;
  }

  /* ── CORPO ── */
  .intro-text {
    font-size: 11px; line-height: 1.6; text-align: justify;
    margin: 14px 0 10px;
  }

  /* ── TABELA DE DADOS ── */
  .dados-table { width: 100%; border-collapse: collapse; margin: 14px 0; }
  .dados-table td { padding: 6px 10px; border: 1px solid #cfd8dc; vertical-align: top; }
  .dados-table .label {
    width: 38%; background: #e3f0fc;
    font-weight: bold; font-size: 10px; text-transform: uppercase;
    color: #1565c0; white-space: nowrap;
  }
  .dados-table .value { font-size: 11px; }
  .risco-1 { color: #2e7d32; font-weight: bold; }
  .risco-2 { color: #e65100; font-weight: bold; }
  .risco-3 { color: #c62828; font-weight: bold; }

  /* ── OBSERVAÇÕES ── */
  .obs-block {
    border: 1px solid #b0bec5; border-radius: 3px;
    padding: 8px 12px; margin-top: 14px;
    background: #fafafa;
  }
  .obs-title {
    font-size: 10px; font-weight: bold; color: #1565c0;
    text-transform: uppercase; letter-spacing: 0.5px;
    border-bottom: 1px solid #e0e0e0; padding-bottom: 4px; margin-bottom: 6px;
  }
  .obs-item { font-size: 10px; line-height: 1.5; }

  /* ── ASSINATURA ── */
  .assinatura-wrap {
    margin-top: 50px; text-align: center;
  }
  .assinatura-cidade {
    font-size: 10px; color: #555; margin-bottom: 40px;
  }
  .assinatura-line { border-top: 1px solid #555; width: 260px; margin: 0 auto 5px; }
  .assinatura-nome { font-size: 11px; font-weight: bold; text-transform: uppercase; }
  .assinatura-cargo { font-size: 10px; color: #555; }

  /* ── RODAPÉ ── */
  .rodape {
    margin-top: 30px; border-top: 1px solid #ccc;
    padding-top: 6px; font-size: 8px; color: #777;
    text-align: center;
  }
</style>
</head>
<body>

  {{-- ══ CABEÇALHO ══ --}}
  <div class="header-wrap">
    <div class="header-center">
      @if($config->estado || $config->nome_municipio)
        <div class="inst-uf">
          Estado {{ $config->estado ?: '—' }}
        </div>
      @endif
      <div class="inst-prefeitura">
        {{ $config->nome_prefeitura ?: 'Prefeitura Municipal' }}
      </div>
      @if($config->nome_secretaria)
        <div class="inst-secretaria">{{ $config->nome_secretaria }}</div>
      @endif
      @if($config->divisao)
        <div class="inst-divisao">{{ $config->divisao }}</div>
      @endif
      @php
        $contatos = collect([
          $config->endereco ?: null,
          $config->telefone ? 'Tel: ' . $config->telefone : null,
          $config->email    ?: null,
          $config->cnpj_secretaria ? 'CNPJ: ' . $config->cnpj_secretaria : null,
        ])->filter()->implode(' | ');
      @endphp
      @if($contatos)
        <div class="inst-contato">{{ $contatos }}</div>
      @endif
    </div>
    <div class="header-brasao">
      @if($brasaoB64)
        <img src="{{ $brasaoB64 }}" alt="Brasão">
      @endif
    </div>
  </div>

  {{-- ══ TÍTULO ══ --}}
  <div class="titulo-bloco">
    <div class="titulo-principal">{{ $config->grant_type ?: 'ALVARÁ SANITÁRIO DE FUNCIONAMENTO' }}</div>
    <div class="titulo-numero">Nº {{ $alvara->numero_alvara }}</div>
  </div>

  {{-- ══ TEXTO INTRODUTÓRIO ══ --}}
  @php
    $secretaria = $config->nome_secretaria ?: 'a Secretaria Municipal de Saúde';
    $municipio  = $config->nome_municipio  ?: 'este Município';
  @endphp
  <div class="intro-text">
    A {{ $secretaria }}, do Município de {{ $municipio }},
    no uso de suas atribuições legais, <strong>CONCEDE</strong> o presente
    <strong>{{ $config->grant_type ?: 'ALVARÁ SANITÁRIO DE FUNCIONAMENTO' }}</strong>
    ao estabelecimento abaixo identificado, nos termos da legislação sanitária vigente.
  </div>

  {{-- ══ DADOS DO ALVARÁ ══ --}}
  @php
    $est = $alvara->estabelecimento;
    $riscoLabel = match($alvara->nivel_risco) {
        '1'   => 'Grau 1 — Baixo',
        '2'   => 'Grau 2 — Médio',
        '3'   => 'Grau 3 — Alto',
        'N/A' => 'N/A',
        default => $alvara->nivel_risco ?? '—',
    };
    $riscoClass = match($alvara->nivel_risco) {
        '1' => 'risco-1', '2' => 'risco-2', '3' => 'risco-3', default => '',
    };
    $fmtDate = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('d/m/Y') : '—';
  @endphp

  <table class="dados-table">
    <tr>
      <td class="label">Estabelecimento</td>
      <td class="value" style="text-transform: uppercase; font-weight: bold;">
        {{ strtoupper($est?->nome_estabelecimento ?? '—') }}
      </td>
    </tr>
    <tr>
      <td class="label">Responsável</td>
      <td class="value">{{ strtoupper($est?->nome_responsavel ?? '—') }}</td>
    </tr>
    <tr>
      <td class="label">Endereço</td>
      <td class="value">{{ strtoupper($est?->endereco ?? '—') }}</td>
    </tr>
    @if($est?->cnaes)
    <tr>
      <td class="label">Atividade (CNAE)</td>
      <td class="value">{{ $est->cnaes }}</td>
    </tr>
    @endif
    <tr>
      <td class="label">Nível de Risco Sanitário</td>
      <td class="value {{ $riscoClass }}">{{ $riscoLabel }}</td>
    </tr>
    <tr>
      <td class="label">Status</td>
      <td class="value">{{ $alvara->status ?? '—' }}</td>
    </tr>
    <tr>
      <td class="label">Data de Emissão</td>
      <td class="value">{{ $fmtDate($alvara->data_alvara) }}</td>
    </tr>
    <tr>
      <td class="label">Válido até</td>
      <td class="value" style="font-weight: bold; color: #1565c0;">
        {{ $fmtDate($alvara->vencimento_alvara) }}
      </td>
    </tr>
    @if($alvara->contato)
    <tr>
      <td class="label">Contato</td>
      <td class="value">{{ $alvara->contato }}</td>
    </tr>
    @endif
  </table>

  {{-- ══ OBSERVAÇÕES (config) ══ --}}
  @if($config->observacoes && count($config->observacoes) > 0)
    <div class="obs-block">
      <div class="obs-title">Observações</div>
      @foreach($config->observacoes as $i => $obs)
        <div class="obs-item">{{ ($i + 1) }}. {{ $obs }}</div>
      @endforeach
    </div>
  @endif

  {{-- ══ ASSINATURA ══ --}}
  <div class="assinatura-wrap">
    @php
      $dataHoje = \Carbon\Carbon::now()->format('d/m/Y');
    @endphp
    <div class="assinatura-cidade">
      {{ $config->nome_municipio ?: 'Município' }}
      @if($config->estado), {{ $config->estado }}@endif,
      {{ $dataHoje }}
    </div>
    <div class="assinatura-line"></div>
    <div class="assinatura-nome">{{ strtoupper($config->nome_responsavel ?: 'Responsável pela Vigilância Sanitária') }}</div>
    <div class="assinatura-cargo">{{ $config->cargo_responsavel ?: '' }}</div>
    @if($config->nome_secretaria)
      <div class="assinatura-cargo">{{ $config->nome_secretaria }}</div>
    @endif
  </div>

  {{-- ══ RODAPÉ ══ --}}
  <div class="rodape">
    Documento gerado eletronicamente em {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}
    @if($config->nome_prefeitura) | {{ $config->nome_prefeitura }}@endif
  </div>

</body>
</html>
