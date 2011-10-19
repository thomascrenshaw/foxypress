<?php 
global $post;
get_header(); 
if (function_exists('foxypress_handle_shortcode_detail')) //if foxypress is enabled
{
	while (have_posts()) : the_post();		
		echo(foxypress_handle_shortcode_detail(true, true, $post->ID));
		if($post->comment_status == "open")
		{
			echo("<div class=\"foxypress_detail_comments\">");
			comments_template();
			echo("</div>");
		}
	endwhile;
} 
get_footer();
?>