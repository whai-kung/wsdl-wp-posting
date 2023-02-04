<?php
$formattedSoapClientUrl = "";
$numCharPerBreak = 30;

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>Blogger WordPress Web Service</title>
<link rel="stylesheet" type="text/css" href="<?php echo BDWS_getPluginUrl(); ?>/assets/sbws.css"/>
</head>
<body>
<h1><a href="https://code.google.com/p/wordpress-web-service" target="_blank"><img src="<?php echo BDWS_getPluginUrl(); ?>/assets/sbws.png" alt="Blogger WordPress Web Service" width="265" height="73" border="0" /></a> Version <?php echo BDWS_getVersion(); ?></h1>
<table width="100%" border="0" cellspacing="0" cellpadding="0">
	<tr>
		<td><p>Welcome to your Blogger WordPress Web Service (SBWS) plugin!</p>
		<?php if(BDWS_WSDLcustomized()) { ?>
			<p>Add Description</p>
		<?php } ?>
			</td>
		<td>
		</td>
	</tr>
</table>
</body>
</html>
