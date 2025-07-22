<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmploiTemps;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class EmploiTempsController extends Controller
{
    /**
     * Afficher la liste des emplois du temps
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = EmploiTemps::query();

            // Filtres
            if ($request->has('jour')) {
                $query->byJour($request->jour);
            }

            if ($request->has('classe')) {
                $query->byClasse($request->classe);
            }

            if ($request->has('professeur')) {
                $query->byProfesseur($request->professeur);
            }

            if ($request->has('statut')) {
                $query->where('statut', $request->statut);
            } else {
                $query->actif(); // Par défaut, afficher seulement les cours actifs
            }

            // Tri
            $emplois = $query->ordonneParHeure()->get();

            return response()->json([
                'success' => true,
                'data' => $emplois,
                'message' => 'Emplois du temps récupérés avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des emplois du temps',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Créer un nouvel emploi du temps
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'jour' => ['required', Rule::in(['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'])],
                'heure_debut' => 'required|date_format:H:i',
                'heure_fin' => 'required|date_format:H:i|after:heure_debut',
                'matiere' => 'required|string|max:255',
                'professeur' => 'required|string|max:255',
                'salle' => 'required|string|max:255',
                'classe' => 'required|string|max:255',
                'niveau' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'statut' => ['nullable', Rule::in(['actif', 'annule', 'reporte'])]
            ]);

            // Vérifier les conflits d'horaires
            if (EmploiTemps::verifierConflitHoraire(
                $validated['jour'],
                $validated['heure_debut'],
                $validated['heure_fin'],
                $validated['salle'],
                $validated['professeur']
            )) {
                return response()->json([
                    'success' => false,
                    'message' => 'Conflit d\'horaire détecté pour cette salle ou ce professeur'
                ], 422);
            }

            $emploi = EmploiTemps::create($validated);

            return response()->json([
                'success' => true,
                'data' => $emploi,
                'message' => 'Emploi du temps créé avec succès'
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l\'emploi du temps',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher un emploi du temps spécifique
     */
    public function show($id): JsonResponse
    {
        try {
            $emploi = EmploiTemps::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $emploi,
                'message' => 'Emploi du temps récupéré avec succès'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Emploi du temps non trouvé'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'emploi du temps',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour un emploi du temps
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $emploi = EmploiTemps::findOrFail($id);

            $validated = $request->validate([
                'jour' => ['sometimes', Rule::in(['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'])],
                'heure_debut' => 'sometimes|date_format:H:i',
                'heure_fin' => 'sometimes|date_format:H:i|after:heure_debut',
                'matiere' => 'sometimes|string|max:255',
                'professeur' => 'sometimes|string|max:255',
                'salle' => 'sometimes|string|max:255',
                'classe' => 'sometimes|string|max:255',
                'niveau' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'statut' => ['nullable', Rule::in(['actif', 'annule', 'reporte'])]
            ]);

            // Vérifier les conflits d'horaires si les heures ou la salle/professeur changent
            if (isset($validated['jour']) || isset($validated['heure_debut']) || 
                isset($validated['heure_fin']) || isset($validated['salle']) || 
                isset($validated['professeur'])) {
                
                $jour = $validated['jour'] ?? $emploi->jour;
                $heure_debut = $validated['heure_debut'] ?? $emploi->heure_debut;
                $heure_fin = $validated['heure_fin'] ?? $emploi->heure_fin;
                $salle = $validated['salle'] ?? $emploi->salle;
                $professeur = $validated['professeur'] ?? $emploi->professeur;

                if (EmploiTemps::verifierConflitHoraire($jour, $heure_debut, $heure_fin, $salle, $professeur, $id)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Conflit d\'horaire détecté pour cette salle ou ce professeur'
                    ], 422);
                }
            }

            $emploi->update($validated);

            return response()->json([
                'success' => true,
                'data' => $emploi->fresh(),
                'message' => 'Emploi du temps mis à jour avec succès'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Emploi du temps non trouvé'
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de l\'emploi du temps',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer un emploi du temps
     */
    public function destroy($id): JsonResponse
    {
        try {
            $emploi = EmploiTemps::findOrFail($id);
            $emploi->delete();

            return response()->json([
                'success' => true,
                'message' => 'Emploi du temps supprimé avec succès'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Emploi du temps non trouvé'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de l\'emploi du temps',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir l'emploi du temps par semaine
     */
    public function emploiSemaine(Request $request): JsonResponse
    {
        try {
            $classe = $request->get('classe');
            $professeur = $request->get('professeur');

            $query = EmploiTemps::actif()->ordonneParHeure();

            if ($classe) {
                $query->byClasse($classe);
            }

            if ($professeur) {
                $query->byProfesseur($professeur);
            }

            $emplois = $query->get()->groupBy('jour');

            // Organiser par jours de la semaine
            $jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
            $emploiSemaine = [];

            foreach ($jours as $jour) {
                $emploiSemaine[$jour] = $emplois->get($jour, collect())->values();
            }

            return response()->json([
                'success' => true,
                'data' => $emploiSemaine,
                'message' => 'Emploi du temps de la semaine récupéré avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'emploi du temps',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
