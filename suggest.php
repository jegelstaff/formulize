<?php
/**
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license	http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package	core
 * @since		2009
 * @author	Wiliam Hall (aka Mr. Theme) <mrtheme@impresscms.org>
 * @version	$Id: suggest.php 21609 2011-05-15 14:05:17Z david-sf $
 **/
include 'mainfile.php';
icms::$logger->disableLogger();
if (isset($_POST['queryString'])) {
	$queryString = icms::$xoopsDB->escape($_POST['queryString']);

	// Is the string length greater than 2?
	if (strlen($queryString) > 2) {
		$sql = "SELECT * FROM ".icms::$xoopsDB->prefix("autosearch_list")." s INNER JOIN "
			. icms::$xoopsDB->prefix("autosearch_cat") . " c ON s.cat_id = c.cid WHERE name LIKE '%"
			. $queryString . "%' ORDER BY cat_id LIMIT 8";
		$query = icms::$xoopsDB->query($sql);
		$num_results = mysql_num_rows($query);

		echo "<ul id='searchresults'>";
		if ($query) {
			if ($num_results < 1) {
				echo "<li><a href='javascript:void(0);'>
					<img src='" . ICMS_IMAGES_SET_URL . "/actions/exit.png' alt='no results found' />
					<span class='searchheading'>Sorry</span>
					<span class='searchdesc'>No results were found that matched your query. Please try again.</span></a></li>";
			} else {
				$catid = 0;
				while ($result = icms::$xoopsDB->fetchArray($query)) {
					echo '<li><a href="' . ICMS_URL . $result['url'] . '">';
					echo '<img src="'.ICMS_URL.$result['img'].'" alt="" />';

					$name = $result['name'];
					if (strlen($name) > 35) {
						$name = substr($name, 0, 35) . "...";
					}
					echo '<span class="searchheading">'.$name.'</span>';

					$description = $result['desc'];
					if (strlen($description) > 80) {
						$description = substr($description, 0, 80) . "...";
					}
					echo '<span class="searchdesc">'.$description.'</span></a></li>';
				}
			}
			echo '<li><span class="seperator"><a href="http://www.impresscms.org" rel="external">ImpressCMS Project</a></span><br /></li>';
		} else {
			echo '<li>ERROR: There was a problem with the query.</li>';
		}
		echo "</ul>";
	} else {
		// Dont do anything.
	} // There is a queryString.
} else {
	echo 'There should be no direct access to this script!';
}