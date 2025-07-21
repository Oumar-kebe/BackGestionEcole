<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bienvenue sur GestionEcole</title>
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
            background-color: #2c3e50;
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
        .credentials {
            background-color: #fff;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #3498db;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
<div class="header">
    <h1>Bienvenue sur GestionEcole</h1>
</div>

<div class="content">
    <h2>Bonjour {{ $user->prenom }} {{ $user->nom }},</h2>

    <p>Votre compte a été créé avec succès sur notre plateforme de gestion scolaire.</p>

    <p>Vous êtes enregistré(e) en tant que : <strong>{{ ucfirst($user->role) }}</strong></p>

    <div class="credentials">
        <h3>Vos identifiants de connexion :</h3>
        <p><strong>Email :</strong> {{ $user->email }}</p>
        <p><strong>Mot de passe :</strong> {{ $motDePasse }}</p>
        <p><strong>Matricule :</strong> {{ $user->matricule }}</p>
    </div>

    <p><strong>Important :</strong> Pour des raisons de sécurité, nous vous recommandons de changer votre mot de passe lors de votre première connexion.</p>

    <center>
        <a href="{{ $loginUrl }}" class="button">Se connecter</a>
    </center>
</div>

<div class="footer">
    <p>Cet email a été envoyé automatiquement. Merci de ne pas y répondre.</p>
    <p>&copy; {{ date('Y') }} GestionEcole. Tous droits réservés.</p>
</div>
</body>
</html>
