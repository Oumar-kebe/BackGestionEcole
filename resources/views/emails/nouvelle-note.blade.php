<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Nouvelle note</title>
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
            background-color: #3498db;
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
        .note-details {
            background-color: #fff;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #3498db;
        }
        .notes-grid {
            display: table;
            width: 100%;
            margin: 15px 0;
        }
        .note-row {
            display: table-row;
        }
        .note-label {
            display: table-cell;
            font-weight: bold;
            padding: 5px;
            width: 40%;
        }
        .note-value {
            display: table-cell;
            padding: 5px;
            font-size: 18px;
        }
        .moyenne {
            background-color: #e8f4f8;
            font-weight: bold;
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
        .appreciation {
            font-style: italic;
            color: #555;
            margin-top: 10px;
        }
    </style>
</head>
<body>
<div class="header">
    <h1>Nouvelle Note Disponible</h1>
</div>

<div class="content">
    <h2>Bonjour,</h2>

    <p>Une nouvelle note vient d'être saisie pour <strong>{{ $eleve->user->prenom }} {{ $eleve->user->nom }}</strong>.</p>

    <div class="note-details">
        <h3>Détails de la note :</h3>
        <div class="notes-grid">
            <div class="note-row">
                <div class="note-label">Matière :</div>
                <div class="note-value">{{ $matiere->nom }}</div>
            </div>
            <div class="note-row">
                <div class="note-label">Période :</div>
                <div class="note-value">{{ $periode->nom }}</div>
            </div>
            @if($note->note_devoir1 !== null)
                <div class="note-row">
                    <div class="note-label">Devoir 1 :</div>
                    <div class="note-value">{{ $note->note_devoir1 }}/20</div>
                </div>
            @endif
            @if($note->note_devoir2 !== null)
                <div class="note-row">
                    <div class="note-label">Devoir 2 :</div>
                    <div class="note-value">{{ $note->note_devoir2 }}/20</div>
                </div>
            @endif
            @if($note->note_composition !== null)
                <div class="note-row">
                    <div class="note-label">Composition :</div>
                    <div class="note-value">{{ $note->note_composition }}/20</div>
                </div>
            @endif
            <div class="note-row moyenne">
                <div class="note-label">Moyenne :</div>
                <div class="note-value">{{ $note->moyenne }}/20</div>
            </div>
        </div>

        @if($note->appreciation)
            <div class="appreciation">
                <strong>Appréciation :</strong> {{ $note->appreciation }}
            </div>
        @endif
    </div>

    <p>Vous pouvez consulter toutes les notes en vous connectant à votre espace.</p>

    <center>
        <a href="{{ config('app.url') }}/login" class="button">Voir toutes les notes</a>
    </center>
</div>

<div class="footer" style="text-align: center; margin-top: 20px; font-size: 12px; color: #666;">
    <p>Cet email a été envoyé automatiquement. Merci de ne pas y répondre.</p>
    <p>&copy; {{ date('Y') }} GestionEcole. Tous droits réservés.</p>
</div>
</body>
</html>
