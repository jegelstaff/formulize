<?php
/**
 * Extended User Profile
 *
 *
 *
 * @copyright       The ImpressCMS Project http://www.impresscms.org/
 * @license         LICENSE.txt
 * @license			GNU General Public License (GPL) http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @package         modules
 * @since           1.2
 * @author          Jan Pedersen
 * @author          Marcello Brandao <marcello.brandao@gmail.com>
 * @author	   		Sina Asghari (aka stranger) <pesian_stranger@users.sourceforge.net>
 * @version         $Id$
 */

/**
* Protection against inclusion outside the site 
*/
if (!defined("ICMS_ROOT_PATH")) {
die("XOOPS root path not defined");
}

	/**
	* Return search results and show images on userinfo page
	* 
	* @param array $queryarray the terms to look
	* @param text $andor the conector between the terms to be looked
	* @param int $limit The number of maximum results
	* @param int $offset from wich register start
	* @param int $userid from which user to look
	* @return array $ret with all results
	*/
function profile_search($queryarray, $andor, $limit, $offset, $userid)
{
	global $xoopsDB, $icmsModule, $icmsModuleConfig;
	//getting the url to the uploads directory
	$path_uploadimages = ICMS_UPLOAD_URL;

	$ret = array();
	$sql = "SELECT cod_img,	title, 	data_creation, 	uid_owner, url FROM ".$xoopsDB->prefix("profile_images")." WHERE ";
	if ( $userid != 0 ) {
	$sql .= "(uid_owner =".intval($userid).")";
	
	}
	
	// because count() returns 1 even if a supplied variable
	// is not an array, we must check if $querryarray is really an array
	$count = count($queryarray);
	if ( $count > 0 && is_array($queryarray) ) {
		$sql .= " ((title LIKE '%".$queryarray[0]."%')";
		for ( $i = 1; $i < $count; $i++ ) {
			$sql .= " $andor ";
			$sql .= "(title LIKE '%".$queryarray[$i]."%')";
		}
		$sql .= ") ";
	}
	$sql .= "ORDER BY cod_img DESC";
	//echo $sql;
	//printr($icmsModules);
	$result = $xoopsDB->query($sql,$limit,$offset);
	$i = 0;
	$stringofimage = 'images/search.png" />';
 	while ( $myrow = $xoopsDB->fetchArray($result) ) {
		if ( $userid != 0 ) {
		if ($limit>5){
		$ret[$i]['image'] = "images/search.png' /><a href='".ICMS_URL."/modules/".basename(  dirname(  dirname( __FILE__ ) ) )."/album.php?uid=".$myrow['uid_owner']."'><img src='".$path_uploadimages."/thumb_".$myrow['url']."' /></a><br />"."<img src=".ICMS_URL."/modules/".basename(  dirname(  dirname( __FILE__ ) ) )."/images/search.png" ;		
		$ret[$i]['link'] = "album.php?uid=".$myrow['uid_owner'];
		$ret[$i]['title'] = $myrow['title'];
		//$ret[$i]['time'] = $myrow['data_creation'];
		$ret[$i]['uid'] = $myrow['uid_owner'];	
			
			
			}else{
		$stringofimage .= '<a href="'.ICMS_URL."/modules/".basename(  dirname(  dirname( __FILE__ ) ) )."/album.php?uid=".$myrow['uid_owner'].'" title="'.$myrow['title'].'"><img src="'.$path_uploadimages.'/thumb_'.$myrow['url'].'" /></a>&nbsp;' ;
		
		}
		} else {
		$ret[$i]['image'] = "images/search.png' /><a href='".ICMS_URL."/modules/".basename(  dirname(  dirname( __FILE__ ) ) )."/album.php?uid=".$myrow['uid_owner']."'><img src='".$path_uploadimages."/thumb_".$myrow['url']."' /></a><br />"."<img src='".ICMS_URL."/modules/".basename(  dirname(  dirname( __FILE__ ) ) )."/images/search.png" ;	
		$ret[$i]['link'] = "album.php?uid=".$myrow['uid_owner'];
		$ret[$i]['title'] = $myrow['title'];
		//$ret[$i]['time'] = $myrow['data_creation'];
		$ret[$i]['uid'] = $myrow['uid_owner'];
		}
		
		
		$i++;
	}
	if ( $userid != 0 && $i>0) {
		if ($limit<6){
			$ret = array();
	
		$ret[0]['title'] = "See its album";
		$ret[0]['time'] = time();
		$ret[0]['uid'] = $userid;
		$ret[0]['link'] = "album.php?uid=".$userid;
		$stringofimage .= '<img src="'.ICMS_URL.'/modules/'.basename(  dirname(  dirname( __FILE__ ) ) ).'/images/search.png';
		$ret[0]['image'] = $stringofimage;
		}}
	return $ret;
}
?>