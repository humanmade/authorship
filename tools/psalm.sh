#!/usr/bin/env bash

set -euo pipefail

find_compatible_php() {
	if [[ -n "${PSALM_PHP_BIN:-}" ]]; then
		if [[ ! -x "${PSALM_PHP_BIN}" ]]; then
			echo "Configured PSALM_PHP_BIN is not executable: ${PSALM_PHP_BIN}" >&2
			exit 2
		fi

		echo "${PSALM_PHP_BIN}"
		return
	fi

	local default_php
	default_php="$(command -v php)"

	if "${default_php}" -r 'exit(PHP_VERSION_ID >= 80500 ? 0 : 1);'; then
		shopt -s nullglob
		local candidates=(
			"${HOME}/Library/Application Support/Local/lightning-services/php-8.4."*/bin/darwin-arm64/bin/php
			"${HOME}/Library/Application Support/Local/lightning-services/php-8.4."*/bin/darwin/bin/php
			"${HOME}/Library/Application Support/Local/lightning-services/php-8.3."*/bin/darwin-arm64/bin/php
			"${HOME}/Library/Application Support/Local/lightning-services/php-8.3."*/bin/darwin/bin/php
		)
		shopt -u nullglob

		local candidate
		for candidate in "${candidates[@]}"; do
			if [[ -x "${candidate}" ]]; then
				echo "${candidate}"
				return
			fi
		done
	fi

	echo "${default_php}"
}

PHP_BIN="$(find_compatible_php)"

if "${PHP_BIN}" -r 'exit(PHP_VERSION_ID >= 80500 ? 0 : 1);'; then
	echo "Psalm 5 is unstable on PHP 8.5+ in this environment." >&2
	echo "Set PSALM_PHP_BIN to a PHP <= 8.4 binary and retry." >&2
	exit 2
fi

exec "${PHP_BIN}" -d error_reporting='E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED' vendor/bin/psalm "$@"
