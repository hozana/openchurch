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
COPY --from=compiler /opt/venv /opt/venv

# install and configure cron daemon
RUN apt update; apt install cron libterm-readline-gnu-perl procps -y; apt -yq autoremove; apt clean
COPY etc/cron.d/syncro /etc/cron.d/syncro
COPY usr/local/bin/docker-python-entrypoint /usr/local/bin/docker-python-entrypoint

# Enable venv
ENV PATH="/opt/venv/bin:$PATH"
COPY ./scripts /app/

CMD ["/usr/sbin/cron", "-f", "-l", "15"]

ENTRYPOINT ["/usr/local/bin/docker-python-entrypoint"]
