FROM mcr.microsoft.com/devcontainers/php:8.4

# Install database dependencies
RUN apt-get update && apt-get install -y \
    libpq-dev \
    sqlite3 \
    libsqlite3-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PDO extensions for various databases
RUN docker-php-ext-install pdo pdo_mysql pdo_pgsql pdo_sqlite mysqli opcache
