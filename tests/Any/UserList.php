<?php

class Any_UserList extends AbstractAction {
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
		'splunk_docteam' => FALSE,
		'storm_docteam'  => FALSE,
		'docteam'        => FALSE
		);

	}

	public function testUserList() {
		foreach ($this->_users as $user => $allowed) {

			// TODO really need to add in database refresh for each test!

			if ($user != 'anonymous') $this->_login($user);

			if ($allowed) {
				print "testing allowed user: ". $user . "\n";
				$this->open("/Main_Page");
				$this->click("link=Special pages");
				$this->waitForPageToLoad("10000");
				$this->click("link=User group rights");
				$this->waitForPageToLoad("10000");
				// View user groups
				$this->assertTrue($this->isTextPresent("The following is a list of user groups defined on this wiki, with their associated access rights"));
				$this->click("link=Users");
				$this->waitForPageToLoad("10000");
				$this->type("wpTextbox1", "test");
				$this->click("wpSave");
				$this->waitForPageToLoad("30000");
				// Edit user group page
				$this->assertTrue($this->isTextPresent("test"));
				$this->open("/Special:ListUsers");
				// View user list
				$this->assertTrue($this->isTextPresent("RandomUser"));
				$this->type("offset", "e");
				$this->click("css=input[type=submit]");
				$this->waitForPageToLoad("10000");
				// Filter user list
				$this->assertFalse($this->isElementPresent("link=Docteam"));
				$this->click("link=RandomUser");
				$this->waitForPageToLoad("10000");
				$this->type("wpTextbox1", "random user page");
				$this->click("wpSave");
				$this->waitForPageToLoad("10000");
				// Edit user page
				$this->assertTrue($this->isTextPresent("random user page"));
			} else {
				print "testing NOT allowed user: ". $user . "\n";
				$this->open("/Main_Page");
				$this->click("link=Special pages");
				$this->waitForPageToLoad("10000");
				$this->click("link=User group rights");
				$this->waitForPageToLoad("10000");
				// List user groups
				$this->assertFalse($this->isTextPresent("The following is a list of user groups defined on this wiki, with their associated access rights"));
				$this->open("/index.php?title=PonyDocs:Users&action=edit&redlink=1");
				$this->type("wpTextbox1", "test");
				$this->click("wpSave");
				$this->waitForPageToLoad("30000");
				// Edit user group page
				$this->assertFalse($this->isTextPresent("test"));
				$this->open("/Special:ListUsers");
				// List users
				$this->assertFalse($this->isTextPresent("RandomUser"));
				$this->open("/index.php?title=User:RandomUser&action=edit&redlink=1");
				// Edit user page
				$this->assertFalse($this->isElementPresent("wpTextbox1"));
			}

			if ($user != 'anonymous') $this->_logout();
		}
	}
}



?>