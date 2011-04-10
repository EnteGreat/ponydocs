<?php

/**
 * Engine to perform inheritance and branch functions for PonyDocs Documentation 
 * System.
 * Never instantiated, just a container for static methods.
 */
class PonyDocsBranchInheritEngine {

	private function __construct() {
		// It's a static class.
	}

	/**
	 * Branches a topic from a source title to a target title.
	 *
	 * @param $topicTitle string The name of the internal topic.
	 * @param $version PonyDocsVersion The target Version
	 * @param $tocSection The TOC section this title resides in.
	 * @param $tocTitle The toc title that references this topic.
	 * @param $deleteExisting boolean Should we purge any existing conflicts?
	 * @param $skipTOC boolean Should we skip adding to the TOC (for performance 
	 * 							reasons)
	 * @returns boolean
	 */
	static function branchTopic($topicTitle, $version, $tocSection, $tocTitle, $deleteExisting = false, $split = true, $skipTOC = false) {
		// Clear any hooks so no weirdness gets called after we create the 
		// branch
		$wgHooks['ArticleSave'] = array();
		if(!preg_match('/^Documentation:([^:]*):(.*):([^:]*)$/', $topicTitle, $match)) {
			throw new Exception("Invalid Title to Branch From");
		}

		$manual = $match[1];
		$title = $match[2];

		// Get the PonyDocsManual
		$manual = PonyDocsManual::GetManualByShortName($manual);

		// Get conflicts.
		$conflicts = self::getConflicts($topicTitle, $version);
		if(!empty($conflicts)) {
			if($deleteExisting && !$split) {
				// We want to purge each conflicting title completely.
				foreach($conflicts as $conflict) {
					$article = new Article(Title::newFromText($conflict));
					if(!$article->exists()) {
						// Article doesn't exist.  Should never occur, but if it 
						// doesn't, no big deal since it was a conflict.
						continue;
					}
					if($conflict == $topicTitle) {
						// Then the conflict is same as source material, do 
						// nothing.
						continue;
					}
					else {
						// Do actual delete.
						$article->doDelete("Requested purge of conficting article when branching topic " . $topicTitle . " with version: " . $version->getName(), false);
					}
				}
			}
			else if(!$split) {
				// Ruh oh, there's conflicts and we didn't want to purge or 
				// split.  Cancel out.
				throw new Exception("When calling branchTitle, there were conflicts and purge was not requested and we're not splitting.");
			}

		}
		// Load existing article to branch from
		$existingTitle = Title::newFromText($topicTitle); 
		$wgTitle = $existingTitle;
		$existingArticle = new Article($existingTitle);
		if(!$existingArticle->exists()) {
			// No such title exists in the system
			throw new Exception("Invalid Title to Branch From.  Target Article does not exist:" . $topicTitle);
		}
		$title = "Documentation:" . $manual->getShortName() . ":" . $title . ":" . $version->getName();

		$newTitle = Title::newFromText($title);
		$wgTitle = $newTitle;

		$newArticle = new Article($newTitle);
		if($newArticle->exists()) {
			throw new Exception("Article already exists:" . $title);
		}
		// Copy content
		$existingContent = $existingArticle->getContent();
		$newContent = $existingContent;
		// Build the versions which will go into the new array.
		$newVersions = array();
		// Text representation of the versions
		$newVersions[] = $version->getName();
		if($split) {
			// We need to get all versions from PonyDocsVersion
			$rawVersions = PonyDocsVersion::GetVersions();
			$existingVersions = array();
			foreach($rawVersions as $rawVersion) {
				$existingVersions[] = $rawVersion->getName();
			}
			// $existingVersions is now an array of version names in 
			// incremental order
			$versionIndex = array_search($version->getName(), $existingVersions);
			// versionIndex is the index where our target version is
			// we will use this to determine what versions need to be brought 
			// over.
			preg_match_all("/\[\[Category:V:([^\]]*)]]/", $existingContent, $matches);
			foreach($matches[1] as $match) {
				$index = array_search($match, $existingVersions);
				if($index > $versionIndex) {
					$newVersions[] = $match;
				}
			}
		}
		// $newVersions now contains all the versions that needs to be pulled 
		// from the existing Content, if exists, and put into the new content.
		// So let's now remove it form the original content
		foreach($newVersions as $tempVersion) {
			$existingContent = preg_replace("/\[\[Category:V:" . $tempVersion . "]]/", "", $existingContent);
		}
		// Now let's do the edit on the original content.
		$wgTitle = $existingTitle;
		// Set version and manual
		$existingArticle->doEdit($existingContent, "Removed versions from existing article when branching Topic " . $topicTitle, EDIT_UPDATE);
		// Clear categories tags from new article content
		$newContent = preg_replace("/\[\[Category:V:([^\]]*)]]/", "", $newContent);
		// add new category tags to new content
		foreach($newVersions as $version) {
			$newContent .= "[[Category:V:" . $version . "]]";
		}
		$newContent .= "\n";
		// doEdit on new article
		$wgTitle = $newTitle;
		$newArticle->doEdit($newContent, "Created new topic from branched topic " . $topicTitle, EDIT_NEW);
	
		if(!$skipTOC) {
			self::addToTOC($manual, $version, $tocSection, $tocTitle);
		}
		return $title;
	}

	/**
	 * Have an existing Topic "inherit" a new version by applying a category 
	 * version tag to it.
	 *
	 * @param $topicTitle string The internal mediawiki title of the article.
	 * @param $version PonyDocsVersion The target Version
	 * @param $tocSection The TOC section this title resides in.
	 * @param $tocTitle The toc title that references this topic.
	 * @param $deleteExisting boolean Should we purge any existing conflicts?
	 * @param $skipTOC boolean Should we skip adding to the TOC (for performance 
	 * 							reasons)
	 * @returns boolean
	 */
	static function inheritTopic($topicTitle, $version, $tocSection, $tocTitle, $deleteExisting = false, $skipTOC = false) {
		global $wgTitle;
		// Clear any hooks so no weirdness gets called after we save the 
		// inherit
		$wgHooks['ArticleSave'] = array();
		if(!preg_match('/^Documentation:([^:]*):(.*):([^:]*)$/', $topicTitle, $match)) {
			throw new Exception("Invalid Title to Inherit From: " . $topicTitle);
		}
		$manual = $match[1];
		$title = $match[2];

		// Get conflicts.
		$conflicts = self::getConflicts($topicTitle, $version);
		if(!empty($conflicts)) {
			if(!$deleteExisting) {
				throw new Exception("When calling inheritTitle, there were conflicts and deleteExisting was false.");
			}
			// We want to purge each conflicting title completely.
			foreach($conflicts as $conflict) {
				$article = new Article(Title::newFromText($conflict));
				if(!$article->exists()) {
					// No big deal.  A conflict no longer exists?  Continue.
					continue;
				}
				if($conflict == $topicTitle) {
					// Conflict was same as source material.  Do nothing with 
					// it.
					continue;
				}
				else {
					$article->doDelete("Requested purge of conficting article when inheriting topic " . $topicTitle . " with version: " . $version->getName(), false);
				}
			}
		}
		$title = Title::newFromText($topicTitle); 
		$wgTitle = $title;
		$existingArticle = new Article($title);
		if(!$existingArticle->exists()) {
			// No such title exists in the system
			throw new Exception("Invalid Title to Inherit From: " . $topicTitle);
		}
		// Okay, source article exists.
		// Add the appropriate version cateogry
		// Check for existing category
		if(!preg_match("\[\[Category:V:" . $version->getName() . "]]", $content)) {
			$content = $existingArticle->getContent();
			$content .= "[[Category:V:" . $version->getName() . "]]";
			// Save the article as an edit
			$existingArticle->doEdit($content, "Inherited topic " . $topicTitle . " with version: " . $version->getName(), EDIT_UPDATE);
		}
		// Okay, update our toc.
		if(!$skipTOC) {
			$manual = PonyDocsManual::GetManualByShortName($manual);
			self::addToTOC($manual, $version, $tocSection, $tocTitle);
		}
		return $title;

	}


	/**
	 * Determines if TOC exists for a manual and version
	 *
	 * @param $manual PonyDocsManual The manual to check with
	 * @param $version PonyDocsVersion The version to check with
	 * @returns boolean
	 */
	static public function TOCExists($manual, $version) {
		$dbr = wfGetDB(DB_SLAVE);
		$query = "SELECT cl_sortkey FROM categorylinks WHERE cl_to = 'V:" . $version->getName() . "' AND LOWER(cast(cl_sortkey AS CHAR)) LIKE 'documentation:" . strtolower($manual->getShortName()) . "toc%'";
		$res = $dbr->query($query, __METHOD__);

		if($res->numRows()) {
			$row = $dbr->fetchObject($res);
			return $row->cl_sortkey;
		}
		return false;
	}

	/**
	 * Branch a TOC.  Take existing TOC, create new TOC.
	 *
	 * @param $manual PonyDocsManual The manual to create a TOC for.
	 * @param $sourceVersion PonyDocsVersion The source version TOC to copy.
	 * @param $targetVersion PonyDocsVersion The target version for the new TOC.
	 * @returns boolean
	 */
	static function branchTOC($manual, $sourceVersion, $targetVersion) {
		global $wgTitle;
		// Perform old TOC operation
		$title = self::TOCExists($manual, $sourceVersion);
		if($title == false) {
			throw new Exception("TOC does not exist for " . $manual->getShortName() . " with version " . $version->sourceVersion());
		}
		$title = Title::newFromText($title);
		$wgTitle = $title;
		$article = new Article($title);
		if(!$article->exists()) {
			throw new Exception("TOC does not exist for " . $manual->getShortName() . " with version " . $sourceVersion>getName());
		}
		// Let's grab the content and also do an update
		$oldContent = $content = $article->getContent();
		
		// Remove old Version from old TOC (if exists)
		preg_match_all("/\[\[Category:V:" . $targetVersion->getName() . "]]/", $content, $matches);
		foreach($matches[0] as $match) {
			$oldContent = str_replace($match, "", $oldContent);
		}
		$article->doEdit($oldContent, "Removed version " . $targetVersion->getName(), EDIT_UPDATE);

		// Now do the TOC for the new version
		if(self::TOCExists($manual, $targetVersion)) {
			throw new Exception("TOC Already exists for " . $manual->getShortName() . " with version: " . $targetVersion->getName());
		}
		$title = "Documentation:" . $manual->getShortName() . "TOC" . $targetVersion->getName();
		$newTitle = Title::newFromText($title);
		$wgTitle = $newTitle;

		$newArticle = new Article($newTitle);
		if($newArticle->exists()) {
			throw new Exception("TOC Already exists.");
		}
		preg_match_all("/\[\[Category:V:[^\]]*]]/", $content, $matches);
		$lastTag = $matches[0][count($matches[0]) - 1];
		$content = str_replace($lastTag, $lastTag . "[[Category:V:" . $targetVersion->getName() . "]]", $content);
		$newArticle->doEdit($content, "Branched TOC For Version: " . $sourceVersion->getName() . " from Version: " . $sourceVersion->getName(), EDIT_NEW);
		PonyDocsExtension::ClearNavCache();
		return $title;
	}

	/**
	 * Creates new TOC page with a given manual and target version
	 * 
	 * @param $manual PonyDocsManual The manual we're going to create a TOC for.
	 * @param $version PonyDocsVersion the version we're going to create a TOC 
	 * for.
	 */
	static function createTOC($manual, $version) {
		global $wgTitle;
		if(self::TOCExists($manual, $version)) {
			throw new Exception("TOC Already exists for " . $manual->getShortName() . " with version: " . $version->getName());
		}
		$title = "Documentation:" . $manual->getShortName() . "TOC" . $version->getName();

		$newTitle = Title::newFromText($title);
		$wgTitle = $newTitle;

		$newArticle = new Article($newTitle);
		if($newArticle->exists()) {
			throw new Exception("TOC Already exists.");
		}
		// New TOC.  Create empty content.
		$newContent = "\n\n[[Category:V:" . $version->getName() . "]]";
		$newArticle->doEdit($newContent, "Created TOC For Version: " . $version->getName(), EDIT_NEW);
		PonyDocsExtension::ClearNavCache();
		return $title;
	}

	/**
	 * Add a version to an existing TOC.
	 * 
	 * @param $manual PonyDocsManual The manual the TOC belongs to.
	 * @param $version PonyDocsVersion The version the TOC belongs to.
	 * @param $newVersion PonyDocsVersion The version to add to the TOC.
	 * @returns boolean
	 */
	static function addVersionToTOC($manual, $version, $newVersion) {
		global $wgTitle;
		$title = self::TOCExists($manual, $version);
		if($title == false) {
			throw new Exception("TOC does not exist for " . $manual->getShortName() . " with version " . $version->getName());
		}
		$title = Title::newFromText($title);
		$wgTitle = $title;
		$article = new Article($title);
		if(!$article->exists()) {
			throw new Exception("TOC does not exist for " . $manual->getShortName() . " with version " . $version->getName());
		}
		// Okay, let's search for the content.
		$content = $article->getContent();
		preg_match_all("/\[\[Category:V:[^\]]*]]/", $content, $matches);
		$lastTag = $matches[0][count($matches[0]) - 1];
		$content = str_replace($lastTag, $lastTag . "[[Category:V:" . $newVersion->getName() . "]]", $content);
		$article->doEdit($content, "Added version " . $version->getName(), EDIT_UPDATE);
		PonyDocsExtension::ClearNavCache();
		return true;

	}

	/**
	 * Remove entry from TOC.  Will remove any instance of the entry from the 
	 * TOC.
	 *
	 * @param $manual PonyDocsManual The manual the TOC belongs to.
	 * @param $version PonyDocsVersion The version the TOC belongs to.
	 * @returns boolean
	 */
	static function removeFromTOC($manual, $version, $tocTitle) {
		global $wgTitle;
		$title = self::TOCExists($manual, $version);
		if($title == false) {
			throw new Exception("TOC does not exist for " . $manual->getShortName() . " with version " . $version->getName());
		}
		$title = Title::newFromText($title);
		$wgTitle = $title;
		$article = new Article($title);
		if(!$article->exists()) {
			throw new Exception("TOC does not exist for " . $manual->getShortName() . " with version " . $version->getName());
		}
		// Okay, let's search for the content.
		$content = $article->getContent();
		$content = preg_replace("/^ \* \{\{#topic:" . $tocTitle . "}}$/", "", $content);
		$article->doEdit($content, "Removed topic " . $tocTitle, EDIT_UPDATE);
		PonyDocsExtension::ClearNavCache();
		return true;
	}

	/**
	 * Do a bulk add operation.  Take a collection of topics and add them to the 
	 * TOC if it doesn't already exist.
	 *
	 * @param $manual PonyDocsManual The manual the TOC belongs to.
	 * @param $version PonyDocsVersion The version the TOC belongs to.
	 * @param $collection array A multidimensional array of topics.  First keyed 
	 * 							with section name, then titles.
	 * @returns boolean
	 */
	static function addCollectionToTOC($manual, $version, $collection) {
		global $wgTitle;
		$title = self::TOCExists($manual, $version);
		if($title == false) {
			throw new Exception("TOC does not exist for " . $manual->getShortName() . " with version " . $version->getName());
		}
		$title = Title::newFromText($title);
		$wgTitle = $title;
		$article = new Article($title);
		if(!$article->exists()) {
			throw new Exception("TOC does not exist for " . $manual->getShortName() . " with version " . $version->getName());
		}
		// Okay, let's search for the content.
		$content = $article->getContent();

		foreach($collection as $sectionName => $topics) {
			// $evalSectionName is the cleaned up section name to look for.
			$evalSectionName = preg_quote(trim(str_replace('?', "", strtolower($sectionName))));
			foreach($topics as $topic) {
				if($topic == null) {
					continue;
				}
				// $topic is the trimmed original version of the topic.
				$topic = trim($topic);
				// $evalTopic is the clened up topic name to look for
				$evalTopic = preg_quote(str_replace('?', '', strtolower($topic)));
				$content = explode("\n", $content);
				$found = false;
				$inSection = false;
				$newContent = '';
				foreach($content as $line) {
					$evalLine = trim(str_replace('?', '', strtolower($line)));
					if(preg_match("/^" . $evalSectionName . "$/", $evalLine)) {
						$inSection = true;
						$newContent .= $line . "\n";
						continue;
					}
					else if(preg_match("/\* \{\{#topic:" . $evalTopic . "\s*}}/", $evalLine)) {
						if($inSection) {
							$found = true;
						}
						$newContent .= $line . "\n";
						continue;
					}
					else if(preg_match("/^\s?$/", $evalLine)) {
						if($inSection && !$found) {
							$newContent .= "* {{#topic:" . $topic . "}}\n\n";
							$found = true;
							continue;
						}
						$inSection = false;
					}
					$newContent .= $line . "\n";
				}
				if(!$found) {
					// Then the section didn't event exist, we should add to TOC and add 
					// the item.
					// We need to add it before the Category evalLine.
					$text = $sectionName . "\n" . "* {{#topic:" . $topic . "}}\n\n[[Category";
					$newContent = preg_replace("/\[\[Category/", $text, $newContent);
				}
				$inSection = false;
				// Reset loop data
				$content = $newContent;
			}
		}
		// Okay, do the edit
		$article->doEdit($content, "Updated TOC in bulk branch operation.", EDIT_UPDATE);
		PonyDocsExtension::ClearNavCache();
		return true;
	}

	/**
	 * Adds entry to TOC.  Will not add if entry already exists in TOC under 
	 * section.  Note, this isn't used anymore in the inherit/branch process, 
	 * but is kept for historical purposes in case it's needed.
	 * 
	 * @param $manual PonyDocsManual The Manual the TOC belongs to.
	 * @param $version PonyDocsVersion the Version the TOC belongs to.
	 * @param $tocSection string The TOC section of the title.
	 * @param $tocTitle string The topic title to add.
	 * @returns boolean
	 */
	static function addToTOC($manual, $version, $tocSection, $tocTitle) {
		global $wgTitle;

		// Cleanup title
		$tocTitle = preg_replace("/[^a-zA-Z0-9\s]/", "", $tocTitle);	

		$title = self::TOCExists($manual, $version);
		if($title == false) {
			throw new Exception("TOC does not exist for " . $manual->getShortName() . " with version " . $version->getName());
		}
		$title = Title::newFromText($title);
		$wgTitle = $title;
		$article = new Article($title);
		if(!$article->exists()) {
			throw new Exception("TOC does not exist for " . $manual->getShortName() . " with version " . $version->getName());
		}
		// Okay, let's search for the content.
		$content = $article->getContent();
		$content = explode("\n", $content);
		$found = false;
		$inSection = false;
		$newContent = '';
		foreach($content as $line) {
			if(preg_match("/^" . $tocSection . "$/", $line)) {
				$inSection = true;
				$newContent .= $line . "\n";
				continue;
			}
			if(preg_match("/^\* \{\{#topic:" . $tocTitle . "}}$/", $line)) {
				if($inSection) {
					$found = true;
				}
				$newContent .= $line . "\n";
				continue;
			}
			if(preg_match("/^\s?$/", $line)) {
				if($inSection && !$found) {
					$newContent .= "* {{#topic:" . $tocTitle . "}}\n\n";
					$found = true;
					continue;
				}
				$inSection = false;

			}
			$newContent .= $line . "\n";
		}
		if(!$found) {
			// Then the section didn't event exist, we should add to TOC and add 
			// the item.
			// We need to add it before the Category line.
			$text = $tocSection . "\n" . "* {{#topic:" . $tocTitle . "}}\n\n[[Category";
			$newContent = preg_replace("/\[\[Category/", $text, $newContent);
		}
		// Okay, do the edit
		$article->doEdit($newContent, "Updated TOC with " . $tocTitle . " in Section " . $tocSection, EDIT_UPDATE);
		PonyDocsExtension::ClearNavCache();
		return true;
	}

	/**
	 * Determine if there is an existing topic that may interfere with a target 
	 * topic and version.  If conflict(s) exist, return the topic names.
	 *
	 * @param $topicTitle string The Topic name in Documentation:.*:.*:.* format
	 * @param $targetVersion PonyDocsVersion the version to search for
	 * @return Array of conflicting topic names, otherwise false if no conflict 
	 * exists.
	 */
	static function getConflicts($topicTitle, $targetVersion) {
		$dbr = wfGetDB(DB_SLAVE);
		if(!preg_match('/Documentation:(.*):(.*):(.*)/', $topicTitle, $match)) {
			throw new Exception("Invalid Title to Branch From");
		}
		$manual = $match[1];
		$title = $match[2];
		$query = "SELECT cl_sortkey FROM categorylinks WHERE cl_to = 'V:" . $targetVersion->getName() . "' AND LOWER(cast(cl_sortkey AS CHAR)) LIKE 'documentation:" . strtolower($manual) . ":" . strtolower($title) . ":%'";
		$res = $dbr->query($query, __METHOD__);

		if($res->numRows()) {
			/**
			 * Technically we should only EVER get one result back.  But who 
			 * knows with past doc wiki consistency issues.
			 */
			$conflicts = array();
			// Then let's return the topics that conflict.
			while($row = $dbr->fetchObject($res)) {
				$conflicts[] = $row->cl_sortkey;

			}
			return $conflicts;
		}
		// One last ditch effort.  Determine if any page exists that doesn't 
		// have a category link association.
		$query = "SELECT page_title FROM page WHERE LOWER(page_title) LIKE '" . strtolower($manual) . ":" . strtolower($title) . ":" . $targetVersion->getName() . "'";
		$res = $dbr->query($query, __METHOD__);
		if($res->numRows()) {
			$row = $dbr->fetchObject($res);
			return array('Documentation:' . $row->page_title);
		}

		return false;

	}

}

?>
