#!/usr/bin/env bash

OWNER_UID="$(stat -c '%u' /var/www/docker-andrii-test-smake)"
OWNER_GID="$(stat -c '%g' /var/www/docker-andrii-test-smake)"

info () {
  printf "\033[0;36m===> \033[0;33m%s\033[0m\n" "$1"
}

info "/var/www/docker-andrii-test-smake owner is: ${OWNER_UID}:${OWNER_GID}"

if [[ "${OWNER_UID}" != "0" ]] && [[ "${OWNER_UID}" != "$(id -u www-data)" ]]; then
  info "Changing www-data UID to ${OWNER_UID}"
  usermod -u "${OWNER_UID}" www-data
fi

if [[ "${OWNER_GID}" != "0" ]] && [[ "${OWNER_GID}" != "$(id -g www-data)" ]]; then
  info "Changing www-data GID to ${OWNER_GID}"
  groupmod -g "${OWNER_GID}" www-data > /dev/null 2>&1
fi

exec docker-php-entrypoint "$@"