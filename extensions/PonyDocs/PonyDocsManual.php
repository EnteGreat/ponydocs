<?php
if( !defined( 'MEDIAWIKI' ))
	die( "PonyDocs MediaWiki Extension" );


/**
 * An instance represents a single PonyDocs manual based upon the short/long name.  It also contains static
 * methods and data for loading the global list of manuals from the special page. 
 */
class PonyDocsManual
{
	/**
	 * Short/abbreviated name for the manual used in page paths;  it is always lowercase and should be
	 * alphabetic but is not required.
	 *
	 * @var string
	 */
	protected $mShortName;
	
	/**
	 * Long name for the manual which functions as the 'display' name in the list of manuals and so
	 * forth.
	 *
	 * @var string
	 */
	protected $mLongName;
	
	/**
	 * Our list of manuals loaded from the special page, stored statically.  This only contains the manuals
	 * which have a TOC defined and tagged to the currently selected version.
	 *
	 * @var array
	 */
	static protected $sManualList = array( );

	/**
	 * Our COMPLETE list of manuals.
	 *
	 * @var array
	 */
	static protected $sDefinedManualList = array( );	
	
	/**
	 * Constructor is simply passed the short and long (display) name.  We convert the short name to lowercase
	 * immediately so we don't have to deal with case sensitivity.
	 *
	 * @param string $shortName Short name used to refernce manual in URLs.
	 * @param string $longName Display name for manual.
	 */
	public function __construct( $shortName, $longName = '' )
	{
		//$this->mShortName = strtolower( $shortName );
		$this->mShortName = preg_replace( '/([^' . PONYDOCS_MANUAL_LEGALCHARS . '])/', '', $shortName );
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
	 * This loads the list of manuals BASED ON whether each manual defined has a TOC defined for the
	 * currently selected version or not.
	 *
	 * @param boolean $reload
	 * @return array
	 */
	
	static public function LoadManuals( $reload = false )
	{
		$dbr = wfGetDB( DB_SLAVE );
		
		/**
		 * If we have content in our list, just return that unless $reload is true.
		 */
		if( sizeof( self::$sManualList ) && !$reload )
			return self::$sManualList;
		
		self::$sManualList = array( );
			
		// Use 0 as the last parameter to enforce getting latest revision of 
		// this article.
		$article = new Article( Title::newFromText( PONYDOCS_DOCUMENTATION_MANUALS_TITLE ), 0);
		$content = $article->getContent( );

		if( !$article->exists( ))
		{
			/**
			 * There is no manuals file found -- just return.
			 */
			return array( );
		}

		/**
		 * The content of this topic should be of this form:
		 * {{#manual:shortName|longName}}
		 * ...
		 * 
		 * There is a user defined parser hook which converts this into useful output when viewing as well.
		 * 
		 * Then query categorylinks to only add the manual if it has a tagged TOC file with the selected version.
		 * Otherwise, skip it!
		 */
		
		if( !preg_match_all( '/{{#manual:\s*(.*)[|](.*)\s*}}/i', $content, $matches, PREG_SET_ORDER ))
			return array( );
			
		foreach( $matches as $m )
		{			
			$pManual = new PonyDocsManual( $m[1], $m[2] );			
			self::$sDefinedManualList[strtolower($pManual->getShortName( ))] = $pManual;
			
			$res = $dbr->select( 'categorylinks', 'cl_to', 
				array( 	"LOWER(cast(cl_sortkey AS CHAR)) LIKE 'documentation:" . strtolower( $pManual->getShortName( )). "toc%'",
						"cl_to = 'V:" . PonyDocsVersion::GetSelectedVersion( ) . "'" ), __METHOD__ );

			if( !$res->numRows( )) {
				continue ;			
			}
			
			self::$sManualList[strtolower($m[1])] = $pManual;
		}		
		
		return self::$sManualList;		
	}
	
	/**
	 * Just an alias.
	 *
	 * @static
	 * @return array
	 */
	static public function GetManuals( )
	{
		return self::LoadManuals( );
	}
	
	/**
	 * Return list of ALL defined manuals regardless of selected version.
	 *
	 * @static 	
	 * @returns array
	 */
	static public function GetDefinedManuals( )
	{
		self::LoadManuals( );
		return self::$sDefinedManualList;
	}
	
	/**
	 * Our manual list is a map of 'short' name to the PonyDocsManual object.  Returns it, or null if not found.
	 *
	 * @static
	 * @param string $shortName
	 * @return PonyDocsManual&
	 */
	static public function & GetManualByShortName( $shortName )
	{
		$convertedName = preg_replace( '/([^' . PONYDOCS_MANUAL_LEGALCHARS . ']+)/', '', $shortName );
		if( self::IsManual( $convertedName ))
			return self::$sDefinedManualList[strtolower($convertedName)];		
		return null;
	}
	
	/**
	 * Test whether a given manual exists (is in our list).  
	 *
	 * @static
	 * @param string $shortName
	 * @return boolean
	 */
	static public function IsManual( $shortName )
	{
		// We no longer specify to reload the manual data, because that's just 
		// insanity.
		PonyDocsManual::LoadManuals(false);
		// Should just force our manuals to load, just in case.
		$convertedName = preg_replace( '/([^' . PONYDOCS_MANUAL_LEGALCHARS . ']+)/', '', $shortName );
		return isset( self::$sDefinedManualList[strtolower($convertedName)] );
	}

	/**
	 * Return the current manual object based on the title object;  returns null otherwise.
	 *
	 * @static
	 * @return PonyDocsManual
	 */
	static public function GetCurrentManual($title = null )
	{
		global $wgTitle;
		$targetTitle = $title == null ? $wgTitle : $title;
		$pcs = split( ':', $targetTitle->__toString( ));
		if( !PonyDocsManual::IsManual( $pcs[1] ))
			return null;
		return PonyDocsManual::GetManualByShortName( $pcs[1] );
	}
};

/**
 * End of file.
 */
