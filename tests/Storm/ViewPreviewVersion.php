<?php

class Storm_ViewPreviewVersion extends AbstractAction
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
    		'storm_preview'  => TRUE,
    		'employee'       => TRUE,
    		'splunk_docteam' => TRUE,
    		'storm_docteam'  => TRUE,
    		'docteam'        => TRUE
		);
	}

    protected function _allowed($user)
    {
		$this->open("/Main_Page");
		$this->select("docsProductSelect", "label=Storm");
		$this->click("css=option[value=Storm]");
		$this->waitForPageToLoad("10000");
		// Preview version is in dropdown
		$this->assertStringStartsWith("1.0 (latest release)1.1", $this->getText("docsVersionSelect"), $user);
		$this->select("docsVersionSelect", "label=1.1");
		$this->click("css=option[value=1.1]");
		$this->waitForPageToLoad("10000");
		$this->select("docsManualSelect", "label=Storm User Manual");
		$this->waitForPageToLoad("10000");
		$this->click("link=Ways to access Storm");
		$this->waitForPageToLoad("10000");
		// Can view preview version topic
		$this->assertTrue($this->isElementPresent("Ways_to_access_Storm"), $user);
    }

    protected function _notAllowed($user)
    {
        $this->open("/Main_Page");
        if ($user != 'logged_in') { // this is hella janky - needed for logged_in because it immediately follows anonymous; anon can't log out/clear cookie
			$this->select("docsProductSelect", "label=Storm");
			$this->click("css=option[value=Storm]");
			$this->waitForPageToLoad("10000");
		}
		// Preview version is not in dropdown
		$this->assertEquals("1.0 (latest release)", $this->getText("docsVersionSelect"), $user);
		$this->open("/Documentation/Storm/1.1/User/WaystoaccessStorm");
		// Can't view preview version topic
		$this->assertFalse($this->isElementPresent("Ways_to_access_Storm"), $user);
    }
}


?>