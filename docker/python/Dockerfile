FROM python:3.9-slim as compiler
ENV PYTHONUNBUFFERED 1

WORKDIR /app/

RUN python -m venv /opt/venv
# Enable venv
ENV PATH="/opt/venv/bin:$PATH"

COPY ./scripts/requirements.txt /app/requirements.txt
RUN pip install -Ur requirements.txt

FROM python:3.9-slim as runner
WORKDIR /app/

RUN apt update && apt install -y \
    curl \
    jq \
    cron\
    && apt clean

COPY --from=compiler /opt/venv /opt/venv
COPY etc/cron.d/python /etc/cron.d/python
COPY usr/local/bin/docker-python-entrypoint /usr/local/bin/docker-python-entrypoint

# Enable venv
ENV PATH="/opt/venv/bin:$PATH"
COPY ./scripts /app/

RUN rm -f /var/run/crond.pid

ENTRYPOINT ["/usr/local/bin/docker-python-entrypoint"]
