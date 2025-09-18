# UserFrosting CRUD6 Codespace Devcontainer
FROM mcr.microsoft.com/vscode/devcontainers/base:ubuntu

# Install PHP 8.1+ and OpenSSL 1.1.1
RUN apt-get update \
    && apt-get install -y software-properties-common \
    && add-apt-repository ppa:ondrej/php \
    && apt-get update \
    && apt-get install -y php8.1 php8.1-cli php8.1-common php8.1-openssl libssl1.1 \
    && apt-get clean

# Install Composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && php -r "unlink('composer-setup.php');"

# Verify PHP and OpenSSL
RUN php -i | grep -i openssl

# Set working directory
WORKDIR /workspaces/sprinkle-crud6
