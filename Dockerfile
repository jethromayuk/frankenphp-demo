FROM dunglas/frankenphp

# 1. Production domain
ENV SERVER_NAME=":80"

# 2. Worker config (Looks for worker.php in the root of the container)
ENV FRANKENPHP_CONFIG="worker ./worker.php"

# 3. Copy files to the container root
COPY . /app

# 4. NEW: Overwrite the default config with YOUR Caddyfile
COPY Caddyfile /etc/frankenphp/Caddyfile