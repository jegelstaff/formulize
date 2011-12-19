/**
 * $Id: xoopsemotions_plugin.js 100 2008-03-01 16:00:00Z luciorota $
 *
 * @author luciorota (lucio.rota@gmail.com)
 */

(function() {
	// Load plugin specific language pack
	tinymce.PluginManager.requireLangPack('xoopsemotions');

	tinymce.create('tinymce.plugins.XoopsemotionsPlugin', {
		/**
		 * Initializes the plugin, this will be executed after the plugin has been created.
		 * This call is done before the editor instance has finished it's initialization so use the onInit event
		 * of the editor instance to intercept that event.
		 *
		 * @param {tinymce.Editor} ed Editor instance that the plugin is initialized in.
		 * @param {string} url Absolute URL to where the plugin is located.
		 */
		init : function(ed, url) {
			// Register the command so that it can be invoked by using tinyMCE.activeEditor.execCommand('mceXoopsemotions');
			ed.addCommand('mceXoopsemotions', function() {
				ed.windowManager.open({
					file : url + '/xoopsemotions.php',
					width : 400 + parseInt(ed.getLang('xoopsemotions.delta_width', 0)),
					height : 320 + parseInt(ed.getLang('xoopsemotions.delta_height', 0)),
					inline : 1,
					scrollbars : 1
				}, {
					plugin_url : url // Plugin absolute URL
				});
			});

			// Register xoopsemotions button
			ed.addButton('xoopsemotions', {
				title : 'xoopsemotions.desc',
				cmd : 'mceXoopsemotions',
				image : url + '/img/xoopsemotions.gif'
			});
		},
		/**
		 * Returns information about the plugin as a name/value array.
		 * The current keys are longname, author, authorurl, infourl and version.
		 *
		 * @return {Object} Name/value array containing information about the plugin.
		 */
		getInfo : function() {
			return {
				longname : 'ImpressCMS Emotions/Smiles plugin',
				author : 'luciorota (lucio.rota@gmail.com)',
				authorurl : '',
				infourl : '',
				version : "1.0"
			};
		}
	});

	// Register plugin
	tinymce.PluginManager.add('xoopsemotions', tinymce.plugins.XoopsemotionsPlugin);
})();
