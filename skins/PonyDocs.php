<?php
/**
 * PonyDocs Theme, based off of monobook
 * Gives ability to support documentation namespace.
 *
 * Translated from gwicke's previous TAL template version to remove
 * dependency on PHPTAL.
 *
 * @todo document
 * @file
 * @ingroup Skins
 */

if( !defined( 'MEDIAWIKI' ) )
	die( -1 );

/**
 * Inherit main code from SkinTemplate, set the CSS and template filter.
 * @todo document
 * @ingroup Skins
 */
class SkinPonyDocs extends SkinTemplate {
	var $skinname = 'ponydocs', $stylename = 'ponydocs',
		$template = 'PonyDocsTemplate', $useHeadElement = true;

	function setupSkinUserCss( OutputPage $out ) {
		global $wgHandheldStyle;

		parent::setupSkinUserCss( $out );

		// Append to the default screen common & print styles...
		$out->addStyle( 'ponydocs/main.css', 'screen' );
		if( $wgHandheldStyle ) {
			// Currently in testing... try 'chick/main.css'
			$out->addStyle( $wgHandheldStyle, 'handheld' );
		}

		$out->addStyle( 'ponydocs/IE50Fixes.css', 'screen', 'lt IE 5.5000' );
		$out->addStyle( 'ponydocs/IE55Fixes.css', 'screen', 'IE 5.5000' );
		$out->addStyle( 'ponydocs/IE60Fixes.css', 'screen', 'IE 6' );
		$out->addStyle( 'ponydocs/IE70Fixes.css', 'screen', 'IE 7' );

		$out->addStyle( 'ponydocs/rtl.css', 'screen', '', 'rtl' );

	}

	// We are going to totally overwrite this functionality to fix a weird issue
	public function setTitle($t) {
		global $wgTitle;
		$this->mTitle = $wgTitle;
	}

}

/**
 * @todo document
 * @ingroup Skins
 */
class PonyDocsTemplate extends QuickTemplate {
	var $skin;

	/**
	 * This lets you map full titles or namespaces to specific PHP template files and prep methods.  The special '0' index
	 * is the default if not found.  Prefix title mappings with 'T:' and namespace mappings with 'NS:'.  Currently if inside
	 * a namespace it will ignore any title mappings (i.e., either it calls the NS:namespace or the default).
	 *
	 * @var array
	 */	
	private $_methodMappings = array(
		0 => array( 'prep' => '', 'tpl' => 'nsDefault' ),
		'T:Documentation' => array( 'prep' => 'prepareDocumentation', 'tpl' => 'nsDocumentation' ),
		'NS:Documentation' => array( 'prep' => 'prepareDocumentation', 'tpl' => 'nsDocumentation' )
	);


	function execute() {
		global $wgRequest;

		global $wgUser, $wgExtraNamespaces, $wgTitle, $wgArticlePath, $IP;
		global $wgRevision, $action, $wgRequest;
		PonyDocsVersion::LoadVersions();
		PonyDocsManual::LoadManuals();

		$ponydocs = PonyDocsWiki::getInstance( );

		$this->data['versions'] = $ponydocs->getVersionsForTemplate( );		
		$this->data['namespaces'] = $wgExtraNamespaces;		
		$this->data['selectedVersion'] = PonyDocsVersion::GetSelectedVersion( );
		$this->data['versionurl'] = $this->data['wgScript'] . '?title=' . $this->data['thispage'] . '&action=changeversion';

		$this->skin = $skin = $this->data['skin'];

		$action = $wgRequest->getText( 'action' );

		// Suppress warnings to prevent notices about missing indexes in $this->data
		wfSuppressWarnings();

		/**
		 * When displaying a page we output header.php, then a sub-template, and then footer.php.  The namespace
		 * which we are in determines the sub-template, which is named 'ns<Namespace>'.  It defaults to our
		 * nsDefault.php template. 
		 */
		$idx = $this->data['nscanonical'] ? 'NS:'.$this->data['nscanonical'] : 'T:'.$wgTitle->__toString( );
		if( !isset( $this->_methodMappings[$idx] ))	
			$idx = 0;

		$inDocumentation = false;
		if($this->data['nscanonical'] == 'Documentation' || $wgTitle->__toString() == 'Documentation' || preg_match("/^Documentation:/", $wgTitle->__toString())) {
			$inDocumentation = true;
			$this->prepareDocumentation();
		}
		$this->data['versions'] = $ponydocs->getVersionsForTemplate( );		


		$this->html( 'headelement' );
		?>
		<script type="text/javascript">
			function ponyDocsOnLoad() {}

			function AjaxChangeVersion_callback( o ) {
				document.getElementById('docsVersionSelect').disabled = true;
				var s = new String( o.responseText );
				document.getElementById('docsVersionSelect').disabled = false;
				window.location.href = s;
			}

			function AjaxChangeVersion( ) {	
				var versionIndex = document.getElementById('docsVersionSelect').selectedIndex;
				var version = document.getElementById('docsVersionSelect')[versionIndex].value;
				var title = '<?php $this->text('pathinfo'); ?>';		
				sajax_do_call( 'efPonyDocsAjaxChangeVersion', [version,title], AjaxChangeVersion_callback );
			}

			function changeManual(){
				var url = $("#docsManualSelect").attr("value");
				if (url != ""){
					window.location.href = url;
				}
			}
			</script>
	
	<div id="globalWrapper">

<div id="column-content"><div id="content" <?php $this->html("specialpageattributes") ?>>
	<a id="top"></a>
	<?php if($this->data['sitenotice']) { ?><div id="siteNotice"><?php $this->html('sitenotice') ?></div><?php } ?>

	<?php
	if(!$inDocumentation) {
		?>
		<h1 id="firstHeading" class="firstHeading"><?php 
		$this->html('title'); 
		?></h1>
		<?php
	}
	?>
	<div id="bodyContent">
		<h3 id="siteSub"><?php $this->msg('tagline') ?></h3>
		<div id="contentSub"<?php $this->html('userlangattributes') ?>><?php $this->html('subtitle') ?></div>
<?php if($this->data['undelete']) { ?>
		<div id="contentSub2"><?php $this->html('undelete') ?></div>
<?php } ?><?php if($this->data['newtalk'] ) { ?>
		<div class="usermessage"><?php $this->html('newtalk')  ?></div>
<?php } ?><?php if($this->data['showjumplinks']) { ?>
		<div id="jump-to-nav"><?php $this->msg('jumpto') ?> <a href="#column-one"><?php $this->msg('jumptonavigation') ?></a>, <a href="#searchInput"><?php $this->msg('jumptosearch') ?></a></div>
<?php } ?>
		<!-- start content -->
<?php $this->html('bodytext') ?>
		<?php if($this->data['catlinks']) { $this->html('catlinks'); } ?>
		<!-- end content -->
		<?php if($this->data['dataAfterContent']) { $this->html ('dataAfterContent'); } ?>
		<div class="visualClear"></div>
	</div>
</div></div>
<div id="column-one"<?php $this->html('userlangattributes')  ?>>
	<div id="p-cactions" class="portlet">
		<h5><?php $this->msg('views') ?></h5>
		<div class="pBody">
			<ul><?php
				foreach($this->data['content_actions'] as $key => $tab) {
					echo '
				 <li id="' . Sanitizer::escapeId( "ca-$key" ) . '"';
					if( $tab['class'] ) {
						echo ' class="'.htmlspecialchars($tab['class']).'"';
					}
					echo '><a href="'.htmlspecialchars($tab['href']).'"';
					# We don't want to give the watch tab an accesskey if the
					# page is being edited, because that conflicts with the
					# accesskey on the watch checkbox.  We also don't want to
					# give the edit tab an accesskey, because that's fairly su-
					# perfluous and conflicts with an accesskey (Ctrl-E) often
					# used for editing in Safari.
				 	if( in_array( $action, array( 'edit', 'submit' ) )
				 	&& in_array( $key, array( 'edit', 'watch', 'unwatch' ))) {
				 		echo $skin->tooltip( "ca-$key" );
				 	} else {
				 		echo $skin->tooltipAndAccesskey( "ca-$key" );
				 	}
				 	echo '>'.htmlspecialchars($tab['text']).'</a></li>';
				} ?>

			</ul>
		</div>
	</div>
	<div class="portlet" id="p-personal">
		<h5><?php $this->msg('personaltools') ?></h5>
		<div class="pBody">
			<ul<?php $this->html('userlangattributes') ?>>
<?php 			foreach($this->data['personal_urls'] as $key => $item) { ?>
				<li id="<?php echo Sanitizer::escapeId( "pt-$key" ) ?>"<?php
					if ($item['active']) { ?> class="active"<?php } ?>><a href="<?php
				echo htmlspecialchars($item['href']) ?>"<?php echo $skin->tooltipAndAccesskey('pt-'.$key) ?><?php
				if(!empty($item['class'])) { ?> class="<?php
				echo htmlspecialchars($item['class']) ?>"<?php } ?>><?php
				echo htmlspecialchars($item['text']) ?></a></li>
<?php			} ?>
			</ul>
		</div>
	</div>
	<div class="portlet" id="p-logo">
		<a style="background-image: url(<?php $this->text('logopath') ?>);" <?php
			?>href="<?php echo htmlspecialchars($this->data['nav_urls']['mainpage']['href'])?>"<?php
			echo $skin->tooltipAndAccesskey('p-logo') ?>></a><br />
	</div>
	<script type="<?php $this->text('jsmimetype') ?>"> if (window.isMSIE55) fixalpha(); </script>
	<div id="p-documentation" class="portlet">
		<h5>documentation</h5>
		<div id="documentationBody" class="pBody">
		<?php
		$versions = PonyDocsVersion::GetVersions(true);
		if(!count($versions)) {
			?>
				<p>
				No Product Versions Defined.
				</p>
			<?php
		}
		else {
			$manuals = PonyDocsManual::GetDefinedManuals(true);
			if(!count($manuals)) {
				?>
					<p>
					No product manuals defined.
					</p>
				<?php
			}
			else {
				?>
					<p>
					<div class="productVersion">
						<?php
						// do quick manip
						$found = false;
						for($i =(count($this->data['versions']) - 1); $i >= 0; $i--){
							
							$this->data['versions'][$i]['label'] = $this->data['versions'][$i]['name'];
							if(!$found && $this->data['versions'][$i]['status'] == "released") {
								$this->data['versions'][$i]['label'] .= " (latest release)";
								$found = true;
							}
						}
						?>
						<label for='docsVersionSelect'  class="navlabels">Product version:&nbsp;</label><br />
						<select id="docsVersionSelect" name="selectedVersion" onChange="AjaxChangeVersion();">
						<?php
							foreach( $this->data['versions'] as $idx => $data ) {

								echo '<option value="' . $data['name'] . '" ';
								if( !strcmp( $data['name'], $this->data['selectedVersion'] ))
									echo 'selected';
								echo '>' . $data['label'] . '</option>';		
							}	
						?>
						</select>
						
					</div>
					
					<div class="productManual">
						<label for="docsManualSelect" class="navlabels">Select manual:&nbsp;</label><br />
						<select id="docsManualSelect" name="selectedManual" onChange="changeManual();">
						<?php
						$navData = PonyDocsExtension::fetchNavDataForVersion($this->data['selectedVersion']);
						print "<option value=''>Pick One...</option>";
						//loop through nav array and look for current URL
						foreach ($navData as $manual){
							$selected = "";
							if( !strcmp( $this->data['manualname'], $manual['longName'] )) {
								$selected = " selected ";
							}
							print "<option value='". $manual['firstUrl'] . "'   $selected>";
							print $manual['longName'];
							print '</option>';
						}
						?>
						</select>
					</div>	
					</p>
					<p>
					<?php
					if(sizeof($this->data['manualtoc'])) {
						?>
						<p>
						<a href="<?php echo str_replace('$1', '', $wgArticlePath);?>index.php?title=<?php echo $wgTitle->__toString();?>&action=pdfbook">Pdf Version</a>
						</p>
						<?php
						$inUL = false;
						$listid = "";
						foreach( $this->data['manualtoc'] as $idx => $data )
						{
							if( 0 == $data['level'] )
							{
								if( $inUL )
								{
									echo '</ul></div>';
									$inUL = false;
								}
								$listid = "list" . $idx;
								echo '<div class="wikiSidebarBox collapsible">';
								echo '<h3>' . $data['text'] . '</h3>';
								echo '<ul>';
								$inUL = true;
							}
							else if( 1 == $data['level'] )
							{
								if( $data['current'] ) {
									echo '<li class="expanded">' . $data['text'] . '</li>';
								}
								else
									echo '<li><a href="' . $data['link'] . '">' . $data['text'] . '</a></li>';
							}
							else
							{
								if( $data['current'] )
									echo '<li class="expanded" style="margin-left: 13px;">' . $data['text'] . '</li>';
								else
									echo '<li style="margin-left: 13px;"><a href="' . $data['link'] . '">' . $data['text'] . '</a></li>';
							}
							
						}
						if( $inUL )
							echo '</ul></div>';
					}
					?>
					</p>

				<?php
			}
		}
		?>
		</div>
	</div>
<?php
		$sidebar = $this->data['sidebar'];
		if ( !isset( $sidebar['SEARCH'] ) ) $sidebar['SEARCH'] = true;
		if ( !isset( $sidebar['TOOLBOX'] ) ) $sidebar['TOOLBOX'] = true;
		if ( !isset( $sidebar['LANGUAGES'] ) ) $sidebar['LANGUAGES'] = true;
		foreach ($sidebar as $boxName => $cont) {
			if ( $boxName == 'SEARCH' ) {
				$this->searchBox();
			} elseif ( $boxName == 'TOOLBOX' ) {
				$this->toolbox();
			} elseif ( $boxName == 'LANGUAGES' ) {
				$this->languageBox();
			} else {
				$this->customBox( $boxName, $cont );
			}
		}
?>
</div><!-- end of the left (by default at least) column -->
<div class="visualClear"></div>
<div id="footer"<?php $this->html('userlangattributes') ?>>
<?php
if($this->data['poweredbyico']) { ?>
	<div id="f-poweredbyico"><?php $this->html('poweredbyico') ?></div>
<?php }
if($this->data['copyrightico']) { ?>
	<div id="f-copyrightico"><?php $this->html('copyrightico') ?></div>
<?php }

		// Generate additional footer links
		$footerlinks = array(
			'lastmod', 'viewcount', 'numberofwatchingusers', 'credits', 'copyright',
			'privacy', 'about', 'disclaimer', 'tagline',
		);
		$validFooterLinks = array();
		foreach( $footerlinks as $aLink ) {
			if( isset( $this->data[$aLink] ) && $this->data[$aLink] ) {
				$validFooterLinks[] = $aLink;
			}
		}
		if ( count( $validFooterLinks ) > 0 ) {
?>	<ul id="f-list">
<?php
			foreach( $validFooterLinks as $aLink ) {
				if( isset( $this->data[$aLink] ) && $this->data[$aLink] ) {
?>		<li id="<?php echo $aLink ?>"><?php $this->html($aLink) ?></li>
<?php 			}
			}
?>
	</ul>
<?php	}
?>
</div>
</div>
<?php $this->html('bottomscripts'); /* JS call to runBodyOnloadHook */ ?>
<?php $this->html('reporttime') ?>
<?php if ( $this->data['debug'] ): ?>
<!-- Debug output:
<?php $this->text( 'debug' ); ?>

-->
<?php endif; ?>
</body></html>
<?php
	wfRestoreWarnings();
	} // end of execute() method

	/*************************************************************************************************/
	function searchBox() {
		global $wgUseTwoButtonsSearchForm;
?>
	<div id="p-search" class="portlet">
		<h5><label for="searchInput"><?php $this->msg('search') ?></label></h5>
		<div id="searchBody" class="pBody">
			<form action="<?php $this->text('wgScript') ?>" id="searchform">
				<input type='hidden' name="title" value="<?php $this->text('searchtitle') ?>"/>
				<?php
		echo Html::input( 'search',
			isset( $this->data['search'] ) ? $this->data['search'] : '', 'search',
			array(
				'id' => 'searchInput',
				'title' => $this->skin->titleAttrib( 'search' ),
				'accesskey' => $this->skin->accesskey( 'search' )
			) ); ?>

				<input type='submit' name="go" class="searchButton" id="searchGoButton"	value="<?php $this->msg('searcharticle') ?>"<?php echo $this->skin->tooltipAndAccesskey( 'search-go' ); ?> /><?php if ($wgUseTwoButtonsSearchForm) { ?>&nbsp;
				<input type='submit' name="fulltext" class="searchButton" id="mw-searchButton" value="<?php $this->msg('searchbutton') ?>"<?php echo $this->skin->tooltipAndAccesskey( 'search-fulltext' ); ?> /><?php } else { ?>

				<div><a href="<?php $this->text('searchaction') ?>" rel="search"><?php $this->msg('powersearch-legend') ?></a></div><?php } ?>

			</form>
		</div>
	</div>
<?php
	}

	/*************************************************************************************************/
	function toolbox() {
?>
	<div class="portlet" id="p-tb">
		<h5><?php $this->msg('toolbox') ?></h5>
		<div class="pBody">
			<ul>
<?php
		if($this->data['notspecialpage']) { ?>
				<li id="t-whatlinkshere"><a href="<?php
				echo htmlspecialchars($this->data['nav_urls']['whatlinkshere']['href'])
				?>"<?php echo $this->skin->tooltipAndAccesskey('t-whatlinkshere') ?>><?php $this->msg('whatlinkshere') ?></a></li>
<?php
			if( $this->data['nav_urls']['recentchangeslinked'] ) { ?>
				<li id="t-recentchangeslinked"><a href="<?php
				echo htmlspecialchars($this->data['nav_urls']['recentchangeslinked']['href'])
				?>"<?php echo $this->skin->tooltipAndAccesskey('t-recentchangeslinked') ?>><?php $this->msg('recentchangeslinked-toolbox') ?></a></li>
<?php 		}
		}
		if( isset( $this->data['nav_urls']['trackbacklink'] ) && $this->data['nav_urls']['trackbacklink'] ) { ?>
			<li id="t-trackbacklink"><a href="<?php
				echo htmlspecialchars($this->data['nav_urls']['trackbacklink']['href'])
				?>"<?php echo $this->skin->tooltipAndAccesskey('t-trackbacklink') ?>><?php $this->msg('trackbacklink') ?></a></li>
<?php 	}
		if($this->data['feeds']) { ?>
			<li id="feedlinks"><?php foreach($this->data['feeds'] as $key => $feed) {
					?><a id="<?php echo Sanitizer::escapeId( "feed-$key" ) ?>" href="<?php
					echo htmlspecialchars($feed['href']) ?>" rel="alternate" type="application/<?php echo $key ?>+xml" class="feedlink"<?php echo $this->skin->tooltipAndAccesskey('feed-'.$key) ?>><?php echo htmlspecialchars($feed['text'])?></a>&nbsp;
					<?php } ?></li><?php
		}

		foreach( array('contributions', 'log', 'blockip', 'emailuser', 'upload', 'specialpages') as $special ) {

			if($this->data['nav_urls'][$special]) {
				?><li id="t-<?php echo $special ?>"><a href="<?php echo htmlspecialchars($this->data['nav_urls'][$special]['href'])
				?>"<?php echo $this->skin->tooltipAndAccesskey('t-'.$special) ?>><?php $this->msg($special) ?></a></li>
<?php		}
		}

		if(!empty($this->data['nav_urls']['print']['href'])) { ?>
				<li id="t-print"><a href="<?php echo htmlspecialchars($this->data['nav_urls']['print']['href'])
				?>" rel="alternate"<?php echo $this->skin->tooltipAndAccesskey('t-print') ?>><?php $this->msg('printableversion') ?></a></li><?php
		}

		if(!empty($this->data['nav_urls']['permalink']['href'])) { ?>
				<li id="t-permalink"><a href="<?php echo htmlspecialchars($this->data['nav_urls']['permalink']['href'])
				?>"<?php echo $this->skin->tooltipAndAccesskey('t-permalink') ?>><?php $this->msg('permalink') ?></a></li><?php
		} elseif ($this->data['nav_urls']['permalink']['href'] === '') { ?>
				<li id="t-ispermalink"<?php echo $this->skin->tooltip('t-ispermalink') ?>><?php $this->msg('permalink') ?></li><?php
		}

		wfRunHooks( 'PonyDocsTemplateToolboxEnd', array( &$this ) );
		wfRunHooks( 'SkinTemplateToolboxEnd', array( &$this ) );
?>
			</ul>
		</div>
	</div>
<?php
	}

	/*************************************************************************************************/
	function languageBox() {
		if( $this->data['language_urls'] ) {
?>
	<div id="p-lang" class="portlet">
		<h5<?php $this->html('userlangattributes') ?>><?php $this->msg('otherlanguages') ?></h5>
		<div class="pBody">
			<ul>
<?php		foreach($this->data['language_urls'] as $langlink) { ?>
				<li class="<?php echo htmlspecialchars($langlink['class'])?>"><?php
				?><a href="<?php echo htmlspecialchars($langlink['href']) ?>"><?php echo $langlink['text'] ?></a></li>
<?php		} ?>
			</ul>
		</div>
	</div>
<?php
		}
	}

	/*************************************************************************************************/
	function customBox( $bar, $cont ) {
?>
	<div class='generated-sidebar portlet' id='<?php echo Sanitizer::escapeId( "p-$bar" ) ?>'<?php echo $this->skin->tooltip('p-'.$bar) ?>>
		<h5><?php $out = wfMsg( $bar ); if (wfEmptyMsg($bar, $out)) echo htmlspecialchars($bar); else echo htmlspecialchars($out); ?></h5>
		<div class='pBody'>
<?php   if ( is_array( $cont ) ) { ?>
			<ul>
<?php 			foreach($cont as $key => $val) { ?>
				<li id="<?php echo Sanitizer::escapeId($val['id']) ?>"<?php
					if ( $val['active'] ) { ?> class="active" <?php }
				?>><a href="<?php echo htmlspecialchars($val['href']) ?>"<?php echo $this->skin->tooltipAndAccesskey($val['id']) ?>><?php echo htmlspecialchars($val['text']) ?></a></li>
<?php			} ?>
			</ul>
<?php   } else {
			# allow raw HTML block to be defined by extensions
			print $cont;
		}
?>
		</div>
	</div>
<?php
	}

	public function prepareDocumentation() {
		global $wgArticle, $wgParser, $wgTitle, $wgOut, $wgScriptPath, $wgUser;
		/**
		 * We need a lot of stuff from our PonyDocs extension!
		 */		
		$ponydocs = PonyDocsWiki::getInstance( );		
		$this->data['manuals'] = $ponydocs->getManualsForTemplate( );	

		/**
		 * Adjust content actions as needed, such as add 'view all' link.
		 */
		$this->contentActions( );		
		$this->navURLS( );

		/**
		 * Possible topic syntax we must handle:
		 * 
		 * Documentation:<topic> *Which may include a version tag at the end, we don't care about this.
		 * Documentation:<manualShortName>:<topic>:<version>
		 * Documentation:<manualShortName>
		 */

		/**
		 * Based on the name; i.e. 'Documentation:Manual:Topic' we need to parse it out and store the manual name and
		 * the topic name as parameters.  We store manual in 'manualname' and topic in 'topicname'.  Special handling
		 * needs to be done for versions and TOC?
		 *
		 * 	0=NS (Documentation)
		 *  1=Manual (Short name)
		 *  2=Topic
		 *  3=Version
		 */
		$pManual = null;
		$pieces = split( ':', $wgTitle->__toString( ));
		$helpClass = '';
		
		/**
		 * This isn't a specific topic+version -- handle appropriately.
		 */
		if( sizeof( $pieces ) < 3 )
		{				
			if( !strcmp( PONYDOCS_DOCUMENTATION_VERSION_TITLE, $wgTitle->__toString( )))
			{
				$this->data['titletext'] = 'Versions Management';
				$wgOut->addHTML( '<br><span class="' . $helpClass . '"><i>* Use {{#version:name|status}} to define a new version, where status is released, unreleased, or preview.  Valid chars in version name are A-Z, 0-9, period, comma, underscore, and dash.</i></span>');
			}
			else if( !strcmp( PONYDOCS_DOCUMENTATION_MANUALS_TITLE, $wgTitle->__toString( )))
			{
				$this->data['titletext'] = 'Manuals Management';
				$wgOut->addHTML( '<br><span class="' . $helpClass . '"><i>* Use {{#manual:shortName|displayName}} to define a new manual.  If you omit display name, the short name will be used in links.</i></span>');
			}
			else if( preg_match( '/(.*)TOC(.*)/', $pieces[1], $matches ))
			{										
				$this->data['titletext'] = $matches[1] . ' Table of Contents Page';			
				$wgOut->addHTML( '<br><span class="' . $helpClass . '"><i>* Use {{#topic:Display Name}} to assign within a bullet.  Place topic tags below proper section name.</i></span>' );	
			}			
			else if( PonyDocsManual::IsManual( $pieces[1] ))
			{
				$pManual = PonyDocsManual::GetManualByShortName( $pieces[1] );				
				if( $pManual )
					$this->data['manualname'] = $pManual->getLongName( );
				else					
					$this->data['manualname'] = $pieces[1]; 
				$this->data['topicname'] = $pieces[2];
				$this->data['titletext'] = $pieces[1] ;
			}
			else
				$this->data['topicname'] = $pieces[1];				
		}
		else
		{			
			$pManual = PonyDocsManual::GetManualByShortName( $pieces[1] );
			if( $pManual )
				$this->data['manualname'] = $pManual->getLongName( );
			else
				$this->data['manualname'] = $pieces[1];			 			
			$this->data['topicname'] = $pieces[2];
			
			$h1 = PonyDocsTopic::FindH1ForTitle( $wgTitle->__toString( ));			
			if( $h1 !== false )
				$this->data['titletext'] = $h1;				
		}
		
		/**
		 * Get current topic, passing it our global Article object.  From this, generate our TOC based on the current
		 * topic selected.  This generates our left sidebar TOC plus our prev/next/start navigation links.  This should ONLY
		 * be done if we actually are WITHIN a manual, so special pages like TOC, etc. should not do this!
		 */
				
		if( $pManual )
		{						
			$v = PonyDocsVersion::GetVersionByName( PonyDocsVersion::GetSelectedVersion( ));
			$toc = new PonyDocsTOC( $pManual, $v );			
			list( $this->data['manualtoc'], $this->data['tocprev'], $this->data['tocnext'], $this->data['tocstart'] ) = $toc->loadContent( );							
			$this->data['toctitle'] = $toc->getTOCPageTitle();
		}

		/**
		 * Create a PonyDocsTopic from our article.  From this we populate:
		 *
		 * topicversions:  List of version names topic is tagged with.
		 * inlinetoc:  Inline TOC shown above article body.
		 * catcode:  Special category code.
		 * cattext:  Category description.
		 * basetopicname:  Base topic name (w/o :<version> at end).
		 * basetopiclink:  Link to special TopicList page to view all same topics.
		 */		
		
		//echo '<pre>' ;print_r( $wgArticle ); die();
		$topic = new PonyDocsTopic( $wgArticle );
		
		if( preg_match( '/^Documentation:(.*):(.*):(.*)/', $wgTitle->__toString( )) ||
			preg_match( '/^Documentation:.*TOC.*/', $wgTitle->__toString( )))
		{
			$this->data['topicversions'] = PonyDocsWiki::getVersionsForTopic( $topic );
			$this->data['inlinetoc'] = $topic->getSubContents( );
			$this->data['versionclass'] = $topic->getVersionClass( );
			
			/**
			 * Sort of a hack -- we only use this right now when loading a TOC page which is new/does not exist.  When this
			 * happens a hook (AlternateEdit) adds an inline script to define this JS function, which populates the edit
			 * box with the proper Category tag based on the currently selected version.
			 */
			
			$this->data['body_onload'] = 'ponyDocsOnLoad();';
		
			switch( $this->data['catcode'] )
			{		
				case 0:
					$this->data['cattext'] = 'Applies to latest version which is currently unreleased.';
					break;
				case 1:
					$this->data['cattext'] = 'Applies to latest version.';
					break;
				case 2:
					$this->data['cattext'] = 'Applies to released version(s) but not the latest.';
					break;
				case 3:
					$this->data['cattext'] = 'Applies to latest preview version.';
					break;
				case 4:
					$this->data['cattext'] = 'Applies to one or more preview version(s) only.';
					break;
				case 5:	
					$this->data['cattext'] = 'Applies to one or more unreleased version(s) only.';
					break;	
				case -2: /** Means its not a a title name which should be checked. */
					break;		
				default:
					$this->data['cattext'] = 'Does not apply to any version of PonyDocs.';
					break;
			}
		}
		
		$this->data['basetopicname'] = $topic->getBaseTopicName( );
		if( strlen( $this->data['basetopicname'] ))
		{
			$this->data['basetopiclink'] = '<a href="' . $wgScriptPath . '/index.php?title=Special:TopicList&topic=' . $this->data['basetopicname'] . '">View All</a>';
		}
		$temp = PonyDocsTopic::FindH1ForTitle("Documentation:" . $topic->getTitle()->getText());
		if($temp !== false) {
			// We got an H1!
			$this->data['pagetitle'] = $temp;
		}

	}

	private function contentActions( )
	{
		global $wgUser, $wgTitle, $wgArticle, $wgArticlePath, $wgScriptPath, $wgUser;
		
		$groups = $wgUser->getGroups( );
		
		if( preg_match( '/Documentation:(.*):(.*):(.*)/i', $wgTitle->__toString( ), $match ))
		{			
			if( in_array( PONYDOCS_EMPLOYEE_GROUP, $groups ) || in_array( PONYDOCS_AUTHOR_GROUP, $groups ))
			{			
				array_pop( $match );  array_shift( $match );
				$title = 'Documentation:' . implode( ':', $match );
				
				$this->data['content_actions']['viewall'] = array(
					'class' => '',
					'text' => 'View All',
					'href' => $wgScriptPath . '/index.php?title=Special:TopicList&topic=' . $title 
				);
			}
			if( $wgUser->isAllowed( 'branchtopic' ))
			{
				$this->data['content_actions']['branch'] = array(
					'class' => '',
					'text'  => 'Branch',
					'href'	=> $wgScriptPath . '/Special:BranchInherit?titleName=' . $wgTitle->__toString()
				);
			}
		}
		else if( preg_match( '/Documentation:(.*)TOC(.*)/i', $wgTitle->__toString( ), $match ))
		{
			if( $wgUser->isAllowed( 'branchmanual' ))
			{	
				$this->data['content_actions']['branch'] = array(
					'class' => '',
					'text'  => 'Branch',
					'href'	=> $wgScriptPath . '/Special:BranchInherit?toc=' . $wgTitle->__toString( )
				);				
			}
		}
	}
	
	/**
	 * Update the nav URLs (toolbox) to include certain special pages for authors and bureaucrats.
	 */	
	private function navURLS( )
	{
		global $wgUser, $wgArticlePath, $wgArticle, $wgTitle;
		
		$groups = $wgUser->getGroups( );
		
		if( in_array( 'bureaucrat', $groups ) || in_array( PONYDOCS_AUTHOR_GROUP, $groups ))
		{
			$this->data['nav_urls']['special_doctopics'] = array(
				'href' => str_replace( '$1', 'Special:DocTopics', $wgArticlePath ),
				'text' => 'Document Topics' );
	
			$this->data['nav_urls']['special_tocmgmt'] = array(
				'href' => str_replace( '$1', 'Special:TOCList', $wgArticlePath ),
				'text' => 'TOC Management' );
			
			$this->data['nav_urls']['documentation_manuals'] = array(
				'href' => str_replace( '$1', 'Documentation:Manuals', $wgArticlePath ),
				'text' => 'Manuals' );

			$this->data['nav_urls']['document_links'] = array(
				'href' => str_replace( '$1', 'Special:PonyDocsDocumentLinks?t=' . $wgTitle->__toString()  , $wgArticlePath),
				'text' => 'Document Links');
			
		}		
	}



} // end of class


