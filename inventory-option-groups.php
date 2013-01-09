<?php

/**************************************************************************
FoxyPress provides a complete shopping cart and inventory management tool 
for use with FoxyCart's e-commerce solution.
Copyright (C) 2008-2013 WebMovement, LLC - View License Information - FoxyPress.php
**************************************************************************/

add_action('admin_init', 'inventory_option_groups_postback');

function inventory_option_groups_postback()
{
	global $wpdb;
	$PageName = filter(foxypress_FixGetVar("page"));
	$action = filter(foxypress_FixGetVar("action"));
	if($PageName == "inventory-option-groups")
	{
		if(isset($_POST['option_group_save']))
		{
			$group_name = filter(foxypress_FixPostVar('option_group_name'));
			if($group_name != "")
			{
				//insert new option group
				$wpdb->query("insert into " . $wpdb->prefix . "foxypress_inventory_option_group" . " (option_group_name) values ('" . $group_name . "')");
			}
			header("location: " . get_admin_url() . "edit.php?post_type=" . FOXYPRESS_CUSTOM_POST_TYPE . "&page=inventory-option-groups");
		}
		else if(isset($_POST['foxypress_edit_option_save']))
		{
			$group_name = foxypress_FixPostVar('foxypress_edit_option_name');
			$group_id = foxypress_FixPostVar('foxypress_edit_option_id');
			if($group_name != "" && $group_id != "")
			{
				//insert new option group
				$wpdb->query("update " . $wpdb->prefix . "foxypress_inventory_option_group" . " set option_group_name ='" . $group_name . "' where option_group_id='" . $group_id . "'");
			}
			header("location: " . get_admin_url() . "edit.php?post_type=" . FOXYPRESS_CUSTOM_POST_TYPE. "&page=inventory-option-groups");
		}
		else if($action == "deleteoptiongroup")
		{
			$option_group_id = filter(foxypress_FixGetVar('optiongroupid', ''));
			if($option_group_id != "")
			{
				//delete option group
				$wpdb->query("delete from " . $wpdb->prefix . "foxypress_inventory_option_group" . " where option_group_id = '" . $option_group_id . "'");
				//delete options related to option group
				$wpdb->query("delete from " . $wpdb->prefix . "foxypress_inventory_options" . " where option_group_id = '" . $option_group_id . "'");
			}
			header("location: " . get_admin_url() . "edit.php?post_type=" . FOXYPRESS_CUSTOM_POST_TYPE . "&page=inventory-option-groups");
		}
	}
}

function foxypress_inventory_option_groups_page_load() { 
	global $wpdb;
?>
    <div class="wrap">
        <h2><?php _e('Manage Option Groups', 'foxypress'); ?></h2>
        <div>
            <i>
                <?php _e('To create a list of options you must first create an option group. Example: "Color" or "Size".', 'foxypress'); ?> 
                <?php _e('These option groups can be used for options on any item.', 'foxypress'); ?>
            </i>
        </div>
                
        <form name="foxy_add_new_option_group" id="foxy_add_new_option_group" class="wrap" method="post">
            <div id="linkadvanceddiv" class="postbox">
                <div style="float: left; width: 98%; clear: both;" class="inside">
                    <table cellspacing="5" cellpadding="5">
                        <tr>
                            <td><legend><?php _e('New Option Group', 'foxypress'); ?>: </legend></td>
                            <td><input type="text" name="option_group_name" id="option_group_name" class="input" size="30" maxlength="30" value="" /></td>
                            <td><input type="submit" name="option_group_save" id="option_group_save" class="button bold" value="<?php _e('Save','foxypress'); ?> &raquo;" /></td>
                        </tr>
                    </table>
                </div>
                <div style="clear:both; height:1px;">&nbsp;</div>
            </div>
        </form>

         <table class="widefat page fixed" width="50%" cellpadding="3" cellspacing="3">	
            <thead>
                <tr>
                    <th class="manage-column" scope="col"><?php _e('Option Group Name', 'foxypress'); ?></th>
                    <th class="manage-column" scope="col">&nbsp;</th>
                </tr>
            </thead>
            <?php				
				//set up paging				
				$limit = 10;
				$targetpage = get_admin_url() . "edit.php?post_type=" . FOXYPRESS_CUSTOM_POST_TYPE . "&page=inventory-option-groups";
				$targetpage = foxypress_RemoveQSValue($targetpage, "fp_pn");
				$pos = strrpos($targetpage, "?");
				if ($pos === false) { 
					$targetpage .= "?";
				}	
				$drRows = $wpdb->get_row("select count(option_group_id) as RowCount from " . $wpdb->prefix . "foxypress_inventory_option_group");
				$pageNumber = filter(foxypress_FixGetVar('fp_pn'));
				$start = ($pageNumber != "" && $pageNumber != "0") ? $start = ($pageNumber - 1) * $limit : 0;	
				
                //get option groups
                $groups = $wpdb->get_results("select * from " . $wpdb->prefix . "foxypress_inventory_option_group" . " order by option_group_name LIMIT $start, $limit");
                if(!empty($groups))
                {
                    foreach($groups as $group)
                    {
                        echo("<tr>
                                <td>
									<form name=\"foxy_edit_option_group_form\" id=\"foxy_edit_option_group_form\" method=\"POST\">
										<input type=\"text\" id=\"foxypress_edit_option_name\" name=\"foxypress_edit_option_name\" value=\"" . stripslashes($group->option_group_name)  . "\" />
										<input type=\"hidden\" id=\"foxypress_edit_option_id\" name=\"foxypress_edit_option_id\" value=\"" . $group->option_group_id . "\" />
										<input type=\"submit\" id=\"foxypress_edit_option_save\" name=\"foxypress_edit_option_save\" value=\"" . __('Save', 'foxypress') . "\" />
									</form>
								</td>
                                <td><a href=\"" . get_admin_url() . "edit.php?post_type=" . FOXYPRESS_CUSTOM_POST_TYPE . "&page=inventory-option-groups&action=deleteoptiongroup&optiongroupid=" . $group->option_group_id . "\" class=\"delete\" onclick=\"return confirm('" . __('Are you sure you want to delete this option group? All of the options related to this option group will be deleted.', 'foxypress') . "');\">Delete</td>
                             </tr>");
                    }
                }		
                else
                {
                    echo("<tr><td colspan=\"2\">" . __('There are currently no option groups', 'foxypress') . "</td></tr>");
                }		
            ?>
        </table>
        <?php 
			if($drRows->RowCount > $limit)
			{
				$Pagination = foxypress_GetPagination($pageNumber, $drRows->RowCount, $limit, $targetpage, 'fp_pn');
				echo ("<br />" . $Pagination);
			}	
		?>
	</div>
<?php } ?>