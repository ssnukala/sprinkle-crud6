# UserFrosting CRUD6 Full Stack Development Container
# Combines PHP (UserFrosting/CRUD6 Sprinkle) + Node.js (Theme Development)
FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libicu-dev \
    libssl-dev \
    openssh-client \
    sudo \
    vim \
    nano \
    bash \
    python3 \
    g++ \
    make \
    && rm -rf /var/lib/apt/lists/*

# Configure and install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        gd \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        zip \
        intl \
        xml \
        soap \
        opcache

# XDebug removed due to PECL connectivity issues in build environments
# Can be installed manually post-build if needed: pecl install xdebug

# Install Node.js 20 LTS and npm
# Handle SSL certificate issues in restricted network environments
RUN curl -fsSL --insecure https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs npm

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configure PHP settings for development
RUN echo "memory_limit=512M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "max_execution_time=300" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "upload_max_filesize=100M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "post_max_size=100M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "error_reporting=E_ALL" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "display_errors=On" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "log_errors=On" >> /usr/local/etc/php/conf.d/custom.ini

# Create a non-root user for development
RUN useradd -m -s /bin/bash vscode \
    && echo "vscode ALL=(ALL) NOPASSWD:ALL" >> /etc/sudoers

# Set working directory
WORKDIR /workspace

# Change ownership of the working directory
RUN chown -R vscode:vscode /workspace

# Switch to non-root user
USER vscode

# Verify installations
RUN php --version \
    && composer --version \
    && node --version \
    && npm --version

# Expose ports
EXPOSE 8080 5173

# Default command
CMD ["bash"]
