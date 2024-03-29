<?php

require_once(dirname(__FILE__) . "/bdws-access.php");

define("BDWS_RESIZE_IMAGE_TEMPFILE", BDWS_CACHE_DIR . "/" . BDWS_genUniqueCode() . ".img");
define("BDWS_RESIZE_IMAGE_ESCAPESIGN", "---");

class BDWS_ImageUtils {
	
	/**
	 * Returns true if the given file resides in the given directory.
	 * @param string dir
	 * @param string file
	 * @return string cache entry file name
	 */
	function file_resides_in_directory($dir, $file) {
		$dir = realpath($dir);
		$file = realpath($file);
		if(!$dir || !$file) return false;
		if(strlen($file) < strlen($dir)) return false;
		if(substr($file, 0, strlen($dir)) != $dir) return false;
		return true;
	}
	
	/**
	 * Return the file name to be used as a cache entry
	 * @param string file (path)
	 * @param int image width
	 * @param int image height
	 * @param int value between 0 and 100 for jpeg; null for any other format
	 * @return string cache entry file name
	 */
	function generate_cache_filename($src, $width, $height, $quality) {
		$normalized = (strpos($src, "/") == 0) ? substr($src, 1) : $src;
		$escaped = str_replace(BDWS_RESIZE_IMAGE_ESCAPESIGN, BDWS_RESIZE_IMAGE_ESCAPESIGN . BDWS_RESIZE_IMAGE_ESCAPESIGN, $normalized);
		$masked = str_replace("/", BDWS_RESIZE_IMAGE_ESCAPESIGN, $escaped);
		$widthed = $masked . BDWS_RESIZE_IMAGE_ESCAPESIGN . $width;
		$heighted = $widthed . BDWS_RESIZE_IMAGE_ESCAPESIGN . $height;
		$qualitied = $heighted . BDWS_RESIZE_IMAGE_ESCAPESIGN . $quality;
		return $qualitied;
	}
	
	/**
	 * Return the file name to be used as a timestamped cache entry
	 * @param string file (path)
	 * @param int image width
	 * @param int image height
	 * @param int value between 0 and 100 for jpeg; null for any other format
	 * @param int last modified timestamp
	 * @return string timestamped cache entry file name
	 */
	function generate_timestamped_cache_filename($src, $width, $height, $quality, $timestamp) {
		$timestamped = BDWS_ImageUtils::generate_cache_filename($src, $width, $height, $quality) . BDWS_RESIZE_IMAGE_ESCAPESIGN . $timestamp;
		return $timestamped;
	}
	
	/**
	 * Returns the mime type of a file (e.g. text, gif, png, jpg, ...)
	 * @param string file
	 * @return string file type
	 */
	function get_mime_type($file) {
		$image_info = getimagesize($file);
		return $image_info["mime"];
	}
	
	/**
	 * Returns the type of a file (e.g. text, gif, png, jpg, ...)
	 * @param string file
	 * @return string file type
	 */
	function get_type($file) {
		list(, $type) = split("/", BDWS_ImageUtils::get_mime_type($file));
		return $type;
	}
	
	/**
	 * Returns the PHP function name for graphics creation
	 * @param string file format to use (gif|png|...|jpg)
	 * @return string function name; preceeded with a dollar sign the corresponding function gets called
	 */
	function get_create_function_name($file) {
		return "imagecreatefrom" . BDWS_ImageUtils::get_type($file);
	}
	
	/**
	 * Returns the PHP function name for graphics output
	 * @param string file format to use (gif|png|...|jpg)
	 * @return string function name; preceeded with a dollar sign the corresponding function gets called
	 */
	function get_send_function_name($file) {
		return "image" . BDWS_ImageUtils::get_type($file);
	}
	
	/**
	 * Resized a given image
	 * @param string file (path) pointing to the image
	 * @param int width to resize to
	 * @param int height to resize to
	 * @return Image resized image
	 */
	function generate_resized_image($file, $width, $height) {
		$image_info = getimagesize($file);
			
		// Make sure, the image does not become bigger than the original
		if($width > $image_info[0]) $width = $image_info[0];
		if($height > $image_info[1]) $height = $image_info[1];
		
		if($width && $height) {
			// Final dimensions are already set
		} else if($width) {
			// Width set
			$height = round($width/($image_info[0]/$image_info[1]));
		} else if($height) {
			// Height set
			$width = round($height*($image_info[0]/$image_info[1]));
		} else {
			// Nothing set
			$width = $image_info[0];
			$height = $image_info[1];
		}
		
		$create_function_name = BDWS_ImageUtils::get_create_function_name($file);
		$original_image = $create_function_name($file);
		$resized_image = imagecreatetruecolor($width, $height);
		$white = imagecolorallocate($resized_image, 255, 255, 255);
		imagefill($resized_image, 0, 0, $white);
		
		imagesavealpha($resized_image, true);
		imagealphablending($resized_image, true);
		imageantialias($resized_image, true);	
		
		imagecopyresampled(
			$resized_image,
			$original_image,
			0,
			0,
			0,
			0,
			$width,
			$height,
			$image_info[0],
			$image_info[1]
		);
		
		imagedestroy($original_image);
		return $resized_image;
	}
	
	/**
	 * Returns the absolute path to a given cache entry
	 * @param string file (path)
	 * @param int width to resize to
	 * @param int height to resize to
	 * @param int value between 0 and 100 for jpeg; null for any other format
	 */
	function get_cached_file($src, $width, $height, $quality) {
		$testfile = BDWS_ImageUtils::generate_cache_filename($src, $width, $height, $quality);
		
		$cached_file = null;
		$dh = opendir(BDWS_CACHE_DIR);
		while(($file = readdir($dh)) !== false) {
		    if(substr($file, 0, strlen($testfile)) == $testfile) {
		    	$cached_file = $file;
		    	break;
		    }
		}
		closedir($dh);
		return ($cached_file == null) ? null : BDWS_CACHE_DIR . "/" . $cached_file;
	}
	
	/**
	 * Removes a cache entry for a given file.
	 * @param string file (path)
	 * @param int width to resize to
	 * @param int height to resize to
	 * @param int value between 0 and 100 for jpeg; null for any other format
	 */
	function remove_cached_file($src, $width, $height, $quality) {
		$cached_file = BDWS_ImageUtils::get_cached_file($src, $width, $height, $quality);
		if($cached_file) unlink($cached_file);
	}
	
	/**
	 * Determines the last modified timestamp of a file.
	 * @param string file (path)
	 * @return int last modified timestamp
	 */
	function get_mtime_from_cached_file($file) {
		$parts = explode(BDWS_RESIZE_IMAGE_ESCAPESIGN, $file);
		if(count($parts) > 1) return $parts[count($parts)-1];
		else return null;
	}
	
	/**
	 * Determines whether the cache entry needs to be updated
	 * for a specified file.
	 * @param string file (path)
	 * @param int width to resize to
	 * @param int height to resize to
	 * @param int value between 0 and 100 for jpeg; null for any other format
	 * @param int last modified timestamp of the file in question
	 * @param boolean true if cache needs to be created / updated
	 */
	function cached_file_is_needed($src, $width, $height, $quality, $timestamp) {
		$cached_file = BDWS_ImageUtils::get_cached_file($src, $width, $height, $quality);
		if(!$cached_file) return true;
		
		$cached_file_timestamp = BDWS_ImageUtils::get_mtime_from_cached_file($cached_file);
		if($cached_file_timestamp != $timestamp) return true;
		
		return false;
	}

	
}

?>