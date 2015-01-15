<?php

// Authors: James Lao, Jason Schuller
// URL: http://wordpress.org/extend/plugins/simple-video-embedder/

require_once('config.php');
require_once('video-embed.php');

/**
 * Post admin hooks
 */
add_action('admin_menu', "cp_videoAdminInit");
add_action('save_post', 'cp_saveVideo');

function cp_videoAdminInit() {
	if( function_exists("add_meta_box") ) {
		add_meta_box("cp-video-posting", "Post Video Options", "cp_videoPosting", "post", "advanced");
	}
}

/**
 * Code for the meta box.
 */
function cp_videoPosting() {
	global $post_ID;
	$videoURL = get_post_meta($post_ID, '_videoembed', true);
	$videoHeight = get_post_meta($post_ID, '_videoheight', true);
	$videoWidth = get_post_meta($post_ID, '_videowidth', true);
	$videoEmbed = get_post_meta($post_ID, '_videoembed_manual', true);
	
?>

	<div style="float:left; margin-right: 5px;">
		<label for="cp-video-url">Video URL:</label><br />
		<input style="width: 300px; margin-top:5px;" type="text" id="cp-video-url" name="cp-video-url" value="<?php echo $videoURL; ?>" tabindex='100' />
	</div>
	<div style="float:left; margin-right: 5px;">
		<label for="cp-video-width3">Width:</label><br />
		<input style="margin-top:5px;" type="text" id="cp-video-width3" name="cp-video-width" size="4" value="<?php echo $videoWidth; ?>" tabindex='101' />
	</div>
	<div style="float:left;">
		<label for="cp-video-height4">Height:</label><br />
		<input style="margin-top:5px;" type="text" id="cp-video-height4" name="cp-video-height" size="4" value="<?php echo $videoHeight; ?>" tabindex='102' />
	</div>
	<div class="clear"></div>
	
	<div style="margin-top:10px;">
		  <label for="cp-video-embed">Embed Code: (Overrides Above Settings)</label><br />
		  <textarea style="width: 100%; margin:5px 2px 0 0;" id="cp-video-embed" name="cp-video-embed" rows="4" tabindex="103"><?php echo htmlspecialchars(stripslashes($videoEmbed)); ?></textarea>
	</div>
	<p style="margin:10px 0 0 0;">
		<input id="cp-remove-video" type="checkbox" name="cp-remove-video" /> <label for="cp-remove-video">Remove video</label>
	</p>

<?php
	if ( $videoURL ) {
		echo '<div style="margin-top:10px;">Video Preview: (Actual Size)<br /><div id="video_preview" style="padding: 3px; border: 1px solid #CCC;float: left; margin-top: 5px;">';
		$videoEmbedder = new cpVideoEmbedder($videoURL);
		$videoEmbedder->setDefaultWidth(cp_VIDEO_W);
		$videoEmbedder->setHeight($videoHeight);
		$videoEmbedder->setWidth($videoWidth);
		echo $videoEmbedder->getEmbedCode();
		echo '</div></div><div class="clear"></div>';
	} else if ( $videoEmbed ) {
		echo '<div style="margin-top:10px;">Video Preview: (Actual Size)<br /><div id="video_preview" style="padding: 3px; border: 1px solid #CCC;float: left; margin-top: 5px;">';
		echo stripslashes($videoEmbed);
		echo '</div></div><div class="clear"></div>';
	}
?>
<p style="margin:10px 0 0 0;"><input id="publish" class="button-primary" type="submit" value="Update Post" accesskey="p" tabindex="5" name="save"/></p>
<?php /*
	if ( $thumbURL )
		echo '<a href="' . $thumbURL . '" title="Preview Video" target="_blank">Preview Video</a>';
*/ }

/**
 * Saves the thumbnail image as a meta field associated
 * with the current post. Runs when a post is saved.
 */
function cp_saveVideo( $postID ) { 
  if(isset($_POST['post_type']) && 'post' == $_POST['post_type']) {    
	global $wpdb;

	// Get the correct post ID if revision.
	if ( $wpdb->get_var("SELECT post_type FROM $wpdb->posts WHERE ID=$postID")=='revision')
		$postID = $wpdb->get_var("SELECT post_parent FROM $wpdb->posts WHERE ID=$postID");

	// Trim white space just in case.

	$_POST['cp-video-embed'] = trim($_POST['cp-video-embed']);
	$_POST['cp-video-url'] = trim($_POST['cp-video-url']);
	$_POST['cp-video-width'] = trim($_POST['cp-video-width']);
	$_POST['cp-video-height'] = trim($_POST['cp-video-height']);

	if ( isset($_POST['cp-remove-video']) ) {
		// Remove video
		delete_post_meta($postID, '_videoembed');
		delete_post_meta($postID, '_videowidth');
		delete_post_meta($postID, '_videoheight');
		delete_post_meta($postID, '_videoembed_manual');
	} elseif ( $_POST['cp-video-embed'] ) {
		// Save video embed code.
		if( !update_post_meta($postID, '_videoembed_manual', $_POST['cp-video-embed'] ) )
		add_post_meta($postID, '_videoembed_manual', $_POST['cp-video-embed'] );
		delete_post_meta($postID, '_videoembed');
		delete_post_meta($postID, '_videowidth');
		delete_post_meta($postID, '_videoheight');
	} elseif ( $_POST['cp-video-url'] ) {
		// Save video URL.
		if( !update_post_meta($postID, '_videoembed', $_POST['cp-video-url'] ) )
		add_post_meta($postID, '_videoembed', $_POST['cp-video-url'] );
		delete_post_meta($postID, '_videoembed_manual');
		
		// Save width and height.
		$videoWidth = is_numeric($_POST['cp-video-width']) ? $_POST['cp-video-width'] : "";
		if( !update_post_meta($postID, '_videowidth', $videoWidth) )
		add_post_meta($postID, '_videowidth', $videoWidth);
   
		$videoHeight = is_numeric($_POST['cp-video-height']) ? $_POST['cp-video-height'] : "";
		if( !update_post_meta($postID, '_videoheight', $videoHeight) )
		add_post_meta($postID, '_videoheight', $videoHeight);
	} 
	
  }

}

/**
 * Gets the embed code for a video.
 *
 * @param $postID The post ID of the video
 * @return The embed code
 */
function cpGetVideo($postID) {
	if ( $videoURL = get_post_meta($postID, 'videoembed', true) ) return $videoURL;
	if ( $videoEmbed = get_post_meta($postID, '_videoembed_manual', true ) ) return $videoEmbed;

	$videoURL = get_post_meta($postID, '_videoembed', true);
	$videoWidth = get_post_meta($postID, '_videowidth', true);
	$videoHeight = get_post_meta($postID, '_videoheight', true);

	$videoEmbedder = new cpVideoEmbedder($videoURL);
	$videoEmbedder->setDefaultWidth(cp_VIDEO_W);
	$videoEmbedder->setWidth($videoWidth);
	$videoEmbedder->setHeight($videoHeight);

	return $videoEmbedder->getEmbedCode();
}

?>
