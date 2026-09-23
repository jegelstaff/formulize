// Works out which Docker containers the end-to-end tests reach into with `docker exec`.
//
// Docker Compose names containers <project>-<service>-1, where the project is
// COMPOSE_PROJECT_NAME from the .env file at the root of the repository or, when
// that is blank, the name of the folder the repository is in. So a checkout in a
// folder called formulize2 runs formulize2-web-1 and formulize2-mariadb-1, and a
// hardcoded formulize-web-1 would quietly reach into some other copy of Formulize.
// Rather than repeat Compose's naming rules here, this asks Compose, from the root
// of the repository, which container is running each service.
//
// Like base-url.js, this is plain CommonJS so the reporter, utils.js and the specs
// can all use it. The lookup runs on first use rather than on import, and once per
// process, because every spec imports utils.js and most never touch a container.
//
// E2E_WEB_CONTAINER and E2E_DB_CONTAINER still override the lookup, for a one-off
// run against a container Compose doesn't know about.

const { execFileSync } = require('child_process');
const path = require('path');

// The root of the repository, two levels up from tests/e2e, where docker-compose.yaml is.
const REPO_ROOT = path.resolve(__dirname, '../..');

const found = {};

function containerFor(service, overrideVariable) {
	if (process.env[overrideVariable]) {
		return process.env[overrideVariable];
	}
	if (!(service in found)) {
		let name = '';
		try {
			name = execFileSync('docker', ['compose', 'ps', '--format', '{{.Name}}', service], {
				cwd: REPO_ROOT,
				encoding: 'utf8',
				stdio: ['ignore', 'pipe', 'ignore'],
				timeout: 30000,
			}).trim().split('\n')[0];
		} catch (e) {
			// Compose isn't available or the service isn't running. Fall back to the name a
			// checkout in a folder called formulize gets, which is what CI uses.
		}
		found[service] = name || `formulize-${service}-1`;
	}
	return found[service];
}

function webContainer() {
	return containerFor('web', 'E2E_WEB_CONTAINER');
}

function dbContainer() {
	return containerFor('mariadb', 'E2E_DB_CONTAINER');
}

module.exports = { webContainer, dbContainer };
