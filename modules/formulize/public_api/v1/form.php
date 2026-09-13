<?php
###############################################################################
##     Formulize - ad hoc form creation and reporting module for XOOPS       ##
##                Copyright (c) 2026 Formulize Incorporated                  ##
###############################################################################
##  Released under the GNU General Public License. See license elsewhere in  ##
##  this distribution for details.                                           ##
###############################################################################
/**
 * Public API endpoints that act on a single form.
 *
 *   /formulize-public-api/v1/form/{form_handle_or_id}/read
 *
 * $id, $method and $formulize_publicApiUser are set by the controller that landed us
 * here. $id is the form handle or id, $method is what to do with it, and the user is
 * whoever the controller authenticated the request as, null for anonymous. A write
 * method is expected to join read here later, which is why this file is named for the
 * object rather than the action.
 *
 * Parameters travel in a JSON request body, or in the query string for simple
 * cases. The real work is done by the shared core in include/readentries.php,
 * which the MCP server uses too.
 */

if(!defined('FORMULIZE_PUBLIC_API_REQUEST')) {
	http_response_code(500);
	exit();
}

include_once XOOPS_ROOT_PATH.'/modules/formulize/include/readentries.php';


/**
 * Read a parameter that is a list of values.
 *
 * A JSON body sends a real array. A query string can send either repeated parameters
 * or a single comma separated value, so both are accepted.
 *
 * @param array parameters The request parameters
 * @param string name The parameter to read
 * @return array
 */
function formulize_publicApiReadList($parameters, $name) {
	if(!isset($parameters[$name])) {
		return array();
	}
	$value = $parameters[$name];
	if(is_array($value)) {
		return $value;
	}
	if(trim((string) $value) === '') {
		return array();
	}
	return array_map('trim', explode(',', (string) $value));
}

/**
 * Work out what the caller asked for with the raw parameter.
 *
 * true asks for raw database values throughout. A list of field handles asks for those
 * fields raw while everything else stays readable. Anything else means readable.
 *
 * @param array parameters The request parameters
 * @return bool|array
 */
function formulize_publicApiReadRawSetting($parameters) {
	if(!isset($parameters['raw'])) {
		return false;
	}
	$raw = $parameters['raw'];
	if(is_array($raw)) {
		return $raw;
	}
	if($raw === true OR $raw === 1 OR $raw === '1' OR strtolower((string) $raw) === 'true') {
		return true;
	}
	if($raw === false OR $raw === 0 OR $raw === '0' OR strtolower((string) $raw) === 'false' OR trim((string) $raw) === '') {
		return false;
	}
	// A comma separated list of the fields that should be raw
	return array_map('trim', explode(',', (string) $raw));
}

switch($method) {

	case "read":

		$requestMethod = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';
		if($requestMethod !== 'GET' AND $requestMethod !== 'POST') {
			formulize_publicApiSendError('method_not_allowed', 'Use GET or POST to read entries', 405);
		}

		try {

			$parameters = formulize_publicApiReadParameters();

			$options = array(
				'fields' => formulize_publicApiReadList($parameters, 'fields'),
			);
			// Only pass through what the caller actually sent, so the core's own defaults apply
			// to everything else. limitSize is deliberately included when it was sent as null,
			// since null means "no limit" rather than "not specified".
			foreach(array('filter', 'andOr', 'sortField', 'sortOrder', 'limitStart', 'relationship') as $parameterName) {
				if(isset($parameters[$parameterName]) AND $parameters[$parameterName] !== '') {
					$options[$parameterName] = $parameters[$parameterName];
				}
			}
			if(array_key_exists('limitSize', $parameters)) {
				$limitSize = $parameters['limitSize'];
				if($limitSize === null OR $limitSize === 'null') {
					$options['limitSize'] = null;
				} elseif($limitSize !== '') {
					$options['limitSize'] = $limitSize;
				}
			}

			$result = formulize_readEntries($id, $options, $formulize_publicApiUser);
			$rows = formulize_renderEntriesAsRows($result, formulize_publicApiReadRawSetting($parameters));

			formulize_publicApiSendJson(array(
				'data' => $rows,
				'meta' => array(
					'form' => $result['formHandle'],
					'count' => count($rows),
					'limitStart' => $result['limitStart'],
					'limitSize' => $result['limitSize'],
				),
			));

		} catch (FormulizeApiException $e) {
			formulize_publicApiSendException($e);
		}
		break;

	default:
		formulize_publicApiSendError(
			'method_not_found',
			$method ? 'Unknown method for a form: '.$method : 'No method specified. Try /read',
			404
		);
}
