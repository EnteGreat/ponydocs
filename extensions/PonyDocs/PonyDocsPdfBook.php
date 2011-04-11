<?php
/**
 * PonyDocsPdfBook extension
 * - Composes a book from documentation and exports as a PDF book
 * - Derived from PdfBook Mediawiki Extension
 *
 * @package MediaWiki
 * @subpackage Extensions
 * @author Taylor Dondich tdondich@splunk.com 
 * @licence GNU General Public Licence 2.0 or later
 */
if (!defined('MEDIAWIKI')) die('Not an entry point.');

define('PONYDOCS_PDFBOOK_VERSION', '1.1, 2010-04-22');

$wgExtensionCredits['parserhook'][] = array(
	'name'	      => 'PonyDocsPdfBook',
	'author'      => 'Taylor Dondich and [http://www.organicdesign.co.nz/nad User:Nad]',
	'description' => 'Composes a book from documentation and exports as a PDF book',
	'url'	      => 'http://www.splunk.com',
	'version'     => PONYDOCS_PDFBOOK_VERSION
	);

// Catch the pdfbook action
$wgHooks['UnknownAction'][] = "PonyDocsPdfBook::onUnknownAction";

// Add a new pdf log type
$wgLogTypes[]             = 'ponydocspdf';
$wgLogNames['ponydocspdf']      = 'ponydocspdflogpage';
$wgLogHeaders['ponydocspdf']      = 'ponydocspdflogpagetext';
$wgLogActions['ponydocspdf/book'] = 'ponydocspdflogentry';


class PonyDocsPdfBook {

	/**
	 * Called when an unknown action occurs on url.  We are only interested in 
	 * pdfbook action.
	 */
	function onUnknownAction($action, $article) {
		global $wgOut, $wgUser, $wgTitle, $wgParser, $wgRequest;
		global $wgServer, $wgArticlePath, $wgScriptPath, $wgUploadPath, $wgUploadDirectory, $wgScript;

		// We don't do any processing unless it's pdfbook
		if($action != 'pdfbook') {
			return true;
		}

		// Get the title and make sure we're in Documentation namespace
		$title = $article->getTitle();
		if($title->getNamespace() != PONYDOCS_DOCUMENTATION_NAMESPACE_ID) {
			return true;
		}


		// Grab parser options for the logged in user.
		$opt = ParserOptions::newFromUser($wgUser);

		# Log the export
		$msg = $wgUser->getUserPage()->getPrefixedText().' exported as a PonyDocs PDF Book';
		$log = new LogPage('ponydocspdfbook', false);
		$log->addEntry('book', $wgTitle, $msg);

		# Initialise PDF variables
		$layout  = '--firstpage toc';
		$left    = '1cm';
		$right   = '1cm';
		$top     = '1cm';
		$bottom  = '1cm';
		$font    = 'Arial';
		$size    = '12';
		$linkcol = '4d9bb3';
		$levels  = '2';
		$exclude = array();
		$width   = '1024';
		$width   = "--browserwidth 1024";

		// Determine articles to gather
		$articles = array();
		$ponydocs = PonyDocsWiki::getInstance();
		$pieces = split(":", $wgTitle->__toString());
		// Try and get rid of the TOC portion of the title
		if(strpos($pieces[1], "TOC")) {
			$pieces[1] = substr($pieces[1], 0, strpos($pieces[1], "TOC"));
		}
		if(PonyDocsManual::isManual($pieces[1])) {
			$pManual = PonyDocsManual::GetManualByShortName($pieces[1]);
		}

		$versionText = PonyDocsVersion::GetSelectedVersion();

		if(!empty($pManual)) {
			// We should always have a pManual, if we're printing 
			// from a TOC
			$v = PonyDocsVersion::GetVersionByName($versionText);

			// We have our version and our manual
			// Check to see if a file already exists for this combination
			$pdfFileName = "$wgUploadDirectory/ponydocspdf-" . $versionText . "-" . $pManual->getShortName() . "-book.pdf";
			// Check first to see if this PDF has already been created and 
			// is up to date.  If so, serve it to the user and stop 
			// execution.
			if(file_exists($pdfFileName)) {
				error_log("INFO [PonyDocsPdfBook::onUnknownAction] " . php_uname('n') . ": cache serve username=\"" . $wgUser->getName() . "\" version=\"" . $versionText ."\" " . " manual=\"" . $pManual->getShortName() . "\"");
				PonyDocsPdfBook::servePdf($pdfFileName, $versionText, $pManual->getShortName());
				// No more processing
				return false;
			}
			// Oh well, let's go on our merry way and create our pdf.

			$toc = new PonyDocsTOC($pManual, $v);
			list($manualtoc, $tocprev, $tocnext, $tocstart) = $toc->loadContent();

			// We successfully got our table of contents.  It's 
			// stored in $manualtoc
			foreach($manualtoc as $tocEntry) {
				if($tocEntry['level'] > 0 && strlen($tocEntry['title']) > 0) {
					$title = Title::newFromText($tocEntry['title']);
					$articles[$tocEntry['section']][] = array('title' => $title, 'text' => $tocEntry['text']);
				}
			}
		}
		else {
			error_log("ERROR [PonyDocsPdfBook::onUnknownAction] " . php_uname('n') . ": User attempted to print a pdfbook from a non TOC page with path:" . $wgTitle->__toString());
		}

		# Format the article(s) as a single HTML document with absolute URL's
		$book = $pManual->getLongName();
		$html = '';
		$wgArticlePath = $wgServer.$wgArticlePath;
		$wgScriptPath  = $wgServer.$wgScriptPath;
		$wgUploadPath  = $wgServer.$wgUploadPath;
		$wgScript      = $wgServer.$wgScript;
		$currentSection = '';
		foreach ($articles as $section => $subarticles) {
			foreach($subarticles as $article) {
				$title = $article['title'];
				$h2 = "<h2>" . $article['text'] . "</h2>";
				$ttext = $title->getPrefixedText();
				if (!in_array($ttext, $exclude)) {
					if($currentSection != $section) {
						$html .= '<h1>' . $section . '</h1>';
						$currentSection = $section;
					}		
					$article = new Article($title);
					$text    = $article->fetchContent();
					$text    = preg_replace('/<!--([^@]+?)-->/s', '@@'.'@@$1@@'.'@@', $text); # preserve HTML comments
						$text   .= '__NOTOC__';
					$opt->setEditSection(false);    # remove section-edit links
						$wgOut->setHTMLTitle($ttext);   # use this so DISPLAYTITLE magic works
						$out     = $wgParser->parse($text, $title, $opt, true, true);
					$ttext   = $wgOut->getHTMLTitle();
					$text    = $out->getText();
					$text = preg_replace('|<h1>|', "<h2>", $text);
					$text = preg_replace('|<h2>|', "<h3>", $text);
					$text = preg_replace('|<h3>|', "<h4>", $text);
					$text = preg_replace('|<h4>|', "<h5>", $text);
					$text = preg_replace('|<h5>|', "<h6>", $text);

					// Link removal
					$text = preg_replace('|<a[^\<]*>|', '', $text);
					$text = preg_replace('|</a>|', '', $text);

					$text    = preg_replace('|(<img[^>]+?src=")(/.+?>)|', "$1$wgServer$2", $text); 
					$text    = preg_replace('|<div\s*class=[\'"]?noprint["\']?>.+?</div>|s', '', $text); # non-printable areas
					$text    = preg_replace('|@{4}([^@]+?)@{4}|s', '<!--$1-->', $text);                  # HTML comments hack
					$ttext   = basename($ttext);
					$html   .= utf8_decode("$h2$text\n");
				}
			}
		}

		# Write the HTML to a tmp file
		$file = "$wgUploadDirectory/".uniqid('ponydocs-pdf-book');
		$fh = fopen($file, 'w+');
		fwrite($fh, $html);
		fclose($fh);

		// Okay, create the title page
		$titlepagefile = "$wgUploadDirectory/" .uniqid('ponydocs-pdf-book-title');
		$fh = fopen($titlepagefile, 'w+');

		$titleText = "<br><br><br><br><center><img src=\"" . PONYDOCS_PRODUCT_LOGO_URL .  "\"><br /><h2>" . PONYDOCS_PRODUCT_NAME . " " . $book . "</h2><h3>Version: " . $versionText . "</h3><h4>Generated: " . date('n/d/Y h:i a', time()) . "<br /> " . PONYDOCS_PDF_COPYRIGHT_MESSAGE . "</h4>";

		fwrite($fh, $titleText);
		fclose($fh);

		$footer = $format == 'single' ? '...' : '.1.';
		$toc    = $format == 'single' ? '' : " --toclevels $levels";

		# Send the file to the client via htmldoc converter
		$wgOut->disable();
		$cmd  = "--left $left --right $right --top $top --bottom $bottom";
		$cmd .= " --header ... --footer $footer --bodyfont Helvetica --quiet --jpeg --color";
		$cmd .= " --bodyfont $font --fontsize $size --linkstyle underline --linkcolor $linkcol";
		$cmd .= "$toc --format pdf14 $layout $width --titlefile $titlepagefile";
		$cmd  = "htmldoc -t pdf --book --charset iso-8859-1 --no-numbered $cmd $file > $pdfFileName";
		putenv("HTMLDOC_NOCGI=1");
		$output = array();
		$returnVar = 0;
		exec($cmd, $output, $returnVar);
		if($returnVar != 5) {	// Why is htmldoc's success return code 5?  Try to be different htmldoc, go for it.
			error_log("INFO [PonyDocsPdfBook::onUnknownAction] " . php_uname('n') . ": Failed to run htmldoc (" . $returnVar . ") Output is as follows: " . implode("-", $output));
			print("Failed to create PDF.  Our team is looking into it.");
		}
		// Delete the htmlfile and title file from the filesystem.
		@unlink($file);
		if(file_exists($file)) {
			error_log("ERROR [PonyDocsPdfBook::onUnknownAction] " . php_uname('n') . ": Failed to delete temp file $file");
		}
		@unlink($titlepagefile);
		if(file_exists($titlepagefile)) {
			error_log("ERROR [PonyDocsPdfBook::onUnknownAction] " . php_uname('n') . ": Failed to delete temp file $titlepagefile");
		}
		// Okay, let's add an entry to the error log to dictate someone 
		// requested a pdf
		error_log("INFO [PonyDocsPdfBook::onUnknownAction] " . php_uname('n') . ": fresh serve username=\"" . $wgUser->getName() . "\" version=\"$versionText\" " . " manual=\"" . $book . "\"");
		PonyDocsPdfBook::servePdf($pdfFileName, $versionText, $book);
		// No more processing
		return false;
	}

	/**
	 * Serves out a PDF file to the browser
	 *
	 * @param $fileName string The full path to the PDF file.
	 */
	static public function servePdf($fileName, $version, $manual) {
		if(file_exists($fileName)) {
			header("Content-Type: application/pdf");
			header("Content-Disposition: attachment; filename=\"PonyDocs-$version-$manual.pdf\"");
			readfile($fileName);		
			die();				// End processing right away.
		}
		else {
			return false;
		}
	}

	/**
	 * Removes a cached PDF file.  Just attempts to unlink.  However, does a 
	 * quick check to see if the file exists after the unlink.  This is a bad 
	 * situation to be in because that means cached versions will never be 
	 * removed and will continue to be served.  So log that situation.
	 *
	 * @param $manual string The short name of the manual remove
	 * @param $version string The version of the manual to remove
	 */
	static public function removeCachedFile($manual, $version) {
		global $wgUploadDirectory;
		$pdfFileName = "$wgUploadDirectory/ponydocspdf-" . $version . "-" . $manual . "-book.pdf";
		@unlink($pdfFileName);
		if(file_exists($pdfFileName)) {
			error_log("ERROR [PonyDocsPdfBook::removeCachedFile] " . php_uname('n') . ": Failed to delete cached pdf file $pdfFileName");
			return false;
		}
		else {
			error_log("INFO [PonyDocsPdfBook::removeCachedFile] " . php_uname('n') . ": Cache file $pdfFileName removed.");
		}
		return true;
	}


	/**
	 * Needed in some versions to prevent Special:Version from breaking
	 */
	function __toString() { return 'PonyDocsPdfBook'; }
}

/**
 * Called from $wgExtensionFunctions array when initialising extensions
 */
function wfSetupPdfBook() {
	global $wgPonyDocsPdfBook;
	$wgPonyDocsPdfBook = new PonyDocsPdfBook();
}

