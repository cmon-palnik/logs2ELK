#!/bin/bash
update-ca-certificates
php /app/bin/console cache:warmup && \
tail -f >/dev/null
