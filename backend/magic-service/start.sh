#!/bin/bash

set -eo pipefail

# Get the directory where the script lives
base_dirname=$(
  cd "$(dirname "$0")"
  pwd
)
# Path to the Hyperf entrypoint
bin="${base_dirname}/bin/hyperf.php"
# ........................
# Run migrations (guarded by a distributed lock to avoid concurrent runs)
php "${bin}" shell:locker migrate

# Run one-time initialization if needed
if [ ! -f "${base_dirname}/.initialized" ]; then
    echo "Initializing magic-service for the first time..."
    
  # Run composer update
    cd ${base_dirname}
    
  # Seed the database
    php "${bin}" db:seed
    
  # Create a marker file to indicate initialization is done
    touch ${base_dirname}/.initialized
    echo "Initialization completed!"
else
    echo "magic-service has already been initialized, skipping..."
fi 

# Run additional seeders
#php "${bin}" db:seed

# Start the service
USE_ZEND_ALLOC=0 php -dopcache.enable_cli=1 -dopcache.jit_buffer_size=128M -dopcache.jit=1255 -dopcache.validate_timestamps=0 "${bin}" start
