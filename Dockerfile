FROM php:8.1-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    sqlite3 \
    libsqlite3-dev \
    && docker-php-ext-install pdo pdo_mysql pdo_sqlite mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Install Drush globally first
RUN composer global require drush/drush:^11
ENV PATH="/root/.composer/vendor/bin:${PATH}"

# Download Drupal 10
RUN composer create-project drupal/recommended-project:^10.0 . --no-interaction --no-dev

# Install additional Drupal modules
RUN composer require drupal/devel

# Copy the viostream module
COPY . /var/www/html/web/modules/contrib/viostream/

# Copy custom configuration files
COPY docker/settings.local.php /var/www/html/web/sites/default/
COPY docker/apache-drupal.conf /etc/apache2/sites-available/000-default.conf

# Set up directory permissions and create required directories
RUN mkdir -p /var/www/html/web/sites/default/files/private \
    && mkdir -p /var/www/html/web/sites/default/files \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/web/sites/default/files \
    && chmod 755 /var/www/html/web/sites/default

# Expose port 80
EXPOSE 80

# Copy entrypoint script
COPY docker/entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]