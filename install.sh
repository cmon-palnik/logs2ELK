#!/bin/bash
# shellcheck disable=SC2164
SCRIPT_PATH="$( cd "$(dirname "$0")" ; pwd -P )"
INSTALL_PATH=/usr/local/logs2elk
BIN_PATH=/usr/local/bin
ENVIRONMENT="${1:-dev}"

echo "Logs2ELK installer"
echo "------------------"

# If user is not root and there is no sudo installed
if ! [ -x "$(command -v sudo)" ] && [ "$EUID" -ne 0 ]; then
  echo "No sudo installed and user is not root. Exited."
  exit 1
fi

# Is there PHP?
if ! [ -x "$(command -v php)" ]; then
  echo "PHP not installed. Exited."
  exit 1
fi

# Is there composer?
if ! [ -x "$(command -v composer)" ]; then
  echo "Composer not installed. Exited."
  exit 1
fi

echo "Running Composer..."
cd $SCRIPT_PATH
composer install
if [ $? -eq 0 ]; then
  echo "Composer install succeeded."
else
  echo "Logs2ELK: Composer couldn't resolve & install dependencies. " \
    "It may be a problem with your PHP version or missing extensions. Exited."
  exit 1
fi

# If user is not root and there is no sudo installed
if ! [ -x "$(command -v sudo)" ] && [ "$EUID" -ne 0 ]; then
  # Debian
  if [ -x "$(command -v apt-get)" ]; then
    apt-get update
    apt-get install -y sudo
  # Red Hat
  elif [ -x "$(command -v yum)" ]; then
    yum install -y sudo
  # Alpine
  elif [ -x "$(command -v apk)" ]; then
    apk add --no-cache sudo
  else
    echo "Cannot install sudo. There are no appropriate commands for managing package. Exited."
    exit 1
  fi
fi

# Set appropriate alias to run commands
if [ "$EUID" -eq 0 ]; then
  mysudo=""
else
  mysudo="sudo"
fi

echo "Copying files..."
$mysudo mkdir -p $INSTALL_PATH
$mysudo cp -R bin config docker src var vendor $INSTALL_PATH
$mysudo cp .env README.md $INSTALL_PATH
$mysudo mkdir -p $INSTALL_PATH/var/cache $INSTALL_PATH/var/log
$mysudo touch $INSTALL_PATH/var/log/$ENVIRONMENT.log

if [ "$ENVIRONMENT" != 'prod' ]; then
  echo "Creating .env.local..."
  echo "APP_ENV=$ENVIRONMENT" | $mysudo tee $INSTALL_PATH/.env.local >/dev/null
fi

$mysudo chown -R www-data:www-data $INSTALL_PATH
$mysudo chmod +x $INSTALL_PATH/bin/console

echo "Creating a symlink..."
$mysudo ln -s $INSTALL_PATH/bin/console $BIN_PATH/logs2elk
$mysudo chmod +x $BIN_PATH/logs2elk

echo "Installation complete. Now you can use 'logs2elk' as a command directly"
echo "Remember to have TZ, ELASTIC_URL, ELASTIC_USER, ELASTIC_PASSWORD, USE_EXTERNAL_ELK set" \
  " in environment variables (or .env.local if not on production)."
