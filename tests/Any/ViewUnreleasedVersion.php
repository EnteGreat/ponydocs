<?php

class Any_ViewUnreleasedVersion extends AbstractAction {
	public function setUp() {

		parent::setUp();

		$this->_users = array
		(
		'admin'          => TRUE,
		'anonymous'      => FALSE,
		'logged_in'      => FALSE,
		'splunk_preview' => FALSE,
		'storm_preview'  => FALSE,
		'employee'       => TRUE,
		'splunk_docteam' => TRUE,
		'storm_docteam'  => TRUE,
		'docteam'        => TRUE
		);

	}

	public function testViewUnreleasedVersion() {
		foreach ($this->_users as $user => $allowed) {

			// TODO really need to add in database refresh for each test!

			if ($user != 'anonymous') $this->_login($user);

			if ($allowed) {
				print "testing allowed user: ". $user . "\n";
				$this->open("/Main_Page");
				// Unreleased version (3.0) is in the dropdown
				$this->assertEquals("1.0 (latest release)2.03.0", $this->getText("docsVersionSelect"));
				$this->select("docsVersionSelect", "label=3.0");
				$this->click("css=option[value=3.0]");
				$this->waitForPageToLoad("30000");
				$this->select("docsManualSelect", "label=Splunk Installation Manual");
				$this->waitForPageToLoad("30000");
				// Unreleased Manual is viewable
				$this->assertEquals("Whats in Splunk Installation Manual", $this->getText("Whats_in_Splunk_Installation_Manual"));
				$this->click("link=System Requirements for Splunk");
				$this->waitForPageToLoad("30000");
				// Unreleased Topic is viewable
				$this->assertEquals("System Requirements for Splunk", $this->getText("System_Requirements_for_Splunk"));
			} else {
				print "testing NOT allowed user: ". $user . "\n";
				$this->open("/Main_Page");
				// Unreleased version (3.0) is NOT in the dropdown
				$this->assertNotEquals("1.0 (latest release)2.03.0", $this->getText("docsVersionSelect"));
				$this->open("/Documentation/Splunk/3.0/Installation/WhatsinSplunkInstallationManual");
				// Unreleased topic is not viewable
				$this->assertElementNotPresent("Whats_in_Splunk_Installation_Manual");
				//TODO Add a product with no released versions and test against that
			}

			if ($user != 'anonymous') $this->_logout();
		}
	}
}

?>