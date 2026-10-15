FROM node:22-slim

RUN apt-get update \
    && apt-get install -y --no-install-recommends chromium ca-certificates fonts-liberation \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /audit
RUN npm install --omit=dev --no-audit --no-fund lighthouse@13.5.0 chrome-launcher@1.2.1 \
    && npm cache clean --force

ENV CHROME_PATH=/usr/bin/chromium
COPY theme/tests/lighthouse-audit.mjs /audit/run.mjs
ENTRYPOINT ["node", "/audit/run.mjs"]
