<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Eleve;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $query = Document::query();

        if ($request->has('documentable_type')) {
            $query->where('documentable_type', $request->documentable_type);
        }

        if ($request->has('documentable_id')) {
            $query->where('documentable_id', $request->documentable_id);
        }

        $documents = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $documents
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'type' => 'required|string',
            'fichier' => 'required|file|max:10240', // 10MB max
            'documentable_type' => 'required|string',
            'documentable_id' => 'required|integer',
        ]);

        // Vérifier que l'entité existe
        $documentable = $request->documentable_type::find($request->documentable_id);
        if (!$documentable) {
            return response()->json([
                'success' => false,
                'message' => 'Entité non trouvée'
            ], 404);
        }

        // Stocker le fichier
        $path = $request->file('fichier')->store('documents/' . $request->documentable_type);

        $document = Document::create([
            'nom' => $request->nom,
            'type' => $request->type,
            'fichier' => $path,
            'documentable_type' => $request->documentable_type,
            'documentable_id' => $request->documentable_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Document téléversé avec succès',
            'data' => $document
        ], 201);
    }

    public function show($id)
    {
        $document = Document::with('documentable')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $document
        ]);
    }

    public function destroy($id)
    {
        $document = Document::findOrFail($id);

        // Supprimer le fichier
        Storage::delete($document->fichier);

        $document->delete();

        return response()->json([
            'success' => true,
            'message' => 'Document supprimé avec succès'
        ]);
    }

    public function telecharger($id)
    {
        $document = Document::findOrFail($id);

        if (!Storage::exists($document->fichier)) {
            return response()->json([
                'success' => false,
                'message' => 'Fichier non trouvé'
            ], 404);
        }

        return Storage::download($document->fichier, $document->nom);
    }

    public function documentsEleve($eleveId)
    {
        $eleve = Eleve::findOrFail($eleveId);

        $documents = $eleve->documents()
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $documents
        ]);
    }

    public function uploadDocumentEleve(Request $request, $eleveId)
    {
        $eleve = Eleve::findOrFail($eleveId);

        $request->validate([
            'nom' => 'required|string|max:255',
            'type' => 'required|in:certificat_naissance,certificat_medical,photo,autre',
            'fichier' => 'required|file|max:10240', // 10MB max
        ]);

        // Stocker le fichier
        $path = $request->file('fichier')->store('documents/eleves/' . $eleveId);

        $document = $eleve->documents()->create([
            'nom' => $request->nom,
            'type' => $request->type,
            'fichier' => $path,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Document téléversé avec succès',
            'data' => $document
        ], 201);
    }
}
