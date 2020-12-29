FROM node:lts-alpine as build-stage

WORKDIR /app

COPY package*.json ./

RUN npm install

COPY . .

RUN npm run prod

FROM registry.cn-hangzhou.aliyuncs.com/duc-cnzj/base:7.4

LABEL maintainer="ducong"

ENV QUEUE_NUM=1

RUN apt-get update --fix-missing\
    && apt-get install -y nginx \
    && apt-get -y autoremove \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* \
    && echo "daemon off;" >> /etc/nginx/nginx.conf

WORKDIR /var/www/html

COPY . .
COPY --from=build-stage /app/public/ public/
COPY nginx-supervisord.conf /etc/supervisor/conf.d/nginx.conf
COPY default.conf /etc/nginx/sites-available/default

RUN composer install --no-suggest --no-dev

RUN chown -R www-data: public/ storage/
