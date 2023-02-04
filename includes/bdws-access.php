<?php
$credential = esc_attr(get_option('blogdrip_token', '00000000-0000-0000-0000-000000000000'));
define("BDWS_TOKEN", $credential);
define("BDWS_PLUGIN_NAME", "blogdrip-web-service");
define("BDWS_ENTRY_FILE", dirname(__FILE__) . "/../" . BDWS_PLUGIN_NAME . ".php");
define("BDWS_INDEX_FILE", dirname(__FILE__) . "/bdws-index.php");

define("BDWS_BLOG_URL", "%{BLOG_URL}");
define("BDWS_CACHE_DIR", dirname(__FILE__) . "/../cache");
define("SB_UPLOAD_DIR", "/wp-content/uploads");

function BDWS_getVersion() {
	$entry_file = file_get_contents(BDWS_ENTRY_FILE);
	$version = preg_replace("~.*Version:\W*([a-zA-Z0-9\._]*).*~sm", "\\1", $entry_file);
	return $version;
}

function BDWS_WSDLcustomized() {
	if(file_exists(BDWS_WSDL)) {
		$wsdl = file_get_contents(BDWS_WSDL_TEMPLATE);
		return (strpos($wsdl, BDWS_BLOG_URL) !== false);
	}
}

function BDWS_getBlogUrl() {
	return (defined("WP_HOME")) ? WP_HOME : get_option("home", "");
}

function BDWS_getPluginUrl() {
	return BDWS_getBlogUrl() . "/wp-content/plugins/" . BDWS_PLUGIN_NAME;
}

function BDWS_getWSDLfromTemplate() {
	$wsdl = file_get_contents(BDWS_WSDL_TEMPLATE);
	return str_replace(BDWS_BLOG_URL, BDWS_getBlogUrl(), $wsdl);
}

function BDWS_createWSDL() {
	$wsdl = BDWS_getWSDLfromTemplate();
	
	// Should this operation fail, write permission aren't given.
	// In this case you need to create the customized WSDL on the base
	// of the template file on your own.
	@file_put_contents(BDWS_WSDL, $wsdl);
}

function BDWS_genUniqueCode($length = 8) {	
	$code = md5(uniqid(rand(), true));
	if(is_int($length)) return substr($code, 0, $length);
	else return $code;
}

?>