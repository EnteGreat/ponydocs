<?php

class Any_UserRightsManagement extends AbstractAction {
	public function setUp() {

		parent::setUp();

		$this->_users = array
		(
			'admin'		  => TRUE,
			'anonymous'	  => FALSE,
			'logged_in'	  => FALSE,
			'splunk_preview' => FALSE,
			'storm_preview'  => FALSE,
			'employee'	   => FALSE,
			'splunk_docteam' => FALSE,
			'storm_docteam'  => FALSE,
			'docteam'		=> FALSE
		);

	}

	protected function _allowed($user)
	{
		$this->open("/Main_Page");
		$this->click("link=Special pages");
		$this->waitForPageToLoad("10000");
		$this->click("link=User rights management");
		$this->waitForPageToLoad("10000");
		$this->type("username", "RandomUser");
		$this->click("css=input[type=submit]");
		$this->waitForPageToLoad("10000");
		$this->click("wpGroup-employees");
		$this->type("wpReason", "testing");
		$this->click("saveusergroups");
		$this->waitForPageToLoad("10000");
		// User successfully added to group
		$this->assertTrue($this->isTextPresent("Member of: employees"), $user);
	}

	protected function _notAllowed($user)
	{
		$this->open("/Special:UserRights");
		// Cannot access user rights management functionality
		$this->assertTrue($this->isTextPresent("You do not have permission to do that"), $user);
	}
}

?>