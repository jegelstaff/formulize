/**
 * $Id: editor_plugin_src.js 520 2008-01-07 16:30:32Z spocke $
 *
 * @author Moxiecode
 * @copyright Copyright © 2004-2008, Moxiecode Systems AB, All rights reserved.
 */


// created 2005-1-12 by Martin Sadera (sadera@e-d-a.info)
// ported to Xoops CMS by ralf57
// updated to TinyMCE v3.0.1 / 2008-02-29 / by luciorota


(function() {
	// Load plugin specific language pack
	tinymce.PluginManager.requireLangPack('xoopsquotecode');

	tinymce.create('tinymce.plugins.XoopsquotecodePlugin', {
		init : function(ed, url) {
			// Register commands
			ed.addCommand('mceXoopsquote', function() {
				ed.windowManager.open({
					file : url + '/xoopsquote.htm',
					width : 460 + parseInt(ed.getLang('xoopsquotecode.delta_width', 0)),
					height : 300 + parseInt(ed.getLang('xoopsquote.delta_height', 0)),
					inline : 1
				}, {
					plugin_url : url
				});
			});
			ed.addCommand('mceXoopscode', function() {
				ed.windowManager.open({
					file : url + '/xoopscode.htm',
					width : 460 + parseInt(ed.getLang('xoopscode.delta_width', 0)),
					height : 300 + parseInt(ed.getLang('xoopscode.delta_height', 0)),
					inline : 1
				}, {
					plugin_url : url
				});
			});

			ed.addCommand('mceIcmshide', function() {
				ed.windowManager.open({
					file : url + '/icmshide.htm',
					width : 460 + parseInt(ed.getLang('xoopsquotecode.delta_width', 0)),
					height : 300 + parseInt(ed.getLang('xoopsquote.delta_height', 0)),
					inline : 1
				}, {
					plugin_url : url
				});
			});
			// Register buttons
			ed.addButton('xoopsquote', {
				title : 'xoopsquotecode.quote_desc',
				image : url + '/img/xoopsquote.gif',
				cmd : 'mceXoopsquote'
				});
			ed.addButton('xoopscode', {
				title : 'xoopsquotecode.code_desc',
				image : url + '/img/xoopscode.gif',
				cmd : 'mceXoopscode'
				});

			ed.addButton('icmshide', {
				title : 'xoopsquotecode.hide_desc',
				image : url + '/img/icmshide.gif',
				cmd : 'mceIcmshide'
				});
		},
		getInfo : function() {
			return {
				longname : 'XoopsquoteCode',
				author : '',
				authorurl : '',
				infourl : '',
				version : tinymce.majorVersion + "." + tinymce.minorVersion
			};
		}
	});

	// Register plugin
	tinymce.PluginManager.add('xoopsquotecode', tinymce.plugins.XoopsquotecodePlugin);
})();
