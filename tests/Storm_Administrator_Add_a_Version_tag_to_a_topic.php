<?php

require_once 'Testing/Selenium.php';

class Storm_Administrator_Add_a_Version_tag_to_a_topic extends PHPUnit_Extensions_SeleniumTestCase
{
    private $_selenium;
    
    protected function setUp()
    {
        $this->_selenium = new Testing_Selenium("*chrome", "http://lightswitch-ponydocs.splunk.com/");
    }

    public function testAddAVersionTagToATopic()
    {
        $this->_selenium->open("http://lightswitch-ponydocs.splunk.com/index.php?title=Special:UserLogin");
        $this->assertEquals("Log in / create account - PonyDocs", $this->_selenium->getTitle());
        $this->_selenium->type("wpName1", "admin");
        $this->_selenium->type("wpPassword1", "useradmin");
        $this->_selenium->click("wpLoginAttempt");
        $this->_selenium->waitForPageToLoad("30000");
        $this->assertEquals("Main Page - PonyDocs", $this->_selenium->getTitle());
        $this->_selenium->select("docsProductSelect", "Storm");
        $this->_selenium->waitForPageToLoad("30000");
        $this->_selenium->select("docsManualSelect", "Storm Installation Manual");
        $this->_selenium->waitForPageToLoad("30000");
        $this->assertEquals("Documentation:Storm:Installation:WhatsintheStormInstallationManual:1.0 - PonyDocs", $this->_selenium->getTitle());
        $this->_selenium->click("link=Edit");
        $this->_selenium->waitForPageToLoad("30000");
        $this->assertEquals("Editing Documentation:Storm:Installation:WhatsintheStormInstallationManual:1.0 - PonyDocs", $this->_selenium->getTitle());
        $this->_selenium->type("wpTextbox1", "= What's in the Storm Installation Manual =\n\n'''1.0'''\n\nUse this guide to find [[Documentation:Installation:SystemRequirements|system requirements]], [[Documentation:Installation:installalicense|licensing information]], and [http://www.splunk.com/base/Documentation/latest/Installation/Aboutupgradingto4.1:READTHISFIRST procedures for installing or migrating Splunk].\n\n==Find what you need==\nYou can use the table of contents to the left of this panel, or simply search for what you want in the search box in the upper right. \n\nIf you're interested in more specific [http://www.splunk.com/wiki scenarios and best practices], you can visit the [http://www.splunk.com/wiki Splunk Community Wiki] to see how other users Splunk IT.\n\n====Make a PDF====\nIf you'd like a PDF of any version of this manual, click the '''pdf version''' link above the table of contents bar on the left side of this page. A PDF version of the manual is generated on the fly for you, and you can save it or print it out to read later. \n\n[[Category:V:Storm:1.0]][[Category:V:Storm:1.1]]");
        $this->_selenium->click("wpSave");
        $this->_selenium->waitForPageToLoad("30000");
        $this->assertTrue($this->_selenium->isElementPresent("link=exact:V:Storm:1.1"));
    }
    
    public function tearDown()
    {
        $this->assertEquals("Documentation:Storm:Installation:WhatsintheStormInstallationManual:1.0 - PonyDocs", $this->_selenium->getTitle());
        $this->_selenium->click("link=Edit");
        $this->_selenium->waitForPageToLoad("30000");
        $this->assertEquals("Editing Documentation:Storm:Installation:WhatsintheStormInstallationManual:1.0 - PonyDocs", $this->_selenium->getTitle());
        $this->_selenium->type("wpTextbox1", "= What's in the Storm Installation Manual =\n\n'''1.0'''\n\nUse this guide to find [[Documentation:Installation:SystemRequirements|system requirements]], [[Documentation:Installation:installalicense|licensing information]], and [http://www.splunk.com/base/Documentation/latest/Installation/Aboutupgradingto4.1:READTHISFIRST procedures for installing or migrating Splunk].\n\n==Find what you need==\nYou can use the table of contents to the left of this panel, or simply search for what you want in the search box in the upper right. \n\nIf you're interested in more specific [http://www.splunk.com/wiki scenarios and best practices], you can visit the [http://www.splunk.com/wiki Splunk Community Wiki] to see how other users Splunk IT.\n\n====Make a PDF====\nIf you'd like a PDF of any version of this manual, click the '''pdf version''' link above the table of contents bar on the left side of this page. A PDF version of the manual is generated on the fly for you, and you can save it or print it out to read later. \n\n[[Category:V:Storm:1.0]]");
        $this->_selenium->click("wpSave");
        $this->_selenium->waitForPageToLoad("30000");
        $this->assertEquals("Documentation:Storm:Installation:WhatsintheStormInstallationManual:1.0 - PonyDocs", $this->_selenium->getTitle());
        $this->_selenium->open("http://lightswitch-ponydocs.splunk.com/index.php?title=Special:UserLogout");
        
        $this->_selenium = NULL;
    }
}