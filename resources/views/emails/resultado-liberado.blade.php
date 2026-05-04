<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
  body { margin: 0; padding: 0; background: #f4f6f8; font-family: Arial, sans-serif; }
  .wrapper { max-width: 600px; margin: 30px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
  .header { background: #1565c0; color: #fff; padding: 28px 32px; }
  .header h1 { margin: 0; font-size: 20px; }
  .body { padding: 28px 32px; }
  .body p { color: #444; font-size: 14px; line-height: 1.6; margin: 0 0 16px; }
  .credentials { background: #f0f4ff; border: 1px solid #c5d5f7; border-radius: 6px; padding: 16px 20px; margin: 20px 0; }
  .credentials .label { font-size: 11px; color: #888; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
  .credentials .value { font-size: 18px; font-weight: bold; color: #1565c0; letter-spacing: 2px; }
  .btn { display: inline-block; background: #1565c0; color: #fff !important; text-decoration: none; padding: 12px 28px; border-radius: 6px; font-size: 14px; font-weight: bold; margin-top: 8px; }
  .footer { background: #f4f6f8; padding: 16px 32px; font-size: 11px; color: #aaa; text-align: center; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <h1>Resultado de Exame Disponível</h1>
  </div>
  <div class="body">
    <p>Olá, <strong>{{ $resultado->pedido->cliente->name ?? 'Paciente' }}</strong>.</p>
    <p>Seu resultado de exame laboratorial foi liberado e já está disponível para consulta.</p>

    <div class="credentials">
      <div class="label">Protocolo</div>
      <div class="value">{{ $resultado->protocolo }}</div>
    </div>
    <div class="credentials">
      <div class="label">Senha de acesso</div>
      <div class="value">{{ $senha }}</div>
    </div>

    <p>Utilize o protocolo e a senha acima para acessar seu resultado.</p>

    <p style="margin-top: 20px;">
      <a href="{{ config('app.url') }}/consulta-exame" class="btn">Consultar Resultado</a>
    </p>

    <p style="margin-top: 24px; font-size: 12px; color: #888;">
      Este resultado é válido até {{ $resultado->data_validade ? \Carbon\Carbon::parse($resultado->data_validade)->format('d/m/Y') : '—' }}.
      Guarde sua senha com segurança.
    </p>
  </div>
  <div class="footer">
    Mensagem automática — não responda este e-mail.
  </div>
</div>
</body>
</html>
