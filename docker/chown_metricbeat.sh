#!/usr/bin/env bash
# shellcheck disable=SC2164
SCRIPT_PATH="$( cd "$(dirname "$0")" ; pwd -P )"
FILE=$SCRIPT_PATH/metricbeat.yml
OWNER=$(stat -c %U "$FILE")

if [ -f .metric ]; then
    source "$SCRIPT_PATH/.metric"
fi

if [ "$OWNER" != "root" ] && [ "$NO_METRIC_QUESTION" != "true" ]; then
    echo "Welcome to logs2ELK dev environment :)"
    echo "--------------------------------------"
    echo "If you want to have Metricbeat (Elastic monitoring container -- NOT REQUIRED)"
    echo "running, docker/metricbeat.yml must be owned by root. You may use your sudoer"
    echo "rights now and chown this file. You may also omit this step and do it later"
    echo "manually (or not). Anyway, you won't be bothered any more."
    echo
    read -p "Do you want to sudo chown the file now? (y/N): " ANSWER

    if [ "$ANSWER" == "y" ]; then
        sudo chown root:root "$FILE"
        echo "Owned. Starting environment with Metricbeat..."
    else
        echo "Starting environment without Metricbeat..."
    fi

    echo "export NO_METRIC_QUESTION=true" > "$SCRIPT_PATH/.metric"
fi
