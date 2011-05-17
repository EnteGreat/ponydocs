<?php
if( !defined( 'MEDIAWIKI' ))
	die( "PonyDocs MediaWiki Extension" );


/**
 * An instance represents a single PonyDocs product based upon the short/long name.  It also contains static
 * methods and data for loading the global list of products from the special page. 
 */
class PonyDocsProduct
{
	/**
	 * Short/abbreviated name for the product used in page paths;  it is always lowercase and should be
	 * alphabetic but is not required.
	 *
	 * @var string
	 */
	protected $mShortName;
	
	/**
	 * Long name for the product which functions as the 'display' name in the list of products and so
	 * forth.
	 *
	 * @var string
	 */
	protected $mLongName;
	
	/**
	 * Our list of products loaded from the special page, stored statically.  This only contains the products
	 * which have a TOC defined and tagged to the currently selected version.
	 *
	 * @var array
	 */
	static protected $sProductList = array( );

	/**
	 * Our COMPLETE list of products.
	 *
	 * @var array
	 */
	static protected $sDefinedProductList = array( );	
	
	/**
	 * Constructor is simply passed the short and long (display) name.  We convert the short name to lowercase
	 * immediately so we don't have to deal with case sensitivity.
	 *
	 * @param string $shortName Short name used to refernce product in URLs.
	 * @param string $longName Display name for product.
	 */
	public function __construct( $shortName, $longName = '' )
	{
		//$this->mShortName = strtolower( $shortName );
		$this->mShortName = preg_replace( '/([^' . PONYDOCS_PRODUCT_LEGALCHARS . '])/', '', $shortName );
		$this->mLongName = strlen( $longName ) ? $longName : $shortName;
	}

	public function getShortName( )
	{
		return $this->mShortName;
	}

	public function getLongName( )
	{
		return $this->mLongName;
	}

	/**
	 * This loads the list of products BASED ON whether each product defined has a TOC defined for the
	 * currently selected version or not.
	 *
	 * @param boolean $reload
	 * @return array
	 */

	static public function LoadProducts( $reload = false )
	{
		$dbr = wfGetDB( DB_SLAVE );

		/**
		 * If we have content in our list, just return that unless $reload is true.
		 */
		if( sizeof( self::$sProductList ) && !$reload )
			return self::$sProductList;

		self::$sProductList = array( );

		// Use 0 as the last parameter to enforce getting latest revision of 
		// this article.
		$article = new Article( Title::newFromText( PONYDOCS_DOCUMENTATION_PRODUCTS_TITLE ), 0);
		$content = $article->getContent( );

		if( !$article->exists( ))
		{
			/**
			 * There is no products file found -- just return.
			 */
			return array( );
		}

		/**
		 * The content of this topic should be of this form:
		 * {{#product:shortName|longName}}
		 * ...
		 * 
		 * There is a user defined parser hook which converts this into useful output when viewing as well.
		 * 
		 * Then query categorylinks to only add the product if it has a tagged TOC file with the selected version.
		 * Otherwise, skip it!
		 * NOTE product is the top entity, we need to verify better it has at least one version defined
		 */

		if( !preg_match_all( '/{{#product:\s*(.*)[|](.*)\s*}}/i', $content, $matches, PREG_SET_ORDER ))
			return array( );

		foreach( $matches as $m )
		{
			$pProduct = new PonyDocsProduct( $m[1], $m[2] );
			self::$sDefinedProductList[strtolower($pProduct->getShortName( ))] = $pProduct;

			//$res = $dbr->select( 'categorylinks', 'cl_to',
			//	array( 	"LOWER(cast(cl_sortkey AS CHAR)) LIKE 'documentation:" . strtolower( $pProduct->getShortName( )). "%toc%'",
			//			"cl_to = 'P:" . PonyDocsVersion::GetSelectedVersion( ) . "'" ), __METHOD__ );

			//if( !$res->numRows( )) {
			//	continue;
			//}

			self::$sProductList[strtolower($m[1])] = $pProduct;
		}

		return self::$sProductList;
	}
	
	/**
	 * Just an alias.
	 *
	 * @static
	 * @return array
	 */
	static public function GetProducts( )
	{
		return self::LoadProducts( );
	}
	
	/**
	 * Return list of ALL defined products regardless of selected version.
	 *
	 * @static 	
	 * @returns array
	 */
	static public function GetDefinedProducts( )
	{
		self::LoadProducts( );
		return self::$sDefinedProductList;
	}
	
	/**
	 * Our product list is a map of 'short' name to the PonyDocsProduct object.  Returns it, or null if not found.
	 *
	 * @static
	 * @param string $shortName
	 * @return PonyDocsProduct&
	 */
	static public function & GetProductByShortName( $shortName )
	{
		$convertedName = preg_replace( '/([^' . PONYDOCS_PRODUCT_LEGALCHARS . ']+)/', '', $shortName );
		if( self::IsProduct( $convertedName ))
			return self::$sDefinedProductList[strtolower($convertedName)];		
		return null;
	}
	
	/**
	 * Test whether a given product exists (is in our list).  
	 *
	 * @static
	 * @param string $shortName
	 * @return boolean
	 */
	static public function IsProduct( $shortName )
	{
		// We no longer specify to reload the product data, because that's just 
		// insanity.
		PonyDocsProduct::LoadProducts(false);
		// Should just force our products to load, just in case.
		$convertedName = preg_replace( '/([^' . PONYDOCS_PRODUCT_LEGALCHARS . ']+)/', '', $shortName );
		return isset( self::$sDefinedProductList[strtolower($convertedName)] );
	}

	/**
	 * Return the current product object based on the title object;  returns null otherwise.
	 *
	 * @static
	 * @return PonyDocsProduct
	 */
	static public function GetCurrentProduct($title = null )
	{
		global $wgTitle;
		$targetTitle = $title == null ? $wgTitle : $title;
		$pcs = explode( ':', $targetTitle->__toString( ));
		if( !PonyDocsProduct::IsProduct( $pcs[1] ))
			return null;
		return PonyDocsProduct::GetProductByShortName( $pcs[1] );
	}

	/**
	 * This returns the selected product for the current user.  This is stored in our session data, whether the user is
	 * logged in or not.  A special session variable 'wsProduct' contains it.  If it is not set we must apply some logic
	 * to auto-select the proper product.  Typically if it is not set it means the user just loaded the site for the
	 * first time this session and is thus not logged in, so its a safe bet to auto-select the most recent RELEASED
	 * product. We're only going to use sessions to track this. 
	 *	 
	 *
	 * @static
	 * @return string Currently selected product string.
	 */
	static public function GetSelectedProduct( )
	{
		global $wgUser, $_SESSION;

		$groups = $wgUser->getGroups();

		/**
	 	 * Do we have the session var and is it non-zero length?  Could also check if valid here.
		 */
		if( isset( $_SESSION['wsProduct'] ) && strlen( $_SESSION['wsProduct'] )) {
			// Make sure product exists.
			if(!array_key_exists($_SESSION['wsProduct'], self::$sProductList)) {
				unset($_SESSION['wsProduct']);
			}
			else {
				return $_SESSION['wsProduct'];
			}
		}
		/// If we are here there is no product set, use default product from configuration
		self::SetSelectedProduct(PONYDOCS_DEFAULT_PRODUCT);
		return $_SESSION['wsProduct'];
	}

	static public function SetSelectedProduct( $p )
	{
		global $_SESSION;
		$_SESSION['wsProduct'] = $p;
		return $p;
	}

}

/**
 * End of file.
 */
?>