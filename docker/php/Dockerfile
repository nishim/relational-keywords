FROM php:7-apache

# node
RUN set -x && \
    apt update && \
    apt upgrade -y && \
    curl -sL https://deb.nodesource.com/setup_10.x | bash - && \
    apt install -y nodejs && \
# local
    apt install -y locales && \
    echo "ja_JP UTF-8" > /etc/locale.gen && \
    locale-gen

ENV LANG=ja_JP.UTF-8