#!/bin/bash

# Define the VPS user and IP
VPS_USER="adminarachnova"
VPS_IP="103.175.221.144"

# Define the target directories on the VPS
STAGING_DIR="~/novatix/app/develop"
PRODUCTION_DIR="~/novatix/app/production"

# Copy .env.staging to the staging directory
scp -i ~/.ssh/id_rsa .env.staging "$VPS_USER@$VPS_IP:$STAGING_DIR/.env.staging"

# Copy .env.production to the production directory
scp -i ~/.ssh/id_rsa .env.production "$VPS_USER@$VPS_IP:$PRODUCTION_DIR/.env.production"
