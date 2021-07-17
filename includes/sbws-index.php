<?php

function sbws_getSoapClientUrl() {
	return "http://www.soapclient.com/soapclient?template=/clientform.html&fn=soapform&SoapTemplate=none&SoapWSDL=" .
	urlencode(sbws_getWsdlUrl());
}

$soapClientUrl = sbws_getSoapClientUrl();
$formattedSoapClientUrl = "";
$numCharPerBreak = 30;
for($i=0; $i<strlen($soapClientUrl); $i+=$numCharPerBreak) $formattedSoapClientUrl .= substr($soapClientUrl, $i, $numCharPerBreak) . " ";

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>Blogger WordPress Web Service</title>
<link rel="stylesheet" type="text/css" href="<?php echo sbws_getPluginUrl(); ?>/assets/sbws.css"/>
</head>
<body>
<h1><a href="https://code.google.com/p/wordpress-web-service" target="_blank"><img src="<?php echo sbws_getPluginUrl(); ?>/assets/sbws.png" alt="Blogger WordPress Web Service" width="265" height="73" border="0" /></a> Version <?php echo sbws_getVersion(); ?></h1>
<table width="100%" border="0" cellspacing="0" cellpadding="0">
	<tr>
		<td><p>Welcome to your Blogger WordPress Web Service (SBWS) plugin!</p>
		<?php if(sbws_WSDLcustomized()) { ?>
			<p><strong>You have successfully installed SBWS and you're ready to connect <br />
			your WSDL enabled application to your WordPress installation.</strong></p>
			<p>Your WSDL url is:<br />
	<a href="<?php echo sbws_getWsdlUrl(); ?>" target="_blank"><?php echo sbws_getWsdlUrl(); ?></a>.</p>
			<p>On the right you can see the Generic SOAP Client which<br />
				has already loaded your WSDL file.<br />
				You may want to check for proper operation of the plugin before using it.</p>
		<?php } else { ?>
			<p>You have nearly completed the installation of your SBWS plugin. <br />
			<strong>Unfortunately your WSDL file could not be created <br />
			automatically because of missing write rights.</strong><br />
			But that's no problem! Follow the 5 steps to create one manually:</p>
			<ol>
				<li>Use a FTP client to connect to your WordPress installation</li>
				<li>Go to the directory <code>wp-content</code> then to <code>plugins</code> and finally open the folder <code>sbws</code></li>
				<li>Make a copy of <code>sbws.template.wsdl</code> and name it <code>sbws.wsdl</code></li>
				<li>Open the file <code>sbws.wsdl</code> in a text editor</li>
				<li>Scroll to the end of the file and replace <code>%{BLOG_PATH}</code> by <code><?php echo sbws_getBlogUrl(); ?></code></li>
			</ol>
			<p>Revisit this page after having created the customized <code>sbws.wsdl</code> file.</p>
		<?php } ?>
			</td>
		<td>
		<?php if(sbws_WSDLcustomized()) { ?>
			<h2><a href="http://www.soapclient.com/soaptest.html" target="_blank">Generic SOAP Client</a></h2>
			<iframea src="<?php echo $soapClientUrl; ?>" width="100%" height="400"></iframe>
			<p class="source"><a href="<?php echo $soapClientUrl; ?>" target="_blank"><?php echo $formattedSoapClientUrl; ?></a></p>
		<?php } else { ?>
			&nbsp;
		<?php } ?>
		</td>
	</tr>
</table>
</body>
</html>
