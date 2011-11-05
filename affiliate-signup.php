<?php
function foxypress_create_affiliate_signup()
{
	global $current_user;
    get_currentuserinfo();

    $user_id = $current_user->ID; ?>
    <h3>FoxyPress Affiliate Sign Up</h3>
   	<?php if (isset($_POST['affiliate_signup_submit'])) { 
    	
    	$first_name    = foxypress_FixPostVar('affiliate_first_name');
    	$last_name     = foxypress_FixPostVar('affiliate_last_name');
    	$facebook_page = foxypress_FixPostVar('affiliate_facebook_page');
    	$age 		   = foxypress_FixPostVar('affiliate_age');
    	$gender 	   = foxypress_FixPostVar('affiliate_gender');
    	$message 	   = foxypress_FixPostVar('affiliate_description');
    	$error 		   = false;

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
	    	update_usermeta($user_id, 'first_name', $first_name);
	    	update_usermeta($user_id, 'last_name', $last_name);
	    	update_usermeta($user_id, 'affiliate_facebook_page', $facebook_page);
	    	update_usermeta($user_id, 'affiliate_age', $age);
	    	update_usermeta($user_id, 'affiliate_gender', $gender);
	    	update_usermeta($user_id, 'description', $message);
	    	update_usermeta($user_id, 'affiliate_user', 'pending'); ?>

	    	<div class="updated" id="message">
        		<p><strong>Affiliate Request Sent Successfully.</strong></p>
    		</div>

    	<?php } ?>
    <?php }
		$affiliate_user = get_the_author_meta('affiliate_user', $user_id);
		if ($affiliate_user === 'pending') { ?>
	   		<div class="updated" id="message">
	        	<p><strong>Affiliate Request Currently Pending.</strong></p>
	    	</div>
	    <?php } else { ?>
			<form id="affiliate_signup_form" name="affiliate_signup_form" method="POST">
			<table class="form-table">
				<tr class="<?php if ($first_name_error) { ?>form-invalid<?php } ?>">
					<th><label for="affiliate_first_name">First Name <span class="description">(required)</span></label></th>
					<td><input class="regular-text" type="text" name="affiliate_first_name" id="affiliate_first_name" value="<?php echo get_the_author_meta('first_name', $user_id); ?>"></td>
				</tr>
				<tr class="<?php if ($last_name_error) { ?>form-invalid<?php } ?>">
					<th><label for="affiliate_last_name">Last Name <span class="description">(required)</span></label></th>
					<td><input class="regular-text" type="text" name="affiliate_last_name" id="affiliate_last_name" value="<?php echo get_the_author_meta('last_name', $user_id); ?>"></td>
				</tr>
				<tr class="<?php if ($facebook_page_error) { ?>form-invalid<?php } ?>">
					<th><label for="affiliate_facebook_page">Your Facebook Page <span class="description">(required)</span></label></th>
					<td><input class="regular-text" type="text" name="affiliate_facebook_page" id="affiliate_facebook_page" value="<?php echo get_the_author_meta('affiliate_facebook_page', $user_id); ?>"></td>
				</tr>
				<tr class="<?php if ($age_error) { ?>form-invalid<?php } ?>">
					<th><label for="affiliate_age">Age <span class="description">(required)</span></label></th>
					<td><input type="text" name="affiliate_age" id="affiliate_age" value="<?php echo get_the_author_meta('affiliate_age', $user_id); ?>"></td>
				</tr>
				<tr class="<?php if ($gender_error) { ?>form-invalid<?php } ?>">
					<th><label for="affiliate_gender">Gender <span class="description">(required)</span></label></th>
					<td><?php $gender = get_the_author_meta('affiliate_gender', $user_id); ?>
						<select name="affiliate_gender" id="affiliate_gender">
							<option>Select</option>
							<option <?php if ($gender == 'Male') { ?>selected<?php } ?> value="Male">Male</option>
							<option <?php if ($gender == 'Female') { ?>selected<?php } ?> value="Female">Female</option>
						</select>
					</td>
				</tr>
				<tr class="<?php if ($message_error) { ?>form-invalid<?php } ?>">
					<th><label for="affiliate_description">Message <span class="description">(required)</span></label></th>
					<td><textarea id="affiliate_description" cols="60" rows="5" name="affiliate_description"><?php echo get_the_author_meta('description', $user_id); ?></textarea><br>
					<span class="description">Tell us about yourself.</span></td>
				</tr>
			</table>
			<p class="submit"><input type="submit" value="Send Request" class="button-primary" id="affiliate_signup_submit" name="affiliate_signup_submit"></p>
			</form>
		<?php }
	
}