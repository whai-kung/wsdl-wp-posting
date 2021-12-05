<?php

require_once(dirname(__FILE__) . "/sbws-valueobjects.php");

/**
 * This class is the web service itself.
 * A PHP SoapServer instance instanciates a wp_WebService object and calls
 * the methods as specified in the WSDL file and requested as in the client SOAP request
 * on that wp_WebService object.
 */
class sb_WebService {
	/*** POSTS ***/
	 
	function deletePost($token, $postId) {
		if ($token != SBWS_TOKEN) {
			throw new SoapFault("Blogger Webservice", "Invalid token");
		}
		
		$postId = is_numeric($postId) ? intval($postId) : 0;

		if ($postId > 0) {
			$findPost = get_post($postId, OBJECT);
			if (findPost != null){
				$result = wp_delete_post($postId, true);
				return ($result != false);
			}
			return false;
		}
		
		return false;
	}

	function insertPost($token, $post) {
		if ($token != SBWS_TOKEN) {
			throw new SoapFault("Blogger Webservice", "Invalid token");
		}
		
		$valueArray = get_object_vars($post); 

				$categoryTerms = null;
		if ($valueArray["categories"] != null && $valueArray["categories"] != '') {
					// Set category
			$categoryName = $valueArray["categories"];
			$addToCat = term_exists($categoryName, "category");
			
					if ($addToCat != 0 && $addToCat !== null) {
			$addToCatId = $addToCat;
					}
					else {
						$addToCat = wp_insert_term($categoryName, "category");
			$addToCatId = $addToCat;
					} 

			$categoryTerms = array($addToCatId['term_id']);
				}
		
		if ($valueArray["id"] != null && is_numeric($valueArray["id"])) {
			$r = wp_set_object_terms(intval($valueArray["id"]), null, 'category' ); 
		}
		
		if ($valueArray["tags"] == null) {
			$valueArray["tags"] = '';
		}

		// Create post object
		$my_post = array(
			'ID'			  => $valueArray["id"],
			'post_title'    => $valueArray["title"],
			'post_content'  => $valueArray["content"],
			'post_date'	  => $valueArray["date"],
			'post_date_gmt' => $valueArray["dateGmt"],
			'post_category' => $categoryTerms,
			'tags_input'	  => $valueArray["tags"],
			'post_status'		=> $valueArray["postStatus"] ?: "publish"
		);
		
		// Insert the post into the database
		$newPostId = wp_insert_post( $my_post );
		if(!empty($valueArray["featureImage"])) {
			$this->set_featured_image_from_external_url($valueArray["featureImage"], $newPostId);
			update_post_meta($newPostId, '_yoast_wpseo_opengraph-image', $valueArray["featureImage"]);
		}

		if(!empty($valueArray["attachmentId"])) {
			$attach_id = $valueArray["attachmentId"];
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
		update_post_meta($newPostId, '_yoast_wpseo_title', $this->defaultAt($valueArray["yoastTitle"], $valueArray["title"]));
		update_post_meta($newPostId, '_yoast_wpseo_metadesc', $this->defaultAt($this->getYoastDescription($valueArray["yoastDesc"]), $this->getYoastDescription($valueArray["content"])));
		update_post_meta($newPostId, '_yoast_wpseo_opengraph-title', $this->defaultAt($valueArray["yoastFBTitle"], $valueArray["title"]));
		update_post_meta($newPostId, '_yoast_wpseo_opengraph-description', $this->defaultAt($this->getYoastDescription($valueArray["yoastFBDesc"]), $this->getYoastDescription($valueArray["content"])));
		update_post_meta($newPostId, '_yoast_wpseo_twitter-title', $this->defaultAt($valueArray["yoastTWTitle"], $valueArray["title"]));
		update_post_meta($newPostId, '_yoast_wpseo_twitter-description', $this->defaultAt($this->getYoastDescription($valueArray["yoastTWDesc"]), $this->getYoastDescription($valueArray["content"])));

		return $newPostId;
	}

	private  function defaultAt($value, $default) {
		return $value == null ? $default : $value;
	}

	private function getYoastDescription($message) {
		if ($message != null && strlen($message) > 155)
		{
				return substr($message, 0, 150)."..";
		}
		else
		{
				return $message;
		}
	}
		
	private function set_featured_image_from_external_url($url, $post_id){

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
	 
}

?>