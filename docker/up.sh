#!/usr/bin/env bash
#
# Formulize local Docker launcher.
#
# Picks host ports that are actually free, records them in the .env file at the
# root of the repository, and then hands off to "docker compose up".
#
# Recording the ports is the point: it means a given copy of Formulize keeps the
# same ports every time you start it, so bookmarks, the database port and the
# test suite's base URL all stay put. Once .env has ports in it, plain
# "docker compose up" works too, and this script will not second-guess them.
#
# Usage:
#   ./docker/up.sh                start in the foreground
#   ./docker/up.sh -d             any extra arguments are passed to docker compose
#   ./docker/up.sh --ports-only   choose and record ports, but don't start anything

set -euo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ENV_FILE="$REPO_ROOT/.env"
ENV_EXAMPLE_FILE="$REPO_ROOT/.env.example"

PORTS_ONLY=0
COMPOSE_ARGS=()
for arg in "$@"; do
	if [ "$arg" = "--ports-only" ]; then
		PORTS_ONLY=1
	else
		COMPOSE_ARGS+=("$arg")
	fi
done

# Returns success when nothing is listening on the port. Prefers ss or lsof,
# which report ports that are bound but not yet accepting connections. Falls back
# to a bash /dev/tcp connection attempt, which needs no tools installed at all.
port_is_free() {
	local port="$1"
	if command -v ss >/dev/null 2>&1; then
		! ss -ltn "sport = :$port" 2>/dev/null | tail -n +2 | grep -q .
	elif command -v lsof >/dev/null 2>&1; then
		! lsof -nP -iTCP:"$port" -sTCP:LISTEN >/dev/null 2>&1
	else
		! (exec 3<>"/dev/tcp/127.0.0.1/$port") >/dev/null 2>&1
	fi
}

find_free_port() {
	local start_port="$1" port
	for (( port = start_port; port < start_port + 100; port++ )); do
		if port_is_free "$port"; then
			printf '%s\n' "$port"
			return 0
		fi
	done
	echo "Could not find a free port in the range $start_port-$(( start_port + 99 ))." >&2
	return 1
}

get_env_value() {
	local key="$1" line
	[ -f "$ENV_FILE" ] || return 0
	while IFS= read -r line || [ -n "$line" ]; do
		case "$line" in
			"$key"=*) printf '%s\n' "${line#*=}"; return 0 ;;
		esac
	done < "$ENV_FILE"
}

set_env_value() {
	local key="$1" value="$2" tmp line found=0
	tmp="$(mktemp)"
	if [ -f "$ENV_FILE" ]; then
		while IFS= read -r line || [ -n "$line" ]; do
			case "$line" in
				"$key"=*) printf '%s=%s\n' "$key" "$value" >> "$tmp"; found=1 ;;
				*) printf '%s\n' "$line" >> "$tmp" ;;
			esac
		done < "$ENV_FILE"
	fi
	if [ "$found" -eq 0 ]; then
		printf '%s=%s\n' "$key" "$value" >> "$tmp"
	fi
	mv "$tmp" "$ENV_FILE"
}

# Start from the existing .env, or seed a new one from .env.example so the
# comments explaining each setting come along too.
if [ ! -f "$ENV_FILE" ] && [ -f "$ENV_EXAMPLE_FILE" ]; then
	cp "$ENV_EXAMPLE_FILE" "$ENV_FILE"
	echo "Created .env from .env.example"
fi

choose_port() {
	local key="$1" base="$2" label="$3" existing port
	existing="$(get_env_value "$key")"
	if [[ "$existing" =~ ^[0-9]+$ ]]; then
		# Already settled, by us on a previous run or by hand. Leave it alone.
		printf '%s\n' "$existing"
		return 0
	fi
	port="$(find_free_port "$base")"
	set_env_value "$key" "$port"
	if [ "$port" != "$base" ]; then
		echo "$label port $base is already in use, using $port instead." >&2
	else
		echo "Recorded $label port $port in .env" >&2
	fi
	printf '%s\n' "$port"
}

WEB_PORT="$(choose_port FORMULIZE_WEB_PORT 8080 Web)"
DB_PORT="$(choose_port FORMULIZE_DB_PORT 3306 MariaDB)"

echo ""
echo "Formulize:  http://localhost:$WEB_PORT"
echo "MariaDB:    127.0.0.1:$DB_PORT"
echo ""

if [ "$PORTS_ONLY" -eq 1 ]; then
	exit 0
fi

# docker compose reads .env from the project directory on its own, so the ports
# recorded above are already in effect.
cd "$REPO_ROOT"
# The +... form keeps "set -u" happy when no extra arguments were passed, which
# matters on the bash 3.2 that ships with macOS.
exec docker compose up ${COMPOSE_ARGS[@]+"${COMPOSE_ARGS[@]}"}
