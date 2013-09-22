<?php
/**
 * Module RSS Feed Class
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @category	ICMS
 * @package	    Feeds
 * @subpackage	RSS
 * @since		1.1
 * @author		Ignacio Segura, "Nachenko"
 * @version		$Id: Rss.php 12097 2012-10-28 21:01:59Z skenow $
 */

defined('ICMS_ROOT_PATH') or exit();

/**
 * Generates the data necessary for an RSS feed and assigns it to a smarty template
 *
 * @category	ICMS
 * @package		Feeds
 * @subpackage	RSS
 *
 */
class icms_feeds_Rss {

	public $title;
	public $url;
	public $description;
	public $language;
	public $charset;
	public $category;
	public $pubDate;
	public $webMaster;
	public $generator;
	public $copyright;
	public $lastbuild;
	public $channelEditor;
	public $width;
	public $height;
	public $ttl;
	public $image = array();

	/**
	 * Constructor
	 */
	public function __construct() {
		global $icmsConfig;
		$this->title = htmlspecialchars($icmsConfig['sitename'], ENT_QUOTES, _CHARSET);
		$this->url = ICMS_URL;
		$this->description = htmlspecialchars($icmsConfig['slogan'], ENT_QUOTES, _CHARSET);
		$this->language = _LANGCODE;
		$this->charset = _CHARSET;
		$this->pubDate = date('r', time());
		$this->lastbuild = date('r', time());
		$this->webMaster = $icmsConfig['adminmail'];
		$this->channelEditor = $icmsConfig['adminmail'];
		$this->generator = ICMS_VERSION_NAME;
		$this->copyright = _COPYRIGHT . ' ' . formatTimestamp(time(), 'Y')
			. ' ' . htmlspecialchars($icmsConfig['sitename'], ENT_QUOTES, _CHARSET);
		$this->width  = 144;
		$this->height = 50;
		$this->ttl    = 60;
		$this->image = array(
			'title' => $this->title,
			'url' => ICMS_URL . '/images/logo.gif',
		);
		$this->feeds = array();
	}

	/**
	 * Render the feed and display it directly
	 */
	public function render() {
		icms::$logger->disableLogger();

		//header ('Content-Type:text/xml; charset='._CHARSET);
		$xoopsOption['template_main'] = "db:system_rss.html";
		$tpl = new icms_view_Tpl();

		$tpl->assign('channel_title', $this->title);
		$tpl->assign('channel_link', $this->url);
		$tpl->assign('channel_desc', $this->description);
		$tpl->assign('channel_webmaster', $this->webMaster);
		$tpl->assign('channel_editor', $this->channelEditor);
		$tpl->assign('channel_category', $this->category);
		$tpl->assign('channel_generator', $this->generator);
		$tpl->assign('channel_language', $this->language);
		$tpl->assign('channel_lastbuild', $this->lastbuild);
		$tpl->assign('channel_copyright', $this->copyright);
		$tpl->assign('channel_width', $this->width);
		$tpl->assign('channel_height', $this->height);
		$tpl->assign('channel_ttl', $this->ttl);
		$tpl->assign('image_url', $this->image['url']);
		foreach ($this->feeds as $feed) {
			$tpl->append('items', $feed);
		}
		$tpl->display('db:system_rss.html');
	}
}

