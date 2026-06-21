FROM php:8.3-cli

RUN docker-php-ext-install pdo_sqlite

WORKDIR /app
COPY . .

EXPOSE $PORT

CMD ["bash", "start.sh"]
