<?php
if( !defined( 'MEDIAWIKI' ))
	die( "Splunk MediaWiki Extension" );

/**
 * Needed since we subclass it;  it doesn't seem to be loaded elsewhere.
 */
require_once( $IP . '/includes/SpecialPage.php' );

/**
 * Register our 'Special' page so it is listed and accessible.
 */
$wgSpecialPages["Comments"] = 'SpecialComments';

/**
 * Implements Special:Comments
 * Renders recent comments added to documentation system.  Also provides 
 * RSS/Atom feed
 * @ingroup SpecialPage
 */
class SpecialComments extends SpecialPage {
	public function __construct() {
  		parent::__construct("Comments");
		$this->includable( false );
	}

	/**
	 * Get a FormOptions object containing the default options
	 *
	 * @return FormOptions
	 */
	public function getDefaultOptions() {
		global $wgUser;
		$opts = new FormOptions();

		$opts->add( 'days',  30 );
		$opts->add( 'limit', 200 );

		return $opts;
	}

	/**
	 * Get a FormOptions object with options as specified by the user
	 *
	 * @return FormOptions
	 */
	public function setup( $parameters ) {
		global $wgRequest;

		$opts = $this->getDefaultOptions();
		$opts->fetchValuesFromRequest( $wgRequest );

		// Give precedence to subpage syntax
		if( $parameters !== null ) {
			$this->parseParameters( $parameters, $opts );
		}

		$opts->validateIntBounds( 'limit', 0, 500 );
		return $opts;
	}

	/**
	 * Get a FormOptions object sepcific for feed requests
	 *
	 * @return FormOptions
	 */
	public function feedSetup() {
		global $wgFeedLimit, $wgRequest;
		$opts = $this->getDefaultOptions();
		# Feed is cached on limit,hideminor; other params would randomly not work
		$opts->fetchValuesFromRequest( $wgRequest, array( 'limit' ) );
		$opts->validateIntBounds( 'limit', 0, $wgFeedLimit );
		return $opts;
	}

	/**
	 * Main execution point
	 *
	 * @param $parameters string
	 */
	public function execute( $parameters ) {
		global $wgRequest, $wgOut;
		$feedFormat = $wgRequest->getVal( 'feed' );

		# 10 seconds server-side caching max
		$wgOut->setSquidMaxage( 10 );

		$opts = $feedFormat ? $this->feedSetup() : $this->setup( $parameters );
		$this->setHeaders();
		$this->outputHeader();

		// Fetch results, prepare a batch link existence check query
		$rows = array();
		$conds = $this->buildMainQueryConds( $opts );
		$rows = $this->doMainQuery( $conds, $opts );
		if( $rows === false ){
			return;
		}

		$target = isset($opts['target']) ? $opts['target'] : ''; // RCL has targets
		if( $feedFormat ) {
			$this->feed($feedFormat, $rows, $opts['limit']);
		} else {
			$this->webOutput( $rows, $opts );
		}

		$rows->free();
	}

	/**
	 * Renders a feed to the user based on requested type
	 */
	public function feed($feedFormat, $rows, $limit) {
		global $wgFeed, $wgFeedClasses, $wgFeedLimit;
		if(!$wgFeed) {
			global $wgOut;
			$wgOut->addWikiMsg( 'feed-unavailable' );
			return;
		}
		if(!isset($wgFeedClasses[$feedFormat])) {
			global $wgOut;
			$wgOut->addWikiMsg( 'feed-invalid' );
			return;
		}

		$feed = new $wgFeedClasses[$feedFormat]('Documentation Comment Listings',
										  'Recent Comments Added To Documentation',
										  $this->getTitle()->getFullUrl());
		$feed->outHeader();
		foreach($rows as $row) {
			$feed->outItem($this->feedItem($row));
		}
		$feed->outFooter();
	}

	/**
	 * Returns a FeedItem which represents the comment added.
	 *
	 * @return FeedItem
	 *
	 */
	public function feedItem($row) {
		return new FeedItem(
							date('n/j/Y g:i:s a',$row->date) . ": " . $row->title,
							$row->username . " Commented: " .$row->comment,	
							"/base/index.php?title=" . $row->title,
							$row->date,
							$row->username,
							$row->comment);
	}


	/**
	 * Process $par and put options found if $opts
	 * Mainly used when including the page
	 *
	 * @param $par String
	 * @param $opts FormOptions
	 */
	public function parseParameters( $par, FormOptions $opts ) {
		$bits = preg_split( '/\s*,\s*/', trim( $par ) );
		foreach( $bits as $bit ) {
			if( is_numeric( $bit ) ) $opts['limit'] =  $bit;
			$m = array();
			if( preg_match( '/^limit=(\d+)$/', $bit, $m ) ) $opts['limit'] = $m[1];
			if( preg_match( '/^days=(\d+)$/', $bit, $m ) ) $opts['days'] = $m[1];
		}
	}

	/**
	 * Return an array of conditions depending of options set in $opts
	 *
	 * @param $opts FormOptions
	 * @return array
	 */
	public function buildMainQueryConds( FormOptions $opts ) {
		global $wgUser;

		$dbr = wfGetDB( DB_SLAVE );
		$conds = array();

		// Calculate cutoff
		$cutoff_unixtime = time() - ( $opts['days'] * 86400 );
		$cutoff_unixtime = $cutoff_unixtime - ($cutoff_unixtime % 86400);

		$conds[] = 'date >= ' . $cutoff_unixtime;

		return $conds;
	}

	/**
	 * Process the query
	 *
	 * @param $conds array
	 * @param $opts FormOptions
	 * @return database result 
	 */
	public function doMainQuery( $conds, $opts ) {
		global $wgUser;
		global $wgDBprefix;

		$dbr = wfGetDB( DB_SLAVE );

		$tables = array( $wgDBprefix . 'ponydocs_comments' );

		$res = $dbr->select( $tables, '*', $conds, __METHOD__,
				array( 'ORDER BY' => 'date DESC', 'LIMIT' => $limit ));
		return $res;
	}

	/**
	 * Send output to $wgOut, only called if not used feeds
	 *
	 * @param $rows array of database rows
	 * @param $opts FormOptions
	 */
	public function webOutput( $rows, $opts ) {
		global $wgOut, $wgUser, $wgRCShowWatchingUsers, $wgShowUpdatedMarker;
		global $wgAllowCategorizedRecentChanges;

		$limit = $opts['limit'];

		if( !$this->including() ) {
			// Output options box
			$this->doHeader( $opts );
		}

		// And now for the content
		$wgOut->setSyndicated( true );

		$dbr = wfGetDB( DB_SLAVE );

		$counter = 1;

		$html = '';
		foreach( $rows as $obj ) {
			if( $limit == 0 ) break;

			// Render the comment info.
			
			$html .= "<p>" . date('m/d/Y g:i a', $obj->date) . ": <em>{$obj->username}</em> added comment to <em><a href=\"index.php?title={$obj->title}\">{$obj->title}</a></em> : {$obj->comment}</p>";

		}


		$wgOut->addHTML( $html );
	}

	/**
	 * Return the text to be displayed above the comments
	 *
	 * @param $opts FormOptions
	 * @return String: XHTML
	 */
	public function doHeader( $opts ) {
		global $wgScript, $wgOut;

		$this->setTopText( $wgOut, $opts );

		$defaults = $opts->getAllValues();
		$nondefaults = $opts->getChangedValues();

		$panel = array();
		$panel[] = $this->optionsPanel( $defaults, $nondefaults );
		$panel[] = '<hr />';

		$panelString = implode( "\n", $panel );

		$wgOut->addHTML(
			Xml::fieldset( 'Comments Filter Options', $panelString, array( 'class' => 'rcoptions' ) )
		);

	}


	/**
	 * Send the text to be displayed above the options
	 *
	 * @param $out OutputPage
	 * @param $opts FormOptions
	 */
	function setTopText( OutputPage $out, FormOptions $opts ){
		$out->addWikiText( 'Lists the most recent Comments added to Documentation topics.');
	}


	/**
	 * Makes change an option link which carries all the other options
	 * @param $title see Title
	 * @param $override
	 * @param $options
	 */
	function makeOptionsLink( $title, $override, $options, $active = false ) {
		global $wgUser;
		$sk = $wgUser->getSkin();
		$params = $override + $options;
		return $sk->link( $this->getTitle(), htmlspecialchars( $title ),
			( $active ? array( 'style'=>'font-weight: bold;' ) : array() ), $params, array( 'known' ) );
	}

	/**
	 * Creates the options panel.
	 * @param $defaults array
	 * @param $nondefaults array
	 */
	function optionsPanel( $defaults, $nondefaults ) {
		global $wgLang, $wgUser, $wgRCLinkLimits, $wgRCLinkDays;

		$options = $nondefaults + $defaults;

		$note = '';

		# Sort data for display and make sure it's unique after we've added user data.
		$wgRCLinkLimits[] = $options['limit'];
		$wgRCLinkDays[] = $options['days'];
		sort( $wgRCLinkLimits );
		sort( $wgRCLinkDays );
		$wgRCLinkLimits = array_unique( $wgRCLinkLimits );
		$wgRCLinkDays = array_unique( $wgRCLinkDays );

		// limit links
		foreach( $wgRCLinkLimits as $value ) {
			$cl[] = $this->makeOptionsLink( $wgLang->formatNum( $value ),
				array( 'limit' => $value ), $nondefaults, $value == $options['limit'] ) ;
		}
		$cl = $wgLang->pipeList( $cl );

		// day links, reset 'from' to none
		foreach( $wgRCLinkDays as $value ) {
			$dl[] = $this->makeOptionsLink( $wgLang->formatNum( $value ),
				array( 'days' => $value, 'from' => '' ), $nondefaults, $value == $options['days'] ) ;
		}
		$dl = $wgLang->pipeList( $dl );

		// show from this onward link

		$rclinks = wfMsgExt( 'rclinks', array( 'parseinline', 'replaceafter' ),
			$cl, $dl, $hl );
		return "{$note}$rclinks";
	}
}
