<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinição de Senha</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f5f5f5; margin: 0; padding: 20px; }
        .container { max-width: 580px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .header { background-color: #1976d2; padding: 32px; text-align: center; }
        .header h1 { color: #fff; margin: 0; font-size: 24px; }
        .body { padding: 32px; }
        .body p { color: #333; line-height: 1.6; margin: 0 0 16px; }
        .btn { display: inline-block; background-color: #1976d2; color: #fff !important; padding: 14px 32px; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 16px; margin: 16px 0; }
        .footer { padding: 20px 32px; background: #f5f5f5; border-top: 1px solid #eee; }
        .footer p { color: #666; font-size: 12px; margin: 0; }
        .warning { background: #fff3cd; border: 1px solid #ffc107; border-radius: 4px; padding: 12px 16px; margin: 16px 0; }
        .warning p { color: #856404; margin: 0; font-size: 13px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 SysDoc</h1>
        </div>
        <div class="body">
            <p>Olá, <strong>{{ $user->name }}</strong>!</p>
            <p>Recebemos uma solicitação de redefinição de senha para sua conta no <strong>SysDoc</strong>.</p>
            <p>Clique no botão abaixo para criar uma nova senha:</p>

            <div style="text-align: center;">
                <a href="{{ $resetUrl }}" class="btn">Redefinir Minha Senha</a>
            </div>

            <div class="warning">
                <p>⏱ Este link expira em <strong>60 minutos</strong>. Após isso, será necessário solicitar um novo.</p>
            </div>

            <p>Se você não solicitou a redefinição de senha, ignore este e-mail. Sua senha não será alterada.</p>

            <p>Caso o botão não funcione, copie e cole o link abaixo no seu navegador:</p>
            <p style="word-break: break-all; font-size: 12px; color: #666;">{{ $resetUrl }}</p>
        </div>
        <div class="footer">
            <p>Este e-mail foi enviado automaticamente. Não responda a esta mensagem.</p>
            <p>SysDoc — Sistema de Gestão Jr Ferragens</p>
        </div>
    </div>
</body>
</html>
