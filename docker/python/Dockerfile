FROM alpine:3.20.3

WORKDIR /app

RUN apk update && apk add --update --no-cache \
    python3 \
    py3-pip \
    && ln -sf python3 /usr/bin/python

COPY ./usr/local/bin/docker-python-entrypoint /usr/local/bin/
COPY .env /app/