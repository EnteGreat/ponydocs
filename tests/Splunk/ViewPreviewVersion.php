<?php

class Splunk_ViewPreviewVersion extends AbstractAction
{
	public function setUp()
	{
		parent::setUp();

		$this->_users = array
		(
			'admin'		  => TRUE,
			'anonymous'	  => FALSE,
			'logged_in'	  => FALSE,
			'splunk_preview' => TRUE,
			'storm_preview'  => FALSE,
			'employee'	   => TRUE,
			'splunk_docteam' => TRUE,
			'storm_docteam'  => FALSE,
			'docteam'		=> FALSE
		);
	}

	protected function _allowed($user)
	{
		$this->open("/Main_Page");
		// Preview version is in dropdown
		$this->assertStringStartsWith("1.01.1 (latest release)", $this->getText("docsVersionSelect"), $user);
		$this->select("docsVersionSelect", "label=2.0");
		$this->click("css=option[value=2.0]");
		$this->waitForPageToLoad("10000");
		$this->select("docsManualSelect", "label=Splunk Installation Manual", $user);
		$this->waitForPageToLoad("10000");
		// Can view preview version topic
		$this->assertTrue($this->isElementPresent("Whats_in_Splunk_Installation_Manual"), $user);
	}

	protected function _notAllowed($user)
	{
		$this->open("/Main_Page");
		// Preview version is not in dropdown
		$this->assertEquals("1.01.1 (latest release)", $this->getText("docsVersionSelect"), $user);
		$this->open("/Documentation/Splunk/2.0/Installation/WhatsinSplunkInstallationManual");
		// Can't view preview version topic
		$this->assertFalse($this->isElementPresent("Whats_in_Splunk_Installation_Manual"), $user);
	}
}