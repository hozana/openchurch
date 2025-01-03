FROM python:3.9-slim as compiler
ENV PYTHONUNBUFFERED 1

WORKDIR /app/

# install cron daemon
RUN apt update && apt install cron -y && apt -yq autoremove ; apt clean

RUN python -m venv /opt/venv
# Enable venv
ENV PATH="/opt/venv/bin:$PATH"

COPY ./scripts/requirements.txt /app/requirements.txt
RUN pip install -Ur requirements.txt

FROM python:3.9-slim as runner
WORKDIR /app/
COPY --from=compiler /opt/venv /opt/venv

# Enable venv
ENV PATH="/opt/venv/bin:$PATH"
COPY ./scripts /app/

ENTRYPOINT ["/usr/sbin/cron", "-f", "-l", "15"]
