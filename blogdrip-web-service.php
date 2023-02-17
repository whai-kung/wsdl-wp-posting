<?php

/*
Plugin Name: BlogDrip Web Service
Plugin URI: https://blogdrip.com/
Description: WordPress Web Service is used to access WordPress resources via APIs. After installation simply open https://yoursite.com/wp-json/bd/v1/version to test the plugin.
Version: 1.7.1
Author: BlogDrip Content Marketing Platform
Author URI: https://blogdrip.com/
Requires at least: 7.4
Requires PHP:      7.4
*/

/*  Copyright 2021 BREMIC Digital Services (email: servicedesk@bremic.co.th)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 3, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/* Change Log
release: 0.0.1
Add feature `featureImage`

*/

/**
 * Set auto update
 */
require_once(dirname(__FILE__) . "/plugin-update-checker/plugin-update-checker.php");
$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
	'https://my.blogdrip.com/wordpress/update-plugin.json',
	__FILE__, //Full path to the main plugin file or functions.php.
	'unique-plugin-or-theme-slug'
);

require_once(dirname(__FILE__) . "/includes/bdws-access.php");

/**
 * Add left menu to set credential
 */
function settings_page() {
	if (!current_user_can('activate_plugins')) return;
	?>
	<div class="wrap">
		<div id="icon-themes" class="icon32"></div>
		<h2>Settings Credential for Blogdrip</h2>  
		<!--NEED THE settings_errors below so that the errors/success messages are shown after submission - wasn't working once we started using add_menu_page and stopped using add_options_page so needed this-->
		<?php settings_errors(); ?>  
		<form method="POST" action="options.php">
				<?php settings_fields('blogdrip_plugin');?>
		    <?php do_settings_sections('blogdrip_plugin')?>
		    <?php submit_button();?>
		</form> 
	</div>
	<?php
}

function settings_menu_page_init() {
	add_settings_section(
		'blogdrip-settings-section', // id of the section
		'My Settings', // title to be displayed
		'', // callback function to be called when opening section
		'blogdrip_plugin' // page on which to display the section, this should be the same as the slug used in add_submenu_page()
	);

	// register the setting
	register_setting(
		'blogdrip_plugin', // option group
		'blogdrip_token'
	);

	add_settings_field(
		'blogdrip-token-field', // id of the settings field
		'BlogDrip Token', // title
		'my_settings_cb', // callback function
		'blogdrip_plugin', // page on which settings display
		'blogdrip-settings-section' // section on which to show settings
	);
}

function my_settings_cb() {
	$credential = esc_attr(get_option('blogdrip_token', '00000000-0000-0000-0000-000000000000'));
	?>
    <div id="titlediv">
        <input id="title" type="text" name="blogdrip_token" class="regular-text" value="<?php echo $credential; ?>">
    </div>
    <?php
}

function add_settings_menu_page() {
	if (!current_user_can('activate_plugins')) return;

	global $rsssl_admin_page;

	$rsssl_admin_page = add_options_page(
		'blogdrip settings', //link title
		'BlogDrip', //page title
		'activate_plugins', //capability
		'blogdrip_plugin', //url
		'settings_page'); //function

	add_action('admin_init', 'settings_menu_page_init');
}

function add_settings_link($links, $file) {
	if ( current_filter() === 'plugin_action_links_'.plugin_basename(__FILE__) ) {
		$url = admin_url( 'options-general.php?page=blogdrip_plugin' );
	}

	// Prevent warnings in PHP 7.0+ when a plugin uses this filter incorrectly.
	$links = (array) $links;
	$links[] = sprintf( '<a href="%s">%s</a>', $url,'Settings' );

	return $links;
}

add_filter( 'plugin_action_links_'.plugin_basename(__FILE__), 'add_settings_link', 50, 2 );
add_filter( 'network_admin_plugin_action_links','add_settings_link', 50, 2 );
add_action('admin_menu', 'add_settings_menu_page', 50);

// Add allow protocal
function ss_allow_other_protocol( $protocols ){
	$protocols = array( 'sms', 'tel', 'mailto', 'skype', 'viber', 'weixin', 'http', 'https', 'ftp', 'ftps', 'mailto', 'news', 'irc', 'irc6', 'ircs', 'gopher', 'nntp', 'feed', 'telnet', 'mms', 'rtsp', 'sms', 'svn', 'tel', 'fax', 'xmpp', 'webcal', 'urn' );
	return $protocols;
}
add_filter( 'kses_allowed_protocols' , 'ss_allow_other_protocol' );

// creates a customized WSDL on plugin activation
register_activation_hook(__FILE__, 'BDWS_createWSDL');

// checks whether the request should be handled by WPWS
add_action("parse_request", "BDWS_handle_request");

// POST bd/v1/upload
function bd_upload_media($request) {
	$token = $request->get_header('x-authen-token');
	if ($token != BDWS_TOKEN) {
		header("HTTP/1.1 403 Forbidden");
		exit;
	}
	if ( isset( $_FILES["file"] ) ) {

    require_once( ABSPATH . 'wp-admin/includes/image.php' );
    require_once( ABSPATH . 'wp-admin/includes/file.php' );
    require_once( ABSPATH . 'wp-admin/includes/media.php' );

		$attachment_id = media_handle_upload( 'file', 0 );

		if ( is_wp_error( $attachment_id ) ) {
				// There was an error uploading the image.
				return $attachment_id->get_error_message();
		} else {
				// The image was uploaded successfully!
				$file_url = wp_get_attachment_url($attachment_id);
				return json_decode('{"attachment_id": "'.$attachment_id.'", "url": "'.$file_url.'"}');
		}

	} else {
			return "File not found";
	}
}

// GET bd/v1/version
function bd_version($request) {
	$token = $request->get_header('x-authen-token');
	if ($token != BDWS_TOKEN) {
		header("HTTP/1.1 403 Forbidden");
		exit;
	}
	return BDWS_getVersion();
}

// GET bd/v1/link/categories
function all_links($request) {
	global $wpdb;
	$token = $request->get_header('x-authen-token');
	if ($token != BDWS_TOKEN) {
		header("HTTP/1.1 403 Forbidden");
		exit;
	}
	try {
		$all_link_query = <<<SQL
		SELECT
				p.ID
				, p.post_title
				, t.term_taxonomy_id as term_id
				, p.post_date_gmt
				, p.post_status
				, p.post_modified_gmt
				, p.post_content
				, p.comment_status
				, (SELECT meta_value FROM $wpdb->postmeta WHERE post_id = p.ID AND meta_key = 'link_no_follow') AS no_follow
				, (SELECT meta_value FROM $wpdb->postmeta WHERE post_id = p.ID AND meta_key = 'link_description') AS description
				, (SELECT meta_value FROM $wpdb->postmeta WHERE post_id = p.ID AND meta_key = 'link_url') AS url
		FROM
				$wpdb->posts p
				LEFT JOIN
				$wpdb->term_relationships r ON p.ID = r.object_id
				LEFT JOIN
				$wpdb->term_taxonomy t ON r.term_taxonomy_id = t.term_taxonomy_id
		WHERE
				t.taxonomy = 'link_library_category'
		SQL;
		$all_link_cats = $wpdb->get_results( $all_link_query, ARRAY_A );
		echo json_encode($all_link_cats);
	} catch(Exception $e) {
		echo 'Error Message: ' .$e->getMessage();
	}
}

// POST bd/v1/link/submit
function link_submit($request) {
	$token = $request->get_header('x-authen-token');
	if ($token != BDWS_TOKEN) {
		header("HTTP/1.1 403 Forbidden");
		exit;
	}
	$post_title = $request->get_param( 'post_title' );
	$post_status = $request->get_param( 'post_status' ) ?: "publish";
	$link_url = $request->get_param( 'link_url' );
	$link_description = $request->get_param( 'link_description' );
	$link_category_id = $request->get_param( 'link_category_id' );	// term_id
	$link_no_follow = $request->get_param( 'link_no_follow' ) ?: "false";
	$date =  $request->get_param( 'date' );
	$date_gmt =  $request->get_param( 'date_gmt' );


	try {
		// $existingcat = get_term_by( 'id', $cat_element, $genoptions['cattaxonomy'] );
		// $newlinkcatlist[$existingcat->term_id] = $existingcat->name;
		$new_link_data = array(
			'post_type' => 'link_library_links',
			'post_content' => '',
			'post_title' => esc_html( stripslashes( $post_title ) ),
			'post_status' => $post_status,
			'post_date'	  => $date,
			'post_date_gmt' => $date_gmt
		);

		if ($request->get_param( 'id' ) != null && is_numeric($request->get_param( 'id' ))) {
			$new_link_data['ID'] = intval($request->get_param( 'id' ));
		}
		
		$new_link_ID = wp_insert_post( $new_link_data );
		wp_set_post_terms( $new_link_ID, $link_category_id, 'link_library_category', false );
		update_post_meta( $new_link_ID, 'link_target', '_blank' );
		update_post_meta( $new_link_ID, 'link_url', esc_url( stripslashes( $link_url ) ) );
		update_post_meta( $new_link_ID, 'link_description', sanitize_text_field( $link_description ) );
		update_post_meta( $new_link_ID, 'link_updated', current_time( 'timestamp' ) );
		update_post_meta( $new_link_ID, 'link_no_follow', filter_var($link_no_follow, FILTER_VALIDATE_BOOLEAN) );

		echo 	$new_link_ID;	// return post_id

	} catch(Exception $e) {
		echo 'Error Message: ' .$e->getMessage();
	}
}

// POST bd/v1/link/delete
function link_delete($request) {
	$token = $request->get_header('x-authen-token');
	if ($token != BDWS_TOKEN) {
		header("HTTP/1.1 403 Forbidden");
		exit;
	}
	try {
		$result = false;
		$postId = intval($request->get_param( 'id' ));

		if ($postId > 0) {
			$findPost = get_post($postId, OBJECT);
			if (findPost != null) {
				$result = wp_delete_post($postId, true);
			}
		}
		if($result != false) {
			echo 'Delete success';
		} else {
			echo 'Delete fail';
		}
	} catch(Exception $e) {
		echo 'Error Message: ' .$e->getMessage();
	}
}

// GET bd/v1/link/categories
function link_categories($request) {
	global $wpdb;
	$token = $request->get_header('x-authen-token');
	if ($token != BDWS_TOKEN) {
		header("HTTP/1.1 403 Forbidden");
		exit;
	}
	try {
		$all_link_cats_query = <<<SQL
		SELECT
				t.*, tt.description, tt.count
		FROM
				$wpdb->terms t
				LEFT JOIN
				$wpdb->term_taxonomy tt ON t.term_id = tt.term_id
		WHERE
				tt.taxonomy = 'link_library_category'
		SQL;
		$all_link_cats = $wpdb->get_results( $all_link_cats_query, ARRAY_A );
		echo json_encode($all_link_cats);
	} catch(Exception $e) {
		echo 'Error Message: ' .$e->getMessage();
	}
}

// GET bd/v1/blog/categories
function blog_categories($request) {
	global $wpdb;
	$token = $request->get_header('x-authen-token');
	if ($token != BDWS_TOKEN) {
		header("HTTP/1.1 403 Forbidden");
		exit;
	}
	try {
		$all_link_cats_query = <<<SQL
		SELECT
				t.*, tt.description, tt.count
		FROM
				$wpdb->terms t
				LEFT JOIN
				$wpdb->term_taxonomy tt ON t.term_id = tt.term_id
		WHERE
				tt.taxonomy = 'category'
		SQL;
		$all_link_cats = $wpdb->get_results( $all_link_cats_query, ARRAY_A );
		echo json_encode($all_link_cats);
	} catch(Exception $e) {
		echo 'Error Message: ' .$e->getMessage();
	}
}

// POST bd/v1/blog/submit
function blog_submit($request) {
	$token = $request->get_header('x-authen-token');
	if ($token != BDWS_TOKEN) {
		header("HTTP/1.1 403 Forbidden");
		exit;
	}

	$categoryTerms = null;
	if ($request->get_param( 'categories' ) != null && $request->get_param( 'categories' ) != '') {
		// Set category
		$categoryName = $request->get_param( 'categories' );
		$addToCat = term_exists($categoryName, "category");
		
		if ($addToCat != 0 && $addToCat !== null) {
			$addToCatId = $addToCat;
		} else {
			$addToCat = wp_insert_term($categoryName, "category");
			$addToCatId = $addToCat;
		} 

		$categoryTerms = array($addToCatId['term_id']);
	}
	
	$title = $request->get_param( 'title' );
	$content = $request->get_param( 'content' );
	$date =  $request->get_param( 'date' );
	$date_gmt =  $request->get_param( 'dateGmt' );
	$category = $categoryTerms;
	$tags_input = $request->get_param( 'tags' ) ?: "";
	$status = $request->get_param( 'postStatus' ) ?: "publish";

	try {
		$new_blog_data = array(
			'post_title' => esc_html( stripslashes( $title ) ),
			'post_content' => $content,
			'post_date'	  => $date,
			'post_date_gmt' => $date_gmt,
			'post_category' => $category,
			'tags_input'	  => $tags_input,
			'post_status' => $status,
		);

		if ($request->get_param( 'id' ) != null && is_numeric($request->get_param( 'id' ))) {
			$new_blog_data['ID'] = intval($request->get_param( 'id' ));
			$r = wp_set_object_terms($new_blog_data['ID'], null, 'category' ); 
		}
		
		$newPostId = wp_insert_post( $new_blog_data );
		if(!empty($request->get_param( 'featureImage' ))) {
			set_featured_image_from_external_url($request->get_param( 'featureImage' ), $newPostId);
			update_post_meta($newPostId, '_yoast_wpseo_opengraph-image', $request->get_param( 'featureImage' ));
		}
	
		if(!empty($request->get_param( 'attachmentId' ))) {
			$attach_id = $request->get_param( 'attachmentId' );
			$url = wp_get_attachment_url($attach_id);
			set_post_thumbnail( $newPostId, $attach_id );
			update_post_meta($newPostId, '_yoast_wpseo_opengraph-image', $url);
		}

		// reference : https://www.wpallimport.com/documentation/plugins-themes/yoast-wordpress-seo/
		/** 
		 * yoast_title
		 * yoast_desc
		 * yoast_fb_title
		 * yoast_fb_desc
		 * yoast_tw_title
		 * yoast_tw_desc
		*/
		update_post_meta($newPostId, '_yoast_wpseo_title', defaultAt($request->get_param( 'yoastTitle' ), $title));
		update_post_meta($newPostId, '_yoast_wpseo_metadesc', defaultAt(getYoastDescription($request->get_param( 'yoastDesc' )), getYoastDescription($content)));
		update_post_meta($newPostId, '_yoast_wpseo_opengraph-title', defaultAt($request->get_param( 'yoastFBTitle' ), $title));
		update_post_meta($newPostId, '_yoast_wpseo_opengraph-description', defaultAt(getYoastDescription($request->get_param( 'yoastFBDesc' )), getYoastDescription($content)));
		update_post_meta($newPostId, '_yoast_wpseo_twitter-title', defaultAt($request->get_param( 'yoastTWTitle' ), $title));
		update_post_meta($newPostId, '_yoast_wpseo_twitter-description', defaultAt(getYoastDescription($request->get_param( 'yoastTWDesc' )), getYoastDescription($content)));

		
		$result = array('id' => $newPostId, 'url' => get_permalink($newPostId));
		echo 	json_encode($result);

	} catch(Exception $e) {
		echo 'Error Message: ' .$e->getMessage();
	}
}

// POST bd/v1/blog/delete
function blog_delete($request) {
	$token = $request->get_header('x-authen-token');
	if ($token != BDWS_TOKEN) {
		header("HTTP/1.1 403 Forbidden");
		exit;
	}
	try {
		$result = false;
		$postId = intval($request->get_param( 'id' ));

		if ($postId > 0) {
			$findPost = get_post($postId, OBJECT);
			if (findPost != null) {
				$result = wp_delete_post($postId, true);
			}
		}
		if($result != false) {
			echo 'Delete success';
		} else {
			echo 'Delete fail';
		}
	} catch(Exception $e) {
		echo 'Error Message: ' .$e->getMessage();
	}
}

// GET bd/v1/blog/url
function blog_url($request) {
	$token = $request->get_header('x-authen-token');
	if ($token != BDWS_TOKEN) {
		header("HTTP/1.1 403 Forbidden");
		exit;
	}
	try {
		$post_id = $request->get_param( 'id' );
		$result = array('id' => $post_id, 'url' => get_permalink($post_id));
		echo json_encode($result);
	} catch(Exception $e) {
		echo 'Error Message: ' .$e->getMessage();
	}
}

add_action("rest_api_init", function() {
	register_rest_route('bd/v1', 'upload', [
		'methods' => 'POST',
		'callback' => 'bd_upload_media',
	]);

	register_rest_route('bd/v1', 'version', [
		'methods' => 'GET',
		'callback' => 'bd_version',
	]);

	register_rest_route('bd/v1', 'link/categories', [
		'methods' => 'GET',
		'callback' => 'link_categories',
	]);

	register_rest_route('bd/v1', 'link/submit', [
		'methods' => 'POST',
		'callback' => 'link_submit',
	]);

	register_rest_route('bd/v1', 'link/delete', [
		'methods' => 'POST',
		'callback' => 'link_delete',
	]);

	register_rest_route('bd/v1', 'link/all', [
		'methods' => 'GET',
		'callback' => 'all_links',
	]);

	register_rest_route('bd/v1', 'blog/submit', [
		'methods' => 'POST',
		'callback' => 'blog_submit',
	]);

	register_rest_route('bd/v1', 'blog/delete', [
		'methods' => 'POST',
		'callback' => 'blog_delete',
	]);

	register_rest_route('bd/v1', 'blog/url', [
		'methods' => 'GET',
		'callback' => 'blog_url',
	]);

	register_rest_route('bd/v1', 'blog/categories', [
		'methods' => 'GET',
		'callback' => 'blog_categories',
	]);
});

function defaultAt($value, $default) {
	return $value == null ? $default : $value;
}

function getYoastDescription($message) {
	if ($message != null && strlen($message) > 155)
	{
			return substr($message, 0, 150)."..";
	}
	else
	{
			return $message;
	}
}
	
function set_featured_image_from_external_url($url, $post_id){

	if ( ! filter_var($url, FILTER_VALIDATE_URL) ||  empty($post_id) ) {
		return;
	}
	
	// Add Featured Image to Post
	$image_url 		  = preg_replace('/\?.*/', '', $url); // removing query string from url & Define the image URL here
	$image_name       = basename($image_url);
	$upload_dir       = wp_upload_dir(); // Set upload folder
	$image_data       = file_get_contents($url); // Get image data
	$unique_file_name = wp_unique_filename( $upload_dir['path'], $image_name ); // Generate unique name
	$filename         = basename( $unique_file_name ); // Create image file name

	// Check folder permission and define file location
	if( wp_mkdir_p( $upload_dir['path'] ) ) {
		$file = $upload_dir['path'] . '/' . $filename;
	} else {
		$file = $upload_dir['basedir'] . '/' . $filename;
	}

	// Create the image  file on the server
	file_put_contents( $file, $image_data );

	// Check image file type
	$wp_filetype = wp_check_filetype( $filename, null );

	// Set attachment data
	$attachment = array(
		'post_mime_type' => $wp_filetype['type'],
		'post_title'     => sanitize_file_name( $filename ),
		'post_content'   => '',
		'post_status'    => 'inherit'
	);

	// Create the attachment
	$attach_id = wp_insert_attachment( $attachment, $file, $post_id );

	// Include image.php
	require_once(ABSPATH . 'wp-admin/includes/image.php');

	// Define attachment metadata
	$attach_data = wp_generate_attachment_metadata( $attach_id, $file );

	// Assign metadata to attachment
	wp_update_attachment_metadata( $attach_id, $attach_data );

	// And finally assign featured image to post
	set_post_thumbnail( $post_id, $attach_id );
}

?>
