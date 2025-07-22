#!/bin/bash

echo "🔄 Redémarrage du serveur Laravel..."

# Arrêter le serveur s'il tourne
pkill -f "php artisan serve"

# Nettoyer le cache
echo "🧹 Nettoyage du cache..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# Reconfigurer
echo "⚙️ Rechargement de la configuration..."
php artisan config:cache

# Redémarrer le serveur
echo "🚀 Démarrage du serveur sur http://localhost:8000..."
php artisan serve --host=0.0.0.0 --port=8000 &

echo "✅ Serveur redémarré !"
echo "📡 Test de l'API..."

# Attendre un moment pour que le serveur démarre
sleep 3

# Tester l'API
curl -X GET "http://localhost:8000/api/test" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json"

echo ""
echo "🎯 Le serveur est prêt à recevoir les requêtes du frontend !"
