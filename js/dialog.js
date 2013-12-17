var FoxyPressDialog = {
	init : function() {
		//nothing
	},
	InsertInventoryItem : function(item_id) {
		var embedCode = "[foxypress id='" + item_id + "' mode='single']FoxyPress[/foxypress]";
		tinyMCEPopup.editor.execCommand('mceInsertRawHTML', false, embedCode);
		tinyMCEPopup.close();		
	},
	InsertCategoryListing : function(category_id, showaddtocart, itemsperpage, itemsperrow, showmoredetail) {
		var embedCode = "[foxypress categoryid='" + category_id + "' addtocart='" + showaddtocart + "' items='" + itemsperpage + "' cols='" + itemsperrow + "' mode='list' showmoredetail='" + showmoredetail + "']FoxyPress[/foxypress]";
		tinyMCEPopup.editor.execCommand('mceInsertRawHTML', false, embedCode);
		tinyMCEPopup.close();	
	},
	InsertRelatedItems : function(item_id) {
		var embedCode = "[foxypress productid='" + item_id + "' addtocart='1' cols='1' showmoredetail='1' mode='related']FoxyPress[/foxypress]";
		tinyMCEPopup.editor.execCommand('mceInsertRawHTML', false, embedCode);
		tinyMCEPopup.close();		
	},
	/*InsertInventoryDetail : function(item_id) {
		var embedCode = "[foxypress mode='detail']FoxyPress[/foxypress]";
		tinyMCEPopup.editor.execCommand('mceInsertRawHTML', false, embedCode);
		tinyMCEPopup.close();		
	},*/
};

tinyMCEPopup.onInit.add(FoxyPressDialog.init, FoxyPressDialog);


/*
//old garbage
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
*/