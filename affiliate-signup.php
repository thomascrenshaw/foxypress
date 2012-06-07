<?php
/**************************************************************************
FoxyPress provides a complete shopping cart and inventory management tool 
for use with FoxyCart's e-commerce solution.
Copyright (C) 2008-2012 WebMovement, LLC - View License Information - FoxyPress.php
**************************************************************************/

function foxypress_create_affiliate_signup()
{
	global $wpdb, $current_user;
    get_currentuserinfo();
    $user_data = get_userdata($current_user->ID); ?>

    <h3><?php _e('FoxyPress Affiliate Sign Up', 'foxypress'); ?></h3>
   	<?php if (isset($_POST['affiliate_signup_submit'])) { 
    	
    	$first_name    			   = foxypress_FixPostVar('affiliate_first_name');
    	$last_name     			   = foxypress_FixPostVar('affiliate_last_name');
    	$facebook_page 			   = foxypress_FixPostVar('affiliate_facebook_page');
    	$age 		   			   = foxypress_FixPostVar('affiliate_age');
    	$gender 	   			   = foxypress_FixPostVar('affiliate_gender');
    	$message 	   			   = foxypress_FixPostVar('affiliate_description');
    	$avatar_name   			   = foxypress_FixPostVar('affiliate_avatar_name');
    	$avatar_ext    			   = foxypress_FixPostVar('affiliate_avatar_ext');
    	$affiliate_referred_by_id  = foxypress_FixPostVar('affiliate_referred_by_id');
    	$error 		   			   = false;

    	if (empty($first_name)) {
    		$error = true;
    		$first_name_error = true;
    	}

    	if (empty($last_name)) {
    		$error = true;
    		$last_name_error = true;
    	}

    	if (empty($facebook_page)) {
    		$error = true;
    		$facebook_page_error = true;
    	}

    	if (empty($age)) {
    		$error = true;
    		$age_error = true;
    	}

    	if (empty($gender)) {
    		$error = true;
    		$gender_error = true;
    	}

    	if (empty($message)) {
    		$error = true;
    		$message_error = true;
    	}

    	if (!$error) {
    		if ($affiliate_referred_by_id != "") {
    			$sql = "INSERT INTO " . $wpdb->prefix . "foxypress_affiliate_referrals (foxy_affiliate_referred_by_id, foxy_affiliate_id) values ('" . $affiliate_referred_by_id . "', '" . $user_data->ID . "')";
				$wpdb->query($sql);
    		}

	    	update_user_meta($user_data->ID, 'first_name', $first_name);
	    	update_user_meta($user_data->ID, 'last_name', $last_name);
	    	update_user_option($user_data->ID, 'affiliate_facebook_page', $facebook_page);
	    	update_user_option($user_data->ID, 'affiliate_age', $age);
	    	update_user_option($user_data->ID, 'affiliate_gender', $gender);
	    	update_user_meta($user_data->ID, 'description', $message);
	    	update_user_option($user_data->ID, 'affiliate_avatar_name', $avatar_name);
	    	update_user_option($user_data->ID, 'affiliate_avatar_ext', $avatar_ext);
	    	update_user_option($user_data->ID, 'affiliate_user', 'pending');
	    	update_user_option($user_data->ID, 'affiliate_referred_by_id', $affiliate_referred_by_id); ?>

	    	<div class="updated" id="message">
        		<p><strong><?php _e('Affiliate Request Sent Successfully.', 'foxypress'); ?></strong></p>
    		</div>

    	<?php } ?>
    <?php }
		$affiliate_user = get_user_option('affiliate_user', $user_id);
		if ($affiliate_user === 'pending') { ?>
	   		<div class="updated" id="message">
	        	<p><strong><?php _e('Affiliate Request Currently Pending.', 'foxypress'); ?></strong></p>
	    	</div>
	    <?php } else { 
		    //Multisite specific variables
		    $user_facebook_page = $wpdb->prefix . 'affiliate_facebook_page'; 
    		$user_age = $wpdb->prefix . 'affiliate_age';
    		$user_gender = $wpdb->prefix . 'affiliate_gender'; ?>

		    <script type="text/javascript" language="javascript">
				jQuery(document).ready(function() {
					jQuery('#affiliate_gender').val(<?php echo $user_data->$user_gender; ?>);
				});
			</script>
			<form id="affiliate_signup_form" name="affiliate_signup_form" method="POST">
			<table class="form-table">
				<tr class="<?php if ($first_name_error) { ?>form-invalid<?php } ?>">
					<th><label for="affiliate_first_name"><?php _e('First Name', 'foxypress'); ?> <span class="description">(<?php _e('required', 'foxypress'); ?>)</span></label></th>
					<td><input class="regular-text" type="text" name="affiliate_first_name" id="affiliate_first_name" value="<?php echo $user_data->first_name; ?>"><br /></td>
				</tr>
				<tr class="<?php if ($last_name_error) { ?>form-invalid<?php } ?>">
					<th><label for="affiliate_last_name"><?php _e('Last Name', 'foxypress'); ?> <span class="description">(<?php _e('required', 'foxypress'); ?>)</span></label></th>
					<td><input class="regular-text" type="text" name="affiliate_last_name" id="affiliate_last_name" value="<?php echo $user_data->last_name; ?>"></td>
				</tr>
				<tr class="<?php if ($avatar_error) { ?>form-invalid<?php } ?>">
					<th><label for="affiliate_avatar"><?php _e('Avatar', 'foxypress'); ?></label></th>
					<td>
						<div id="avatar"></div>
						<input type="file" name="avatar_upload" id="avatar_upload" value="">
						<input type="hidden" name="affiliate_avatar_name" id="affiliate_avatar_name" value="">
						<input type="hidden" name="affiliate_avatar_ext" id="affiliate_avatar_ext" value="">
					</td>
				</tr>
				<tr class="<?php if ($facebook_page_error) { ?>form-invalid<?php } ?>">
					<th><label for="affiliate_facebook_page"><?php _e('Your Facebook Page', 'foxypress'); ?> <span class="description">(<?php _e('required', 'foxypress'); ?>)</span></label></th>
					<td><input class="regular-text" type="text" name="affiliate_facebook_page" id="affiliate_facebook_page" value="<?php echo $user_data->$user_facebook_page; ?>"></td>
				</tr>
				<tr class="<?php if ($age_error) { ?>form-invalid<?php } ?>">
					<th><label for="affiliate_age"><?php _e('Age', 'foxypress'); ?> <span class="description">(<?php _e('required', 'foxypress'); ?>)</span></label></th>
					<td><input type="text" name="affiliate_age" id="affiliate_age" value="<?php echo $user_data->$user_age; ?>"></td>
				</tr>
				<tr class="<?php if ($gender_error) { ?>form-invalid<?php } ?>">
					<th><label for="affiliate_gender"><?php _e('Gender', 'foxypress'); ?> <span class="description">(<?php _e('required', 'foxypress'); ?>)</span></label></th>
					<td>
						<select name="affiliate_gender" id="affiliate_gender">
							<option><?php _e('Select', 'foxypress'); ?></option>
							<option <?php if ($user_data->$user_gender == 'Male') { ?>selected<?php } ?> value="Male"><?php _e('Male', 'foxypress'); ?></option>
							<option <?php if ($user_data->$user_gender == 'Female') { ?>selected<?php } ?> value="Female"><?php _e('Female', 'foxypress'); ?></option>
						</select>
					</td>
				</tr>
				<tr class="<?php if ($message_error) { ?>form-invalid<?php } ?>">
					<th><label for="affiliate_description"><?php _e('Message', 'foxypress'); ?> <span class="description">(<?php _e('required', 'foxypress'); ?>)</span></label></th>
					<td><textarea id="affiliate_description" cols="60" rows="5" name="affiliate_description"><?php echo $user_data->description; ?></textarea><br>
					<span class="description"><?php _e('Tell us about yourself.', 'foxypress'); ?></span></td>
				</tr>
			</table>
			<input type="hidden" name="affiliate_referred_by_id" id="affiliate_referred_by_id" value="<?php echo $_SESSION['affiliate_id']; ?>">
			<p class="submit"><input type="submit" value="<?php _e('Send Request', 'foxypress'); ?>" class="button-primary" id="affiliate_signup_submit" name="affiliate_signup_submit"></p>
			</form>
		<?php }
	
}