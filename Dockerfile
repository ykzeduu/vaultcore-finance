FROM php:8.2-apache

# Instala as extensões PDO e PostgreSQL que seu código precisa agora
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Copia todos os arquivos do seu projeto para o diretório padrão do Apache
COPY . /var/www/html/

# Altera a porta padrão do Apache (80) para ler a variável dinâmica do Render ($PORT)
RUN sed -i 's/Listen 80/Listen ${PORT}/g' /etc/apache2/ports.conf \
    && sed -i 's/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/g' /etc/apache2/sites-available/000-default.conf

# Garante as permissões corretas para o Apache ler os arquivos
RUN chown -R www-data:www-data /var/www/html

# Define uma porta padrão caso queira testar localmente
ENV PORT=80

EXPOSE ${PORT}