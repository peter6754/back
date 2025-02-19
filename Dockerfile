FROM php:8.3-fpm

# Setup document root
WORKDIR /var/www/html

# Set timezone trigger
RUN ln -snf /usr/share/zoneinfo/UTC /etc/localtime && echo UTC > /etc/timezone

# install dependencies
RUN apt-get clean && rm -r /var/lib/apt/lists/* && apt-get update --fix-missing
RUN apt-get install -y --no-install-recommends \
    libcurl4-openssl-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    build-essential \
    libsqlite3-dev \
    libmcrypt-dev \
    libsqlite3-0 \
    libxml2-dev \
    libwebp-dev \
    libpng-dev \
    libbz2-dev \
    libzip-dev \
    zlib1g-dev \
    supervisor \
    libpq-dev \
    ffmpeg \
    nginx \
    curl \
    exif \
    wget \
    ftp \
    zip \
    git

# pecl scripts
RUN pecl install mcrypt
RUN pecl install redis

# extensions
RUN docker-php-ext-enable mcrypt
RUN docker-php-ext-enable redis

# configure, install and enable all php packages
RUN docker-php-ext-configure pdo_mysql --with-pdo-mysql=mysqlnd
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-configure zip

RUN docker-php-ext-install -j$(nproc) pdo_mysql
RUN docker-php-ext-install -j$(nproc) sockets
RUN docker-php-ext-install -j$(nproc) curl
RUN docker-php-ext-install -j$(nproc) zip
RUN docker-php-ext-install -j$(nproc) pdo
RUN docker-php-ext-install -j$(nproc) gd


# Copy configs
#COPY ./ /var/www/html
COPY ./_docker /

# Set chmod
RUN find /var/www/html -type d -exec chmod 755 {} \;
RUN chmod -R 777 /var/www/html
RUN chmod +x /entrypoint.sh

# Configure project
#RUN cd /var/www/html && php composer.phar update

# Clean cache
RUN apt-get clean && rm -r /var/lib/apt/lists/*

# Enrtypoint #
ENTRYPOINT ["/entrypoint.sh"]

