#!/bin/bash

echo "🔍 Script de debug pour les parents..."

# Récupérer un parent de test depuis la base
echo "📋 Recherche d'un parent dans la base..."

cd /Users/kahtech/Documents/isi/laravel/examenDaneLo/backebd-ecole

# Exécuter une requête pour récupérer un parent
php artisan tinker --execute="
\$parent = \App\Models\User::where('role', 'parent')->with('parent.enfants.user')->first();
if (\$parent) {
    echo 'Parent trouvé:' . PHP_EOL;
    echo 'Email: ' . \$parent->email . PHP_EOL;
    echo 'Nom: ' . \$parent->prenom . ' ' . \$parent->nom . PHP_EOL;
    echo 'ID: ' . \$parent->id . PHP_EOL;
    if (\$parent->parent) {
        echo 'Profil parent ID: ' . \$parent->parent->id . PHP_EOL;
        echo 'Nombre d enfants: ' . \$parent->parent->enfants->count() . PHP_EOL;
        foreach (\$parent->parent->enfants as \$enfant) {
            echo '  - Enfant: ' . \$enfant->user->prenom . ' ' . \$enfant->user->nom . ' (ID: ' . \$enfant->id . ')' . PHP_EOL;
        }
    } else {
        echo 'PROBLÈME: Aucun profil parent trouvé!' . PHP_EOL;
    }
} else {
    echo 'PROBLÈME: Aucun parent trouvé dans la base!' . PHP_EOL;
}
"

echo ""
echo "🧪 Test de connexion API avec un parent..."

# Récupérer l'email d'un parent pour tester
PARENT_EMAIL=$(php artisan tinker --execute="echo \App\Models\User::where('role', 'parent')->first()->email ?? 'aucun';" 2>/dev/null | tail -1)

echo "📧 Email parent trouvé: $PARENT_EMAIL"

if [ "$PARENT_EMAIL" != "aucun" ]; then
    echo "🔐 Test de connexion..."
    
    # Test de connexion
    TOKEN_RESPONSE=$(curl -s -X POST "http://localhost:8000/api/auth/login" \
        -H "Content-Type: application/json" \
        -H "Accept: application/json" \
        -d "{
            \"email\": \"$PARENT_EMAIL\",
            \"password\": \"password123\"
        }")
    
    echo "📝 Réponse login: $TOKEN_RESPONSE"
    
    # Extraire le token
    TOKEN=$(echo $TOKEN_RESPONSE | grep -o '"access_token":"[^"]*' | grep -o '[^"]*$')
    
    if [ ! -z "$TOKEN" ]; then
        echo "✅ Token obtenu: ${TOKEN:0:20}..."
        
        echo "🔍 Test des endpoints debug..."
        
        # Test user info
        echo "👤 Test /api/debug/user-info"
        curl -s -X GET "http://localhost:8000/api/debug/user-info" \
            -H "Authorization: Bearer $TOKEN" \
            -H "Accept: application/json" | jq '.'
        
        echo ""
        echo "👨‍👩‍👧‍👦 Test /api/debug/parent-enfants"
        curl -s -X GET "http://localhost:8000/api/debug/parent-enfants" \
            -H "Authorization: Bearer $TOKEN" \
            -H "Accept: application/json" | jq '.'
        
        echo ""
        echo "📚 Test /api/parent/mes-enfants"
        curl -s -X GET "http://localhost:8000/api/parent/mes-enfants" \
            -H "Authorization: Bearer $TOKEN" \
            -H "Accept: application/json" | jq '.'
            
    else
        echo "❌ Impossible d'obtenir le token"
    fi
else
    echo "❌ Aucun parent trouvé pour les tests"
fi

echo ""
echo "✅ Script de debug terminé"
