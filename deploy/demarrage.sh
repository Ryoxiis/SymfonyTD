#!/bin/sh
# Script lancé au démarrage du serveur en production
set -e

# Crée ou met à jour les tables de la base de données
php bin/console doctrine:schema:update --force --no-interaction

# Charge le catalogue de démonstration seulement si la base est encore vide
php bin/console app:demo --si-vide --no-interaction

# Démarre le serveur web
exec frankenphp run --config /app/deploy/Caddyfile
