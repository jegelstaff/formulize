const { execSync } = require('child_process');

function extractMostRecentError(logContent) {
  const lines = logContent.split('\n').filter(line => line.trim());

  // Search from bottom to top for the most recent error
  for (let i = lines.length - 1; i >= 0; i--) {
    try {
      const logEntry = JSON.parse(lines[i]);

      if (logEntry.formulize_event === 'PHP-error-recorded') {
        return {
          errorString: logEntry.PHP_error_string || 'Unknown error',
          errorFile: logEntry.PHP_error_file || 'Unknown file',
          errorLine: logEntry.PHP_error_errline || 'Unknown line',
          microtime: logEntry.microtime,
          url: logEntry.url
        };
      }
    } catch (e) {
      // Skip malformed JSON lines
      continue;
    }
  }

  return null;
}

function containerPathToRepoPath(containerPath) {
  // Convert container path to repository path
  // /var/www/html/modules/... -> modules/...
  return containerPath.replace(/^\/var\/www\/html\//, '');
}

class PhpErrorReporter {
  onTestEnd(test, result) {

    if (result.status === 'passed' || result.status === 'skipped') {
      return; // Only check for PHP errors if the test ended badly
    }

    try {
      const containerName = 'formulize-web-1';
      const logPath = '/var/www/html/logs/formulize_log_active.log';

      const catCommand = `docker exec ${containerName} cat ${logPath}`;
      const logContent = execSync(catCommand, { encoding: 'utf8', timeout: 10000 });

      const errorInfo = extractMostRecentError(logContent);

      if (errorInfo) {
        const errorMessage = [
          `Error: ${errorInfo.errorString}`,
          `File: ${errorInfo.errorFile}`,
          `Line: ${errorInfo.errorLine}`,
          errorInfo.url ? `URL: ${errorInfo.url}` : ''
        ].filter(Boolean).join('\n');

        console.error('\n' + '='.repeat(70));
        console.error('APPLICATION ERROR (from logs):');
        console.error('='.repeat(70));
        console.error(errorMessage);
        console.error('='.repeat(70) + '\n');

        // GitHub Actions annotation
        if (process.env.GITHUB_ACTIONS) {
          const githubSafeText = errorMessage.replace(/\n/g, '%0A');
          const repoPath = containerPathToRepoPath(errorInfo.errorFile);
          console.log(`::error file=${repoPath},line=${errorInfo.errorLine},title=PHP Error in ${test.title}::${githubSafeText}`);
        }

        // Attach to the test result for the HTML report
        result.attachments.push({
          name: 'php-error-from-log',
          body: Buffer.from(errorMessage),
          contentType: 'text/plain'
        });
      }
    } catch (e) {
      // Silently fail - log might not exist or container might not be running
      console.log('Could not read log from container:', e.message);
    }
  }
}

module.exports = PhpErrorReporter;
