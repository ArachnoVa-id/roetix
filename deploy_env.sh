#!/bin/bash

# Load the .env file
set -a
source .env
set +a

# Define the SSH key file
SSH_KEY="${SSH_LOCAL_DIR%.ppk}"  # Remove .ppk extension for the converted key

# Check if the SSH key is in .ppk format and convert it if necessary
if [[ "$SSH_LOCAL_DIR" == *.ppk ]]; then
    echo "Converting .ppk key to OpenSSH format..."
    puttygen "$SSH_LOCAL_DIR" -O private-openssh -o "$SSH_KEY"
    if [ $? -ne 0 ]; then
        echo "Error converting key. Exiting."
        exit 1
    fi
    SSH_LOCAL_DIR="$SSH_KEY"  # Update the SSH_LOCAL_DIR to the new key
fi

# Set permissions on the SSH key
chmod 600 "$SSH_LOCAL_DIR"

# Define the target directories on the VPS
STAGING_DIR="~/novatix/app/develop"
PRODUCTION_DIR="~/novatix/app/production"

# Copy .env.staging to the staging directory
scp -i "$SSH_LOCAL_DIR" .env.staging "$VPS_USER@$VPS_IP:$STAGING_DIR/.env"

# Clear Laravel caches
ssh -i "$SSH_LOCAL_DIR" "$VPS_USER@$VPS_IP" "cd $STAGING_DIR && sudo php artisan config:clear && sudo php artisan cache:clear && sudo php artisan config:cache && sudo php artisan route:clear && sudo php artisan view:clear"

# Copy .env.production to the production directory
scp -i "$SSH_LOCAL_DIR" .env.production "$VPS_USER@$VPS_IP:$PRODUCTION_DIR/.env"

# Clear Laravel caches
ssh -i "$SSH_LOCAL_DIR" "$VPS_USER@$VPS_IP" "cd $PRODUCTION_DIR && sudo php artisan config:clear && sudo php artisan cache:clear && sudo php artisan config:cache && sudo php artisan route:clear && sudo php artisan view:clear"
