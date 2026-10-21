<?php
/**
 * Manage configuration items
 *
 * @copyright    http://www.xoops.org/ The XOOPS Project
 * @copyright    XOOPS_copyrights.txt
 * @copyright    http://www.impresscms.org/ The ImpressCMS Project
 * @license      LICENSE.txt
 * @package      core
 * @subpackage   config
 * @since        XOOPS
 * @author       Kazumi Ono (aka onokazo)
 * @author       http://www.xoops.org The XOOPS Project
 * @version      $Id:Handler.php 19775 2010-07-11 18:54:25Z malanciault $
 */

if (!defined('ICMS_ROOT_PATH')) die("ImpressCMS root path not defined");

/**#@+
 * Config type
 */
define('ICMS_CONF', 1);
define('ICMS_CONF_USER', 2);
define('ICMS_CONF_METAFOOTER', 3);
define('ICMS_CONF_CENSOR', 4);
define('ICMS_CONF_SEARCH', 5);
define('ICMS_CONF_MAILER', 6);
define('ICMS_CONF_AUTH', 7);
define('ICMS_CONF_MULILANGUAGE', 8);
define('ICMS_CONF_CONTENT', 9);
define('ICMS_CONF_PERSONA', 10);
define('ICMS_CONF_CAPTCHA', 11);
define('ICMS_CONF_PLUGINS', 12);
define('ICMS_CONF_AUTOTASKS', 13);
define('ICMS_CONF_PURIFIER', 14);
/**#@-*/

/**
 * Configuration handler class.
 *
 * This class is responsible for providing data access mechanisms to the data source
 * of configuration class objects.
 *
 * @author       Kazumi Ono <onokazu@xoops.org>
 * @category	ICMS
 * @package     Config
 * @subpackage  Item
 */
class icms_config_Item_Handler extends icms_core_ObjectHandler {

	/**
	 * Create a new {@link icms_config_Item_Object}
	 *
	 * @see     icms_config_Item_Object
	 * @param	bool    $isNew  Flag the config as "new"?
	 * @return	object  reference to the new config
	 */
	public function &create($isNew = true) {
		$config = new icms_config_Item_Object();
		if ($isNew) {
			$config->setNew();
		}
		return $config;
	}

	/**
	 * Load a config from the database
	 *
	 * @param	int $id ID of the config
	 * @return	object  reference to the config, FALSE on fail
	 */
	public function &get($id) {
		$config = false;
		$id = (int) $id;
		if ($id > 0) {
			$sql = "SELECT * FROM " . $this->db->prefix('config') . " WHERE conf_id='" . $id . "'";
			if (!$result = $this->db->query($sql)) {
				return $config;
			}
			$numrows = $this->db->getRowsNum($result);
			if ($numrows == 1) {
				$myrow = $this->db->fetchArray($result);
				$config = new icms_config_Item_Object();
				$config->assignVars($myrow);
			}
		}
		return $config;
	}

	/**
	 * Insert a config to the database
	 *
	 * @param	object  &$config    {@link icms_config_Item_Object} object
	 * @return  mixed   FALSE on fail.
	 */
	public function insert(&$config, $force=true) {
		/* As of PHP5.3.0, is_a() is no longer deprecated, no need to replace this */
		if (!is_a($config, 'icms_config_Item_Object')) {
			return false;
		}
		if (!$config->isDirty()) {
			return true;
		}
		if (!$config->cleanVars()) {
			return false;
		}
		/**
		 * MAJOR HACK: encrypt the site-wide AI API key before it is ever written to
		 * conf_value. This is the one place every save path funnels through - this
		 * settings page's own delegated save, and the legacy system preferences page it
		 * delegates through - so it is where the plaintext gets swapped for its encrypted
		 * form. formulizeAIApiKey's own posted value is a meaningless constant (see
		 * formulize_configAiKeyFieldHtml() in modules/formulize/include/configsettings.php)
		 * used only so this config item is always treated as "changed" and reaches here;
		 * the values that matter - formulizeAIApiKey_new, formulizeAIApiKey_clear,
		 * formulizeAIProvider - travel as ordinary, unregistered POST fields the save loop
		 * above never looks at, but that PHP's extract($_POST) (also above) still turns
		 * into globals we can read here.
		 *
		 * $config->getVar('conf_value') cannot be used to find out what is currently
		 * stored: the save loop above already called setConfValueForInput() on $config
		 * with the meaningless 'touch' placeholder before insert() was ever reached, so
		 * the in-memory copy no longer reflects the database. Reading it here (as an
		 * earlier version of this hack did) makes formulizeAI_prepareApiKeyForConfigStorage()
		 * treat 'touch' as the existing key map, which fails to decode as JSON and reads
		 * as empty - so every save of this settings page, not only ones where a new key
		 * was typed, silently erased whatever key was already stored. Read the real,
		 * still-current value straight from the database instead.
		 */
		if ($config->getVar('conf_name') == 'formulizeAIApiKey') {
			include_once XOOPS_ROOT_PATH . '/modules/formulize/include/aiadminconfig.php';
			if (function_exists('formulizeAI_prepareApiKeyForConfigStorage')) {
				global $formulizeAIApiKey_new, $formulizeAIApiKey_clear, $formulizeAIProvider;
				$currentRow = false;
				if ($result = $this->db->query("SELECT conf_value FROM " . $this->db->prefix('config') . " WHERE conf_id = '" . (int) $config->getVar('conf_id') . "'")) {
					$currentRow = $this->db->fetchArray($result);
				}
				$config->setVar('conf_value', formulizeAI_prepareApiKeyForConfigStorage(
					$currentRow ? $currentRow['conf_value'] : '',
					isset($formulizeAIApiKey_new) ? $formulizeAIApiKey_new : '',
					!empty($formulizeAIApiKey_clear),
					isset($formulizeAIProvider) ? $formulizeAIProvider : ''
				));
				$config->cleanVars();
			}
		}
		/**
		 * END OF FORMULIZE AI KEY HACK
		 */

		/**
		 * MAJOR HACK TO VERIFY IF FORMULIZE REWRITE URL SETTING, PUBLIC API, OR MCP SERVER IS CAPABLE OF BEING ENABLED
		 */
		if(($config->getVar('conf_name') == 'formulizeRewriteRulesEnabled'
			OR $config->getVar('conf_name') == 'formulizePublicAPIEnabled'
			OR $config->getVar('conf_name') == 'formulizeMCPServerEnabled')
			AND $config->getVar('conf_value') == 1
			AND function_exists('curl_version')) {

			// when inside Docker, discard the mapped port, so that these requests will be answered by this server inside Docker. The port in XOOPS_URL is the mapping from host to container (8080 by default, but configurable so that several copies of Formulize can run at once), which will not work when requests are being routed from the container itself.
			$correctXOOP_URL = XOOPS_URL;
			if(file_exists('/.dockerenv') AND preg_match('~^(https?://(?:localhost|127\.0\.0\.1)):\d+(.*)$~', XOOPS_URL, $urlParts)) {
				$correctXOOP_URL = $urlParts[1].$urlParts[2];
			}
			switch($config->getVar('conf_name')) {
				case 'formulizeRewriteRulesEnabled':
					$url = $correctXOOP_URL.'/formulize-check-if-alternate-urls-are-properly-enabled-please'; // will resolve based on DNS available to server, so Docker gets confused by localhost!
					break;
				case 'formulizePublicAPIEnabled':
					$url = $correctXOOP_URL.'/formulize-public-api/v1/status/formulize_check_if_public_api_is_properly_enabled_please'; // will resolve based on DNS available to server, so Docker gets confused by localhost!
					break;
				case 'formulizeMCPServerEnabled':
					$url = $correctXOOP_URL.'/mcp/index.php/health'; // The health endpoint, addressed the same way MCP clients address it
					break;
			}

			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLINFO_HEADER_OUT, true);
			$testedViaApiKeyHeader = false;

			// The MCP server and the Public API both authenticate with an Authorization header,
			// so both need to know whether this server passes that header through to PHP at all.
			if($config->getVar('conf_name') == 'formulizeMCPServerEnabled'
				OR $config->getVar('conf_name') == 'formulizePublicAPIEnabled') {
				// Add a test Authorization header to verify it passes through
				$testHeaders = array('Authorization: Bearer test-header-passthrough-check');
				// If the site is behind HTTP Basic Auth, this request would be refused by the web
				// server before reaching the MCP server, so forward the administrator's own Basic
				// credentials and put the test key in X-Formulize-API-Key instead, the same way an
				// external client would have to on this site. The Public API does not read that
				// header (yet), so it keeps the Bearer test.
				if($config->getVar('conf_name') == 'formulizeMCPServerEnabled') {
					$incomingAuth = '';
					if(function_exists('getallheaders')) {
						foreach(getallheaders() as $headerName => $headerValue) {
							if(strtolower($headerName) === 'authorization') {
								$incomingAuth = $headerValue;
								break;
							}
						}
					}
					if(!$incomingAuth) {
						$incomingAuth = $_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
					}
					if(!preg_match('/^Basic\s/i', $incomingAuth) AND isset($_SERVER['PHP_AUTH_USER']) AND isset($_SERVER['PHP_AUTH_PW'])) {
						$incomingAuth = 'Basic '.base64_encode($_SERVER['PHP_AUTH_USER'].':'.$_SERVER['PHP_AUTH_PW']);
					}
					if(preg_match('/^Basic\s/i', $incomingAuth)) {
						$testHeaders = array(
							'Authorization: '.$incomingAuth,
							'X-Formulize-API-Key: test-header-passthrough-check'
						);
						$testedViaApiKeyHeader = true;
					}
				}
				curl_setopt($curl, CURLOPT_HTTPHEADER, $testHeaders);
				curl_setopt($curl, CURLOPT_TIMEOUT, 10); // MCP server might take a moment to respond
			}

			// Keep the response headers, so a web server asking for Basic Auth can be recognized
			$responseHeaders = array();
			curl_setopt($curl, CURLOPT_HEADERFUNCTION, function($curl, $headerLine) use (&$responseHeaders) {
				$headerParts = explode(':', $headerLine, 2);
				if(count($headerParts) == 2) {
					$responseHeaders[strtolower(trim($headerParts[0]))] = trim($headerParts[1]);
				}
				return strlen($headerLine);
			});

			$response = curl_exec($curl);
			$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			$curlError = curl_error($curl);
			curl_close($curl);

			// Validate response based on the service type
			$validResponse = false;
			switch($config->getVar('conf_name')) {
				case 'formulizeRewriteRulesEnabled':
					$validResponse = ($response == 1);
					break;
				case 'formulizePublicAPIEnabled':
					$json = json_decode($response);
					$validResponse = (is_object($json) AND !empty($json->status) AND $json->status == "healthy");
					// A stripped Authorization header does not stop the Public API being enabled,
					// because session based and anonymous callers still work without one. Record the
					// result so the settings page can warn that API keys will not work on this server.
					// Recorded through the shared function rather than written straight to the session,
					// so that it carries the timestamp the cache needs - without one this answer would
					// never go stale, and the warning would still be on the settings page long after
					// the administrator had fixed their server configuration. See
					// formulize_authHeaderPassthrough() in modules/formulize/include/functions.php.
					if($validResponse) {
						include_once XOOPS_ROOT_PATH.'/modules/formulize/include/functions.php';
						formulize_recordAuthHeaderPassthrough(!empty($json->authorization_header_received));
					}
					break;
				case 'formulizeMCPServerEnabled':
					$json = json_decode($response);
					// The MCP server should answer HTTP 200, with JSON saying canBeEnabled. The test value
					// authenticates nobody, so mcp/index.php answers that whether or not the setting is
					// already on (see authenticateRequest in mcp/mcp.php).
					$validResponse = ($httpCode == 200 AND is_object($json) AND isset($json->status) AND $json->status == 'canBeEnabled');
					// The web server in front of Formulize asked for Basic Auth credentials, and either
					// PHP could not see the administrator's to forward them (common with PHP-FPM), or
					// they were refused. The request never reached the MCP server, so there is nothing
					// more to learn here, but external clients will bring their own credentials and
					// send the API key in X-Formulize-API-Key, so let the setting stay on rather than
					// make it impossible to turn on behind Basic Auth. Formulize's own 401s do not send
					// WWW-Authenticate, so this cannot be mistaken for the MCP server refusing a key.
					$behindBasicAuth = false;
					if(!$validResponse AND $httpCode == 401
						AND isset($responseHeaders['www-authenticate'])
						AND preg_match('/^Basic\b/i', $responseHeaders['www-authenticate'])) {
						$validResponse = true;
						$behindBasicAuth = true;
					}
					include_once XOOPS_ROOT_PATH.'/modules/formulize/include/functions.php';
					// canBeEnabled is set only when the test Authorization header actually arrived
					// (see mcp/mcp.php), so a valid response here is proof the header gets through,
					// and worth recording for the warnings that ask about it elsewhere. Only the
					// positive: a failure here could equally be the MCP server being unreachable or
					// broken, which says nothing about the header either way. Nor when the test key
					// went in X-Formulize-API-Key, or the web server answered for the MCP server,
					// neither of which proves anything about Authorization.
					if($validResponse AND !$testedViaApiKeyHeader AND !$behindBasicAuth) {
						formulize_recordAuthHeaderPassthrough(true);
					}
					// Say why the setting is about to be switched back off, so the preferences page
					// can show it instead of the setting silently refusing to save
					if($validResponse) {
						formulize_recordMCPEnableCheckFailure(null);
					} elseif($response === false) {
						formulize_recordMCPEnableCheckFailure("this server could not connect to its own MCP server at $url".($curlError ? " ($curlError)" : '').'.');
					} else {
						$reason = "the test request to $url got HTTP $httpCode";
						if(is_object($json) AND isset($json->error->message)) {
							$reason .= ', with the message "'.$json->error->message.'"';
						}
						if($httpCode == 503 AND is_object($json) AND isset($json->error->type) AND $json->error->type == 'server_disabled') {
							$reason .= '. That means the test Authorization header did not reach the MCP server';
						}
						formulize_recordMCPEnableCheckFailure($reason.'.');
					}
					break;
			}

			// If validation failed, force the setting back to disabled
			if(!$validResponse) {
				$config->setVar('conf_value', 0);
				$config->cleanVars();
			}
		}
		/**
		 * END OF MAJOR HACK
		 */
		foreach ( $config->cleanVars as $k => $v) {
			${$k} = $v;
		}
		if ($config->isNew()) {
			$conf_id = $this->db->genId('config_conf_id_seq');
			$sql = sprintf(
				"INSERT INTO %s (
				conf_id,
				conf_modid,
				conf_catid,
				conf_name,
				conf_title,
				conf_value,
				conf_desc,
				conf_formtype,
				conf_valuetype,
				conf_order)
				 VALUES ('%u', '%u', '%u', %s, %s, %s, %s, %s, %s, '%u')",
				$this->db->prefix('config'),
				(int) $conf_id,
				(int) $conf_modid,
				(int) $conf_catid,
				$this->db->quoteString($conf_name),
				$this->db->quoteString($conf_title),
				$this->db->quoteString($conf_value),
				$this->db->quoteString($conf_desc),
				$this->db->quoteString($conf_formtype),
				$this->db->quoteString($conf_valuetype),
				(int) $conf_order
			);
		} else {
			$sql = sprintf(
				"UPDATE %s SET conf_modid = '%u',
				conf_catid = '%u',
				conf_name = %s,
				conf_title = %s,
				conf_value = %s,
				conf_desc = %s,
				conf_formtype = %s,
				conf_valuetype = %s,
				conf_order = '%u'
				WHERE conf_id = '%u'",
				$this->db->prefix('config'),
				(int) $conf_modid,
				(int) $conf_catid,
				$this->db->quoteString($conf_name),
				$this->db->quoteString($conf_title),
				$this->db->quoteString($conf_value),
				$this->db->quoteString($conf_desc),
				$this->db->quoteString($conf_formtype),
				$this->db->quoteString($conf_valuetype),
				(int) $conf_order,
				(int) $conf_id
			);
		}
		if($force) {
			if (!$result = $this->db->queryF($sql)) {
				return false;
			}
		} else {
			if (!$result = $this->db->query($sql)) {
				return false;
			}
		}
		if (empty($conf_id)) {
			$conf_id = $this->db->getInsertId();
		}
		$config->assignVar('conf_id', $conf_id);

		/**
		 * FORMULIZE: when one of the date/time format settings is saved, regenerate
		 * the small file that the language global.php files include, so the
		 * _DATESTRING / _SHORTDATESTRING / _SHORTTIMESTRING constants reflect the
		 * saved values. Kept here (the lowest persistence level) so it fires for
		 * the Formulize admin UI and the legacy preferences page alike, with no
		 * Formulize code needing to be loaded. Self-contained: reads the three
		 * values straight from the config table and writes the file.
		 * _MEDIUMDATESTRING is a backwards-compat alias for _DATESTRING defined
		 * in global.php; it is not stored separately.
		 */
		$formulizeDateFormatConstants = array(
			'datestring'      => '_DATESTRING',
			'shortdatestring' => '_SHORTDATESTRING',
			'shorttimestring' => '_SHORTTIMESTRING',
		);
		if (defined('XOOPS_ROOT_PATH') && isset($formulizeDateFormatConstants[$config->getVar('conf_name')])) {
			$fmlzNames = "'" . implode("','", array_keys($formulizeDateFormatConstants)) . "'";
			$fmlzRes = $this->db->query("SELECT conf_name, conf_value FROM " . $this->db->prefix('config') . " WHERE conf_name IN ($fmlzNames)");
			if ($fmlzRes) {
				$fmlzLines = "<?php\n";
				while ($fmlzRow = $this->db->fetchArray($fmlzRes)) {
					// Only write a define for a non-empty value. An empty value means
					// "use the language's global.php default", so we leave the constant
					// undefined here and let global.php's own fallback define it.
					if (isset($formulizeDateFormatConstants[$fmlzRow['conf_name']]) && $fmlzRow['conf_value'] !== '') {
						$fmlzConst = $formulizeDateFormatConstants[$fmlzRow['conf_name']];
						$fmlzLines .= "if (!defined('" . $fmlzConst . "')) define('" . $fmlzConst . "', '" . addslashes($fmlzRow['conf_value']) . "');\n";
					}
				}
				global $icmsConfig;
				$langFolder = isset($icmsConfig['language']) ? $icmsConfig['language'] : 'english';
				@file_put_contents(XOOPS_ROOT_PATH . '/modules/formulize/language/' . $langFolder . '/formulize_dateformats.php', $fmlzLines);
			}
		}

		return true;
	}

	/**
	 * Delete a config from the database
	 *
	 * @param	object  &$config    Config to delete
	 * @return	bool    Successful?
	 */
	public function delete(&$config, $force=false) {
		/* As of PHP5.3.0, is_as() is no longer deprecated, there is no need to replace it */
		if (!is_a($config, 'icms_config_Item_Object')) {
			return false;
		}
		$sql = sprintf(
			"DELETE FROM %s WHERE conf_id = '%u'",
			$this->db->prefix('config'), (int) $config->getVar('conf_id')
		);
		if($force) {
			if (!$result = $this->db->queryF($sql)) {
				return false;
			}
		} else {
			if (!$result = $this->db->query($sql)) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Get configs from the database
	 *
	 * @param	object  $criteria   {@link icms_db_criteria_Element}
	 * @param	bool    $id_as_key  return the config's id as key?
	 * @return	array   Array of {@link icms_config_Item_Object} objects
	 */
	public function getObjects($criteria = null, $id_as_key = false) {
		$ret = array();
		$limit = $start = 0;
		$sql = 'SELECT * FROM ' . $this->db->prefix('config');
		if (isset($criteria) && is_subclass_of($criteria, 'icms_db_criteria_Element')) {
			$sql .= ' ' . $criteria->renderWhere();
			$sql .= ' ORDER BY conf_order ASC';
			$limit = $criteria->getLimit();
			$start = $criteria->getStart();
		}
		$result = $this->db->query($sql, $limit, $start);
		if (!$result) {
			return false;
		}
		while ($myrow = $this->db->fetchArray($result)) {
			$config = new icms_config_item_Object();
			$config->assignVars($myrow);
			if (!$id_as_key) {
				$ret[] =& $config;
			} else {
				$ret[$myrow['conf_id']] =& $config;
			}
			unset($config);
		}
		return $ret;
	}

	/**
	 * Count configs
	 *
	 * @param	object  $criteria   {@link icms_db_criteria_Element}
	 * @return	int     Count of configs matching $criteria
	 */
	public function getCount($criteria = null) {
		$ret = array();
		$limit = $start = 0;
		$sql = 'SELECT * FROM ' . $this->db->prefix('config');
		if (isset($criteria) && is_subclass_of($criteria, 'icms_db_criteria_Element')) {
			$sql .= ' ' . $criteria->renderWhere();
		}
		$result =& $this->db->query($sql);
		if (!$result) {
			return false;
		}
		list($count) = $this->db->fetchRow($result);
		return $count;
	}
}

