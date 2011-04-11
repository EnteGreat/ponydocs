<?php
if( !defined( 'MEDIAWIKI' ))
	die( "PonyDocs MediaWiki Extension" );

/**
 * Class to manage versions in PonyDocs MediaWiki.  Each instance represents a defined version based upon the
 * PONYDOCS_DOCUMENTATION_VERSION_TITLE special page.  It also statically contains the complete list of defined versions and
 * their mappings.
 *
 * @
 */
class PonyDocsVersion
{
	/**
	 * Constants for status types available.
	 *
	 * @FIXME:  Use these instead!
	 *
	 * @staticvar
	 */
	const STATUS_RELEASED = 0;
	const STATUS_UNRELEASED = 1;
	const STATUS_PREVIEW = 2;
	const STATUS_UNKNOWN = -1;
	const STATUS_INVALID = -2;
	
	/**
	 * The name of the version, which can be of any form but is typically a decimal point delimited version
	 * number (3.9.1) or a "code name."
	 *
	 * @var string
	 */
	protected $mName = '';
	
	/**
	 * The status of this release;  one of:  released, unreleased, preview.  Should be checked.
	 *
	 * @var string
	 */
	protected $mStatus = '';
	
	/**
	 * Status code (see consts above).
	 *
	 * @var integer
	 */
	protected $mStatusCode = 0;
	
	/**
	 * STATIC MEMBERS
	 * 
	 * Class has dual use;  these hold info on ALL versions defined.  Each contains a list and a map.  The
	 * list is just simple PHP list storing objects.  We need this to be able to select the 'most recent'
	 * easily by using size-1.  The map is an associative array of version NAME to object, which is needed
	 * for lookups but cannot be used in a size-1 sort of way (?).  It's messy and I hate it but it works
	 * for now.  We store a complete list then a separate list for each type.
	 */
	static protected $sVersionList = array( );
	static protected $sVersionMap = array( );
	
	static protected $sVersionListReleased = array( );
	static protected $sVersionMapReleased = array( );
	
	static protected $sVersionListUnreleased = array( );
	static protected $sVersionMapUnreleased = array( );
	
	static protected $sVersionListPreview = array( );
	static protected $sVersionMapPreview = array( );
	
	/**
	 * Construct a representation of a single version.
	 * 
	 * @tbd Should check the $status to ensure it is one of the valid values or setup some static consts.
	 * 
	 * @param string $name Actual name of version, such as 1.0.2 or Foo.
	 * @param string $status Version status: released, unreleased, preview.
	 */
	public function __construct( $name, $status )
	{
		if( !preg_match( PONYDOCS_VERSION_REGEX, $name ))
		{
			$this->mStatusCode = PonyDocsVersion::STATUS_INVALID;
			return;
		}
			
		$this->mName = $name;
		$this->mStatus = strtolower( $status );
		$this->mStatusCode = PonyDocsVersion::StatusToInt( $this->mStatus );
	}
	
	/**
	 * Converts a status string to the integer const.
	 *
	 * @static 	
	 * @param string $status
	 * @returns integer
	 */
	static public function StatusToInt( $status )
	{
		$status = strtolower( $status );
		if( !strcmp( $status, 'released' ))
			return PonyDocsVersion::STATUS_RELEASED;
		else if( !strcmp( $status, 'unreleased' ))
			return PonyDocsVersion::STATUS_UNRELEASED;
		else if( !strcmp( $status, 'preview' ))
			return PonyDocsVersion::STATUS_PREVIEW;
		else	
			return PonyDocsVersion::STATUS_UNKNOWN;
	}
	
	/**
	 * Return the name of the version.
	 *
	 * @return string Name of version.
 	 */
	public function getName( )
	{
		return $this->mName;
	}
	
	/**
	 * Return the status of the version (released, unreleased, or preview).
	 *
	 * @return string Status string.
	 */
	public function getStatus( )
	{
		return $this->mStatus;
	}

	/**
	 * Return the status of the version (released, unreleased, or preview) as integer code.
	 *
	 * @return integer
	 */	
	public function getStatusCode( )
	{
		return $this->mStatusCode;
	}
	
	/**
	 * Return true if status code is valid, false if not.
	 *
	 * @return boolean
	 */
	public function isValid( )
	{
		return (( $this->mStatusCode == PonyDocsVersion::STATUS_UNKNOWN ) || ( $this->mStatusCode == PonyDocsVersion::STATUS_INVALID )) ? false : true;
	}
	
	/**
	 * This returns the selected version for the current user.  This is stored in our session data, whether the user is
	 * logged in or not.  A special session variable 'wsVersion' contains it.  If it is not set we must apply some logic
	 * to auto-select the proper version.  Typically if it is not set it means the user just loaded the site for the
	 * first time this session and is thus not logged in, so its a safe bet to auto-select the most recent RELEASED
	 * version. We're only going to use sessions to track this. 
	 *	 
	 *
	 * @static
	 * @return string Currently selected version string.
	 */
	static public function GetSelectedVersion( )
	{
		global $wgUser, $_SESSION;

		/**
	 	 * Do we have the session var and is it non-zero length?  Could also check if valid here.
		 */
		if( isset( $_SESSION['wsVersion'] ) && strlen( $_SESSION['wsVersion'] )) {
			return $_SESSION['wsVersion'];
		}
		
	
		/**
			* If we're here, we don't have a version set previously.
			* Get latest RELEASED version and set the our active version to it..
			*/
		if( sizeof( self::$sVersionListReleased )) {
			self::SetSelectedVersion( self::$sVersionListReleased[count(self::$sVersionListReleased)-1]->getName( ));
		}

		return $_SESSION['wsVersion'];	
	}

	/**
	 * This sets the selected version by updating the session variable.  There are times it seems this isn't working, with older
	 * sessions.. fix this somehow?  Call session_start() if session_id() has no strlen?
	 *
	 * @static
	 * @param string $v Version name to set.
	 * @return string Version which was set.
	 */
	static public function SetSelectedVersion( $v )
	{		
		global $_SESSION;
		$_SESSION['wsVersion'] = $v;
		return $v;		
	}	
		
	/**
	 * Loads our version data from the special page.  These are defined in the form:
	 * 	{{#version:name|status}}
	 *
	 * There is a special parser hook to handle outputting this in a clean form when viewing the page.  This updates our internal
	 * static maps and lists of versions (total and by each state) from this page.  The first call will load from the file, subsequent
	 * will just return the stored list (unless $reload=true).
	 *
	 * The list created does NOT simply contain all versions in the defined page -- it is dependent upon the GROUPS to which the 
	 * current user belongs.
	 *
	 *	- Anonymous (all):  Released ONLY.
	 *  - Customers/users:  Released AND preview ONLY.
	 *  - Emp/Author:		All.
	 *
	 * @FIXME:  Cache this?
	 *
	 * @static
	 * @param boolean $reload True to force reload from the wiki page.
	 * @return array LIST of all versions (not map!).
	 */
	static public function LoadVersions( $reload = false, $ignorePermissions = false )
	{
		global $wgUser;
		global $ponydocsMediaWiki;
		
		/**
		 * If we have content in our list, just return that unless $reload is true.
		 */
		if( sizeof( self::$sVersionList ) && !$reload )
			return self::$sVersionList;
		
		$groups = $wgUser->getGroups( );
			
		self::$sVersionList = array( );
		
		$title = Title::newFromText(PONYDOCS_DOCUMENTATION_VERSION_TITLE);

		$article = new Article( Title::newFromText( PONYDOCS_DOCUMENTATION_VERSION_TITLE ), 0);		

		$content = $article->getContent( );

		if( -1 == $article->mCounter )
		{
			/**
			 * There is no versions file found -- just return.
			 */
			return array( );
		}

		/**
		 * Parse our versions content which should be of the form:
		 * {{#version:name|status}}
		 * ...
		 * Validate 'STATUS' is valid; if it is not, we ignore it.  THis will populate
		 * $this->versionsList (and others) properly and return it.
		 */
		$versions = split( "\n", $content );
		foreach( $versions as $v )
		{
			$matches = preg_replace( '/{{#version:\s*(.*)\s*}}/i', '\\1', $v );
			$pcs = split( '\|', trim( $matches ), 2 );
		
			$pVersion = new PonyDocsVersion( $pcs[0], $pcs[1] );
			if( !$pVersion->isValid( ))
				continue;
			
			if( !strcasecmp( $pcs[1], 'UNRELEASED' ))
			{			
				if(in_array( PONYDOCS_EMPLOYEE_GROUP, $groups ) || in_array( PONYDOCS_AUTHOR_GROUP, $groups ) || (isset($_SERVER['HTTP_USER_AGENT']) && preg_match(PONYDOCS_CRAWLER_AGENT_REGEX, $_SERVER['HTTP_USER_AGENT'])) || (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] == $ponydocsMediaWiki['CrawlerAddress']) || $ignorePermissions)
					self::$sVersionList[] = self::$sVersionListUnreleased[] = self::$sVersionMap[$pcs[0]] = self::$sVersionMapUnreleased[$pcs[0]] = $pVersion;
			}
			else if( !strcasecmp( $pcs[1], 'RELEASED' ))
			{
				self::$sVersionList[] = self::$sVersionListReleased[] = self::$sVersionMap[$pcs[0]] = self::$sVersionMapReleased[$pcs[0]] = $pVersion;
			}
			else if( !strcasecmp( $pcs[1], 'PREVIEW' ))
			{
				if( in_array( PONYDOCS_EMPLOYEE_GROUP, $groups ) || in_array( PONYDOCS_AUTHOR_GROUP, $groups ) || in_array( PONYDOCS_CUSTOMER_GROUP, $groups ) || (isset($_SERVER['HTTP_USER_AGENT']) && preg_match(PONYDOCS_CRAWLER_AGENT_REGEX, $_SERVER['HTTP_USER_AGENT'])) || (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] == $ponydocsMediaWiki['CrawlerAddress']) || $ignorePermissions)
					self::$sVersionList[] = self::$sVersionListPreview[] = self::$sVersionMap[$pcs[0]] = self::$sVersionMapPreview[$pcs[0]] = $pVersion;
			}
		}		

		return self::$sVersionList;		
	}
	
	/**
	 * Returns whether or not a supplied version is defined.
	 *
	 * @static 	
	 * @param string $version Name of version to check.
	 * @return boolean
	 */
	static public function IsVersion( $version )
	{
		if( preg_match( '/^v:(.*)/i', $version, $match ))
			$version = $match[1];
		return isset( self::$sVersionMap[$version] );
	}
	
	/**
	 * Retrieve a PonyDocsVersion instance by version name, or return null if it does not exist.
	 *
	 * @static
	 * @param string $name Name of version to retrieve.
	 * @return PonyDocsVersion
	 */
	static public function & GetVersionByName( $name )
	{		
		if( preg_match( '/^v:(.*)/i', $name, $match ))	
			$name = $match[1];	
		if( isset( self::$sVersionMap[$name] ))
			return self::$sVersionMap[$name] ;
		
		//echo '<pre>';print_r( self::$sVersionMap );echo '</pre>';
		
		// Okay, crappy fix to resolve the issue of Only variable references 
		// should be returned by reference that's been happening for some time 
		// now.
		$result = false;
		return $result;
	}
	
	/**
	 * Alias for LoadVersions.
	 *
	 * @static
	 * @return array Returns out version LIST.
	 */
	static public function GetVersions( $asMap = false )
	{
		$v = self::LoadVersions( );
		if( $asMap )
			return self::$sVersionMap;
		return $v;
	}
	
	/**
	 * Return a list of our versions in released state.
	 *
	 * @param boolean $asMap Should we return the map version
	 * @static
	 * @return array
	 */
	static public function & GetReleasedVersions( $asMap = false )
	{
		if($asMap)
			return self::$sVersionMapReleased;
		return self::$sVersionListReleased;
	}
	
	/**
	 * Return a list of our unreleased versions.
	 *
	 * @static 	
	 * @return array
	 */	
	static public function & GetUnreleasedVersions( )
	{
		return self::$sVersionListUnreleased;
	}
	
	/**
	 * Return a list of our preview versions.
	 *
	 * @static 
	 * @return array
	 */
	static public function & GetPreviewVersions( )
	{
		return self::$sVersionListPreview;
	}

	static public function & GetLatestVersion( )
	{
		if( sizeof( self::$sVersionList ))
			return self::$sVersionList[sizeof( self::$sVersionList )-1];
		return null;	
	}
	
	static public function & GetLatestReleasedVersion( )
	{
		if( sizeof( self::$sVersionListReleased )) {
			return self::$sVersionListReleased[sizeof( self::$sVersionListReleased )-1];
		}
		return null;			
	}
	
	static public function & GetLatestUnreleasedVersion( )
	{
		if( sizeof( self::$sVersionListUnreleased ))
			return self::$sVersionListUnreleased[sizeof( self::$sVersionListUnreleased )-1];
		return null;			
	}
	
	static public function & GetLatestPreviewVersion( )
	{
		if( sizeof( self::$sVersionListPreview ))
			return self::$sVersionListPreview[sizeof( self::$sVersionListPreview )-1];
		return null;			
	}	
	
	/**
	 * Return an array which is simply a list of versions in ascending order that the current user has access to based
	 * on group membership.  This is:
	 *
	 *	sysop/author/employee:	All.
	 *	user: All released and preview versions.
	 *	anon: All released versions.
	 *
	 * Result is a mapping of version name to PonyDocsVersion objects.
	 *
	 * @FIXME:  I don't think we need this?  The LoadVersions() method only loads versions from the defined list based
	 * on the user's permissions anyway.
	 *
	 * @static
	 * @return array Map of PonyDocsVersion instances (name => object).
	 */
	static public function GetVersionsForUser( )
	{
		global $wgUser;
		$groups = $wgUser->getGroups( );
		
		if( in_array( PONYDOCS_AUTHOR_GROUP, $groups ) || in_array( PONYDOCS_EMPLOYEE_GROUP, $groups ) || preg_match(PONYDOCS_CRAWLER_AGENT_REGEX,$_SERVER['HTTP_USER_AGENT']) || $_SERVER['REMOTE_ADDR'] == $ponydocsMediaWiki['CrawlerAddress'])
		{
			return self::$sVersionMap;	
		}
		else if( in_array( PONYDOCS_CUSTOMER_GROUP, $groups ))
		{
			$retList = array( );
			foreach( self::$sVersionMap as $pVersion )
			{
				if(	( $pVersion->getStatusCode( ) == PonyDocsVersion::STATUS_RELEASED ) ||
					( $pVersion->getStatusCode( ) == PonyDocsVersion::STATUS_PREVIEW ))
					$retList[] = $pVersion;					
			}
			return $retList;
		}
		else
		{
			return self::$sVersionMapReleased;
		}		
	}
			
	/**
	 * Given a LIST of PonyDocsVersion objects determine which of them is the "earliest" version based upon our internal list of
	 * versions (which are ordered).
	 *
	 * @static
	 * @param array $versionList
	 * @return PonyDocsVersion
	 */
	static public function findEarliest( $versionList = array( ))
	{		
		$earliest = -1;
		
		foreach( $versionList as $pV )
		{
			$idx = array_search( $pV, self::$sVersionList );
			if( $idx === false )
				continue;
			if( -1 == $earliest )
				$earliest = $idx;
			else
			{
				if( $idx < $earliest )
					$earliest = $idx;
			}
		}
		
		return isset( self::$sVersionList[$earliest] ) ? self::$sVersionList[$earliest] : null;
	}
};

/**
 * This is a callback for usort() which compares two versions to determine which is earlier.  It is passed
 * two PonyDocsVersion instances and returns -1 if $vA is earlier than $vB, 0 if they are the same, and 1 if
 * $vB is earlier than $vA.  You can then use:
 *	usort( $versionList, _ponydocs_versionCmp )
 * And $versionList will come back sorted.
 * 
 * @param PonyDocsVersion $vA First version to compare.
 * @param PonyDocsVersion $vB Second version to compare.
 * @return integer
 */
function PonyDocs_versionCmp( $vA, $vB )
{	
	$versions = PonyDocsVersion::GetVersions( );

	$indexA = array_search( $vA, $versions );
	$indexB = array_search( $vB, $versions );
	
	if( $indexA == $indexB )
		return 0;
		
	return ( $indexA < $indexB ) ? -1 : 1;
}

/**
 * End of file.
 */
