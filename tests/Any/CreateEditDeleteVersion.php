<?php

class Any_CreateEditDeleteVersion extends AbstractAction
{
	public function setUp()
	{
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
    		'docteam'        => TRUE
		);
	}

    protected function _allowed($user)
    {
		// Splunk
		$this->open("/Documentation:Splunk:Versions");
		$this->click("link=Edit");
		$this->waitForPageToLoad("10000");
		$this->type("wpTextbox1", "{{#version:1.0|released}}\n{{#version:2.0|preview}}\n{{#version:3.0|unreleased}}\n{{#version:4.0|unreleased}}");
		$this->click("wpSave");
		$this->waitForPageToLoad("10000");
		// Version created
		$this->assertTrue($this->isTextPresent("Version 4.0 (unreleased)"), $user);
		$this->click("link=Edit");
		$this->waitForPageToLoad("10000");
		$this->type("wpTextbox1", "{{#version:1.0|released}}\n{{#version:2.0|preview}}\n{{#version:3.0|unreleased}}\n{{#version:4.1|preview}}");
		$this->click("wpSave");
		$this->waitForPageToLoad("10000");
		// Version edited
		$this->assertTrue($this->isTextPresent("Version 4.1 (preview)"), $user);
		$this->click("link=Edit");
		$this->waitForPageToLoad("10000");
		$this->type("wpTextbox1", "{{#version:1.0|released}}\n{{#version:2.0|preview}}\n{{#version:3.0|unreleased}}");
		$this->click("wpSave");
		$this->waitForPageToLoad("10000");
		// Version deleted
		$this->assertFalse($this->isTextPresent("Version 4.1 (preview)"), $user);

		//Storm
		$this->open("/Documentation:Storm:Versions");
		$this->click("link=Edit");
		$this->waitForPageToLoad("10000");
		$this->type("wpTextbox1", "{{#version:1.0|released}}\n{{#version:1.1|preview}}\n{{#version:1.2|unreleased}}\n{{#version:1.3|unreleased}}");
		$this->click("wpSave");
		$this->waitForPageToLoad("10000");
		// Version created
		$this->assertTrue($this->isTextPresent("Version 1.3 (unreleased)"), $user);
		$this->click("link=Edit");
		$this->waitForPageToLoad("10000");
		$this->type("wpTextbox1", "{{#version:1.0|released}}\n{{#version:1.1|preview}}\n{{#version:1.2|unreleased}}\n{{#version:1.3.1|preview}}");
		$this->click("wpSave");
		$this->waitForPageToLoad("10000");
		// Version edited
		$this->assertTrue($this->isTextPresent("Version 1.3.1 (preview)"), $user);
		$this->click("link=Edit");
		$this->waitForPageToLoad("10000");
		$this->type("wpTextbox1", "{{#version:1.0|released}}\n{{#version:1.1|preview}}\n{{#version:1.2|unreleased}}");
		$this->click("wpSave");
		$this->waitForPageToLoad("10000");
		// Version deleted
		$this->assertFalse($this->isTextPresent("Version 1.3.1 (preview)"), $user);
    }

    protected function _notAllowed($user)
    {
		// Splunk
        $this->open("/Documentation:Splunk:Versions");

		// TODO They really shouldn't even be able to see this page!
		
		// No edit link
		$this->assertFalse($this->isElementPresent("link=Edit"), $user);
		$this->open("/index.php?title=Documentation:Splunk:Versions&action=edit");
		// No edit perms
		$this->assertTrue($this->isTextPresent("You do not have permission to edit this page"), $user);

		// Storm
		$this->open("/Documentation:Storm:Versions");
		// No edit link
		$this->assertFalse($this->isElementPresent("link=Edit"), $user);
		$this->open("/index.php?title=Documentation:Storm:Versions&action=edit");
		// No edit perms
		$this->assertTrue($this->isTextPresent("You do not have permission to edit this page"), $user);
	}
}