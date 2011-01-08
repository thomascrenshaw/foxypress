(function() {
	tinymce.create('tinymce.plugins.FoxyPress', {

		init : function(ed, url) {
			ed.addCommand('mceFoxyPress', function() {
				ed.windowManager.open({
					file : url + '/dialog.htm',
					width : 445 + parseInt(ed.getLang('foxypress.delta_width', 0)),
					height : 635 + parseInt(ed.getLang('foxypress.delta_height', 0)),
					inline : 1
				}, {
					plugin_url : url, // Plugin absolute URL
					some_custom_arg : 'custom arg' // Custom argument
				});
				
			});

			ed.addButton('foxypress', {
				title : 'insert foxycart product',
				cmd : 'mceFoxyPress',
				image : url + '/img/wysiwyg.jpg'
			});


			ed.onNodeChange.add(function(ed, cm, n) {
				cm.setActive('foxypress', n.nodeName == 'IMG');
			});
		},


		createControl : function(n, cm) {
			return null;
		},

		
		getInfo : function() {
			return {
				longname : 'FoxyPress Plugin',
				author   :  'WebMovement, LLC',
				authorurl : 'http://www.webmovementllc.com',
				infourl : 'http://www.webmovementllc.com/foxypress/forum',
				version : "1.0"
			};
		}
	});

	// Register plugin
	tinymce.PluginManager.add('foxypress', tinymce.plugins.FoxyPress);
})();