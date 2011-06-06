<?php

error_reporting(E_ALL | E_STRICT | E_DEPRECATED);
ini_set('display_errors', 1);
ini_set('date.timezone', 'America/Los_Angeles');

define('RC_SERVER_IP', '10.3.1.55');
define('TEST_HOST', 'lightswitch-ponydocs.splunk.com');

function autoload($class_name)
{
    require str_replace('_', DIRECTORY_SEPARATOR, $class_name) . '.php';
}

spl_autoload_register('autoload');