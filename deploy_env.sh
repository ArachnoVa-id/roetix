#!/bin/bash

# Define the VPS user and IP
VPS_USER="roetixadmin"
VPS_IP="103.127.137.60"

# Define the target directories on the VPS
STAGING_DIR="~/novatix/app/develop"
PRODUCTION_DIR="~/novatix/app/production"

# Copy .env.staging to the staging directory
scp -i ~/.ssh/roetix-key .env.staging "$VPS_USER@$VPS_IP:$STAGING_DIR/.env"

# Clear laravel caches
ssh -i ~/.ssh/roetix-key "$VPS_USER@$VPS_IP" "cd $STAGING_DIR && sudo php artisan config:clear && sudo php artisan cache:clear && sudo php artisan config:cache && sudo php artisan route:clear && sudo php artisan view:clear"

# Copy .env.production to the production directory
scp -i ~/.ssh/roetix-key .env.production "$VPS_USER@$VPS_IP:$PRODUCTION_DIR/.env"

# Clear laravel caches
ssh -i ~/.ssh/roetix-key "$VPS_USER@$VPS_IP" "cd $PRODUCTION_DIR && sudo php artisan config:clear && sudo php artisan cache:clear && sudo php artisan config:cache && sudo php artisan route:clear && sudo php artisan view:clear"
