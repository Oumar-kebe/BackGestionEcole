<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rappel de connexion</title>
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
            background-color: #f39c12;
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
        .info-box {
            background-color: #fff;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #f39c12;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #f39c12;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .last-login {
            background-color: #fff5e6;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            margin: 15px 0;
        }
    </style>
</head>
<body>
<div class="header">
    <h1>Nous vous manquons ? 😊</h1>
</div>

<div class="content">
    <h2>Bonjour {{ $user->prenom }} {{ $user->nom }},</h2>

    <p>Nous avons remarqué que vous ne vous êtes pas connecté(e) à votre espace GestionEcole depuis un certain temps.</p>

    @if($derniere_connexion)
        <div class="last-login">
            <strong>Dernière connexion :</strong> {{ $derniere_connexion->format('d/m/Y à H:i') }}
        </div>
    @else
        <div class="last-login">
            <strong>Vous ne vous êtes jamais connecté(e) à votre espace.</strong>
        </div>
    @endif

    <div class="info-box">
        <h3>Rappel de vos identifiants :</h3>
        <p><strong>Email :</strong> {{ $user->email }}</p>
        <p><strong>Matricule :</strong> {{ $user->matricule }}</p>
        <p><strong>Rôle :</strong> {{ ucfirst($user->role) }}</p>
    </div>

    <p>N'oubliez pas que votre espace vous permet de :</p>
    <ul>
        @if($user->role === 'eleve')
            <li>Consulter vos notes et moyennes</li>
            <li>Télécharger vos bulletins</li>
            <li>Suivre votre progression scolaire</li>
        @elseif($user->role === 'parent')
            <li>Suivre les résultats de vos enfants</li>
            <li>Télécharger leurs bulletins</li>
            <li>Rester informé(e) de leur progression</li>
        @elseif($user->role === 'enseignant')
            <li>Saisir les notes de vos élèves</li>
            <li>Consulter vos classes</li>
            <li>Suivre les statistiques de vos matières</li>
        @endif
    </ul>

    <p>Si vous avez oublié votre mot de passe, contactez l'administration.</p>

    <center>
        <a href="{{ $loginUrl }}" class="button">Se connecter maintenant</a>
    </center>
</div>

<div class="footer" style="text-align: center; margin-top: 20px; font-size: 12px; color: #666;">
    <p>Cet email a été envoyé automatiquement. Merci de ne pas y répondre.</p>
    <p>&copy; {{ date('Y') }} GestionEcole. Tous droits réservés.</p>
</div>
</body>
</html>
