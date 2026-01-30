FROM dunglas/frankenphp

# Production domain
ENV SERVER_NAME=":80"

# Worker config (Looks for worker.php in the root of the container)
ENV FRANKENPHP_CONFIG="worker ./worker.php"

# Copy files to the container root
COPY . /app

# Overwrite the default config with Caddyfile
COPY Caddyfile /etc/frankenphp/Caddyfile