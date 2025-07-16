<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Réinitialisation du mot de passe</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #e74c3c;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f4f4f4;
            padding: 20px;
            border-radius: 0 0 5px 5px;
        }
        .alert {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .credentials {
            background-color: #fff;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #e74c3c;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #e74c3c;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .password {
            font-size: 18px;
            font-weight: bold;
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            font-family: monospace;
            text-align: center;
            text-align: center;
            margin: 10px 0;
        }
    </style>
</head>
<body>
<div class="header">
    <h1>Réinitialisation de votre mot de passe</h1>
</div>

<div class="content">
    <h2>Bonjour {{ $user->prenom }} {{ $user->nom }},</h2>

    <p>Votre mot de passe a été réinitialisé avec succès.</p>

    <div class="alert">
        <strong>⚠️ Important :</strong> Ce mot de passe est temporaire. Pour votre sécurité, nous vous recommandons vivement de le changer dès votre prochaine connexion.
    </div>

    <div class="credentials">
        <h3>Vos nouveaux identifiants :</h3>
        <p><strong>Email :</strong> {{ $user->email }}</p>
        <p><strong>Nouveau mot de passe :</strong></p>
        <div class="password">{{ $nouveauMotDePasse }}</div>
        <p><strong>Matricule :</strong> {{ $user->matricule }}</p>
    </div>

    <p>Si vous n'avez pas demandé cette réinitialisation, veuillez contacter immédiatement l'administration.</p>

    <center>
        <a href="{{ $loginUrl }}" class="button">Se connecter</a>
    </center>
</div>

<div class="footer" style="text-align: center; margin-top: 20px; font-size: 12px; color: #666;">
    <p>Cet email a été envoyé automatiquement. Merci de ne pas y répondre.</p>
    <p>&copy; {{ date('Y') }} GestionEcole. Tous droits réservés.</p>
</div>
</body>
</html>
