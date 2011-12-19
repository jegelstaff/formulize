<?php
/**
 * ImpressCMS Customtags
 *
 * @copyright	The ImpressCMS Project http://www.impresscms.org/
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 * @package		Administration
 * @since		1.1
 * @author		marcan <marcan@impresscms.org>
 * @version		$Id: customtag.php 9409 2009-09-18 18:05:15Z skenow $
 */

if (! defined ( "ICMS_ROOT_PATH" ))
	die ( "ImpressCMS root path not defined" );

include_once ICMS_ROOT_PATH . "/kernel/icmspersistableobject.php";

define ( 'ICMS_CUSTOMTAG_TYPE_XCODES', 1 );
define ( 'ICMS_CUSTOMTAG_TYPE_HTML', 2 );
define ( 'ICMS_CUSTOMTAG_TYPE_PHP', 3 );

class SystemCustomtag extends IcmsPersistableObject {

	public $content = false;
	public $evaluated = false;

	function SystemCustomtag(&$handler) {
		$this->IcmsPersistableObject($handler);

		$this->quickInitVar ( 'customtagid', XOBJ_DTYPE_INT, true );
		$this->quickInitVar ( 'name', XOBJ_DTYPE_TXTBOX, true, _CO_ICMS_CUSTOMTAG_NAME, _CO_ICMS_CUSTOMTAG_NAME_DSC );
		$this->quickInitVar ( 'description', XOBJ_DTYPE_TXTAREA, false, _CO_ICMS_CUSTOMTAG_DESCRIPTION, _CO_ICMS_CUSTOMTAG_DESCRIPTION_DSC );
		$this->quickInitVar ( 'content', XOBJ_DTYPE_TXTAREA, true, _CO_ICMS_CUSTOMTAG_CONTENT, _CO_ICMS_CUSTOMTAG_CONTENT_DSC );
		$this->quickInitVar ( 'language', XOBJ_DTYPE_TXTBOX, true, _CO_ICMS_CUSTOMTAG_LANGUAGE, _CO_ICMS_CUSTOMTAG_LANGUAGE_DSC );
		$this->quickInitVar ( 'customtag_type', XOBJ_DTYPE_INT, true, _CO_ICMS_CUSTOMTAG_TYPE, _CO_ICMS_CUSTOMTAG_TYPE_DSC, ICMS_CUSTOMTAG_TYPE_XCODES );

		$this->initNonPersistableVar ( 'dohtml', XOBJ_DTYPE_INT, 'class', 'dohtml', '', true );
		$this->initNonPersistableVar ( 'doimage', XOBJ_DTYPE_INT, 'class', 'doimage', '', true );
		$this->initNonPersistableVar ( 'doxcode', XOBJ_DTYPE_INT, 'class', 'doxcode', '', true );
		$this->initNonPersistableVar ( 'dosmiley', XOBJ_DTYPE_INT, 'class', 'dosmiley', '', true );

		$this->setControl ( 'content', array ('name' => 'textarea', 'form_editor' => 'textarea', 'form_rows' => 25 ) );
		$this->setControl ( 'language', array ('name' => 'language', 'all' => true ) );
		$this->setControl ( 'customtag_type', array ('itemHandler' => 'customtag', 'method' => 'getCustomtag_types', 'module' => 'system' ) );
	}

	function getVar($key, $format = 's') {
		if ($format == 's' && in_array ( $key, array ( ) )) {
			return call_user_func ( array ($this, $key ) );
		}
		return parent::getVar ( $key, $format );
	}

	function render() {
		$myts = MyTextSanitizer::getInstance ();
		if (! $this->content) {
			switch ( $this->getVar ( 'customtag_type' )) {
				case ICMS_CUSTOMTAG_TYPE_XCODES :
					$ret = $this->getVar ( 'content', 'N' );
					$ret = $myts->displayTarea ( $ret, 1, 1, 1, 1, 1, 'system-basic' );
				break;
				case ICMS_CUSTOMTAG_TYPE_HTML :
					$ret = $this->getVar ( 'content', 'N' );
					$ret = $myts->displayTarea ( $ret, 1, 1, 1, 1, 0, 'system-basic' );
				break;

				case ICMS_CUSTOMTAG_TYPE_PHP :
					$ret = $this->renderWithPhp ();
				break;
			}
			$this->content = $ret;
		}
		return $this->content;
	}

	function renderWithPhp() {
		if (! $this->content && ! $this->evaluated) {
			$ret = $this->getVar ( 'content', 'N' );

			// check for PHP if we are not on admin side
			if (! defined ( 'XOOPS_CPFUNC_LOADED' ) && $this->getVar ( 'customtag_type' ) == ICMS_CUSTOMTAG_TYPE_PHP) {
				// we have PHP code, let's evaluate
				ob_start ();
				echo eval ( $ret );
				$ret = ob_get_contents ();
				ob_end_clean ();
				$this->evaluated = true;
			}
			$this->content = $ret;
		}
		return $this->content;
	}

	function getXoopsCode() {
		$ret = '[customtag]' . $this->getVar ( 'name', 'n' ) . '[/customtag]';
		return $ret;
	}

	function getCloneLink() {
		$ret = '<a href="' . ICMS_URL . '/modules/system/admin.php?fct=customtag&amp;op=clone&amp;customtagid=' . $this->id () . '"><img src="' . ICMS_IMAGES_SET_URL . '/actions/editcopy.png" style="vertical-align: middle;" alt="' . _CO_ICMS_CUSTOMTAG_CLONE . '" title="' . _CO_ICMS_CUSTOMTAG_CLONE . '" /></a>';
		return $ret;
	}

	function emptyString($var) {
		return (strlen ( $var ) > 0);
	}

	function generateTag() {
		$title = rawurlencode ( strtolower ( $this->getVar ( 'description', 'e' ) ) );
		$title = xoops_substr ( $title, 0, 10, '' );
		// Transformation des ponctuations
		//				 Tab	 Space	  !		"		#		%		&		'		(		)		,		/		:		;		<		=		>		?		@		[		\		]		^		{		|		}		~	   .
		$pattern = array ("/%09/", "/%20/", "/%21/", "/%22/", "/%23/", "/%25/", "/%26/", "/%27/", "/%28/", "/%29/", "/%2C/", "/%2F/", "/%3A/", "/%3B/", "/%3C/", "/%3D/", "/%3E/", "/%3F/", "/%40/", "/%5B/", "/%5C/", "/%5D/", "/%5E/", "/%7B/", "/%7C/", "/%7D/", "/%7E/", "/\./" );
		$rep_pat = array ("-", "-", "-", "-", "-", "-100", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-at-", "-", "-", "-", "-", "-", "-", "-", "-", "-" );
		$title = preg_replace ( $pattern, $rep_pat, $title );

		// Transformation des caract�res accentu�s
		//				  �		�		�		�		�		�		�		�		�		�		�		�		�		�		�		�		$pattern = array ("/%B0/", "/%E8/", "/%E9/", "/%EA/", "/%EB/", "/%E7/", "/%E0/", "/%E2/", "/%E4/", "/%EE/", "/%EF/", "/%F9/", "/%FC/", "/%FB/", "/%F4/", "/%F6/" );
		$rep_pat = array ("-", "e", "e", "e", "e", "c", "a", "a", "a", "i", "i", "u", "u", "u", "o", "o" );
		$title = preg_replace ( $pattern, $rep_pat, $title );

		$tableau = explode ( "-", $title ); // Transforme la chaine de caract�res en tableau
		$tableau = array_filter ( $tableau, array ($this, "emptyString" ) ); // Supprime les chaines vides du tableau
		$title = implode ( "-", $tableau ); // Transforme un tableau en chaine de caract�res s�par� par un tiret


		$title = $title . time ();
		$title = md5 ( $title );
		return $title;
	}

	function getCustomtagName() {
		$ret = $this->getVar ( 'name' );
		return $ret;
	}
}

class SystemCustomtagHandler extends IcmsPersistableObjectHandler {

	public $objects = false;

	function SystemCustomtagHandler($db) {
		$this->IcmsPersistableObjectHandler ( $db, 'customtag', 'customtagid', 'name', 'description', 'system' );
		$this->addPermission ( 'view', _CO_ICMS_CUSTOMTAG_PERMISSION_VIEW, _CO_ICMS_CUSTOMTAG_PERMISSION_VIEW_DSC );
	}

	function getCustomtagsByName() {
		if (! $this->objects) {
			global $xoopsConfig;

			$ret = array ( );

			$criteria = new CriteriaCompo ( );

			$criteria_language = new CriteriaCompo ( );
			$criteria_language->add ( new Criteria ( 'language', $xoopsConfig ['language'] ) );
			$criteria_language->add ( new Criteria ( 'language', 'all' ), 'OR' );
			$criteria->add ( $criteria_language );

			$icms_permissions_handler = new IcmsPersistablePermissionHandler ( $this );
			$granted_ids = $icms_permissions_handler->getGrantedItems ( 'view' );

			if ($granted_ids && count ( $granted_ids ) > 0) {
				$criteria->add ( new Criteria ( 'customtagid', '(' . implode ( ', ', $granted_ids ) . ')', 'IN' ) );
				$customtagsObj = $this->getObjects ( $criteria, true );
				foreach ( $customtagsObj as $customtagObj ) {
					$ret [$customtagObj->getVar ( 'name' )] = $customtagObj;
				}
			}
			$this->objects = $ret;
		}
		return $this->objects;
	}

	function getCustomtag_types() {
		$ret = array (ICMS_CUSTOMTAG_TYPE_XCODES => 'BB-Codes', ICMS_CUSTOMTAG_TYPE_HTML => 'HTML', ICMS_CUSTOMTAG_TYPE_PHP => 'PHP' );
		return $ret;
	}
}

?>