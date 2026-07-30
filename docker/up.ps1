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
#   .\docker\up.ps1                 start in the foreground
#   .\docker\up.ps1 -d              any extra arguments are passed to docker compose
#   .\docker\up.ps1 -PortsOnly      choose and record ports, but don't start anything

[CmdletBinding()]
param(
	[switch]$PortsOnly,
	[Parameter(ValueFromRemainingArguments = $true)]
	[string[]]$ComposeArgs
)

$ErrorActionPreference = 'Stop'

$repoRoot = Split-Path -Parent $PSScriptRoot
$envFile = Join-Path $repoRoot '.env'
$envExampleFile = Join-Path $repoRoot '.env.example'

# The port is free if we can bind it ourselves. Asking for an exclusive binding
# makes the test strict, so a port that is being held in any way at all counts as
# taken - erring that way is harmless, because we just move on to the next one.
function Test-PortIsFree {
	param([int]$Port)
	$listener = $null
	try {
		$listener = New-Object System.Net.Sockets.TcpListener([System.Net.IPAddress]::Loopback, $Port)
		$listener.ExclusiveAddressUse = $true
		$listener.Start()
		return $true
	} catch {
		return $false
	} finally {
		if ($null -ne $listener) {
			try { $listener.Stop() } catch { }
		}
	}
}

function Find-FreePort {
	param([int]$StartPort)
	for ($port = $StartPort; $port -lt ($StartPort + 100); $port++) {
		if (Test-PortIsFree -Port $port) {
			return $port
		}
	}
	throw "Could not find a free port in the range $StartPort-$($StartPort + 99)."
}

function Get-EnvValue {
	param([string]$Key, [string[]]$Lines)
	$pattern = '^\s*' + [regex]::Escape($Key) + '\s*=\s*(.*)$'
	foreach ($line in $Lines) {
		if ($line -match $pattern) {
			return $Matches[1].Trim()
		}
	}
	return ''
}

function Set-EnvValue {
	param([string]$Key, [string]$Value, [string[]]$Lines)
	$pattern = '^\s*' + [regex]::Escape($Key) + '\s*='
	$found = $false
	$result = @(foreach ($line in $Lines) {
		if ($line -match $pattern) {
			$found = $true
			"$Key=$Value"
		} else {
			$line
		}
	})
	if (-not $found) {
		$result += "$Key=$Value"
	}
	return $result
}

# Start from the existing .env, or seed a new one from .env.example so the
# comments explaining each setting come along too.
if (Test-Path -LiteralPath $envFile) {
	$lines = @(Get-Content -LiteralPath $envFile)
} elseif (Test-Path -LiteralPath $envExampleFile) {
	$lines = @(Get-Content -LiteralPath $envExampleFile)
} else {
	$lines = @()
}
$originalLines = $lines

$portSettings = @(
	@{ Key = 'FORMULIZE_WEB_PORT'; Base = 8080; Label = 'Web' },
	@{ Key = 'FORMULIZE_DB_PORT';  Base = 3306; Label = 'MariaDB' }
)

$chosen = @{ }
foreach ($setting in $portSettings) {
	$existing = Get-EnvValue -Key $setting.Key -Lines $lines
	if ($existing -match '^\d+$') {
		# Already settled, by us on a previous run or by hand. Leave it alone.
		$chosen[$setting.Key] = [int]$existing
		continue
	}
	$port = Find-FreePort -StartPort $setting.Base
	$chosen[$setting.Key] = $port
	$lines = Set-EnvValue -Key $setting.Key -Value $port -Lines $lines
	if ($port -ne $setting.Base) {
		Write-Host "$($setting.Label) port $($setting.Base) is already in use, using $port instead." -ForegroundColor Yellow
	}
}

if (Compare-Object -ReferenceObject $originalLines -DifferenceObject $lines -SyncWindow 0) {
	Set-Content -LiteralPath $envFile -Value $lines -Encoding UTF8
	Write-Host "Recorded the port settings in .env" -ForegroundColor Green
}

$webPort = $chosen['FORMULIZE_WEB_PORT']
$dbPort = $chosen['FORMULIZE_DB_PORT']

Write-Host ""
Write-Host "Formulize:  http://localhost:$webPort" -ForegroundColor Cyan
Write-Host "MariaDB:    127.0.0.1:$dbPort"
Write-Host ""

if ($PortsOnly) {
	return
}

# docker compose reads .env from the project directory on its own, so the ports
# recorded above are already in effect.
Push-Location -LiteralPath $repoRoot
try {
	docker compose up @ComposeArgs
	exit $LASTEXITCODE
} finally {
	Pop-Location
}
