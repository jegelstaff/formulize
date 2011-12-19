<?php

/**
 * ImpressCMS AUTOTASKSs Library - Crontab Support
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		core
 * @since		1.2 alpha 2
 * @author		MekDrop <mekdrop@gmail.com>
 */

require_once ICMS_ROOT_PATH.'/class/autotasks/icmsautotaskssystem.php';

/**
 * some parts are taked from CronTab class developed by cjpa@audiophile.com
 */
class IcmsAutoTasksCron
	extends IcmsAutoTasksSystem {

	private $_lines = array();
	private $_line_id = -1;

	/**
	 * Constructor
	 */
	function __construct() {
		if ($this->canRun()) {
			$this->readCronTab();
		}
	}

   /**
    * return if we need to start it
    */
   function needStart() {
   	 return false;
   }

   /**
    * check if can run
    * @return bool
    */
   function canRun() {
	   static $canRun = null;
	   if ($canRun === null) {
		   $crons = null; $return = null;
		   // checking if cron servise is active
		   exec( 'ps -ef | grep cron | grep -v grep', $crons, $return);
		   $canRun = is_array($crons) && (count($crons) > 0) && (intval($return) === 0);
		   if ($canRun) {
			   // checking if we have access to use cron
			   exec( 'crontab -l', $crons, $return);
			   $crons = @implode("\r\n", $crons);
			   $canRun = (strpos($crons, 'not allowed to use this program (crontab)') === false);
		   }
	   }   	
	   return $canRun;
   }

   /**
    * Get crontab command line
    * @return string
    */
    function getCronCommandLine() {
		if (trim($user = $this->getCronTabUser())=='') {
	    	return 'crontab';
		} else {
			return 'crontab -u '.$user;
		}
    }

   /**
    * Set Checking Interval (if not enabled enables automated tasks system
	* @param  int	$interval	interval of checking for new tasks
	* @return bool				returns true if start was succesfull
	*/
   function start(int $interval) {
	   $id = $this->getProcessId();
	   if ($id < 0) {
			$this->_line_id = count($this->_lines);
	   } else {			
			//if ($this->getInterval() == $interval) return false;
	   }
	   $arx = &$this->getIntervalArray($interval);
	   $arx['command'] = $this->getCommandLine();
	   $this->_lines[$this->_line_id] = array($arx, 4);
	   $this->writeCronTab();
	   return true;
   }

   function getInterval() {
	   return $this->getNormalValue($this->_lines[$this->_line_id][0]['minute']) +
	  	  $this->getNormalValue($this->_lines[$this->_line_id][0]['hour']) * 60 +
 		  $this->getNormalValue($this->_lines[$this->_line_id][0]['day']) * 60 * 24 +
		  $this->getNormalValue($this->_lines[$this->_line_id][0]['month']) * 60 * 24 * 30;
   }

   function &getIntervalArray($interval) {
	    $hours = $days = $months = 0;
		if ($interval>60) {
			$minutes   = $interval % 60;
			$interval -= $minutes * 60;
			if ($interval > 24) {
				$hours   = $interval % 24;
				$interval -= $hours * 60;
				if ($interval > 30) {
					$days   = $interval % 30;
					$interval -= $hours * 60;
					if ($interval > 12) {
						$months = 12;
					} else {
						$months = $interval;
					}
				} else {
					$days = $interval;
				}
			} else {
				$hours = $interval;
			}
		} else {
			$minutes = $interval;
		}
		$hours	 = $this->getCronTabValue($hours);
		$days	 = $this->getCronTabValue($days);
		$months	 = $this->getCronTabValue($months);
		$rez = array( "minute" => $this->getCronTabValue($minutes), "hour" => $this->getCronTabValue($hours), "dayofmonth" => $this->getCronTabValue($days), "month" => $this->getCronTabValue($months), 'dayofweek' => '*');
		return $rez;
   }

   function getCronTabValue($number) {
	  if ($number == 0) return '*';
	  return '*/'.$number;
   }

   function getNormalValue($crontab_number) {
	  if ($crontab_number == '*') return 0;
	  return intval(substr($crontab_number,2));
   }

   /**
	* Stops automated tasks system
	* @return bool returns true if was succesfull
	*/
   function stop() {
	    $id = $this->getProcessId();
		if ($id < 0) return false;
		unset($this->_lines[$id]);
		$this->writeCronTab();
		return true;
   }

   /**
    *  checks if core is enabled
	*
    * @return bool
	*/
	function isEnabled() {
		return ($this->getProcessId()>-1);
	}

	/**
	 * gets command executed
	 * @return string
	 */
	function getCommandLine() {
		$atasks_handler = &xoops_getmodulehandler('autotasks', 'system');
		$config_atasks = &$atasks_handler->getConfig();
		if ( ($config_atasks['autotasks_helper_path'] = trim($config_atasks['autotasks_helper_path'])) != '') {
			if (substr($config_atasks['autotasks_helper_path'], -1) != '/') {
				$config_atasks['autotasks_helper_path'] .= '/';
			}
		}
		$autotasks_helper_path = $config_atasks['autotasks_helper_path'].str_replace(array('%path%','%url%'),array(ICMS_ROOT_PATH, ICMS_URL),trim($config_atasks['autotasks_helper']))  . '/include/autotasks.php > /dev/null';
		return $autotasks_helper_path;
	}

	/**
	 * gets running process id
	 *
	 * @return int
	 */
	function getProcessId() {	
		$this->_lines = array();
		$this->readCronTab();
		$cmd = $this->getCommandLine();
		$this->_line_id = -1;
		foreach ($this->_lines as $id => $line) {
			if ($line[1] != 4) continue;
			if (isset($line[0]['command'])) {
				$line = $line[0];
			}
			if (strpos($line['command'], ICMS_ROOT_PATH . '/include/autotasks.php')!==false) {
				$this->_line_id = (int)$id;
				break;
			}
			if (strpos($line['command'], ICMS_URL . '/include/autotasks.php')!==false) {
				$this->_line_id = (int)$id;
				break;
			}
		}
		return $this->_line_id;
	}

	function getCronTabUser() {
		static $user = null;
		if ($user === null) {
			$atasks_handler = &xoops_getmodulehandler('autotasks', 'system');
			$config = &$atasks_handler->getConfig();
			$user = $config['autotasks_user'];
		}
		if (trim("$user") == '') $user = '';
		return $user;
	}

	/**
	 *	Reads cron tab file and parses to $this->_lines array
	 */
	function readCronTab() {
		exec( $this->getCronCommandLine()." -l 2>&1", $crons, $return);
		if ($return != 0) return false;

        foreach ( $crons as $line ) {
            $line = trim( $line ); // discarding all prepending spaces and tabs
            // empty lines..
            if ( !$line ) {
				$this->_lines[] = array('',0);
				continue;
			}
            // checking if this is a comment
            if ( $line[0] == "#" ) {
				$this->_lines[] = array($line,1);
				continue;
            }
            // Checking if this is an assignment
            if ( ereg( "(.*)=(.*)", $line, $assign ) ) {
				$this->_lines[] = array(array( "name" => $assign[1], "value" => $assign[2] ),2);
                continue;
            }
            // Checking if this is a special -entry. check man 5 crontab for more info
            if ( $line[0] == '@' ) {
                $this->_lines[] = array(split( "[ \t]", $line, 2 ), 3);
                continue;
            }
            // It's a regular crontab-entry
            $ct = split( "[ \t]", $line, 6 );
			$this->_lines[] = array(array( "minute" => $ct[0], "hour" => $ct[1], "dayofmonth" => $ct[2], "month" => $ct[3], "dayofweek" => $ct[4], "command" => $ct[5] ), 4);
        }

		return true;
	}

	/**
	 * Writes crontab files back to where it belongs
	 */
	function writeCronTab() {
		$filename = tempnam(ICMS_ROOT_PATH.'/cache', 'cron');
        $file = fopen( $filename, "w" );
		foreach($this->_lines as $current_line) {
            switch ( $current_line[1] ) {
                case 1: // comment
                    $line = $current_line[0];
                    break;
                case 2: //assign
                    $line = $current_line[0]['name'] . " = " . $current_line[0]['value'];
                    break;
                case 4: //comand
                    $line = implode( ' ', $current_line[0] );
                    break;
                case 3: //special
                    $line = implode( ' ', $current_line[0] );
                    break;
                case 0: //empty line
                    $line = "\n"; // an empty line in the crontab-file
                    break;
                default:
                    die('ERROR: Unknown type of line.');
            }
            fwrite( $file, $line . "\n" );
        }
        fclose( $file );

		exec( $this->getCronCommandLine()." $filename 2>&1", $returnar, $return );
        if ( $return != 0 ) {
           die("Error running crontab ($return). $filename not deleted\n");
		} else {
           unlink( $filename );
        }
	}

}

?>