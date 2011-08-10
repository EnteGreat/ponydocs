<?php
if( !defined( 'MEDIAWIKI' ))
	die( "PonyDocs MediaWiki Extension" );

$wgSpecialPages['SpecialDocumentLinks'] = 'SpecialDocumentLinks';

/**
 * This page states the title is not available in the latest documentation 
 * version available to the user and gives the user a chance to view the topic 
 * in a previous version where it is available.
 */

class SpecialDocumentLinks extends SpecialPage {
	private $categoryName;
	private $skin;
	private $titles;

	/**
	 * Call the parent with our name.
	 */
	public function __construct() {
		SpecialPage::__construct("SpecialDocumentLinks");
	}

	/**
	 * Return our description.  Used in Special::Specialpages output.
	 */
	public function getDescription() {
		return "View the inbound links from other Documentation topics that links to a specific Documentation topic.";
	}

	/**
	 * This is called upon loading the special page.  It should write output to 
	 * the page with $wgOut
	 */
	public function execute($params) {
		global $wgOut, $wgArticlePath, $wgScriptPath, $wgUser;
		global $wgRequest;
		global $wgDBprefix;

		ob_start();

		$dbh = wfGetDB(DB_SLAVE);

		$this->setHeaders();
		$title =  $wgRequest->getVal('t');
		if (empty($title)) {
			$wgOut->setPagetitle("Documentation Linkage" );
			$wgOut->addHTML('No topic specified.');
			return;
		}
		$wgOut->setPagetitle("Documentation Linkage For " . $title );
		$dbr = wfGetDB( DB_SLAVE );
		$title = Title::newFromText($title);
		$article = new Article($title);
		$content = $article->getContent();
		$topic = new PonyDocsTopic($article);
		$versions = $topic->getProductVersions();
		//$versions = array();
		$mediaWikiTitle = $article->getTitle()->getFullText();
		$mediaWikiTitle = str_replace(" ", "_", $mediaWikiTitle);
		foreach($versions as $ver) {
		?>

			The following is in-bound links to <?php echo $title; ?> from other Documentation namespace topics.
			<h2>Version: <?php echo $ver->getVersionName(); ?></h2>

		<?php
			// Let's grab the mediawiki name of the topic.
			$humanReadableTitle = preg_replace('/' . PONYDOCS_DOCUMENTATION_PREFIX . '([^:]+):([^:]+):([^:]+):([^:]+)$/i', "Documentation/" . $ver->getProductName() . '/' . $ver->getVersionName() . "/$2/$3", $mediaWikiTitle);
			// $humanReadableTitle now contains human readable title.

			// Get our records.
			$res = $dbh->select($wgDBprefix . 'ponydocs_doclinks', '*', array('lower(to_link)' => strtolower($humanReadableTitle)));

			if($res->numRows()) {
				?><ul><?php
					foreach($res as $result) {
						?><li><a href="<?php echo str_replace('$1', $result->from_link, $wgArticlePath);?>"><?php echo $result->from_link;?></a></li><?php
					}
				?></ul><?php
			}
			else {
				?>No inbound links for this version.<?php
			}
		}

		$htmlContent = ob_get_contents();
		ob_end_clean();
		$wgOut->addHTML($htmlContent);
		return true;
	}

}

