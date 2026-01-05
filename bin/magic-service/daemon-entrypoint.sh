#!/bin/bash

# Marker file path
SEED_MARKER="/opt/www/.db_seed_executed"

# Check whether seed has already been run
# if [ ! -f "$SEED_MARKER" ]; then
#     echo "First start detected, running database seed..."
#     # php bin/hyperf.php db:seed
#
#     # Create marker file to avoid reruns
#     touch "$SEED_MARKER"
#     echo "Database seed executed and marked"
# else
#     echo "Marker file detected, skipping database seed"
# fi

# Start the daemon service
echo "Starting magic-service daemon..."
php bin/hyperf.php start

