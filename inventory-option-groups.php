<?
function inventory_option_groups_page_load() { 
	global $wpdb;
?>
	<style type="text/css">
		 	div.foxy_item_pagination a {
				padding: 2px 5px 2px 5px;
				margin: 2px;
				border: 1px solid #AAAADD;	
				text-decoration: none; /* no underline */
				/*color: #000099;*/
			}
			div.foxy_item_pagination a:hover, div.foxy_item_pagination a:active {
				border: 1px solid #000099;
				color: #000;
			}
			div.foxy_item_pagination span.current {
				padding: 2px 5px 2px 5px;
				margin: 2px;
				border: 1px solid #666666;	
				font-weight: bold;
				/*background-color: #000099;*/
				color: #666666;
			}
			div.foxy_item_pagination span.disabled {
				padding: 2px 5px 2px 5px;
				margin: 2px;
				border: 1px solid #EEE;
				color: #ccc;
			}
		 </style>
    <div class="wrap">
        <h2> Manage Option Groups</h2>
        <div>
            <i>
                To create a list of options you must first create an option group. Example: "Color" or "Size". 
                These option groups can be used for options on any item.
            </i>
        </div>
                
        <form name="foxy_add_new_option_group" id="foxy_add_new_option_group" class="wrap" method="post">
            <div id="linkadvanceddiv" class="postbox">
                <div style="float: left; width: 98%; clear: both;" class="inside">
                    <table cellspacing="5" cellpadding="5">
                        <tr>
                            <td><legend>New Option Group: </legend></td>
                            <td><input type="text" name="option_group_name" id="option_group_name" class="input" size="30" maxlength="30" value="" /></td>
                            <td><input type="submit" name="option_group_save" id="option_group_save" class="button bold" value="<?php _e('Save','inventory'); ?> &raquo;" /></td>
                        </tr>
                    </table>
                </div>
                <div style="clear:both; height:1px;">&nbsp;</div>
            </div>
        </form>

         <table class="widefat page fixed" width="50%" cellpadding="3" cellspacing="3">	
            <thead>
                <tr>
                    <th class="manage-column" scope="col">Option Group Name</th>
                    <th class="manage-column" scope="col">&nbsp;</th>
                </tr>
            </thead>
            <?				
				//set up paging				
				$limit = 10;
				$targetpage = foxypress_GetFullURL();
				$targetpage = foxypress_RemoveQSValue($targetpage, "fp_pn");
				$pos = strrpos($targetpage, "?");
				if ($pos === false) { 
					$targetpage .= "?";
				}	
				$drRows = $wpdb->get_row("select count(option_group_id) as RowCount from " . WP_FOXYPRESS_INVENTORY_OPTION_GROUP);
				$pageNumber = foxypress_FixGetVar('fp_pn');
				$start = ($pageNumber != "" && $pageNumber != "0") ? $start = ($pageNumber - 1) * $limit : 0;	
				
                //get option groups
                $groups = $wpdb->get_results("select * from " . WP_FOXYPRESS_INVENTORY_OPTION_GROUP . " order by option_group_name LIMIT $start, $limit");
                if(!empty($groups))
                {
                    foreach($groups as $group)
                    {
                        echo("<tr>
                                <td>
									<form name=\"foxy_edit_option_group_form\" id=\"foxy_edit_option_group_form\" method=\"POST\">
										<input type=\"text\" id=\"foxypress_edit_option_name\" name=\"foxypress_edit_option_name\" value=\"" . stripslashes($group->option_group_name)  . "\" />
										<input type=\"hidden\" id=\"foxypress_edit_option_id\" name=\"foxypress_edit_option_id\" value=\"" . $group->option_group_id . "\" />
										<input type=\"submit\" id=\"foxypress_edit_option_save\" name=\"foxypress_edit_option_save\" value=\"Save\" />
									</form>
								</td>
                                <td><a href=\"" . $_SERVER['PHP_SELF'] . "?page=inventory-option-groups&action=deleteoptiongroup&optiongroupid=" . $group->option_group_id . "\" class=\"delete\" onclick=\"return confirm('Are you sure you want to delete this option group? All of the options related to this option group will be deleted.');\">Delete</td>
                             </tr>");
                    }
                }		
                else
                {
                    echo("<tr><td colspan=\"2\">There are currently no option groups</td></tr>");
                }		
            ?>
        </table>
        <? 
			if($drRows->RowCount > $limit)
			{
				$Pagination = foxypress_GetPagination($pageNumber, $drRows->RowCount, $limit, $targetpage, 'fp_pn');
				echo ("<Br>" . $Pagination);
			}	
		?>
	</div>
<? } ?>