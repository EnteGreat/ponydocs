<?php
if( !defined( 'MEDIAWIKI' ))
	die( "PonyDocs MediaWiki Extension" );

$wgSpecialPages['LatestDoc'] = 'SpecialLatestDoc';


/**
 * This page states the title is not available in the latest documentation 
 * version available to the user and gives the user a chance to view the topic 
 * in a previous version where it is available.
 */

class SpecialLatestDoc extends SpecialPage {
	private $categoryName;
	private $skin;
	private $titles;
	
	/**
	 * Call the parent with our name.
	 */
	public function __construct() {
		SpecialPage::__construct("SpecialLatestDoc");
	}

	/**
	 * Return our description.  Used in Special::Specialpages output.
	 */
	public function getDescription() {
		return "View the latest version that the requested documentation is available in.";
	}

	/**
	 * This is called upon loading the special page.  It should write output to 
	 * the page with $wgOut
	 */
	public function execute($params) {
		global $wgOut, $wgArticlePath, $wgScriptPath, $wgUser;
		global $wgRequest;

		$this->setHeaders();
		$title =  $wgRequest->getVal('t');
		$wgOut->setPagetitle("Latest Documentation For " .$title );

		$dbr = wfGetDB( DB_SLAVE );

		/**
		 * We only care about Documentation namespace for rewrites and they must contain a slash, so scan for it.
		 * $matches[1] = product
		 * $matches[2] = latest|version
		 * $matches[3] = manual
		 * $matches[4] = topic
		 */
		if( !preg_match( '/^' . PONYDOCS_DOCUMENTATION_NAMESPACE_NAME . '\/(.*)\/(.*)\/(.*)\/(.*)$/i', $title, $matches )) {
			?>
			<p>
			Sorry, but <?php echo $title;?> is not a valid Documentation url.
			</p>
			<?php
		}
		else {
			/**
			 * At this point $matches contains:
			 * 	0= Full title.
			 *  1= Product name (short name).
			 *  2= Manual name (short name).
			 *  3= Version OR 'latest' as a string.
			 *  4= Wiki topic name.
			 */
			PonyDocsProductVersion::LoadVersions();		// Load versions from DB
			$productName = $matches[1];
			$versionName = $matches[2];
			$version = '';
			if(strcasecmp('latest', $versionName)) {
				?>
				<p>
				Sorry, but <?php echo $title;?> is not a latest Documentation url.
				</p>
				<?php
			}
			if( !strcasecmp( 'latest', $versionName ))
			{
				/**
				 * This will be a DESCENDING mapping of version name to PonyDocsVersion object and will ONLY contain the
				 * versions available to the current user (i.e. LoadVersions() only loads the ones permitted).
				 */
				$versionList = array_reverse( PonyDocsProductVersion::GetReleasedVersions( $productName, true ));
				$versionNameList = array( );
				foreach( $versionList as $pV )
					$versionNameList[] = $pV->getVersionName( );

				/**
				 * Now get a list of version names to which the current topic is mapped in DESCENDING order as well
				 * from the 'categorylinks' table.
				 *
				 * DB can't do descending order here, it depends on the order defined in versions page!  So we have to
				 * do some magic sorting below.
				 */
				$res = $dbr->select( 'categorylinks', 'cl_to', 
									 "LOWER(cast(cl_sortkey AS CHAR)) LIKE 'documentation:" . $dbr->strencode( strtolower( $matches[1] . ':' . $matches[2] . ':' . $matches[3] )) . ":%'",
									 __METHOD__ );

				if( !$res->numRows( ))
				{
					/**
					 * What happened here is we requested a topic that does not exist or is not linked to any version.
					 * Perhaps setup a default redirect, Main_Page or something?
					 */
					if (PONYDOCS_REDIRECT_DEBUG) {error_log("DEBUG [" . __CLASS__ . "::" . __METHOD__ . "] redirecting to $wgScriptPath/" . PONYDOCS_DOCUMENTATION_NAMESPACE_NAME . " [" . __FILE__ . ":" . __LINE__ . "]");}
					header('Location: ' . $wgScriptPath . '/' . PONYDOCS_DOCUMENTATION_NAMESPACE_NAME);
					exit( 0 );
				}

				/**
				 * Based on our list, get the PonyDocsVersion for each version tag and store in an array.  Then pass this array
				 * to our custom sort function via usort() -- the ending result is a sorted list in $existingVersions, with the
				 * LATEST version at the front.
				 * 
				 */
				$existingVersions = array( );
				while( $row = $dbr->fetchObject( $res ))
				{
					if( preg_match( '/^V:(.*):(.*)/i', $row->cl_to, $vmatch ))
					{
						$pVersion = PonyDocsProductVersion::GetVersionByName( $vmatch[1], $vmatch[2] );
						if( $pVersion && !in_array( $pVersion, $existingVersions )) {
							$existingVersions[] = $pVersion;
						}
					}
				}
				if(count($existingVersions) == 0) {
					// If this happens, this is because it's possible the user 
					// doesn't have access to any of the versions this topic is 
					// linked to.  In this situation, our default behavior is to 
					// redirect to our base homepage.
					if (PONYDOCS_REDIRECT_DEBUG) {error_log("DEBUG [" . __CLASS__ . "::" . __METHOD__ . "] redirecting to $wgScriptPath/" . PONYDOCS_DOCUMENTATION_NAMESPACE_NAME . " [" . __FILE__ . ":" . __LINE__ . "]");}
					header('Location: ' . $wgScriptPath . '/' . PONYDOCS_DOCUMENTATION_NAMESPACE_NAME);
					exit(0);
				}
				usort( $existingVersions, "PonyDocs_ProductVersionCmp" );
				$existingVersions = array_reverse( $existingVersions );

				// $existingVersions[0] points to the latest version this document 
				// is in
				// If this document is in the latest version, then let's go 
				// ahead and redirect over to it.
				if(count($existingVersions) && $existingVersions[0]->getVersionName() == $versionNameList[0]) {
					if (PONYDOCS_REDIRECT_DEBUG) {error_log("DEBUG [" . __CLASS__ . "::" . __METHOD__ . "] redirecting to $wgScriptPath/$title [" . __FILE__ . ":" . __LINE__ . "]");}
					header("Location: " . $wgScriptPath . "/" . $title);
					exit(0);
				}
				// If we are here, we should FORCE the user to be viewing the 
				// latest documentation, and report the issue with the topic not 
				// being in the latest.
				$_SESSION['wsVersion'][$productName] = $versionNameList[0];
				?>
				<p>
				Hi! Just wanted to let you know:
				</p>
				<p>
				The topic you've asked to see does not apply to the most recent version.
				</p>
				<p>
				<ul>
					<li>To search the latest version of the documentation, click <a href="<?php echo $wgScriptPath;;?>/Special:Search?search=<?php echo $matches[4];?>">Search</a></li>
					<li>To look at this topic anyway, click <a href="/<?php echo PONYDOCS_DOCUMENTATION_NAMESPACE_NAME;?>/<?php echo $existingVersions[0]->getProductName();?>/<?php echo $existingVersions[0]->getVersionName();?>/<?php echo $matches[3];?>/<?php echo $matches[4];?>">here</a>.</li>
				</ul>
				</p>
				<?php
			}
		}

		$htmlContent = ob_get_contents();
		ob_end_clean();
		$wgOut->addHTML($htmlContent);
		return true;
	}

}

?>