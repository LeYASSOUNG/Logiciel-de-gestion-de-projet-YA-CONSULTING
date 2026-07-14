<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitation Espace Client</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background-color: #f3f4f6;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        .header {
            background-color: #0f1c33;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            color: #d4af37;
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .content {
            padding: 40px 30px;
            color: #374151;
            line-height: 1.6;
        }
        .content p {
            margin-bottom: 20px;
            font-size: 16px;
        }
        .btn-container {
            text-align: center;
            margin: 35px 0;
        }
        .btn {
            background-color: #d4af37;
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 28px;
            border-radius: 6px;
            font-weight: bold;
            font-size: 16px;
            display: inline-block;
        }
        .footer {
            background-color: #f9fafb;
            padding: 20px;
            text-align: center;
            font-size: 14px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
        }
        .link-fallback {
            word-break: break-all;
            font-size: 13px;
            color: #9ca3af;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>YA CONSULTING</h1>
        </div>
        
        <div class="content">
            <p>Bonjour,</p>
            
            <p>Nous avons le plaisir de vous inviter à configurer votre Espace Client personnel
            pour <strong>{{ $client->name }}</strong>.</p>
            
            <p>Cet espace sécurisé vous permettra de suivre en temps réel l'avancement de vos projets,
            de consulter les livrables et de rester informé.</p>
            
            <div class="btn-container">
                <a href="{{ $invitationLink }}" class="btn">Configurer mon compte</a>
            </div>
            
            <p>Si vous avez des questions, n'hésitez pas à nous contacter.</p>
            
            <p>Cordialement,<br>L'équipe YA Consulting</p>

            <div class="link-fallback">
                <p>Si le bouton ci-dessus ne fonctionne pas, copiez-collez ce lien dans votre navigateur :<br>
                <a href="{{ $invitationLink }}" style="color: #6b7280;">{{ $invitationLink }}</a></p>
            </div>
        </div>
        
        <div class="footer">
            © {{ date('Y') }} YA Consulting. Tous droits réservés.
        </div>
    </div>
</body>
</html>
