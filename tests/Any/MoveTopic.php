<?php

class Any_MoveTopic extends AbstractAction {
	public function setUp() {

		parent::setUp();

		$this->_users = array
		(
		'admin'          => TRUE,
		'anonymous'      => FALSE,
		'logged_in'      => FALSE,
		'splunk_preview' => FALSE,
		'storm_preview'  => FALSE,
		'employee'       => FALSE,
		'splunk_docteam' => TRUE,
		'storm_docteam'  => TRUE,
		'docteam'        => TRUE
		);

	}

	public function testMoveTopic() {
		foreach ($this->_users as $user => $allowed) {

			// TODO really need to add in database refresh for each test!
			
			if ($user != 'anonymous') $this->_login($user);

			if ($allowed) {
				print "testing allowed user: ". $user . "\n";
				$this->open("/Main_Page");
				$this->select("docsManualSelect", "label=Splunk User Manual");
				$this->waitForPageToLoad("10000");
				$this->click("link=Ways to access Splunk");
				$this->waitForPageToLoad("10000");
				$this->click("link=Move");
				$this->waitForPageToLoad("10000");
				$this->type("wpNewTitle", "Documentation:Splunk:User:WaystoaccessSplunktest:1.0");
				$this->click("wpMove");
				$this->waitForPageToLoad("10000");
				// Topic moved
				$this->assertEquals("Move succeeded", $this->getText("firstHeading"));
			} else {
				print "testing NOT allowed user: ". $user . "\n";
				$this->open("/Main_Page");
				$this->select("docsManualSelect", "label=Splunk User Manual");
				$this->waitForPageToLoad("10000");
				$this->click("link=Ways to access Splunk");
				$this->waitForPageToLoad("10000");
				$this->assertFalse($this->isElementPresent("link=Move"));
				$this->open("/Special:MovePage/Documentation:Splunk:User:WaystoaccessSplunk:1.0");
				if ($user == 'anonymous') {
					$this->assertTrue($this->isTextPresent("You do not have permission to do that"));
				} else {
					$this->type("wpNewTitle", "Documentation:Splunk:User:WaystoaccessSplunktest:1.0");
					$this->click("wpMove");
					$this->waitForPageToLoad("10000");
					// Topic not allowed to be moved
					$this->assertTextPresent("You are not allowed to execute the action you have requested.");
				}
			}

			if ($user != 'anonymous') $this->_logout();
		}
	}
}

?>