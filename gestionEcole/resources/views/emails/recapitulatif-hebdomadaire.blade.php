<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Récapitulatif hebdomadaire</title>
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
            background-color: #9b59b6;
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
        .enfant-section {
            background-color: #fff;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #9b59b6;
        }
        .enfant-header {
            background-color: #f8f4fb;
            padding: 10px;
            margin: -15px -15px 15px -15px;
            border-radius: 5px 5px 0 0;
            font-weight: bold;
        }
        .activite {
            background-color: #f9f9f9;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            border-left: 3px solid #e8daef;
        }
        .note-item {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px solid #eee;
        }
        .note-item:last-child {
            border-bottom: none;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #9b59b6;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .semaine-info {
            background-color: #e8daef;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            margin-bottom: 20px;
        }
        .stats {
            display: flex;
            justify-content: space-around;
            margin: 15px 0;
            text-align: center;
        }
        .stat-box {
            flex: 1;
            padding: 10px;
            background-color: #f8f4fb;
            border-radius: 5px;
            margin: 0 5px;
        }
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #9b59b6;
        }
        .stat-label {
            font-size: 12px;
            color: #666;
        }
        .no-activity {
            text-align: center;
            padding: 20px;
            color: #999;
            font-style: italic;
        }
    </style>
</head>
<body>
<div class="header">
    <h1>Récapitulatif Hebdomadaire</h1>
</div>

<div class="content">
    <h2>Bonjour {{ $parent->user->prenom }} {{ $parent->user->nom }},</h2>

    <div class="semaine-info">
        <strong>Semaine du {{ $semaine['debut'] }} au {{ $semaine['fin'] }}</strong>
    </div>

    <p>Voici le récapitulatif de l'activité scolaire de vos enfants cette semaine :</p>

    @forelse($activites as $activite)
        <div class="enfant-section">
            <div class="enfant-header">
                {{ $activite['eleve']->user->prenom }} {{ $activite['eleve']->user->nom }}
                - {{ $activite['eleve']->classe_actuelle->nom ?? 'Non inscrit' }}
            </div>

            <div class="stats">
                <div class="stat-box">
                    <div class="stat-number">{{ $activite['nouvelles_notes']->count() }}</div>
                    <div class="stat-label">Nouvelle(s) note(s)</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number">{{ $activite['nouveaux_bulletins']->count() }}</div>
                    <div class="stat-label">Nouveau(x) bulletin(s)</div>
                </div>
            </div>

            @if($activite['nouvelles_notes']->count() > 0)
                <div class="activite">
                    <h4>📝 Nouvelles notes :</h4>
                    @foreach($activite['nouvelles_notes'] as $note)
                        <div class="note-item">
                            <span><strong>{{ $note->matiere->nom }}</strong> ({{ $note->periode->nom }})</span>
                            <span>Moyenne : <strong>{{ $note->moyenne }}/20</strong></span>
                        </div>
                    @endforeach
                </div>
            @endif

            @if($activite['nouveaux_bulletins']->count() > 0)
                <div class="activite">
                    <h4>📊 Nouveaux bulletins :</h4>
                    @foreach($activite['nouveaux_bulletins'] as $bulletin)
                        <div class="note-item">
                            <span><strong>{{ $bulletin->periode->nom }}</strong></span>
                            <span>
                                    Moyenne : <strong>{{ $bulletin->moyenne_generale }}/20</strong>
                                    | Rang : <strong>{{ $bulletin->rang }}/{{ $bulletin->effectif_classe }}</strong>
                                </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @empty
        <div class="no-activity">
            <p>Aucune nouvelle activité cette semaine.</p>
        </div>
    @endforelse

    <p>Pour plus de détails, connectez-vous à votre espace parent.</p>

    <center>
        <a href="{{ config('app.url') }}/login" class="button">Accéder à mon espace</a>
    </center>

    <hr style="margin-top: 30px; border: none; border-top: 1px solid #ddd;">

    <p style="font-size: 12px; color: #666; margin-top: 20px;">
        💡 <strong>Astuce :</strong> N'oubliez pas de vérifier régulièrement l'espace parent pour rester informé(e)
        de toutes les actualités concernant la scolarité de vos enfants.
    </p>
</div>

<div class="footer" style="text-align: center; margin-top: 20px; font-size: 12px; color: #666;">
    <p>Vous recevez cet email car vous êtes inscrit(e) aux notifications hebdomadaires.</p>
    <p>Pour modifier vos préférences de notification, connectez-vous à votre espace.</p>
    <p>&copy; {{ date('Y') }} GestionEcole. Tous droits réservés.</p>
</div>
</body>
</html>
