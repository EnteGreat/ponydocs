<?php

// Define your user groups here
define('PONYDOCS_EMPLOYEE_GROUP', 'employees');
define('PONYDOCS_AUTHOR_GROUP', 'docteam');
define('PONYDOCS_CUSTOMER_GROUP', 'customers');
define('PONYDOCS_CRAWLER_AGENT_REGEX', '/gsa/');

define('PONYDOCS_PRODUCT_NAME', 'Example Product');
define('PONYDOCS_PRODUCT_LOGO_URL', 'http://' . $_SERVER['SERVER_NAME'] . str_replace("$1", "", $wgArticlePath) . 'extensions/PonyDocs/images/pony.png');
define('PONYDOCS_DOCUMENTATION_NAMESPACE_NAME', 'Documentation');
define('PONYDOCS_DOCUMENTATION_NAMESPACE_ID', 100);

define('PONYDOCS_CACHE_ENABLED', true);
define('PONYDOCS_CACHE_DEBUG', true);


define( 'PONYDOCS_DOCUMENTATION_VERSION_TITLE', 'Documentation:Versions' );
define( 'PONYDOCS_VERSION_REGEX', '/([A-Za-z0-9.,_-]+)/' );


define( 'PONYDOCS_DOCUMENTATION_MANUALS_TITLE', 'Documentation:Manuals' );
define( 'PONYDOCS_MANUAL_LEGALCHARS', 'A-Za-z0-9_,.-' );
define( 'PONYDOCS_MANUAL_REGEX', '/([' . PONYDOCS_MANUAL_LEGALCHARS . ']+)/' );

define('PONYDOCS_PDF_COPYRIGHT_MESSAGE', 'Example Copyright Message');

