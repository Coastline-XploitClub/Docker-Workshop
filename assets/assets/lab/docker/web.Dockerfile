# Use Debian bookworm to match the local setup exactly
FROM debian:bookworm

# Install prereqs
RUN apt-get update && \
    apt-get install -y \
    php8.2 \
    php8.2-cli \
    php8.2-mongodb \
    php8.2-redis \
    php8.2-xml \
    php8.2-curl \
    php8.2-mbstring \
    php8.2-zip \
    curl

# Get Composer ready
RUN curl -sS https://getcomposer.org/installer | php && \
    mv composer.phar /usr/local/bin/composer && \
    chmod +x /usr/local/bin/composer

# We work here moving forward
WORKDIR /var/www/html

# Copy all sourcecode from web/ dir
COPY ../web/ /var/www/html/

# Copy existing upload files (existing data)
COPY ../uploads/* /var/www/html/uploads/

# Make setup.sh executable and run it
RUN chmod +x setup.sh && \
    ./setup.sh

# Document 8080 as port to use
EXPOSE 8080

# Start the PHP built-in server with router.php exactly like we did when running locally
CMD ["php", "-S", "0.0.0.0:8080", "router.php"]
