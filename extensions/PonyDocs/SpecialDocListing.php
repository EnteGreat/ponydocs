<?php
if( !defined( 'MEDIAWIKI' ))
	die( "PonyDocs MediaWiki Extension" );

/**
 * Needed since we subclass it;  it doesn't seem to be loaded elsewhere.
 */
require_once( $IP . '/includes/SpecialPage.php' );

/**
 * Register our 'Special' page so it is listed and accessible.
 */
$wgSpecialPages['DocListing'] = 'SpecialDocListing';

/**
 * Special Page which lists all release versions, and their manuals.  Used for a 
 * start page which could be used for search crawl.
 */
class SpecialDocListing extends SpecialPage
{
	/**
	 * Just call the base class constructor and pass the 'name' of the page as defined in $wgSpecialPages.
	 */
	public function __construct( )
	{
		SpecialPage::__construct( "DocListing" );
	}
	
	public function getDescription( )
	{
		return 'Documentation Listing';
	}
	
	/**
	 * This is called upon loading the special page.  It should write output to the page with $wgOut.
	 */
	public function execute( )
	{
		global $wgOut, $wgScriptPath;
		global $wgUser;
		
		$dbr = wfGetDB( DB_SLAVE );

		$this->setHeaders( );	
		$wgOut->setPagetitle( 'Documentation Listing' );

		$wgOut->addHTML( 	"<p>This page lists all release versions as well as links to the manuals in each version.</p><br><br>" );

		ob_start();

		
		// Get all versions and iterate

		$versions = PonyDocsVersion::GetReleasedVersions(true);

		?>
		<ul>
		<?php
		foreach($versions as $version) {
			?>
			<li><?php echo $version->getName();?>
				<ul>
					<?php
					// Load Manuals for version
					PonyDocsVersion::SetSelectedVersion($version->getName());
					$navData = PonyDocsExtension::fetchNavdataForVersion($version->getName());
					foreach($navData as $nav) {
						$url = str_replace("Documentation/latest/", "Documentation/" . $version->getName() . "/", $nav['firstUrl']);
						?>
						<li><a href="<?php echo $url;?>"><?php echo $nav['longName'];?></a></li>
						<?php
					}
					?>
				</ul>
			</li>
			<?php
		}
		?>
		</ul>
		<?php

		$output = ob_get_clean();

		$wgOut->addHTML( $output );
	}
};
