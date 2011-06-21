<?php

class Any_ViewReleasedVersion extends AbstractAction
{
	public function setUp()
	{
		parent::setUp();

		$this->_users = array
		(
		'admin'		  => TRUE,
		'anonymous'	  => TRUE,
		'logged_in'	  => TRUE,
		'splunk_preview' => TRUE,
		'storm_preview'  => TRUE,
		'employee'	   => TRUE,
		'splunk_docteam' => TRUE,
		'storm_docteam'  => TRUE,
		'docteam'		=> TRUE
		);

	}
	
	protected function _allowed($user)
	{
		$this->open("/Main_Page");
		$this->assertTrue($this->isTextPresent("1.1 (latest release)"), $user);
		$this->select("docsManualSelect", "label=Splunk Installation Manual");
		$this->waitForPageToLoad("10000");
		// Topic page viewable
		$this->assertEquals("Documentation:Splunk:Installation:WhatsinSplunkInstallationManual:1.0 - PonyDocs", $this->getTitle(), $user);
		$this->assertTrue($this->isTextPresent("Whats in Splunk Installation Manual"), $user);
		$this->assertTrue($this->isTextPresent("Find what you need"), $user);
		$this->click("link=History");
		$this->waitForPageToLoad("10000");
		// History page viewable
		$this->assertEquals("22:18, 1 June 2011", $this->getText("link=exact:22:18, 1 June 2011"), $user);
		$this->click("mw-oldid-80");
		$this->click("css=#mw-history-compare > div:nth(1) > input.historysubmit");
		$this->waitForPageToLoad("10000");
		// Can compare revisions
		$this->assertTrue($this->isTextPresent("'''1.0'''"), $user);
		$this->click("css=#mw-diff-ntitle1 > strong > a:nth(1)");
		$this->waitForPageToLoad("10000");
		// Can view source
		$this->assertTrue($this->isElementPresent("wpTextbox1"), $user);
	}
	
	protected function _notAllowed($user)
	{
	}
}