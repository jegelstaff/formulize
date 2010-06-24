/**
 * $Id: editor_plugin_src.js 520 2008-01-07 16:30:32Z spocke $
 *
 * @author Moxiecode
 * @copyright Copyright © 2004-2008, Moxiecode Systems AB, All rights reserved.
 */

(function() {
	// Load plugin specific language pack
	tinymce.PluginManager.requireLangPack('xoopsimagemanager');
	
	tinymce.create('tinymce.plugins.XoopsimagemanagerPlugin', {
		init : function(ed, url) {
			// Register commands
			ed.addCommand('mceXoopsimagemanager', function() {
				var e = ed.selection.getNode();

				// Internal image object like a flash placeholder
				if (ed.dom.getAttrib(e, 'class').indexOf('mceItem') != -1)
					return;

				ed.windowManager.open({
					file : url + '/xoopsimagemanager.php',
					width : 480 + parseInt(ed.getLang('xoopsimagemanager.delta_width', 0)),
					height : 385 + parseInt(ed.getLang('xoopsimagemanager.delta_height', 0)),
					inline : 1
				}, {
					plugin_url : url
				});
			});

			// Register buttons
			ed.addButton('xoopsimagemanager', {
				title : 'xoopsimagemanager.desc',
				cmd : 'mceXoopsimagemanager',
				image : url + '/img/xoopsimagemanager.gif'
			});
			
			// Add a node change handler, selects the button in the UI when a image is selected
			ed.onNodeChange.add(function(ed, cm, n) {
				cm.setActive('xoopsimagemanager', n.nodeName == 'IMG');
			});
		},

		getInfo : function() {
			return {
				longname : 'ImpressCMS Advanced Imagemanager',
				author : 'Moxiecode Systems AB & rotalucio & TheRplima',
				authorurl : 'http://tinymce.moxiecode.com',
				infourl : 'http://wiki.moxiecode.com/index.php/TinyMCE:Plugins/advimage',
				version : tinymce.majorVersion + "." + tinymce.minorVersion
			};
		}
	});

	// Register plugin
	tinymce.PluginManager.add('xoopsimagemanager', tinymce.plugins.XoopsimagemanagerPlugin);
})();
