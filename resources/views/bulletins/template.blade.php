<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bulletin - {{ $eleve->user->nom }} {{ $eleve->user->prenom }}</title>
    <style>
        @page {
            margin: 20mm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        .school-name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .document-title {
            font-size: 18px;
            font-weight: bold;
            margin-top: 15px;
            text-decoration: underline;
        }
        .info-section {
            margin-bottom: 20px;
        }
        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .info-row {
            display: table-row;
        }
        .info-label {
            display: table-cell;
            font-weight: bold;
            width: 150px;
            padding: 5px;
        }
        .info-value {
            display: table-cell;
            padding: 5px;
        }
        .notes-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .notes-table th, .notes-table td {
            border: 1px solid #333;
            padding: 8px;
            text-align: center;
        }
        .notes-table th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .matiere-col {
            text-align: left !important;
            width: 30%;
        }
        .summary-section {
            margin-top: 30px;
            padding: 15px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
        }
        .summary-grid {
            display: table;
            width: 100%;
        }
        .summary-item {
            display: table-cell;
            text-align: center;
            padding: 10px;
        }
        .summary-value {
            font-size: 20px;
            font-weight: bold;
            color: #2c3e50;
        }
        .summary-label {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        .observation-section {
            margin-top: 30px;
            padding: 15px;
            border: 1px solid #ddd;
            min-height: 100px;
        }
        .signature-section {
            margin-top: 40px;
            display: table;
            width: 100%;
        }
        .signature-box {
            display: table-cell;
            text-align: center;
            padding: 20px;
        }
        .signature-line {
            border-bottom: 1px solid #333;
            width: 200px;
            margin: 40px auto 10px;
        }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 10px;
            color: #666;
            padding: 10px;
            border-top: 1px solid #ddd;
        }
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 100px;
            color: rgba(0, 0, 0, 0.05);
            z-index: -1;
        }
    </style>
</head>
<body>
<div class="watermark">ORIGINAL</div>

<div class="header">
    <div class="school-name">GESTIONECOLE</div>
    <div>Année Scolaire : {{ $anneeScolaire->libelle }}</div>
    <div class="document-title">BULLETIN DE NOTES - {{ $periode->nom }}</div>
</div>

<div class="info-section">
    <div class="info-grid">
        <div class="info-row">
            <div class="info-label">Nom et Prénom :</div>
            <div class="info-value">{{ $eleve->user->nom }} {{ $eleve->user->prenom }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Matricule :</div>
            <div class="info-value">{{ $eleve->user->matricule }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Date de naissance :</div>
            <div class="info-value">{{ $eleve->user->date_naissance ? $eleve->user->date_naissance->format('d/m/Y') : '' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Classe :</div>
            <div class="info-value">{{ $classe->nom }} - {{ $classe->niveau->nom }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Effectif :</div>
            <div class="info-value">{{ $bulletin->effectif_classe }} élèves</div>
        </div>
    </div>
</div>

<table class="notes-table">
    <thead>
    <tr>
        <th class="matiere-col">Matière</th>
        <th>Coef.</th>
        <th>Devoir 1</th>
        <th>Devoir 2</th>
        <th>Composition</th>
        <th>Moyenne</th>
        <th>Moy × Coef</th>
        <th>Appréciation</th>
        <th>Enseignant</th>
    </tr>
    </thead>
    <tbody>
    @foreach($notes as $note)
        <tr>
            <td class="matiere-col">{{ $note->matiere->nom }}</td>
            <td>{{ $note->matiere->coefficient }}</td>
            <td>{{ $note->note_devoir1 ?? '-' }}</td>
            <td>{{ $note->note_devoir2 ?? '-' }}</td>
            <td>{{ $note->note_composition ?? '-' }}</td>
            <td><strong>{{ $note->moyenne ?? '-' }}</strong></td>
            <td>{{ $note->moyenne ? number_format($note->moyenne * $note->matiere->coefficient, 2) : '-' }}</td>
            <td>{{ $note->appreciation ?? '-' }}</td>
            <td>{{ $note->enseignant->user->nom }}</td>
        </tr>
    @endforeach
    </tbody>
    <tfoot>
    <tr>
        <th class="matiere-col">TOTAL</th>
        <th>{{ $totalCoefficients }}</th>
        <th colspan="4"></th>
        <th>{{ number_format($bulletin->moyenne_generale * $totalCoefficients, 2) }}</th>
        <th colspan="2"></th>
    </tr>
    </tfoot>
</table>

<div class="summary-section">
    <div class="summary-grid">
        <div class="summary-item">
            <div class="summary-value">{{ $bulletin->moyenne_generale }}/20</div>
            <div class="summary-label">Moyenne Générale</div>
        </div>
        <div class="summary-item">
            <div class="summary-value">{{ $bulletin->rang }}/{{ $bulletin->effectif_classe }}</div>
            <div class="summary-label">Rang</div>
        </div>
        <div class="summary-item">
            <div class="summary-value">{{ $bulletin->mention_label }}</div>
            <div class="summary-label">Mention</div>
        </div>
    </div>
</div>

<div class="observation-section">
    <h3>Observation du Conseil de Classe :</h3>
    <p>{{ $bulletin->observation_conseil ?? 'Aucune observation' }}</p>
</div>

<div class="signature-section">
    <div class="signature-box">
        <div class="signature-line"></div>
        <div>Le Parent</div>
    </div>
    <div class="signature-box">
        <div class="signature-line"></div>
        <div>Le Professeur Principal</div>
    </div>
    <div class="signature-box">
        <div class="signature-line"></div>
        <div>Le Directeur</div>
    </div>
</div>

<div class="footer">
    Généré le {{ $dateGeneration }} | © {{ date('Y') }} GestionEcole
</div>
</body>
</html>
