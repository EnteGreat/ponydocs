<?php
if( !defined( 'MEDIAWIKI' ))
	die( "PonyDocs MediaWiki Extension" );

require_once( "$IP/extensions/PonyDocs/PonyDocsStaticDocImporter.php");

/**
 * Needed since we subclass it;  it doesn't seem to be loaded elsewhere.
 */
require_once( $IP . '/includes/SpecialPage.php' );

/**
 * Register our 'Special' page so it is listed and accessible.
 */
$wgSpecialPages['StaticDocImport'] = 'SpecialStaticDocImport';

/**
 * Special page to control static documentation import
 */
class SpecialStaticDocImport extends SpecialPage
{
	/**
	 * Just call the base class constructor and pass the 'name' of the page as defined in $wgSpecialPages.
	 */
	public function __construct()
	{
		SpecialPage::__construct( "StaticDocImport" );
	}

	public function getDescription()
	{
		return 'Static Documentation Import Tool';
	}

	/**
	 * This is called upon loading the special page.  It should write output to the page with $wgOut.
	 */
	public function execute()
	{
		global $wgOut, $wgArticlePath, $wgScriptPath, $wgUser;
		global $wgRequest;

		$this->setHeaders();

		$product = PonyDocsProduct::GetSelectedProduct( );
		$versions = PonyDocsProductVersion::LoadVersionsForProduct($product);

		$p = PonyDocsProduct::GetProductByShortName($product);
		$wgOut->setPagetitle( 'Static Documentation Import Tool' );
		$wgOut->addHTML( '<h2>Static Documentation Import for ' . $p->getLongName() . '</h2>' );
		if (!$p->isStatic()) {
			$wgOut->addHTML('<h3>' . $p->getLongName() . ' is not defined as a static product.</h3>');
		} else {

			$importer = new PonyDocsStaticDocImporter(PONYDOCS_STATIC_DIR);

			$action = NULL;
			if (isset($_POST['action'])) {
				$action = $_POST['action'];
			}

			switch ($action) {
				case "add":
					if (isset($_POST['version']) && isset($_POST['product'])) {
						if (PonyDocsProductVersion::IsVersion($_POST['product'], $_POST['version'])) {
							$wgOut->addHTML('<h3>Results of Import</h3>');
							// Okay, let's make sure we have file provided
							if (!isset($_FILES['archivefile']) || $_FILES['archivefile']['error'] != 0)  {
								$wgOut->addHTML('There was a problem using your uploaded file.  Make sure you uploaded a file and try again.');
							}
							else {
								try {
									$importer->importFile($_FILES['archivefile']['tmp_name'], $_POST['product'], $_POST['version']);
									$wgOut->addHTML('Success: imported archive for ' . $_POST['product'] . ' version ' . $_POST['version']);
								} catch (RuntimeException $e) {
									$wgOut->addHTML('Error: ' . $e->getMessage());
								}
							}
						}
					}
					break;

				case "remove":
					if (isset($_POST['version']) && isset($_POST['product'])) {
						if (PonyDocsProductVersion::IsVersion($_POST['product'], $_POST['version'])) {
							$wgOut->addHTML('<h3>Results of Deletion</h3>');
							try {
								$importer->removeVersion($_POST['product'], $_POST['version']);
								$wgOut->addHTML('Successfully deleted ' . $_POST['product'] . ' version ' . $_POST['version']);
							} catch (Exception $e) {
								$wgOut->addHTML('Error: ' . $e->getMessage());
							}
						}
					}
					break;

				default:
				$wgOut->addHTML('<h3>Import to Version</h3>');
					$wgOut->addHTML('<form action="/Special:StaticDocImport" method="post" enctype="multipart/form-data">
									<label for="archivefile">File to upload:</label><input id="archivefile" name="archivefile" type="file" />
									<input type="hidden" name="product" value="' . $product . '"/>');
					$versionSelectHTML = '<select name="version">';
					foreach ($versions as $version) {
						$versionSelectHTML .= '<option value="' . $version->getVersionName() . '">' . $version->getVersionName() . '</option>';
					}
					$versionSelectHTML .= '</select>';
					$wgOut->addHTML($versionSelectHTML);
					$wgOut->addHTML('<input type="hidden" name="action" value="add"/>
										<input type="submit" name="submit" value="Submit"/>
										</form>');
			}

			// display existing versions
			$wgOut->addHTML('<h3>Existing Content</h3>');
			$existingVersions = $importer->getExistingVersions($product);
			if (count($existingVersions) > 0) {
				$wgOut->addHTML('<script type="text/javascript">function verify_delete() {return confirm("Are you sure?");}</script>');
				$wgOut->addHTML('<table>');
				$wgOut->addHTML('<tr><th>Version</th><th></th></tr>');
				foreach ($existingVersions as $version) {
					$wgOut->addHTML("<tr>
										<td>$version</td>
										<td>
											<form method='POST' onsubmit=\"return verify_delete()\">
												<input type='submit' name='action' value='remove'/>
												<input type='hidden' name='product' value='$product'/>
												<input type='hidden' name='version' value='$version'/>
											</form>
										</td>
									</tr>
					");
				}
				$wgOut->addHTML('</tr></table>');
			} else {
				$wgOut->addHTML('<p>No existing version defined.</p>');
			}

		}
		$html = '<h2>Other Useful Management Pages</h2>' .
				'<a href="' . str_replace( '$1', PONYDOCS_DOCUMENTATION_PREFIX . $product . PONYDOCS_PRODUCTVERSION_SUFFIX, $wgArticlePath ) . '">Version Management</a> - Define and update available ' . $product . ' versions.<br />' .
				'<a href="' . str_replace( '$1', PONYDOCS_DOCUMENTATION_PREFIX . $product . PONYDOCS_PRODUCTMANUAL_SUFFIX, $wgArticlePath ) . '">Manuals Management</a> - Define the list of available manuals for the Documentation namespace.<br/><br/>';

		$wgOut->addHTML( $html );
	}
}

/**
 * End of file.
 */
?>