<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bulletin disponible</title>
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
            background-color: #27ae60;
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
        .bulletin-info {
            background-color: #fff;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #27ae60;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #27ae60;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .stats {
            display: flex;
            justify-content: space-around;
            margin: 20px 0;
        }
        .stat-box {
            text-align: center;
            padding: 10px;
            background-color: #fff;
            border-radius: 5px;
            flex: 1;
            margin: 0 5px;
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #27ae60;
        }
        .stat-label {
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
<div class="header">
    <h1>Nouveau Bulletin Disponible</h1>
</div>

<div class="content">
    <h2>Bonjour,</h2>

    <p>Le bulletin de <strong>{{ $eleve->user->prenom }} {{ $eleve->user->nom }}</strong> pour la période <strong>{{ $periode->nom }}</strong> est maintenant disponible.</p>

    <div class="bulletin-info">
        <h3>Informations du bulletin :</h3>
        <p><strong>Élève :</strong> {{ $eleve->user->prenom }} {{ $eleve->user->nom }}</p>
        <p><strong>Classe :</strong> {{ $classe->nom }} - {{ $classe->niveau->nom }}</p>
        <p><strong>Période :</strong> {{ $periode->nom }}</p>
        <p><strong>Année scolaire :</strong> {{ $periode->anneeScolaire->libelle }}</p>
    </div>

    <div class="stats">
        <div class="stat-box">
            <div class="stat-value">{{ $moyenneGenerale }}/20</div>
            <div class="stat-label">Moyenne Générale</div>
        </div>
        <div class="stat-box">
            <div class="stat-value">{{ $rang }}</div>
            <div class="stat-label">Rang</div>
        </div>
        <div class="stat-box">
            <div class="stat-value">{{ $mention }}</div>
            <div class="stat-label">Mention</div>
        </div>
    </div>

    <p>Vous pouvez consulter et télécharger le bulletin complet en vous connectant à votre espace.</p>

    <center>
        <a href="{{ config('app.url') }}/login" class="button">Consulter le bulletin</a>
    </center>
</div>

<div class="footer" style="text-align: center; margin-top: 20px; font-size: 12px; color: #666;">
    <p>Cet email a été envoyé automatiquement. Merci de ne pas y répondre.</p>
    <p>&copy; {{ date('Y') }} GestionEcole. Tous droits réservés.</p>
</div>
</body>
</html>
