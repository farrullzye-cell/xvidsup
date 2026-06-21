FROM php:8.3-cli

RUN apt-get update && apt-get install -y libsqlite3-dev && \
    docker-php-ext-install pdo_sqlite

WORKDIR /app
COPY . .

EXPOSE $PORT

CMD bash start.sh
