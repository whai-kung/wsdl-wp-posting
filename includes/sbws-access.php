<?php
require_once(dirname(__FILE__) . "/sbws-config.php");

define("SBWS_PLUGIN_NAME", "blogdrip-web-service");
define("SBWS_ENTRY_FILE", dirname(__FILE__) . "/../" . SBWS_PLUGIN_NAME . ".php");
define("SBWS_INDEX_FILE", dirname(__FILE__) . "/sbws-index.php");

define("SBWS_WSDL_TEMPLATE", dirname(__FILE__) . "/../sbws.template.wsdl");
define("SBWS_WSDL", dirname(__FILE__) . "/../sbws.wsdl");
define("SBWS_SOAP_SERVER_FILE", dirname(__FILE__) . "/sbws-soap.php");
define("SBWS_SOAP_SERVER_CLASS", "sb_WebService");

define("SBWS_BLOG_URL", "%{BLOG_URL}");
define("SBWS_CACHE_DIR", dirname(__FILE__) . "/../cache");
define("SB_UPLOAD_DIR", "/wp-content/uploads");

function sbws_getVersion() {
	$entry_file = file_get_contents(SBWS_ENTRY_FILE);
	$version = preg_replace("~.*Version:\W*([a-zA-Z0-9\._]*).*~sm", "\\3", $entry_file);
	return $version;
}

function sbws_WSDLcustomized() {
	if(file_exists(SBWS_WSDL)) {
		$wsdl = file_get_contents(SBWS_WSDL_TEMPLATE);
		return (strpos($wsdl, SBWS_BLOG_URL) !== false);
	}
}

function sbws_createWSDL() {
	$wsdl = sbws_getWSDLfromTemplate();
	
	// Should this operation fail, write permission aren't given.
	// In this case you need to create the customized WSDL on the base
	// of the template file on your own.
	@file_put_contents(SBWS_WSDL, $wsdl);
}

function sbws_getWSDLfromTemplate() {
	$wsdl = file_get_contents(SBWS_WSDL_TEMPLATE);
	return str_replace(SBWS_BLOG_URL, sbws_getBlogUrl(), $wsdl);
}

function sbws_getBlogUrl() {
	return (defined("WP_HOME")) ? WP_HOME : get_option("home", "");
}

function sbws_getWsdlUrl() {
	return sbws_getBlogUrl() . "/index.php?/sbws/?wsdl";
}

function sbws_getPluginUrl() {
	return sbws_getBlogUrl() . "/wp-content/plugins/" . SBWS_PLUGIN_NAME;
}

function sbws_getBaseDir() {
	$current_path = $_SERVER['SCRIPT_FILENAME'];
	while(true) {
		$slash_pos = strrpos($current_path, "/");
		if($slash_pos === false) return false;
		
		$current_path = substr($current_path, 0, $slash_pos);
		if(file_exists($current_path . "/wp-load.php")) {
			return $current_path;
		}
	}
}

function sbws_cacheIsFunctional() {
	return is_dir(SBWS_CACHE_DIR) && is_writable(SBWS_CACHE_DIR);
}

function sbws_getCacheDir() {
	return SBWS_CACHE_DIR;
}

function sbws_genUniqueCode($length = 8) {	
	$code = md5(uniqid(rand(), true));
	if(is_int($length)) return substr($code, 0, $length);
	else return $code;
}

?>