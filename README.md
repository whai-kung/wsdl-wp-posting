# wsdl-wp-posting

## Setup project
Please create a file name `sbws-config.php` under directory `includes` (/includes/sbws-config.php) with the code below
```
<?php

define("SBWS_TOKEN", "00000000-0000-0000-0000-000000000000");

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

?>
```

config according to your project.

### Note
This file will be in .gitignore due to the auto update reason.