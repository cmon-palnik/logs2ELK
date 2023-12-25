#!/bin/bash
# shellcheck disable=SC2164
SCRIPT_PATH="$( cd "$(dirname "$0")" ; pwd -P )"
INSTALL_PATH=/usr/local/logs2elk
BIN_PATH=/usr/local/bin

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
  INSTALL_COMPOSER=false

  if [ "$1" == "--auto" ]; then
    INSTALL_COMPOSER=true
  else
    echo "Composer not installed."

    read -p "Do you want to download and install Composer? (Y/n): " -r answer
    answer=${answer,,}  # to lowercase

    if [ -z "$answer" ] || [ "$answer" == "y" ]; then
      INSTALL_COMPOSER=true
    else
      echo "Composer is required. Exited."
      exit 1
    fi
  fi

  if [ "$INSTALL_COMPOSER" == true ]; then
    EXPECTED_CHECKSUM="$(php -r 'copy("https://composer.github.io/installer.sig", "php://stdout");')"
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    ACTUAL_CHECKSUM="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"

    if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]
    then
        >&2 echo 'ERROR: Invalid Composer installer checksum. Exited.'
        rm composer-setup.php
        exit 1
    fi

    php composer-setup.php --quiet
    RESULT=$?
    rm composer-setup.php

    if [ "$RESULT" == 1 ]; then
      echo "Composer installation problem. Exited."
      exit 1
    fi
  fi
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
    echo "Cannot install sudo. There are no appropriate commands for managing packages. Exited."
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
$mysudo cp .en* composer.* dev-comp* docker-comp* README.md $INSTALL_PATH

echo "Creating .env.local..."
echo "APP_ENV=prod" | $mysudo tee $INSTALL_PATH/.env.local >/dev/null

$mysudo chown -R www-data:www-data $INSTALL_PATH
$mysudo chmod +x $INSTALL_PATH/bin/console

echo "Creating a symlink..."
$mysudo ln -s $INSTALL_PATH/bin/console $BIN_PATH/logs2elk
$mysudo chmod +x $BIN_PATH/logs2elk

echo "Installation complete. Now you can use 'logs2elk' as a command directly"
echo "Remember to have TZ, ELASTIC_URL, ELASTIC_USER, ELASTIC_PASSWORD, USE_EXTERNAL_ELK set" \
  " in environment variables (or .env.local if not on production)."
