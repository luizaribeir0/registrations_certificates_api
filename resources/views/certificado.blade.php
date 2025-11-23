<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificado de Participação</title>
    <style>
        @page {
            margin: 0;
            size: A4 landscape;
        }
        body {
            margin: 0;
            padding: 0;
            font-family: 'Times New Roman', serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .certificate-container {
            width: 90%;
            max-width: 1000px;
            background: white;
            padding: 60px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            border: 8px solid #d4af37;
            text-align: center;
        }
        .certificate-header {
            font-size: 48px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 30px;
            text-transform: uppercase;
            letter-spacing: 3px;
        }
        .certificate-body {
            font-size: 24px;
            color: #34495e;
            line-height: 1.8;
            margin: 40px 0;
        }
        .certificate-name {
            font-size: 36px;
            font-weight: bold;
            color: #2c3e50;
            margin: 30px 0;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .certificate-event {
            font-size: 28px;
            font-weight: bold;
            color: #2c3e50;
            margin: 30px 0;
            text-transform: uppercase;
        }
        .certificate-date {
            font-size: 20px;
            color: #7f8c8d;
            margin-top: 30px;
        }
        .certificate-code {
            font-size: 16px;
            color: #7f8c8d;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #ecf0f1;
        }
        .certificate-link {
            font-size: 14px;
            color: #3498db;
            margin-top: 10px;
            word-break: break-all;
        }
        .decorative-line {
            width: 200px;
            height: 3px;
            background: linear-gradient(to right, transparent, #d4af37, transparent);
            margin: 30px auto;
        }
    </style>
</head>
<body>
    <div class="certificate-container">
        <div class="certificate-header">Certificado de Participação</div>
        <div class="decorative-line"></div>
        <div class="certificate-body">
            Certificamos que
        </div>
        <div class="certificate-name">{{ $usuario->nome }}</div>
        <div class="certificate-body">
            participou do evento
        </div>
        <div class="certificate-event">{{ $evento->descricao }}</div>
        <div class="certificate-date">
            realizado em {{ date('d/m/Y', strtotime($evento->data_final)) }}
        </div>
        <div class="certificate-code">
            <strong>Código do Certificado:</strong> {{ $codigo }}<br>
            <div class="certificate-link">
                Link para validação: {{ $linkValidacao }}
            </div>
        </div>
    </div>
</body>
</html>

