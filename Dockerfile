FROM php:8.2-apache

# O SQLite já vem nativo no PHP, só precisamos copiar os arquivos
COPY . /var/www/html/

# Altera a porta padrão do Apache (80) para ler a variável dinâmica do Render ($PORT)
RUN sed -i 's/Listen 80/Listen ${PORT}/g' /etc/apache2/ports.conf \
    && sed -i 's/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/g' /etc/apache2/sites-available/000-default.conf

# Dá permissão total para a pasta do Apache ler e escrever (essencial para o SQLite salvar os dados)
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 777 /var/www/html

ENV PORT=80

EXPOSE ${PORT}