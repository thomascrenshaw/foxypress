<?php ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<title>FoxyPress Plugin</title>
<script type="text/javascript" src="../../../wp-includes/js/tinymce/tiny_mce_popup.js"></script>
<script type="text/javascript" src="js/dialog.js"></script>
<link href="css/style.css" rel="stylesheet" type="text/css" />
<script type="text/javascript">

var cfCounter=0;

function validate(){
   var formValid = 0;
	 var element = document.getElementById( 'foxypress-insert' ).elements;

	 for( var i = 0; i < element.length; i++ ) {
	   var elem = element[i]; // set current element to shorter variable, for simplicity sake.
	   
	   var goodValue = "<img src='img/check.png' />";
	   var badValue = "<img src = 'img/x.png' />";
	   
	   switch ( elem.name ){   
      case 'price' : 
        if ( elem.value == '' ) {
         document.getElementById('valid-' + elem.name).innerHTML= badValue;          
        } 
        else{
          document.getElementById('valid-' + elem.name).innerHTML= goodValue;
          formValid += 1;
        } 
      break;
      case 'name' : 
        if ( elem.value == '' ) {
         document.getElementById('valid-' + elem.name).innerHTML= badValue;
          formValid = false;
        } 
        else{
          document.getElementById('valid-' + elem.name).innerHTML= goodValue;
          formValid += 1;
        } 
      break;
     }     
	 }	 
	 return formValid;
}
  	 
function createInput(){
		var controls="<tr><td height='22'><input name='custom" + cfCounter + "' type='text' value='Field Name' class='text' size='25' value='' style='margin-left:24px;' /></td><td height='22'><input name='cvalue" + cfCounter + "' type='text' value='Value' class='text' size='25' value='' onblur='validateValue(this)' style='margin-left:24px;' /><div id='valid-custom" + cfCounter + "'></div></td></tr>";

		document.getElementById("customfieldcontainer").innerHTML+=controls;
		cfCounter = cfCounter + 1;
}

function checkForm(){
	  var formIsValid = validate();
		if( formIsValid != 2 ){
			alert("Please fill in the required fields.");
		}
		else{
			FoxyPressDialog.insert();
		}
}
</script>
</head>
<body>
	 <div style="background-image:url(img/top.jpg);height:19px;"></div>
    <div style="margin-left:auto;margin-right:auto;width:420px; text-align:left;">
      <img src="img/foxycart_logo.png" /><br />
      <form onsubmit="FoxyPressDialog.insert();return false;" id="foxypress-insert" action="#">
    <?php
    // Item information values gathered from inventory
    $inventory_code = $_POST['inventory_code'];
    $inventory_name = $_POST['inventory_name'];
    $inventory_image = $_POST['inventory_image'];
    $inventory_description = $_POST['inventory_description'];
    $date_added = $_POST['date_added'];
    $inventory_price = $_POST['inventory_price'];
    $inventory_reserved = $_POST['inventory_reserved'];
    $category_name = $_POST['category_name'];
    $inventory_quantity = $_POST['inventory_quantity'];
    $inventory_weight = $_POST['inventory_weight'];
    ?>
        <table width="420">
          <tr>
            <td style="font-size: 12pt;" colspan="2">
              Standard Attributes
              </td>
          </tr>
          <tr>            
           <td style="font-size: 10pt;padding-left:24px;">
              <a href="view-inventory.php">Select Item from Inventory</a>          
            </td>
           </tr>           
          <tr>
            <td style="font-size: 10pt;padding-left:24px;">
              Product Name: *
            </td>
            <td width="205" height="22" align="left">
              <input id="name" name="name" type="text" class="text" size="30" value="<?php echo $inventory_name; ?>" "/>
              <div style="display:inline;" id="valid-name"></div>
            </td>
          </tr>          
          <tr>
            <td style="font-size: 10pt;padding-left:24px;">
            Product Price: *
            </td>
            <td height="22">
            <input id="price" name="price" type="text" class="text" size="30" value="<?php echo $inventory_price; ?>" <?php /*onblur="validateValue(this)" onLoad="validateValue(this) */ ?>" />
            <div style="display:inline;" id="valid-price"></div>
            </td>
          </tr>
          <tr>
            <td style="font-size: 10pt;padding-left:24px;">
              Product Image:
            </td>
            <td width="205" height="22" align="left">
              <input id="image" name="image" type="text" class="text" size="30" value="<?php echo $inventory_image; ?>" />
              <div style="display:inline;" id="valid-name"></div>
            </td>
          </tr>
          <tr>
            <td style="font-size: 10pt;padding-left:24px;">
            Product Code:
            </td>
            <td height="22">
            <input id="code" name="code" type="text" class="text" size="30" value="<?php echo $inventory_code; ?>" />
            <div style="display:inline;" id="valid-code"></div>
            </td>
          </tr>
          <tr>
            <td style="font-size: 10pt;padding-left:24px;">
            Product Quantity:
            </td>
            <td height="22">
            <input id="quantity" name="quantity" type="text" class="text" size="30" value="<?php echo $inventory_quantity; ?>" />
            <div style="display:inline;" id="valid-quantity"></div>
            </td>
          </tr>
          <tr>
            <td style="font-size: 10pt;padding-left:24px;">
            Product Category:
            </td>
            <td height="22">
            <input id="category" name="category" type="text" class="text" size="30" value="<?php echo $category_name; ?>" />
            <div style="display:inline;" id="valid-category"></div>
            </td>
          </tr>
          <tr>
            <td style="font-size: 10pt;padding-left:24px;">
            Product Weight:
            </td>
            <td height="22">
            <input id="weight" name="weight" type="text" class="text" size="30" value="<?php echo $inventory_weight; ?>" />
            <div style="display:inline;" id="valid-weight"></div>
            </td>
          </tr>
          <tr>
            <td style="font-size: 10pt;padding-left:24px;">
            Product ShipTo:
            </td>
            <td height="22">
            <input id="shipto" name="shipto" type="text" class="text" size="30" value="" />
            <div style="display:inline;" id="valid-shipto"></div>
            </td>
          </tr>
          <tr>
            <td style="font-size: 8pt;padding-left:24px;" colspan="2">
              * indicates required field
            </td>
          </tr>
          <tr>
            <td height="26" colspan="2">
              <span style="cursor:default;font-size: 10pt;padding-left:24px;color:red;" onclick="createInput()">Add Custom Field</span>
            </td>
          </tr>
        </table>
        <table id="customfieldcontainer" width="420">
        </table>
  			<table id="customfieldcontainer" width="420">
  			</table>
  			<table width="420">
  			  <tr>
  				<td colspan="2" align="right" style="padding-right:36px;" height="26">
  					<input type="button" id="cancel" name="cancel" value="{#cancel}" onclick="tinyMCEPopup.close();"  />
  					<input type="button" id="insert" name="insert" value="{#insert}" onclick="checkForm();"  /><br />
  				</td>
  			  </tr>
  			  <tr>
  				<td colspan="2">
  					<br />
  				</td>
  				</tr>
  			</table>
		</form>
		<img src="img/footer.png" /><br />
		<p style="text-align:center;">Please visit our forum for info and help for all your needs.
		<br />
		<a href="http://www.webmovementllc.com/foxypress/forum" target="_blank">http://www.webmovementllc.com/foxypress/forum</a>
		</p>
	</div>
	<div style="background-image:url(img/bottom.jpg);height:19px;"></div>
</body>
</html>
