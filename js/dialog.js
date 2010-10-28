var FoxyPressDialog = {
	init : function() {
	},

	insert : function() {
		var querystring="";
		var inputCount=0;
		elementsForms = document.forms[0];
		
		for (var adam = 0; adam < elementsForms.length; adam++)
		{
			if(elementsForms[adam].type=="text"){
				inputCount = inputCount + 1;
			}
		}
		for (var intCounter = 0; intCounter < elementsForms.length; intCounter++)
		{
			if(elementsForms[intCounter].type=="text"){
				//inputCount= inputCount + 1;
				if(elementsForms[intCounter].name.substring(0,6)=='custom'){
					querystring += "h:" + elementsForms[intCounter].value + "='" + elementsForms[intCounter+1].value + "' ";

				}else if(elementsForms[intCounter].name.substring(0,6)!='cvalue'){
					querystring += elementsForms[intCounter].name + "='" + elementsForms[intCounter].value + "' ";
				}	
			}
		}

		var embedCode = "[foxypress " + querystring + "]Place your image or text here[/foxypress]";
		tinyMCEPopup.editor.execCommand('mceInsertRawHTML', false, embedCode);
		tinyMCEPopup.close();
	}
};

tinyMCEPopup.onInit.add(FoxyPressDialog.init, FoxyPressDialog);