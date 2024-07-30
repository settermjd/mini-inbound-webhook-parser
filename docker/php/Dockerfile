ARG ALPINE_VERSION=3.20
ARG PHP_VERSION=8.3.9

FROM php:${PHP_VERSION}-fpm-alpine${ALPINE_VERSION} as base

COPY --from=composer:2.3.5 /usr/bin/composer /usr/bin/

RUN apk --update-cache add autoconf gcc g++ make re2c \
  && pecl install --alldeps mailparse \
    && apk del autoconf gcc g++ make re2c