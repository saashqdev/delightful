#!/bin/bash

set -eo pipefail

# Get the directory containing this script
base_dirname=$(
  cd "$(dirname "$0")"
  pwd
)
# Path to the Hyperf console entry
bin="${base_dirname}/bin/hyperf.php"


# Check whether initialization has already run
if [ ! -f "${base_dirname}/.initialized" ]; then
    echo "Initializing delightful-service for the first time..."
    
    # Run composer update to install dependencies
    cd ${base_dirname}
    

    # Run migrations
    php "${bin}" migrate --force
    
    # Run database seeders
    php bin/hyperf.php init-delightful:data

  
    
    # Create a marker file to indicate initialization is complete
    touch ${base_dirname}/.initialized
    echo "Initialization completed!"
else
    echo "delightful-service has already been initialized, skipping..."
fi 


  # Run seeders if needed

  # Start the service
USE_ZEND_ALLOC=0 php -dopcache.enable_cli=0 "${bin}" start
