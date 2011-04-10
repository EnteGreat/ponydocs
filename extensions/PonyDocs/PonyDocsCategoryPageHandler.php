<?php
if( !defined( 'MEDIAWIKI' )) {
	die( "PonyDocs MediaWiki Extension" );
}

require_once($IP . "/includes/CategoryPage.php");

class PonyDocsCategoryPageHandler extends CategoryViewer {
	protected $articleCount = 0;

	/**
	 * Our hook which is processed whenever a Category Page is viewed.  When 
	 * this happens, we hijack the normal mediawiki flow and create an instance 
	 * of our CategoryViewer(PonyDocsCategoryPageHandler) which has a special 
	 * addPage method which adds the H1 of the article content, instead of the 
	 * title itself.
	 *
	 * @param CategoryArticle $categoryArticle a reference to the 
	 * CategoryArticle which fired off the event.
	 * @return boolean We return false to make mediawiki stop processing the 
	 * normal flow.
	 */
	static function onCategoryPageView($categoryArticle) {
		global $wgOut, $wgRequest;
		$from = $wgRequest->getVal('from');
		$until = $wgRequest->getVal('until');
		$cacheKey = "category-" . $categoryArticle->getTitle() . "-$from-$until";
		$res = null;
		if(CATEGORY_CACHE_ENABLED) {
			// Do a quick expire from our cache
			PonyDocsCategoryPageHandler::cacheExpire();
			// See if this exists in our cache
			$res = PonyDocsCategoryPageHandler::cacheFetch($cacheKey);
		}
		if(!$res) {
			// Either cache is disabled, or cached entry does not exist
			$categoryViewer = new PonyDocsCategoryPageHandler($categoryArticle->getTitle(), $from, $until);
			$res = $categoryViewer->getHTML();
			if(CATEGORY_CACHE_ENABLED) {
				// Store in our cache
				PonyDocsCategoryPageHandler::cacheStore($cacheKey, $res, time() + CATEGORY_CACHE_TTL);		
			}
		}
		$wgOut->addHTML($res);
		return false; // We don't want to continue processing the "normal" mediawiki path, so return false here.
	}

	/**
	 * Our special overridden addPage.  Adds the H1 of the article content, 
	 * instead of the title itself.
	 *
	 * @param $title Title the Title obj which represents the article.
	 * @param $sortkey string What key are we sorted on.
	 * @param $pageLength integer Ignored in our implementation.
	 *
	 */
	function addPage($title, $sortkey, $pageLength, $isRedirect = false) {
		global $wgContLang;
		$this->articleCount++;
		$textForm = $title->getText();
		if($isRedirect) {
			// In rare chances where the title will conflict with another, make 
			// all article elements sub-arrays
			if(!is_array($this->articles[strtoupper($textForm[0])])) {
				$this->articles[strtoupper($textForm[0])] = array();
			}
			$this->articles[strtoupper($textForm[0])] = '<span class="redirect-in-category">' . $this->getSkin()->makeKnownLinkObj( $title ) . '</span>';
		}
		else {
			// Not a redirect, a regular article.  Let's grab the h1, if 
			// available.
			// Make sure we run the ArticleFromTitle hook...
			$article = null;
			wfRunHooks('ArticleFromTitle', array(&$title, &$article));
			if(!$article) {
				$article = new Article($title);
			}
			$article->LoadContent();
			preg_match('/^\s*=(.*)=.*\n?/', $article->getContent(), $matches);
			if(isset($matches[1])) {
				// We found a header in the content, use that as our h1
				$h1 = trim($matches[1]);
				if(strlen($h1) == 0) {
					$h1 = trim($textForm);
				}
			}
			else {
				// Could not find a header, use the text form of our title
				$h1 = trim($textForm); 
			}
			// Let's get the namespace, if any.
			$nsText = $title->getNsText();
			if($nsText) {
				// Not in default namespace, add in parenthesis.
				$h1 .= " ($nsText)";
			}

			// In rare chances where the title will conflict with another, make 
			// all article elements sub-arrays
			if(!is_array($this->articles[strtoupper($h1[0])])) {
				$this->articles[strtoupper($h1[0])] = array();
			}
			$this->articles[strtoupper($h1[0])][] = $this->getSkin()->makeKnownLinkObj($title, htmlentities($h1));
		}
	}

	/**
	 * Format a list of articles chunked by letter in a one-column
	 * list, ordered vertically.
	 *
	 * @param array $articles Our collection of articles...
	 * @param array $articles_start_char Totally ignored.
	 * @return string
	 * @private
	 */
	function columnList( $articles, $articles_start_char ) {
		$result = ksort($articles, SORT_STRING);
		$r = '';

		$prev_start_char = 'none';
		foreach($articles as $key => $subArticles) {
			$r .= "<h3>" . htmlspecialchars($key) . "</h3><ul>";
			foreach($subArticles as $article) {
				$r .= "<li>{$article}</li>";
			}
			$r .= "</ul>";
		}
		return $r;
	}

	/**
	 * Format a list when a list is short.  Basically performs 
	 * same function as our columnList.
	 *
	 * @see columnList
	 *
	 */
	function shortList($articles, $articles_start_char) {
		return $this->columnList($articles, $articles_start_char);
	}

	/**
	 * Format a list of categories chunked by letter.
	 * This is overridden to provide a one column layout much like our column 
	 * list method.
	 *
	 * @param array $articles
	 * @param array $articles_start_char
	 * @return string
	 * @private
	 */
	function categoryList( $articles, $articles_start_char ) {
		// divide list into three equal chunks
		$prev_start_char = 'none';
		for ($index = 0 ; $index < count($articles); $index++ ) {
			// check for change of starting letter or begining of chunk
			if ($articles_start_char[$index] != $articles_start_char[$index - 1]) {
				if($prev_start_char != 'none') {
					$r .= "</ul>\n";
				}
				$r .= "<h3>" . htmlspecialchars( $articles_start_char[$index] ) . "</h3>\n<ul>";
				$prev_start_char = $articles_start_char[$index];
			}
			$r .= "<li>{$articles[$index]}</li>";
		}
		return $r;
	}

	/**
	 * Overridden getPagesSection which the only change is using our internal 
	 * article counter to show the total articles returned.
	 */
	function getPagesSection() {
		$ti = htmlspecialchars( $this->title->getText() );
		# Don't show articles section if there are none.
		$r = '';
		$c = $this->articleCount;
		if( $c > 0 ) {
			$r = "<div id=\"mw-pages\">\n";
			$r .= '<h2>' . wfMsg( 'category_header', $ti ) . "</h2>\n";
			$r .= wfMsgExt( 'categoryarticlecount', array( 'parse' ), $c );
			$r .= $this->formatList( $this->articles, $this->articles_start_char );
			$r .= "\n</div>";
		}
		return $r;
	}

	/**
	 * Our overridden getSubcategorySection.
	 * Will call categoryList.
	 */
	function getSubcategorySection() {
		# Don't show subcategories section if there are none.
		$r = '';
		$c = count( $this->children );
		if( $c > 0 ) {
			# Showing subcategories
			$r .= "<div id=\"mw-subcategories\">\n";
			$r .= '<h2>' . wfMsg( 'subcategories' ) . "</h2>\n";
			$r .= wfMsgExt( 'subcategorycount', array( 'parse' ), $c );
			$r .= $this->categoryList( $this->children, $this->children_start_char );
			$r .= "\n</div>";
		}
		return $r;
	}



	
	/**
	 * Expires/deletes old records from our  cache
	 */
	public static function cacheExpire() {
		$dbr = wfGetDB(DB_MASTER);

		// convert epoch to timestamp
		$now = date("Y-m-d H:i:s",time());

		$query = "DELETE FROM splunk_cache WHERE expires < '$now'" ;
		try {
			$dbr->query($query);
		} catch (Exception $ex){
			error_log("FATAL [PonyDocsCategoryPageHandler::cacheExpire] DB call failed pn  Line ".$ex->getLine()." on file ".$ex->getFile().", error Message is: \n".$ex->getMessage()."Stack Trace is:".$ex->getTraceAsString());
		}
		return true;
	}

	/**
	 * Expires cache for specified tag/category
	 * @param array $tags array of unique keys to delete
	 */
	public static function cacheExpireCategories(array $categories) {
		$dbr = wfGetDB(DB_MASTER);
		$sql_conditions = array();
		foreach ($categories as $cat) {
			$sql_conditions[] = "tag LIKE '" . $dbr->strencode($cat) . "%'";
		}
		$query = 'DELETE FROM splunk_cache WHERE ' . implode(' OR ', $sql_conditions);
		try {
			$dbr->query($query);
		} catch (Exception $ex) {
			error_log("FATAL [PonyDocsCategoryPageHandler::cacheExpireCategory] DB call failed pn  Line " . $ex->getLine() . " on file " . $ex->getFile() . ", error Message is: \n" . $ex->getMessage() . "Stack Trace is:" . $ex->getTraceAsString());
		}
		return true;
	}

	/**
	 * Stores an entry into our cache.  The tag is the unique identifier 
	 * for the entry.
	 *
	 * @param $tag string The unique key
	 * @param $data string The data to store
	 * @param $expires The timestamp in which this record is old
	 */
	public static function cacheStore($tag, $data, $expires) {
		if(HELP_CACHE_ENABLED) {
			// We don't see if there's a key collision, if there is, it just means 
			// a different webhead created the cache entry, no biggie.
			$dbr = wfGetDB(DB_MASTER);
			
			// convert epoch to timestamp
			$expires = date("Y-m-d H:i:s",$expires);

			$data = mysql_real_escape_string($data);
			
			$query = "INSERT INTO splunk_cache VALUES('$tag', '$expires',  '$data')";
			try {
				$dbr->query($query);
			} catch (Exception $ex){
				error_log("FATAL [PonyDocsCategoryPageHandler::cacheStore] DB call failed on Line ".$ex->getLine()." on file ".$ex->getFile().", error Message is: \n".$ex->getMessage()."Stack Trace is:".$ex->getTraceAsString());
			}
		}
		return true;
	}

	/**
	 * Fetch a element from our cache. 
	 *
	 * @param $tag the identifier to look for
	 * @returns boolean or string string if succeeded, false if no element found
	 */
	public static function cacheFetch($tag) {
		if(!HELP_CACHE_ENABLED) {
			return false;
		}
		$dbr = wfGetDB(DB_SLAVE);
		$query = "SELECT *  FROM splunk_cache WHERE tag = '$tag'";
		try {
			$res = $dbr->query($query);
			$obj = $dbr->fetchObject($res);
			if($obj) {
				return $obj->data;
			}
		} catch(Exception $ex) {
			error_log("FATAL [PonyDocsCategoryPageHandler::cacheFetch] DB call failed on Line " . $ex->getLine()." on file " . $ex->getFile(). ", error Message is: \n" . $ex->getMessage(). " Stack Trace Is: " . $ex->getTraceAsString());
		}
		return false; 		
	}

}
