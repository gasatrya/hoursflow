#!/usr/bin/env bash

set -euo pipefail

if [[ $# -lt 5 ]]; then
    echo "Usage: $0 <db-name> <db-user> <db-pass> <db-host> <wordpress-version>" >&2
    exit 1
fi

DB_NAME="$1"
DB_USER="$2"
DB_PASS="$3"
DB_HOST="$4"
WP_VERSION="$5"
WP_TESTS_DIR="${WP_TESTS_DIR:-/tmp/wordpress-tests-lib}"
WP_CORE_DIR="${WP_CORE_DIR:-/tmp/wordpress}"

if [[ ! "$WP_VERSION" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
    echo "An exact WordPress patch version is required; received: $WP_VERSION" >&2
    exit 1
fi
if [[ ! "$DB_NAME" =~ ^[A-Za-z0-9_]+$ ]]; then
    echo "The database name may contain only letters, numbers, and underscores." >&2
    exit 1
fi

hoursflow_download() {
    local url="$1"
    local destination="$2"

    if command -v curl >/dev/null 2>&1; then
        curl --fail --location --silent --show-error "$url" --output "$destination"
    elif command -v wget >/dev/null 2>&1; then
        wget --quiet --output-document="$destination" "$url"
    else
        echo "curl or wget is required to download WordPress test files." >&2
        exit 1
    fi
}

hoursflow_create_database() {
    if ! command -v mysql >/dev/null 2>&1; then
        echo "mysql is required to create the WordPress test database." >&2
        exit 1
    fi

    mysql \
        --user="$DB_USER" \
        --password="$DB_PASS" \
        --host="$DB_HOST" \
        --execute="CREATE DATABASE IF NOT EXISTS ${DB_NAME};"
}

hoursflow_install_core() {
    local archive
    local temporary_directory
    local version_file
    local actual_version

    archive="$(mktemp)"
    temporary_directory="$(mktemp -d)"
    hoursflow_download "https://wordpress.org/wordpress-${WP_VERSION}.tar.gz" "$archive"
    tar --extract --gzip --file="$archive" --directory="$temporary_directory"
    rm -rf "$WP_CORE_DIR"
    mkdir -p "$WP_CORE_DIR"
    cp -R "$temporary_directory/wordpress/." "$WP_CORE_DIR/"

    version_file="$WP_CORE_DIR/wp-includes/version.php"
    actual_version="$(sed -n "s/^\$wp_version = '\([^']*\)';/\1/p" "$version_file")"
    if [[ "$actual_version" != "$WP_VERSION" ]]; then
        rm -rf "$temporary_directory"
        rm -f "$archive"
        echo "Downloaded WordPress version $actual_version, expected $WP_VERSION." >&2
        exit 1
    fi

    rm -rf "$temporary_directory"
    rm -f "$archive"
}

hoursflow_install_test_suite() {
    local archive
    local temporary_directory
    local test_directory

    archive="$(mktemp)"
    temporary_directory="$(mktemp -d)"
    hoursflow_download \
        "https://github.com/WordPress/wordpress-develop/archive/refs/tags/${WP_VERSION}.tar.gz" \
        "$archive"
    tar --extract --gzip --file="$archive" --directory="$temporary_directory"
    test_directory="$(find "$temporary_directory" -type d -path '*/tests/phpunit' -print -quit)"
    if [[ -z "$test_directory" ]]; then
        rm -rf "$temporary_directory"
        rm -f "$archive"
        echo "Could not find the WordPress PHPUnit test suite for $WP_VERSION." >&2
        exit 1
    fi

    rm -rf "$WP_TESTS_DIR"
    mkdir -p "$WP_TESTS_DIR"
    cp -R "$test_directory/." "$WP_TESTS_DIR/"
    rm -rf "$temporary_directory"
    rm -f "$archive"
}

hoursflow_write_test_config() {
    cat > "$WP_TESTS_DIR/wp-tests-config.php" <<PHP
<?php

define( 'DB_NAME', '${DB_NAME}' );
define( 'DB_USER', '${DB_USER}' );
define( 'DB_PASSWORD', '${DB_PASS}' );
define( 'DB_HOST', '${DB_HOST}' );
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );
define( 'WP_TESTS_DOMAIN', 'localhost' );
define( 'WP_TESTS_EMAIL', 'admin@example.org' );
define( 'WP_TESTS_TITLE', 'HoursFlow Integration Tests' );
define( 'WP_PHP_BINARY', 'php' );
define( 'ABSPATH', '${WP_CORE_DIR}/' );
PHP
}

hoursflow_create_database
hoursflow_install_core
hoursflow_install_test_suite
hoursflow_write_test_config

echo "Installed WordPress ${WP_VERSION} and its matching PHPUnit test suite."
