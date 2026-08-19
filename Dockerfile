# PayPal JS SDK v6 + Apple Pay test project
# Base image: official PHP + Apache (mod_php), matches a simple .php-file webroot
FROM php:8.2-apache

# config.php talks to PayPal's REST API via cURL. docker-php-ext-install compiles
# the PHP curl extension from source, which needs libcurl's dev headers present
# first (php:apache doesn't include them by default) — hence the apt-get step.
RUN apt-get update \
    && apt-get install -y --no-install-recommends libcurl4-openssl-dev pkg-config \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install curl

# Capsule's service entries for this project expect the container to listen on
# 8080 (see: dockerhub.paypalcorp.com/capsule/applepay-spb ... :8080), so point
# Apache's default vhost + port config at 8080 instead of the image's default 80.
RUN sed -i 's/80/8080/g' /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf

# Copy the app in
COPY . /var/www/html/

# IMPORTANT: explicitly normalize ownership + permissions after the copy.
# This is the step that was missing before — without it, files can end up
# owned by root / with permissions Apache's www-data user can't read, which
# is exactly what caused the earlier "Permission denied" / "Failed opening
# required createPayment.php" fatal error in the deployed container.
RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && find /var/www/html -type f -exec chmod 644 {} \;

EXPOSE 8080

CMD ["apache2-foreground"]
