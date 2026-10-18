<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                Copyright (c) 2026 Formulize Incorporated                  ##
###############################################################################
##  Released under the GNU General Public License. See license elsewhere in  ##
##  this distribution for details.                                           ##
###############################################################################
/**
 * Shared core for receiving files for File Upload elements, other than through a form in a browser.
 *
 * Used by the MCP create_entries and update_entries tools (mcp/tools.php), and intended for the Public
 * API's write endpoint as well. A file arrives as either a public URL, which this server downloads, or as
 * base64 content inline in the request. Either way it gets exactly the checks a file uploaded in a form
 * gets (see formulizeFileUploadElementHandler::checkFileAgainstElementSettings).
 *
 * The work is split in two, because writing entries is not atomic:
 *
 *   formulize_stageFileForElement()   fetch or decode the file into a temporary file and check it,
 *                                     before any entry is written, so a bad file stops the whole
 *                                     request before anything has changed
 *   formulize_commitStagedFile()      once the entry has been written and has an id, move the file
 *                                     into the entry's folder in uploads
 *
 * Downloading from a URL is the risky part. The danger is not the file, which is checked like any upload,
 * but where the request goes: a caller could point this server at an address that only it can reach, like
 * a cloud metadata service or a database admin page on the local network, and then read the response back
 * as an "uploaded file". So every address a URL resolves to, at every redirect, must be on the public
 * internet, and the connection is pinned to the address that was checked. Any public domain is fine.
 *
 * Errors are reported by throwing FormulizeApiException, which each transport maps to its own error shape.
 */

if (!defined('XOOPS_ROOT_PATH')) {
	exit();
}

include_once XOOPS_ROOT_PATH.'/modules/formulize/class/apiexception.php';

/**
 * The most files one request may include, so that downloading them cannot run past the time a request is allowed
 * @return int
 */
function formulize_fileIntakeMaxFilesPerRequest() {
	return 10;
}

/**
 * Whether the site allows files to be fetched from URLs. On unless the webmaster has turned it off.
 * @return bool
 */
function formulize_remoteFileFetchingEnabled() {
	$config_handler = xoops_gethandler('config');
	$formulizeConfig = $config_handler->getConfigsByCat(0, getFormulizeModId());
	return (!isset($formulizeConfig['formulizeRemoteFileFetching']) OR $formulizeConfig['formulizeRemoteFileFetching'] == 1);
}

/**
 * Fetch or decode a file for a File Upload element, and check it against the element's settings.
 * Nothing is written to the element's folders. The file waits in a temporary location until formulize_commitStagedFile.
 * @param mixed $source A URL string, or an array with either 'url' or 'content_base64', and optionally 'filename'
 *        (required with content_base64). Null or an empty string means the file should be removed.
 * @param object $elementObject The File Upload element the file is for
 * @return array Either array('clear' => true), or the staged file: tmpPath, storedName, displayName, mimeType, size
 * @throws FormulizeApiException if the file cannot be fetched or is not acceptable
 */
function formulize_stageFileForElement($source, $elementObject) {

	if($source === null OR $source === '') {
		return array('clear' => true);
	}
	if(is_string($source)) {
		$source = array('url' => $source);
	}
	if(!is_array($source)) {
		throw new FormulizeApiException('A file must be given as a URL, or as an object with either a url or content_base64 property.', 'invalid_data');
	}
	$hasUrl = (isset($source['url']) AND is_string($source['url']) AND trim($source['url']) !== '');
	$hasContent = (isset($source['content_base64']) AND is_string($source['content_base64']) AND $source['content_base64'] !== '');
	if($hasUrl == $hasContent) {
		throw new FormulizeApiException('A file needs exactly one of url or content_base64.', 'invalid_data');
	}

	$ele_value = $elementObject->getVar('ele_value');
	$maxMegabytes = (isset($ele_value[0]) AND is_numeric($ele_value[0]) AND $ele_value[0] > 0) ? $ele_value[0] + 0 : 10;
	$maxBytes = (int) ($maxMegabytes * 1048576);
	$fileName = isset($source['filename']) ? formulize_cleanIntakeFileName($source['filename']) : '';

	if($hasUrl) {
		$fetched = formulize_fetchRemoteFile(trim($source['url']), $maxBytes, $maxMegabytes);
		$tmpPath = $fetched['tmpPath'];
		$fileName = $fileName ? $fileName : $fetched['fileName'];
	} else {
		if(!$fileName) {
			throw new FormulizeApiException('A filename, including its extension, is required with content_base64, eg: report.pdf', 'invalid_data');
		}
		$tmpPath = formulize_decodeInlineFile($source['content_base64'], $maxBytes, $maxMegabytes);
	}

	try {
		if(!$fileName) {
			throw new FormulizeApiException("The file's name could not be worked out from the URL. Provide a filename as well, including its extension, eg: report.pdf", 'invalid_data');
		}
		$mimeType = (string) mime_content_type($tmpPath);
		$dotPos = strrpos($fileName, '.');
		$extension = $dotPos ? strtolower(substr($fileName, $dotPos + 1)) : '';
		// checkFileAgainstElementSettings applies every check a file uploaded through a form gets, including
		// the "web page returned in place of a file" check (returned as 'html_mismatch'), so that check is
		// made in one place for both routes rather than repeated here.
		$fileUploadHandler = xoops_getmodulehandler('fileUploadElement', 'formulize');
		$checkResult = $fileUploadHandler->checkFileAgainstElementSettings($elementObject, $fileName, $tmpPath);
		if($checkResult !== true) {
			throw new FormulizeApiException(formulize_fileIntakeRefusalMessage($checkResult, $fileName, $extension, $mimeType, $ele_value, $hasUrl), 'invalid_data');
		}
		return array(
			'tmpPath' => $tmpPath,
			'storedName' => $fileUploadHandler->storedFileName($elementObject, $fileName),
			'displayName' => $fileName,
			'mimeType' => $mimeType,
			'size' => filesize($tmpPath),
		);
	} catch (Exception $e) {
		@unlink($tmpPath);
		throw $e;
	}
}

/**
 * Explain why a file was refused, in terms the caller can act on
 * @param string $reason The reason from checkFileAgainstElementSettings
 * @param string $fileName The name of the file
 * @param string $extension The file's extension
 * @param string $mimeType The type of the file's contents
 * @param array $ele_value The element's settings
 * @return string The explanation
 */
function formulize_fileIntakeRefusalMessage($reason, $fileName, $extension, $mimeType, $ele_value, $hasUrl = true) {
	switch($reason) {
		case 'no_extension':
			return "'$fileName' has no file extension, so its type cannot be checked. Provide a filename that includes the extension, eg: report.pdf";
		case 'blocked':
			return ".$extension files are never accepted.";
		case 'not_allowed':
			return ".$extension files are not allowed in this element. The allowed types are: ".str_replace(',', ', ', (string) $ele_value[1]).'.';
		case 'html_mismatch':
			// A web page where a file was expected is almost always a sign in page, or a viewer around the real file
			return $hasUrl
				? 'The URL returned a web page, not a file. Use a direct, public download link, or send the file as content_base64.'
				: 'The content is a web page, not a '.($extension ? ".$extension file" : 'file').'.';
		default:
			return "The contents of '$fileName' do not match its extension: it appears to be $mimeType rather than a .$extension file.";
	}
}

/**
 * Make a file name that arrived with a request safe to use
 * @param string $fileName The name as it arrived
 * @return string The name without any folders or control characters, or an empty string if nothing usable is left
 */
function formulize_cleanIntakeFileName($fileName) {
	$fileName = basename(str_replace('\\', '/', (string) $fileName));
	$fileName = trim(preg_replace('/[\x00-\x1F\x7F]/', '', $fileName));
	if(strlen($fileName) > 200) {
		// keep the extension, which is what the type checks rely on
		$dotPos = strrpos($fileName, '.');
		$extension = $dotPos ? substr($fileName, $dotPos) : '';
		$fileName = substr($fileName, 0, 200 - strlen($extension)).$extension;
	}
	return $fileName;
}

/**
 * Decode a file sent as base64, into a temporary file
 * @param string $base64 The content, optionally as a data: URL
 * @param int $maxBytes The largest file the element accepts
 * @param float $maxMegabytes The same limit in megabytes, for messages
 * @return string The path of the temporary file
 * @throws FormulizeApiException if the content is not valid base64, empty, or too large
 */
function formulize_decodeInlineFile($base64, $maxBytes, $maxMegabytes) {
	if(strncmp($base64, 'data:', 5) === 0 AND $commaPos = strpos($base64, ',')) {
		$base64 = substr($base64, $commaPos + 1);
	}
	$base64 = preg_replace('/\s+/', '', $base64);
	// check the size before decoding, so a huge string is not decoded only to be refused
	if(floor(strlen($base64) * 3 / 4) > $maxBytes + 2) {
		throw new FormulizeApiException("The file is larger than this element allows ($maxMegabytes MB).", 'invalid_data');
	}
	$content = base64_decode($base64, true);
	if($content === false) {
		throw new FormulizeApiException('content_base64 is not valid base64.', 'invalid_data');
	}
	if($content === '') {
		throw new FormulizeApiException('content_base64 is empty.', 'invalid_data');
	}
	if(strlen($content) > $maxBytes) {
		throw new FormulizeApiException("The file is larger than this element allows ($maxMegabytes MB).", 'invalid_data');
	}
	$tmpPath = tempnam(sys_get_temp_dir(), 'fzin');
	if(!$tmpPath OR file_put_contents($tmpPath, $content) === false) {
		throw new FormulizeApiException('The file could not be saved temporarily on the server.', 'internal_formulize_error');
	}
	return $tmpPath;
}

/**
 * Download a file from a public URL, into a temporary file.
 * Only addresses on the public internet are contacted, at every redirect, and the connection is pinned to the
 * address that was checked, so the name cannot be pointed somewhere else in between (DNS rebinding).
 * @param string $url The URL
 * @param int $maxBytes The largest file the element accepts
 * @param float $maxMegabytes The same limit in megabytes, for messages
 * @return array tmpPath, fileName (from the response or the URL, may be empty), contentType (as the server reported it)
 * @throws FormulizeApiException if the URL cannot be used, or the file cannot be downloaded
 */
function formulize_fetchRemoteFile($url, $maxBytes, $maxMegabytes) {

	if(!formulize_remoteFileFetchingEnabled()) {
		throw new FormulizeApiException('Fetching files from URLs is turned off on this site. Send the file as content_base64 instead.', 'invalid_data');
	}
	if(!function_exists('curl_init')) {
		throw new FormulizeApiException('This server cannot fetch files from URLs, because PHP\'s cURL extension is not installed. Send the file as content_base64 instead.', 'internal_formulize_error');
	}

	$tmpPath = tempnam(sys_get_temp_dir(), 'fzin');
	$fileHandle = $tmpPath ? fopen($tmpPath, 'wb') : false;
	if(!$fileHandle) {
		throw new FormulizeApiException('The file could not be saved temporarily on the server.', 'internal_formulize_error');
	}

	try {
		$currentUrl = $url;
		$maxRedirects = 3;
		for($hop = 0; $hop <= $maxRedirects; $hop++) {

			$target = formulize_fileIntakeVetUrl($currentUrl);

			ftruncate($fileHandle, 0);
			rewind($fileHandle);
			$headers = array();
			$received = 0;
			$tooBig = false;

			$curl = curl_init();
			curl_setopt_array($curl, array(
				// the URL rebuilt from the vetted parts, so curl cannot parse a different host than was checked
				CURLOPT_URL => $target['url'],
				// connect to the address that was checked, and nowhere else
				CURLOPT_RESOLVE => array($target['host'].':'.$target['port'].':'.$target['pinnedAddress']),
				CURLOPT_PROXY => '', // an environment proxy could reach places this check cannot see
				CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
				CURLOPT_FOLLOWLOCATION => false, // redirects are followed below, so each one can be checked
				CURLOPT_CONNECTTIMEOUT => 5,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_SSL_VERIFYPEER => true,
				CURLOPT_SSL_VERIFYHOST => 2,
				CURLOPT_USERAGENT => 'Formulize file intake',
				CURLOPT_HEADERFUNCTION => function($curl, $line) use (&$headers, &$tooBig, $maxBytes) {
					$colonPos = strpos($line, ':');
					if($colonPos) {
						$name = strtolower(trim(substr($line, 0, $colonPos)));
						$headers[$name] = trim(substr($line, $colonPos + 1));
						if($name == 'content-length' AND intval($headers[$name]) > $maxBytes) {
							$tooBig = true;
							return 0; // stop now, rather than downloading something that will be refused
						}
					}
					return strlen($line);
				},
				CURLOPT_WRITEFUNCTION => function($curl, $data) use ($fileHandle, &$received, &$tooBig, $maxBytes) {
					$received += strlen($data);
					if($received > $maxBytes) {
						$tooBig = true;
						return 0; // stops the transfer
					}
					return fwrite($fileHandle, $data);
				},
			));
			curl_exec($curl);
			$errorNumber = curl_errno($curl);
			$errorMessage = curl_error($curl);
			$status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
			$connectedTo = (string) curl_getinfo($curl, CURLINFO_PRIMARY_IP);
			curl_close($curl);

			// belt and braces: whatever happened, never keep a response from an address that is not public
			if($connectedTo AND !formulize_fileIntakeAddressIsPublic($connectedTo)) {
				throw new FormulizeApiException('That address is not on the public internet, so files cannot be fetched from it.', 'invalid_data');
			}
			if($tooBig) {
				throw new FormulizeApiException("The file is larger than this element allows ($maxMegabytes MB).", 'invalid_data');
			}
			if($errorNumber) {
				throw new FormulizeApiException('The file could not be downloaded from '.$target['host'].': '.$errorMessage, 'invalid_data');
			}
			if($status >= 300 AND $status < 400 AND !empty($headers['location'])) {
				// resolve against the vetted URL, not the raw one, and re-vet the result on the next pass
				$currentUrl = formulize_fileIntakeResolveRedirect($target['url'], $headers['location']);
				continue;
			}
			if($status < 200 OR $status >= 300) {
				throw new FormulizeApiException('The URL answered with HTTP status '.$status.' instead of the file.'.(($status == 401 OR $status == 403) ? ' The file may only be available to people who are signed in; it must be publicly available, or sent as content_base64.' : ''), 'invalid_data');
			}
			if($received == 0) {
				throw new FormulizeApiException('The URL returned an empty file.', 'invalid_data');
			}

			fclose($fileHandle);
			$fileHandle = null;
			return array(
				'tmpPath' => $tmpPath,
				'fileName' => formulize_fileIntakeNameFromResponse($headers, $target['url']),
				'contentType' => $headers['content-type'] ?? '',
			);
		}
		throw new FormulizeApiException('The URL redirected more than '.$maxRedirects.' times.', 'invalid_data');

	} catch (Exception $e) {
		if($fileHandle) {
			fclose($fileHandle);
		}
		@unlink($tmpPath);
		throw $e;
	}
}

/**
 * Check that a URL can be fetched: http or https, and every address its host resolves to is on the public internet
 * @param string $url The URL
 * @return array host, port, and pinnedAddress (the checked address, in the form CURLOPT_RESOLVE expects)
 * @throws FormulizeApiException if the URL cannot be used
 */
function formulize_fileIntakeVetUrl($url) {
	$parts = parse_url($url);
	if(!$parts OR empty($parts['scheme']) OR empty($parts['host'])) {
		throw new FormulizeApiException('Not a valid URL: '.substr($url, 0, 200), 'invalid_data');
	}
	$scheme = strtolower($parts['scheme']);
	if($scheme != 'http' AND $scheme != 'https') {
		throw new FormulizeApiException('Only http and https URLs can be used for files.', 'invalid_data');
	}
	if(isset($parts['user']) OR isset($parts['pass'])) {
		throw new FormulizeApiException('URLs that include a username or password cannot be used for files.', 'invalid_data');
	}
	$host = strtolower(trim($parts['host'], '[]'));
	$port = isset($parts['port']) ? intval($parts['port']) : ($scheme == 'https' ? 443 : 80);

	if(filter_var($host, FILTER_VALIDATE_IP)) {
		$addresses = array($host);
	} else {
		// Hosts like 2130706433 or 0x7f.1 are other ways of writing an IP address, which different
		// software interprets differently, so they are not accepted at all
		if(preg_match('/^[0-9.]+$/', $host) OR preg_match('/^0x/i', $host) OR strpos($host, '.') === false) {
			throw new FormulizeApiException('That address is not on the public internet, so files cannot be fetched from it.', 'invalid_data');
		}
		// gethostbynamel uses the system's resolver, so it sees the same names (including local ones in /etc/hosts or
		// container networks) that anything else on this server would
		$addresses = gethostbynamel($host);
		$addresses = $addresses ? $addresses : array();
		$ipv6Records = @dns_get_record($host, DNS_AAAA);
		foreach((array) $ipv6Records as $record) {
			if(!empty($record['ipv6'])) {
				$addresses[] = $record['ipv6'];
			}
		}
	}
	if(empty($addresses)) {
		throw new FormulizeApiException('The server '.$host.' could not be found.', 'invalid_data');
	}
	foreach($addresses as $address) {
		if(!formulize_fileIntakeAddressIsPublic($address)) {
			throw new FormulizeApiException('That address is not on the public internet, so files cannot be fetched from it.', 'invalid_data');
		}
	}
	// prefer IPv4, which every server can reach
	$pinned = $addresses[0];
	foreach($addresses as $address) {
		if(filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
			$pinned = $address;
			break;
		}
	}
	// Rebuild the URL from the parts that were just checked, instead of handing curl the caller's original
	// string. curl parses a URL with its own rules, so a string crafted to parse one way here (a public
	// host) and another way in curl (an internal host) would otherwise slip past every check above: the
	// CURLOPT_RESOLVE pin is filed under this host, but curl would resolve its own, different host normally.
	// Rebuilding guarantees curl sees exactly the host that was vetted and pinned.
	$hostForUrl = filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? "[$host]" : $host;
	$normalizedUrl = $scheme.'://'.$hostForUrl
		.(isset($parts['port']) ? ':'.$port : '')
		.(isset($parts['path']) ? $parts['path'] : '')
		.(isset($parts['query']) ? '?'.$parts['query'] : '');
	return array(
		'host' => $host,
		'port' => $port,
		'url' => $normalizedUrl,
		'pinnedAddress' => filter_var($pinned, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? "[$pinned]" : $pinned,
	);
}

/**
 * Is an IP address on the public internet? Private networks, loopback, link local (including cloud metadata
 * services), carrier grade NAT, multicast, documentation and other reserved ranges are not. Written out in full
 * rather than relying on PHP's FILTER_FLAG_NO_PRIV_RANGE / NO_RES_RANGE, which have covered different ranges
 * in different PHP versions.
 * @param string $address An IPv4 or IPv6 address
 * @return bool
 */
function formulize_fileIntakeAddressIsPublic($address) {
	$packed = @inet_pton(trim($address, '[]'));
	if($packed === false) {
		return false;
	}
	$blockedRanges = strlen($packed) == 4
		? array(
			'0.0.0.0/8', '10.0.0.0/8', '100.64.0.0/10', '127.0.0.0/8', '169.254.0.0/16', '172.16.0.0/12',
			'192.0.0.0/24', '192.0.2.0/24', '192.88.99.0/24', '192.168.0.0/16', '198.18.0.0/15',
			'198.51.100.0/24', '203.0.113.0/24', '224.0.0.0/4', '240.0.0.0/4'
		)
		: array(
			'::/96', // unspecified, loopback, and the old IPv4 compatible addresses
			'::ffff:0:0/96', // IPv4 mapped
			'64:ff9b::/96', '64:ff9b:1::/48', // NAT64, which embeds an IPv4 address
			'100::/64', '2001:db8::/32', '2002::/16', // discard, documentation, 6to4 (embeds IPv4)
			'fc00::/7', 'fe80::/10', 'fec0::/10', 'ff00::/8' // unique local, link local, site local, multicast
		);
	foreach($blockedRanges as $range) {
		if(formulize_fileIntakeAddressInRange($packed, $range)) {
			return false;
		}
	}
	return true;
}

/**
 * Is a packed IP address within a range?
 * @param string $packed The address, from inet_pton
 * @param string $range The range in CIDR notation, eg: 10.0.0.0/8
 * @return bool
 */
function formulize_fileIntakeAddressInRange($packed, $range) {
	list($subnet, $bits) = explode('/', $range);
	$packedSubnet = inet_pton($subnet);
	if(strlen($packedSubnet) != strlen($packed)) {
		return false;
	}
	$bits = intval($bits);
	$wholeBytes = intdiv($bits, 8);
	if(substr($packed, 0, $wholeBytes) !== substr($packedSubnet, 0, $wholeBytes)) {
		return false;
	}
	$remainingBits = $bits % 8;
	if($remainingBits) {
		$mask = (0xFF << (8 - $remainingBits)) & 0xFF;
		if((ord($packed[$wholeBytes]) & $mask) != (ord($packedSubnet[$wholeBytes]) & $mask)) {
			return false;
		}
	}
	return true;
}

/**
 * Work out the URL a redirect points to, which may be relative to the URL that was requested
 * @param string $currentUrl The URL that was requested
 * @param string $location The Location header of the redirect
 * @return string The URL to request next
 */
function formulize_fileIntakeResolveRedirect($currentUrl, $location) {
	if(preg_match('/^[a-z][a-z0-9+.-]*:/i', $location)) {
		return $location;
	}
	$parts = parse_url($currentUrl);
	$origin = $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');
	if(substr($location, 0, 2) == '//') {
		return $parts['scheme'].':'.$location;
	}
	if(substr($location, 0, 1) == '/') {
		return $origin.$location;
	}
	$path = isset($parts['path']) ? $parts['path'] : '/';
	return $origin.substr($path, 0, strrpos($path, '/') + 1).$location;
}

/**
 * The name of a downloaded file: from the Content-Disposition header if there is one, otherwise the last part of the URL's path
 * @param array $headers The response headers, with lowercase names
 * @param string $url The URL the file came from
 * @return string The name, or an empty string if there is none
 */
function formulize_fileIntakeNameFromResponse($headers, $url) {
	$fileName = '';
	if(!empty($headers['content-disposition'])) {
		if(preg_match("/filename\*\s*=\s*[^']*'[^']*'([^;]+)/i", $headers['content-disposition'], $matches)) {
			$fileName = rawurldecode(trim($matches[1], " \"'"));
		} elseif(preg_match('/filename\s*=\s*"?([^";]+)"?/i', $headers['content-disposition'], $matches)) {
			$fileName = trim($matches[1]);
		}
	}
	if(!$fileName) {
		$path = (string) parse_url($url, PHP_URL_PATH);
		$fileName = rawurldecode(basename($path));
	}
	return formulize_cleanIntakeFileName($fileName);
}

/**
 * The value stored in the database for a staged file, in the same shape that files uploaded in a form are stored
 * @param array $staged A staged file from formulize_stageFileForElement
 * @return string The serialized record
 */
function formulize_storedFileRecord($staged) {
	return serialize(array(
		'name' => $staged['storedName'],
		'isfile' => true,
		'type' => $staged['mimeType'],
		'size' => $staged['size'],
	));
}

/**
 * Move a staged file into the folder for an entry, replacing any file the entry had for this element before
 * @param array $staged A staged file from formulize_stageFileForElement
 * @param object $elementObject The File Upload element
 * @param int $entryId The entry, which must have been written already
 * @return bool True if the file is in place
 */
function formulize_commitStagedFile($staged, $elementObject, $entryId) {
	$fileUploadHandler = xoops_getmodulehandler('fileUploadElement', 'formulize');
	formulize_removeFileForEntry($elementObject, $entryId);
	return $fileUploadHandler->placeFile($fileUploadHandler->entryFolderPath($elementObject, $entryId), $staged['tmpPath'], $staged['storedName']) === true;
}

/**
 * Remove the file an entry has for a File Upload element, if any
 * @param object $elementObject The File Upload element
 * @param int $entryId The entry
 * @return void
 */
function formulize_removeFileForEntry($elementObject, $entryId) {
	if(intval($entryId) > 0) {
		$fileUploadHandler = xoops_getmodulehandler('fileUploadElement', 'formulize');
		$fileUploadHandler->deleteAssociatedDataAndResources($elementObject, intval($entryId));
	}
}

/**
 * Delete the temporary files of staged files that were not committed, ie: because the request failed part way through
 * @param array $stagedFiles Staged files from formulize_stageFileForElement, at any depth of nesting
 * @return void
 */
function formulize_discardStagedFiles($stagedFiles) {
	foreach((array) $stagedFiles as $staged) {
		if(isset($staged['tmpPath'])) {
			if(file_exists($staged['tmpPath'])) {
				@unlink($staged['tmpPath']);
			}
		} elseif(is_array($staged)) {
			formulize_discardStagedFiles($staged);
		}
	}
}
