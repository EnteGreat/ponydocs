<?php
class Example extends PHPUnit_Extensions_SeleniumTestCase
{
  protected function setUp()
  {
    $this->setBrowser("*chrome");
    $this->setBrowserUrl("http://tecate-ponydocs.splunk.com/");
  }

  public function testMyTestCase()
  {
    $this->open("/Main_Page");
    $this->click("link=Log out");
    $this->waitForPageToLoad("30000");
    $this->click("link=Log in / create account");
    $this->waitForPageToLoad("30000");
    $this->type("wpName1", "Admin");
    $this->type("wpPassword1", "useradmin");
    $this->click("wpLoginAttempt");
    $this->waitForPageToLoad("30000");
    $this->assertTrue($this->isElementPresent("link=Log out"));
    $this->select("docsManualSelect", "label=Splunk User Manual");
    $this->waitForPageToLoad("30000");
    $this->click("link=Ways to access Splunk");
    $this->waitForPageToLoad("30000");
    $this->click("link=Move");
    $this->waitForPageToLoad("30000");
    $this->type("wpNewTitle", "Documentation:Splunk:User:WaystoaccessSplunktest:1.0");
    $this->click("wpMove");
    $this->waitForPageToLoad("30000");
    $this->assertEquals("Move succeeded", $this->getText("firstHeading"));
  }
}
?>