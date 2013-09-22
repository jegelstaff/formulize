<?php
/**
 * Manage configuration items
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		LICENSE.txt
 * @category	ICMS
 * @package		Config
 * @author		Kazumi Ono (aka onokazo)
 * @version		SVN: $Id:Handler.php 19775 2010-07-11 18:54:25Z malanciault $
 */

defined('ICMS_ROOT_PATH') or die("ImpressCMS root path not defined");

/**
 * Configuration handling class.
 * This class acts as an interface for handling general configurations
 * and its modules.
 *
 * @category	ICMS
 * @package 	Config
 * @author		Kazumi Ono <webmaster@myweb.ne.jp>
 * @todo		Tests that need to be made:
 * 				- error handling
 * @access		public
 */
class icms_config_Handler {
	static protected $instance;
	/**
	 * Initialize the config handler.
	 * @param $db
	 */
	static public function service() {
		if (isset(self::$instance)) return self::$instance;
		$instance = icms::handler('icms_config');
		$configs = $instance->getConfigsByCat(
			array(
				ICMS_CONF, ICMS_CONF_USER, ICMS_CONF_METAFOOTER, ICMS_CONF_MAILER,
				ICMS_CONF_AUTH, ICMS_CONF_MULILANGUAGE, ICMS_CONF_PERSONA, ICMS_CONF_PLUGINS,
				ICMS_CONF_CAPTCHA, ICMS_CONF_SEARCH
			)
		);
		$GLOBALS['icmsConfig']			 = $configs[ICMS_CONF];
		$GLOBALS['xoopsConfig']			 =& $GLOBALS['icmsConfig'];
		$GLOBALS['icmsConfigUser']       = $configs[ICMS_CONF_USER];
		$GLOBALS['icmsConfigMetaFooter'] = $configs[ICMS_CONF_METAFOOTER];
		$GLOBALS['icmsConfigMailer']     = $configs[ICMS_CONF_MAILER];
		$GLOBALS['icmsConfigAuth']       = $configs[ICMS_CONF_AUTH];
		$GLOBALS['icmsConfigMultilang']  = $configs[ICMS_CONF_MULILANGUAGE];
		$GLOBALS['icmsConfigPersona']    = $configs[ICMS_CONF_PERSONA];
		$GLOBALS['icmsConfigPlugins']    = $configs[ICMS_CONF_PLUGINS];
		$GLOBALS['icmsConfigCaptcha']    = $configs[ICMS_CONF_CAPTCHA];
		$GLOBALS['icmsConfigSearch']     = $configs[ICMS_CONF_SEARCH];
		return self::$instance = $instance;
	}

	/**
	 * holds reference to config item handler(DAO) class
	 *
	 * @var     object
	 * @access	private
	 */
	private $_cHandler;

	/**
	 * holds reference to config option handler(DAO) class
	 *
	 * @var	    object
	 * @access	private
	 */
	private $_oHandler;

	/**
	 * holds an array of cached references to config value arrays,
	 *  indexed on module id and category id
	 *
	 * @var     array
	 * @access  private
	 */
	private $_cachedConfigs = array();

	/**
	 * Constructor
	 *
	 * @param	object  &$db    reference to database object
	 */
	public function __construct(&$db) {
		$this->_cHandler = new icms_config_item_Handler($db);
		$this->_oHandler = new icms_config_option_Handler($db);
	}

	/**
	 * Create a config
	 *
	 * @see     icms_config_Item_Object
	 * @return	object  reference to the new {@link icms_config_Item_Object}
	 */
	public function &createConfig() {
		$instance =& $this->_cHandler->create();
		return $instance;
	}

	/**
	 * Get a config
	 *
	 * @param	int     $id             ID of the config
	 * @param	bool    $withoptions    load the config's options now?
	 * @return	object  reference to the {@link icms_config_Item_Object}
	 */
	public function &getConfig($id, $withoptions = false) {
		$config =& $this->_cHandler->get($id);
		if ($withoptions == true) {
			$config->setConfOptions($this->getConfigOptions(new icms_db_criteria_Item('conf_id', $id)));
		}
		return $config;
	}

	/**
	 * insert a new config in the database
	 *
	 * @param	object  &$config    reference to the {@link icms_config_Item_Object}
	 * @return	true|false if inserting config succeeded or not
	 */
	public function insertConfig(&$config) {
		if (!$this->_cHandler->insert($config)) {
			return false;
		}
		$options =& $config->getConfOptions();
		$count = count($options);
		$conf_id = $config->getVar('conf_id');
		for ( $i = 0; $i < $count; $i++) {
			$options[$i]->setVar('conf_id', $conf_id);
			if (!$this->_oHandler->insert($options[$i])) {
				foreach ( $options[$i]->getErrors() as $msg) {
					$config->setErrors($msg);
				}
			}
		}

		if (!empty($this->_cachedConfigs[$config->getVar('conf_modid')][$config->getVar('conf_catid')])) {
			unset($this->_cachedConfigs[$config->getVar('conf_modid')][$config->getVar('conf_catid')]);
		}
		return true;
	}

	/**
	 * Delete a config from the database
	 *
	 * @param	object  &$config    reference to a {@link icms_config_Item_Object}
	 * @return	true|false if deleting config item succeeded or not
	 */
	public function deleteConfig(&$config) {
		if (!$this->_cHandler->delete($config)) {
			return false;
		}
		$options =& $config->getConfOptions();
		$count = count($options);
		if ($count == 0) {
			$options = $this->getConfigOptions(new icms_db_criteria_Item('conf_id', $config->getVar('conf_id')));
			$count = count($options);
		}
		if (is_array($options) && $count > 0) {
			for ( $i = 0; $i < $count; $i++) {
				$this->_oHandler->delete($options[$i]);
			}
		}
		if (!empty($this->_cachedConfigs[$config->getVar('conf_modid')][$config->getVar('conf_catid')])) {
			unset($this->_cachedConfigs[$config->getVar('conf_modid')][$config->getVar('conf_catid')]);
		}
		return true;
	}

	/**
	 * get one or more Configs
	 *
	 * @param	object  $criteria       {@link icms_db_criteria_Element}
	 * @param	bool    $id_as_key      Use the configs' ID as keys?
	 * @param	bool    $with_options   get the options now?
	 *
	 * @return	array   Array of {@link icms_config_Item_Object} objects
	 */
	public function getConfigs($criteria = null, $id_as_key = false, $with_options = false) {
		return $this->_cHandler->getObjects($criteria, $id_as_key);
	}

	/**
	 * Count some configs
	 *
	 * @param	object  $criteria   {@link icms_db_criteria_Element}
	 * @return	int count result
	 */
	public function getConfigCount($criteria = null) {
		return $this->_cHandler->getCount($criteria);
	}

	/**
	 * Get configs from a certain category
	 *
	 * @param	int $category   ID of a category
	 * @param	int $module     ID of a module
	 *
	 * @return	array   array of {@link icms_config_Item_Object}s
	 */
	public function &getConfigsByCat($category, $module = 0) {
		if (is_array($category)) {
			$criteria = new icms_db_criteria_Compo(new icms_db_criteria_Item('conf_modid', (int) $module));
			$criteria->add(new icms_db_criteria_Item('conf_catid', '(' . implode(',', $category) . ')', 'IN'));
			$configs = $this->getConfigs($criteria, true);
			if (is_array($configs)) {
				foreach ( array_keys($configs) as $i) {
					$ret[$configs[$i]->getVar('conf_catid')][$configs[$i]->getVar('conf_name')] = $configs[$i]->getConfValueForOutput();
				}
				foreach ( $ret as $key => $value) {
					$this->_cachedConfigs[$module][$key] = $value;
				}
				return $ret;
			}
		} else {
			if (!empty($this->_cachedConfigs[$module][$category]) ) return $this->_cachedConfigs[$module][$category];

			$criteria = new icms_db_criteria_Compo(new icms_db_criteria_Item('conf_modid', (int) $module));
			if (!empty($category)) {
				$criteria->add(new icms_db_criteria_Item('conf_catid', (int) $category));
			}
			$ret = array();
			$configs = $this->getConfigs($criteria, true);
			if (is_array($configs)) {
				foreach ( array_keys($configs) as $i) {
					$ret[$configs[$i]->getVar('conf_name')] = $configs[$i]->getConfValueForOutput();
				}
			}
			$this->_cachedConfigs[$module][$category] = $ret;
			return $this->_cachedConfigs[$module][$category];
		}
	}

	/**
	 * Make a new {@link icms_config_option_Object}
	 *
	 * @return	object  {@link icms_config_option_Object}
	 */
	public function &createConfigOption() {
		$inst =& $this->_oHandler->create();
		return $inst;
	}

	/**
	 * Get a {@link icms_config_option_Object}
	 *
	 * @param	int $id ID of the config option
	 *
	 * @return	object  {@link icms_config_option_Object}
	 */
	public function &getConfigOption($id) {
		$inst =& $this->_oHandler->get($id);
		return $inst;
	}

	/**
	 * Get one or more {@link icms_config_option_Object}s
	 *
	 * @param	object  $criteria   {@link icms_db_criteria_Element}
	 * @param	bool    $id_as_key  Use IDs as keys in the array?
	 *
	 * @return	array   Array of {@link icms_config_option_Object}s
	 */
	public function getConfigOptions($criteria = null, $id_as_key = false) {
		return $this->_oHandler->getObjects($criteria, $id_as_key);
	}

	/**
	 * Count some {@link icms_config_option_Object}s
	 *
	 * @param	object  $criteria   {@link icms_db_criteria_Element}
	 *
	 * @return	int     Count of {@link icms_config_option_Object}s matching $criteria
	 */
	public function getConfigOptionsCount($criteria = null) {
		return $this->_oHandler->getCount($criteria);
	}

	/**
	 * Get a list of configs
	 *
	 * @param	int $conf_modid ID of the modules
	 * @param	int $conf_catid ID of the category
	 *
	 * @return	array   Associative array of name=>value pairs.
	 */
	public function getConfigList($conf_modid, $conf_catid = 0) {
		if (!empty($this->_cachedConfigs[$conf_modid][$conf_catid])) {
			return $this->_cachedConfigs[$conf_modid][$conf_catid];
		} else {
			$criteria = new icms_db_criteria_Compo(new icms_db_criteria_Item('conf_modid', $conf_modid));
			if (empty($conf_catid)) {
				$criteria->add(new icms_db_criteria_Item('conf_catid', $conf_catid));
			}
			$configs =& $this->_cHandler->getObjects($criteria);
			$confcount = count($configs);
			$ret = array();
			for ( $i = 0; $i < $confcount; $i++) {
				$ret[$configs[$i]->getVar('conf_name')] = $configs[$i]->getConfValueForOutput();
			}
			$this->_cachedConfigs[$conf_modid][$conf_catid] =& $ret;
			return $ret;
		}
	}
}

