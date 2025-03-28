<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Fila SUS - Ilicínea</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- Fonte moderna --}}
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            background-color: #f1f5f9;
            color: #1f2937;
            padding: 20px;
        }

        .container {
            max-width: 1100px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            background-color: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 3px 6px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }

        .logo {
            width: 80px;
            height: auto;
            margin-bottom: 10px;
        }

        .header-text {
            font-size: 14px;
            line-height: 1.5;
        }

        .titulo {
            font-size: 20px;
            font-weight: bold;
            margin-top: 10px;
        }

        .data {
            font-size: 12px;
            color: #555;
        }

        .especialidade {
            font-size: 18px;
            font-weight: bold;
            margin: 40px 0 15px;
            border-bottom: 2px solid #cbd5e1;
            padding-bottom: 5px;
        }

        .tabela-fila {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        @media(min-width: 768px) {
            .tabela-fila {
                flex-direction: row;
            }
        }

        .coluna {
            flex: 1;
            background-color: #fff;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        }

        .coluna strong {
            display: block;
            margin-bottom: 10px;
            font-size: 15px;
            color: #1e293b;
        }

        .item-fila {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            margin-bottom: 8px;
            white-space: nowrap;
        }

        .cor {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .amarelo {
            background-color: #facc15;
        }

        .vermelho {
            background-color: #ef4444;
        }

        .info {
            display: flex;
            gap: 10px;
            align-items: center;
            font-size: 14px;
            line-height: 1;
        }

        .bloco {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .valor {
            font-weight: bold;
            font-size: 15px;
            line-height: 1;
        }

        .print-btn {
            display: block;
            margin: 0 auto 40px;
            padding: 10px 25px;
            background-color: #2563eb;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .print-btn:hover {
            background-color: #1d4ed8;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="{{ asset('file/brasao.png') }}" class="logo" alt="Brasão">
            <div class="header-text">
                <div>SECRETARIA MUNICIPAL DE SAÚDE DE ILICÍNEA</div>
                <div>Rua 02 de Novembro, 96 - Centro | TEL: 0800 035 1319</div>
                <div>Email: saude@ilicinea.mg.gov.br</div>
            </div>
            <div class="titulo">LISTA DE ESPECIALIDADES / FILA - SUS</div>
            <div class="data">Documento gerado em {{ $data_geracao }}</div>
        </div>

        @foreach ($agrupadas as $especialidade => $filas)
            <div class="especialidade">{{ strtoupper($especialidade) }}</div>
            <div class="tabela-fila">
                <div class="coluna">
                    <strong>Fila Comum ({{ count($filas['comum']) }} registros)</strong>
                    @forelse ($filas['comum'] as $item)
                        <div class="item-fila">
                            <span class="cor amarelo"></span>
                            <div class="info">
                                <div class="bloco">Posição: <span class="valor">{{ $item->position }}º</span></div>
                                <div class="bloco">- Protocolo: <span class="valor">{{ $item->id }}</span></div>
                            </div>
                        </div>
                    @empty
                        <span class="info">Nenhum registro nesta fila.</span>
                    @endforelse
                </div>

                <div class="coluna">
                    <strong>Fila de Urgência ({{ count($filas['urgencia']) }} registros)</strong>
                    @forelse ($filas['urgencia'] as $item)
                        <div class="item-fila">
                            <span class="cor vermelho"></span>
                            <div class="info">
                                <div class="bloco">Posição: <span class="valor">{{ $item->position }}º</span></div>
                                <div class="bloco">- Protocolo: <span class="valor">{{ $item->id }}</span></div>
                            </div>
                        </div>
                    @empty
                        <span class="info">Nenhum registro nesta fila.</span>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>
</body>
</html>
