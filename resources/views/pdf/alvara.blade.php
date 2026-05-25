<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<style>
  @page { size: A4 portrait; margin: 1.4cm 1.9cm 1.7cm 1.9cm; }
  * { box-sizing: border-box; }
  body {
    margin: 0;
    color: #111;
    font-family: "Times New Roman", Times, serif;
    font-size: 12.6px;
    line-height: 1.35;
  }

  .doc { width: 100%; }

  .header { margin-bottom: 12px; }
  .header-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 4px;
  }
  .logo-cell {
    width: 72px;
    vertical-align: top;
    padding-right: 8px;
  }
  .logo { width: 66px; height: 66px; }
  .header-center {
    text-align: center;
    line-height: 1.2;
  }
  .h-line-1 { font-size: 15px; font-weight: 700; text-transform: uppercase; }
  .h-line-2 { font-size: 12.3px; }
  .h-line-3 { font-size: 11.8px; font-weight: 700; }
  .h-line-4 { font-size: 12px; text-transform: uppercase; margin-top: 2px; }
  .h-line-5 { font-size: 11.1px; margin-top: 1px; }

  .sep {
    border-top: 1px solid #000;
    height: 0;
    margin: 3px 0;
  }

  .title {
    margin: 16px 0 12px;
    text-align: center;
    font-size: 43px;
    font-weight: 700;
    line-height: 1.1;
  }

  .content p {
    margin: 0 0 7px;
    text-align: justify;
  }

  .field-inline { margin: 8px 0; }
  .field-label { font-weight: 700; }

  .validade {
    margin: 12px 0 15px;
    font-size: 20px;
    font-weight: 700;
  }

  .afixacao {
    margin: 20px 0 80px;
    text-align: center;
    font-size: 20px;
    font-weight: 700;
    text-transform: uppercase;
  }

  .assinatura {
    text-align: center;
    margin-top: 34px;
    margin-bottom: 11px;
  }
  .assinatura-linha {
    border-top: 1px solid #000;
    width: 230px;
    margin: 0 auto 2px;
  }
  .assinatura-nome { font-size: 18px; line-height: 1.1; }
  .assinatura-cargo { font-size: 17px; line-height: 1.1; }

  .obs {
    margin-top: 8px;
    font-size: 9px;
    line-height: 1.2;
  }
  .obs strong { font-size: 9.5px; }
</style>
</head>
<body>
  @php
    $est = $alvara->estabelecimento;

    $municipio = $config->nome_municipio ?: 'Licínea';
    $estado = $config->estado ?: 'Minas Gerais';
    $secretaria = $config->nome_secretaria ?: 'FUNDO MUNICIPAL DE SAÚDE DE ' . strtoupper($municipio);
    $prefeitura = $config->nome_prefeitura ?: ('PREFEITURA MUNICIPAL DE ' . strtoupper($municipio));

    $fmtDate = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('d/m/Y') : 'XX/XX/XXXX';
    $fmtDateExtenso = function($d) {
      if (!$d) return 'XX de XXXXXXXX de XXXX';
      $meses = [
        1 => 'janeiro', 2 => 'fevereiro', 3 => 'março', 4 => 'abril',
        5 => 'maio', 6 => 'junho', 7 => 'julho', 8 => 'agosto',
        9 => 'setembro', 10 => 'outubro', 11 => 'novembro', 12 => 'dezembro'
      ];
      $c = \Carbon\Carbon::parse($d);
      return $c->format('d') . ' de ' . ($meses[(int)$c->format('n')] ?? '') . ' de ' . $c->format('Y');
    };

    $numero = $alvara->numero_alvara ?: 'XX-XX/XXXX';
    $responsavel = strtoupper($est?->nome_responsavel ?? 'XXXXXXXXXXXXXXXXX');
    $estabelecimento = strtoupper($est?->nome_estabelecimento ?? 'XXXXXXXXXXXXXXXXX');
    $endereco = strtoupper($est?->endereco ?? 'XXXXXXXXXXXXXXXXX');
    $cnaes = collect();
    if ($est && method_exists($est, 'relationLoaded') && $est->relationLoaded('cnaes')) {
      $cnaes = collect($est->getRelation('cnaes'));
    }
    $validadeExtenso = $fmtDateExtenso($alvara->vencimento_alvara);

    $obs = is_array($config->observacoes) ? array_values($config->observacoes) : [];
  @endphp

  <div class="doc">
    <div class="header">
      <table class="header-table">
        <tr>
          <td class="logo-cell">
            @if($brasaoB64)
              <img class="logo" src="{{ $brasaoB64 }}" alt="Brasão">
            @endif
          </td>
          <td class="header-center">
            <div class="h-line-1">{{ $prefeitura }}</div>
            <div class="h-line-2">Estado de {{ $estado }}</div>
            @if($config->cnpj_prefeitura)
              <div class="h-line-3">CNPJ {{ $config->cnpj_prefeitura }}</div>
            @endif
            <div class="sep"></div>
            <div class="h-line-4">{{ $secretaria }}</div>
            <div class="h-line-5">
              @if($config->cnpj_secretaria) CNPJ - {{ $config->cnpj_secretaria }}@endif
              @if($config->telefone) TEL. {{ $config->telefone }}@endif
            </div>
            @if($config->endereco)<div class="h-line-5">{{ $config->endereco }}</div>@endif
            @if($config->email)<div class="h-line-5">{{ $config->email }}</div>@endif
          </td>
        </tr>
      </table>
    </div>

    <div class="title">
      Alvará Sanitário nº {{ $numero }}
    </div>

    <div class="content">
      <p>
        O setor de Vigilância Sanitária da diretoria de ações descentralizadas da saúde do Município de
        <strong>{{ $municipio }}</strong> - Estado de {{ $estado }}, de acordo com a Lei Municipal 1.543, de 15/12/2006
        em seu Art. 13 e a legislação Estadual vigente e tendo em vista a regularidade do processo em que é interessado
        (a) <strong>{{ $estabelecimento }}</strong>, CNPJ: <strong>{{ $config->cnpj_secretaria ?: 'XXXXXXXXXXXXXX' }}</strong>
        situado (a) na <strong>{{ $endereco }}</strong>, {{ $municipio }} - {{ strtoupper(substr($estado,0,2)) }} CEP
        <strong>{{ $config->cep ?: 'XX.XXX-XXX' }}</strong>, resolve conceder-lhe o <strong>ALVARÁ SANITÁRIO</strong>
        pelo período de 1 (um) ano, que o habilita a manter as seguintes atividades:
      </p>

      @if($cnaes->count() > 0)
        @foreach($cnaes as $i => $item)
          <p>{{ $i + 1 }}. {{ $item->codigo }} - {{ $item->descricao }}</p>
        @endforeach
      @else
        <p>1. XXXXXXXX - DESCRIÇÃO NÃO INFORMADA</p>
      @endif

      <div class="field-inline"><span class="field-label">Responsável Técnico:</span> {{ $responsavel }}</div>
      <div class="field-inline">Este Alvará Sanitário se estende apenas ao(s) CNAE(S) citado(s) acima.</div>
      <div class="field-inline">{{ $municipio }}, {{ $fmtDate($alvara->data_alvara) }}.</div>

      <div class="validade">Validade: {{ $validadeExtenso }}.</div>
    </div>

    <div class="afixacao">O PRESENTE ALVARÁ DEVERÁ SER AFIXADO EM LOCAL VISÍVEL</div>

    <div class="assinatura">
      <div class="assinatura-linha"></div>
      <div class="assinatura-nome">{{ $config->nome_responsavel ?: 'Juliana Vilela Mendes' }}</div>
      <div class="assinatura-cargo">{{ $config->cargo_responsavel ?: 'Coordenadora da Vigilância Sanitária' }}</div>
    </div>

    <div class="obs">
      <strong>Obs:</strong><br>
      @if(count($obs) > 0)
        @foreach($obs as $i => $linha)
          {{ $i + 1 }} - {{ $linha }}<br>
        @endforeach
      @else
        1 - Este Alvará não substitui o Alvará de Licença para localização e funcionamento.<br>
        2 - O presente alvará deverá ser renovado anualmente, de acordo com dispositivo no art.85 do código de Saúde de Minas Gerais, lei 13.317 de 24/09/99.<br>
        3 - O presente Alvará poderá ser cassado a qualquer momento por irregularidade no estabelecimento.
      @endif
    </div>
  </div>
</body>
</html>
