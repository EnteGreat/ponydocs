<?php
if( !defined( 'MEDIAWIKI' ))
	die( "PonyDocs MediaWiki Extension" );

/**
 * Singleton class to manage our PonyDocs customized wiki.  This manages things likes the manual list, version list, and
 * methods to manage them.  Any hooks/extension type methods belong in our PonyDocsExtension class as static methods so
 * we can contain them in one place.  This will primarily be used as an access point and provide methods for returning
 * sets of data in template-ready form (as simple arrays) so that the templates never need to directly use our PonyDocs
 * classes.
 */
class PonyDocsWiki
{
	/**
	 * Our singleton instance.
	 *
	 * @var PonyDocsWiki
	 */
	static protected $instance = null;
		
	/**
	 * Made private to enforce singleton pattern.  On instantiation (through the first call to 'getInstance') we cache our
	 * versions and manuals [we don't save them we just cause them to load -- is this necessary?].
	 */
	private function __construct( )
	{
		/**
		 * @FIXME:  Only necessary in Documentation namespace!
		 */		
		PonyDocsVersion::LoadVersions( true );
		PonyDocsManual::LoadManuals( true );						
	}
	
	/**
	 * Return our static singleton instance of the class or initialize if not existing.
	 *
	 * @static
	 * @return PonyDocsWiki
	 */
	static public function &getInstance( )
	{
		if( !self::$instance )		
			self::$instance = new PonyDocsWiki( );					
		return self::$instance;
	}
	
	/**
	 * Return a simple associative array format for template output of all versions which apply to the supplied topic.
	 *
	 * @param PonyDocsTopic $pTopic Topic to obtain versions for.
	 * @return array
	 */
	public function getVersionsForTopic( PonyDocsTopic &$pTopic )
	{
		global $wgArticlePath;
		$versions = $pTopic->getVersions( );
		
		$out = array( );
		foreach( $versions as $v )
		{
			$out[] = array( 'name' => $v->getName( ), 'href' => str_replace( '$1', 'Category:V:' . $v->getName( ), $wgArticlePath ));
		}
		
		return $out;
	}
	
	/**
	 * This returns the list of available versions for template output in a more useful way for templates.  It is a simple list
	 * with each element being an associative array containing two keys:  name and status.
	 * 
	 * @FIXME:  If a version has NO defined manuals (i.e. no TOC pages for a manual tagged to it) it should be REMOVED from this
	 * list.
	 *
	 * @return array
	 */
	public function getVersionsForTemplate( )
	{
		$dbr = wfGetDB( DB_SLAVE );		
		$version = PonyDocsVersion::GetVersions( );
		$validVersions = $out = array( );
		
		/**
		 * This should give us one row per version which has 1 or more TOCs tagged to it.  So basically, if its not in this list
		 * it should not be displayed.
		 */
		$res = $dbr->query( "SELECT cl_to, COUNT(*) AS cl_to_ct 
							 FROM categorylinks 
							 WHERE LOWER(cast(cl_sortkey AS CHAR)) LIKE 'documentation:%toc%'
							 AND cl_to LIKE 'V:%' 
							 GROUP BY cl_to" );

		while( $row = $dbr->fetchObject( $res ))
			$validVersions[] = $row->cl_to;			
		
		foreach( $version as $v )
		{
			/**
			 * 	Only add it to our available list if its in our list of valid versions.
			 */
			if( in_array( 'V:' . $v->getName( ), $validVersions ))
				$out[] = array( 'name' => $v->getName( ), 'status' => $v->getStatus( ));
		}
		
		return $out;
	}
	
	/**
	 * This returns the list of available manuals (active ones) in a more useful way for templates.  It is an associative array
	 * where the key is the short name of the manual and the value is the display/long name.
	 *
	 * @return array
	 */
	public function getManualsForTemplate( )
	{
		PonyDocsVersion::LoadVersions(); 	// Dependency
		PonyDocsVersion::getSelectedVersion();
		PonyDocsManual::LoadManuals();	// Dependency
		$manuals = PonyDocsManual::GetManuals( );
		
		$out = array( );
		foreach( $manuals as $m )
			$out[$m->getShortName( )] = $m->getLongName( );					
			
		return $out;		
	}
	
	/**
	 * Populate toolbox link set and return.  Should be based on user groups/access.  Only used if MediaWiki:Sidebar is EMPTY.
	 *
	 * @return array
	 */
	public function generateSideBar( )
	{
		global $wgArticlePath, $wgScriptPath, $wgUser;
		
		$g = $wgUser->getAllGroups( );
		
		$sidebar = array( 'navigation' => array(
			array( 'text' => 'Main Page', 'href' => str_replace( '$1', 'Main_Page', $wgArticlePath )),
			array( 'text' => 'Help', 'href' => str_replace( '$1', 'helppage', $wgArticlePath ))
			)
		);
		
		/**
		 * Show Special pages if employee or author.
		 */
		if( in_array( PONYDOCS_AUTHOR_GROUP, $g ) || in_array( PONYDOCS_EMPLOYEE_GROUP, $g ))
			$sidebar['navigation'][] = array( 'text' => 'Special Pages', 'href' => str_replace( '$1', 'Special:Specialpages', $wgArticlePath ));
			
		/**
		 * TOC List Mgmt if author.
		 */
		if( in_array( PONYDOCS_AUTHOR_GROUP, $g ))
			$sidebar['navigation'][] = array( 'text' => 'TOC List Mgmt', 'href' => str_replace( '$1', 'Special:TOCList', $wgArticlePath ));
			
		//echo '<pre>'; print_r( $sidebar ); die( );
			
		return $sidebar;
	}
} ;

/**
 * End of file
 */
