#!/bin/bash
# Download the correct FrankenPHP binary for the user's OS
curl -L https://frankenphp.dev/install.sh | sh
mv frankenphp driver
chmod +x driver
echo "FrankenPHP downloaded successfully!"