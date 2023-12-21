#!/bin/bash
update-ca-certificates
runuser -u 0 -- php /app/bin/console cache:warmup && \
tail -f >/dev/null
