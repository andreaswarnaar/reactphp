#!/usr/bin/env bash

# Set environment variables for dev
export HOST_GID=$(id -g)
export HOST_UID=$(id -u)
export PROJECT_NAME='sfreactphp'
export APP_PORT=${APP_PORT:-80}
export DB_PORT=${DB_PORT:-3306}
export DB_ROOT_PASS=${DB_ROOT_PASS:-${PROJECT_NAME}}
export DB_NAME=${DB_NAME:-${PROJECT_NAME}}
export DB_USER=${DB_USER:-${PROJECT_NAME}}
export DB_PASS=${DB_PASS:-${PROJECT_NAME}}
export DB_HOST=${DB_HOST:-mysql}
export APP_IMAGE_NAME="${PROJECT_NAME}_web_image"

export $(egrep -v '^#' .env | xargs);

export DATABASE_URL=${DATABASE_URL:-"mysql://${DB_USER}:${DB_PASS}@${DB_HOST}:${DB_PORT}/${DB_NAME}"}
# Decide which docker-compose file to use
COMPOSE_FILE="dev"
TTY=""

# Change settings for CI
if [ "$1" == "unit-test" ]; then
    COMPOSE_FILE="unit"
fi

COMPOSE="docker-compose -f docker/docker-compose.$COMPOSE_FILE.yml"
COMMAND="docker inspect --format=\"{{.State.Status}}\" ${PROJECT_NAME}_db"
STATUS=$($COMMAND 2> /dev/null)

# If we pass any arguments...
if [ $# -gt 0 ];then

    if [ "$1" == "build" ]; then
        shift 1
        $COMPOSE down
        docker build ./docker/app -t $APP_IMAGE_NAME --build-arg "local_user_id=${HOST_UID}" --build-arg "local_user_group_id=${HOST_GID}"
        $COMPOSE build "$@"

    elif [ "$1" == "app" ]; then
        shift 1
        $COMPOSE run --rm $TTY \
            -w /var/www/html \
            web \
            php bin/console "$@"
       if [[ $STATUS != "running" ]]; then
            $COMPOSE down &> /dev/null
       fi

    elif [ "$1" == "console" ]; then
        shift 1
        $COMPOSE exec web bash

    elif [ "$1" == "node" ]; then
        shift 1
        $COMPOSE exec node bash

    elif [ "$1" == "new-jwt-certs" ]; then
        shift 1
        openssl genrsa -passout pass:$JWT_PASSPHRASE -out $JWT_PRIVATE_KEY_PATH -aes256 4096 && \
        openssl rsa -pubout -in $JWT_PRIVATE_KEY_PATH -passin pass:$JWT_PASSPHRASE -out $JWT_PUBLIC_KEY_PATH

    elif [ "$1" == "composer" ]; then
        shift 1
        $COMPOSE run --rm $TTY \
            -w /var/www/html \
            web \
            composer "$@"
        if [[ $STATUS != "running" ]]; then
            $COMPOSE down &> /dev/null
        fi

   elif [ "$1" == "unit-test" ]; then
        shift 1
        $COMPOSE run --rm $TTY \
            -w /var/www/html \
            web \
            ./vendor/bin/phpunit "$@"
            $COMPOSE down &> /dev/null
    else
        $COMPOSE "$@"
    fi

else
    $COMPOSE ps
fi