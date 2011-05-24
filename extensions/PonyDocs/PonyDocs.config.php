<?php


// Define your user groups here
define('PONYDOCS_EMPLOYEE_GROUP', 'employees');
define('PONYDOCS_BASE_AUTHOR_GROUP', 'docteam');
define('PONYDOCS_BASE_PREVIEW_GROUP', 'preview');
define('PONYDOCS_CRAWLER_AGENT_REGEX', '/gsa/');


define('PONYDOCS_PRODUCT_NAME', 'Example Product');
define('PONYDOCS_PRODUCT_LOGO_URL', 'http://' . $_SERVER['SERVER_NAME'] . str_replace("$1", "", $wgArticlePath) . 'extensions/PonyDocs/images/pony.png');
define('PONYDOCS_DOCUMENTATION_NAMESPACE_NAME', 'Documentation');
define('PONYDOCS_DOCUMENTATION_NAMESPACE_ID', 100);

define('PONYDOCS_CACHE_ENABLED', true);
define('PONYDOCS_CACHE_DEBUG', true);
define('PONYDOCS_REDIRECT_DEBUG', false);
define('PONYDOCS_SESSION_DEBUG', false);

define('PONYDOCS_DOCUMENTATION_PREFIX', PONYDOCS_DOCUMENTATION_NAMESPACE_NAME . ':' );

define('PONYDOCS_DOCUMENTATION_PRODUCTS_TITLE', PONYDOCS_DOCUMENTATION_PREFIX . 'Products' );
define('PONYDOCS_PRODUCT_LEGALCHARS', 'A-Za-z0-9_,.-' );
define('PONYDOCS_PRODUCT_REGEX', '/([' . PONYDOCS_PRODUCT_LEGALCHARS . ']+)/' );

define('PONYDOCS_PRODUCTVERSION_SUFFIX', ':Versions' );
define('PONYDOCS_PRODUCTVERSION_LEGALCHARS', 'A-Za-z0-9_,.-' );
define('PONYDOCS_PRODUCTVERSION_REGEX', '/([' . PONYDOCS_PRODUCTVERSION_LEGALCHARS . ']+)/' );
define('PONYDOCS_PRODUCTVERSION_TITLE_REGEX', '/^' . PONYDOCS_DOCUMENTATION_PREFIX . '([' . PONYDOCS_PRODUCT_LEGALCHARS . ']+)' . PONYDOCS_PRODUCTVERSION_SUFFIX . '/' );

define('PONYDOCS_PRODUCTMANUAL_SUFFIX', ':Manuals' );
define('PONYDOCS_PRODUCTMANUAL_LEGALCHARS', 'A-Za-z0-9_,.-' );
define('PONYDOCS_PRODUCTMANUAL_REGEX', '/([' . PONYDOCS_PRODUCTMANUAL_LEGALCHARS . ']+)/' );
define('PONYDOCS_PRODUCTMANUAL_TITLE_REGEX', '/^' . PONYDOCS_DOCUMENTATION_PREFIX . '([' . PONYDOCS_PRODUCT_LEGALCHARS . ']+)' . PONYDOCS_PRODUCTMANUAL_SUFFIX . '/' );

define('PONYDOCS_PDF_COPYRIGHT_MESSAGE', 'Example Copyright Message');

define('PONYDOCS_DEFAULT_PRODUCT', 'Splunk');

// category cache expiration in seconds
define('CATEGORY_CACHE_TTL', 300);

// Implicit group for all visitors.
$wgGroupPermissions['*']['createaccount']   = false;
$wgGroupPermissions['*']['edit']            = false;
$wgGroupPermissions['*']['createpage']      = false;
$wgGroupPermissions['*']['upload']			= false;
$wgGroupPermissions['*']['reupload']		= false;
$wgGroupPermissions['*']['reupload-shared']	= false;
$wgGroupPermissions['*']['writeapi']        = false;
$wgGroupPermissions['*']['createtalk']      = false;
$wgGroupPermissions['*']['read']            = true;


// User is a logged in CUSTOMER account.
$wgGroupPermissions['user']['read']				= true;
$wgGroupPermissions['user']['createtalk']     		= false;
$wgGroupPermissions['user']['upload']			= false;
$wgGroupPermissions['user']['reupload']			= false;
$wgGroupPermissions['user']['reupload-shared']	= false;
$wgGroupPermissions['user']['edit']				= false;
$wgGroupPermissions['user']['move']				= false;
$wgGroupPermissions['user']['minoredit'] 		= false;
$wgGroupPermissions['user']['createpage']		= false;
$wgGroupPermissions['user']['writeapi']			= false;
$wgGroupPermissions['user']['move-subpages']	= false;
$wgGroupPermissions['user']['move-rootuserpages']= false;
$wgGroupPermissions['user']['purge']			= false;
$wgGroupPermissions['user']['sendemail']		= false;
$wgGroupPermissions['user']['writeapi']			= false;

$wgGroupPermissions[PONYDOCS_EMPLOYEE_GROUP]['read'] 			= true;
$wgGroupPermissions[PONYDOCS_EMPLOYEE_GROUP]['edit'] 			= true;
$wgGroupPermissions[PONYDOCS_EMPLOYEE_GROUP]['upload']			= true;
$wgGroupPermissions[PONYDOCS_EMPLOYEE_GROUP]['reupload']		= true;
$wgGroupPermissions[PONYDOCS_EMPLOYEE_GROUP]['reupload-shared']	= true;
$wgGroupPermissions[PONYDOCS_EMPLOYEE_GROUP]['minoredit']		= true;

// check for empty product list
if (!isset ($ponyDocsProductsList) || sizeof($ponyDocsProductsList) == 0){
	$ponyDocsProductsList[] = PONYDOCS_DEFAULT_PRODUCT;
}

// append empty group for backwards compabability with "docteam" and "preview" groups
$ponyDocsProductsList[] = '';

// these will be tweaked in PonyDocsExtension::onUserCan()
$editorPerms = array(
	'move' => true,
	'edit'   => true,
	'read' => true,
	'createpage' => true,
	'block' => true,
	'createaccount' => true,
	'delete' => true,
	'editinterface' => true,
	'import' => true,
	'importupload' => true,
	'move' => true,
	'patrol' => true,
	'autopatrol' => true,
	'protect' => true,
	'proxyunbannable' => true,
	'rollback' => true,
	'trackback' => true,
	'upload' => true,
	'reupload' => true,
	'reupload-shared' => true,
	'unwatchedpages' => true,
	'autoconfirmed' => true,
	'upload_by_url' => true,
	'ipblock-exempt' => true,
	'blockemail' => true,
	'deletedhistory' => true, // can view deleted history entries, but not see or restore the text
	'branchtopic' => true, // Custom permission to branch a single topic.
	'branchmanual' => true, // Custom permission to branch an entire manual.
	'inherit' => true, // Custom permission to inherit a topic.
	'viewall' => true, // Custom permission to handle View All link for topics.
);
	
foreach ($ponyDocsProductsList as $product){
	
	
	// check for empty product
	if ($product == ''){
		// allow for existing product-less base groups
		$convertedNameProduct = PONYDOCS_BASE_AUTHOR_GROUP;
		$convertedNamePreview = PONYDOCS_BASE_PREVIEW_GROUP;
	} else {
		// TODO: this should be a function that is shared instead
		// of being local, redundant logic
		$legalProduct = preg_replace( '/([^' . PONYDOCS_PRODUCT_LEGALCHARS . ']+)/', '', $product );

		$convertedNameProduct = $legalProduct.'-'.PONYDOCS_BASE_AUTHOR_GROUP;
		$convertedNamePreview = $legalProduct.'-'.PONYDOCS_BASE_PREVIEW_GROUP;

	}

	// push the above perms array into each product
	$wgGroupPermissions[$convertedNameProduct] = $editorPerms;
	
	// define one preview group as well
	$wgGroupPermissions[$convertedNamePreview]['read']  = true;

}

// make sure we have the name space
if (!isset ($wgExtraNamespaces[PONYDOCS_DOCUMENTATION_NAMESPACE_ID])){
	$wgExtraNamespaces[PONYDOCS_DOCUMENTATION_NAMESPACE_ID] = PONYDOCS_DOCUMENTATION_NAMESPACE_NAME;
}

?>