<?php
/**
 * Class to easily export data from IcmsPersistables
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @category	ICMS
 * @package		Ipf
 * @subpackage	Export
 * @since		1.2
 * @author		marcan <marcan@impresscms.org>
 * @author	    Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version		SVN: $Id: Handler.php 11188 2011-04-16 03:29:27Z skenow $
 */

/**
 *
 * @category	ICMS
 * @package		Ipf
 * @subpackage	Export
 */
class icms_ipf_export_Handler {

	public $handler;
	public $criteria;
	public $fields;
	public $format;
	public $filename;
	public $filepath;
	public	$options;
	public $outputMethods=false;
	public $notDisplayFields;

	/**
	 * Constructor
	 *
	 * @param object $objectHandler IcmsPersistableHandler handling the data we want to export
	 * @param object $criteria containing the criteria of the query fetching the objects to be exported
	 * @param array $fields fields to be exported. If FALSE then all fields will be exported
	 * @param string $filename name of the file to be created
	 * @param string $filepath path where the file will be saved
	 * @param string $format format of the ouputed export. Currently only supports CSV
	 * @param array $options options of the format to be exported in
	 */
	public function __construct(&$objectHandler, $criteria=null, $fields=false, $filename=false, $filepath=false, $format='csv', $options=false) {
		$this->handler = $objectHandler;
		$this->criteria = $criteria;
		$this->fields = $fields;
		$this->filename = $filename;
		$this->format = $format;
		$this->options = $options;
		$this->notDisplayFields = array();
	}

	/**
	 * Renders the export
	 */
	public function render($filename) {

		$this->filename = $filename;

		$objects = $this->handler->getObjects($this->criteria);
		$rows = array();
		$columnsHeaders = array();
		$firstObject = true;
		foreach ( $objects as $object) {
			$row = array();
			foreach ( $object->vars as $key=>$var) {
				if ((!$this->fields || in_array($key, $this->fields)) && !in_array($key, $this->notDisplayFields)) {
					if ($this->outputMethods && (isset($this->outputMethods[$key])) && (method_exists($object, $this->outputMethods[$key]))) {
						$method = $this->outputMethods[$key];
						$row[$key] = $object->$method();
					} else {
						$row[$key] = $object->getVar($key);
					}
					if ($firstObject) {
						// then set the columnsHeaders array as well
						$columnsHeaders[$key] = $var['form_caption'];
					}
				}
			}
			$firstObject = false;
			$rows[] = $row;
			unset($row);
		}
		$data = array();
		$data['rows'] = $rows;
		$data['columnsHeaders'] = $columnsHeaders;
		$smartExportRenderer = new icms_ipf_export_Renderer($data, $this->filename, $this->filepath, $this->format, $this->options);
		$smartExportRenderer->execute();
	}

	/**
	 * Set an array contaning the alternate methods to use instead of the default getVar()
	 *
	 * @param 	array	$outputMethods array example : 'uid' => 'getUserName'...
	 */
	public function setOuptutMethods($outputMethods) {
		$this->outputMethods = $outputMethods;
	}

	/*
	 * Set an array of fields that we don't want in export
	 *
	 * @param	str|array	$fields
	 */
	public function setNotDisplayFields($fields) {
		if (!$this->notDisplayFields) {
			if (is_array($fields)) {
				$this->notDisplayFields = $fields;
			} else {
				$this->notDisplayFields = array($fields);
			}
		} else {
			if (is_array($fields)) {
				$this->notDisplayFields = array_merge($this->notDisplayFields, $fields);
			} else {
				$this->notDisplayFields[] = $fields;
			}
		}
	}
}

