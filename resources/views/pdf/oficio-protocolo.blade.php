<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 12px; line-height: 1.6; }
        h1 { margin: 0; font-size: 20px; text-align: center; }
        .number { margin: 4px 0 28px; color: #6b7280; text-align: center; }
        .field { margin-bottom: 14px; }
        .label { font-weight: bold; }
        .content { margin-top: 24px; white-space: pre-wrap; text-align: justify; }
        .signature { margin-top: 42px; text-align: right; }
    </style>
</head>
<body>
    <h1>OFÍCIO</h1>
    <div class="number">Nº {{ $letter->number }}/{{ optional($letter->created_at)->format('Y') }}</div>

    <div class="field"><span class="label">Remetente:</span> {{ $letter->sender }}</div>
    <div class="field"><span class="label">Destinatário:</span> {{ $letter->recipient }}</div>
    <div class="field"><span class="label">Assunto:</span> {{ $letter->subject_matter }}</div>

    @if ($letter->summary)
        <div class="field"><span class="label">Resumo:</span> {{ $letter->summary }}</div>
    @endif

    <div class="content">{{ $letter->obs }}</div>

    <div class="signature">
        {{ $letter->user?->name }}<br>
        {{ optional($letter->created_at)->format('d/m/Y H:i') }}
    </div>
</body>
</html>
