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
    $this->click("link=Watch");
    for ($second = 0; ; $second++) {
        if ($second >= 60) $this->fail("timeout");
        try {
            if ($this->isElementPresent("link=watchlist")) break;
        } catch (Exception $e) {}
        sleep(1);
    }

    $this->click("link=watchlist");
    $this->waitForPageToLoad("30000");
    $this->assertTrue($this->isElementPresent("link=exact:Documentation:Splunk:User:WaystoaccessSplunk:1.0"));
    for ($second = 0; ; $second++) {
        if ($second >= 60) $this->fail("timeout");
        try {
            if ($this->isElementPresent("link=View and edit watchlist")) break;
        } catch (Exception $e) {}
        sleep(1);
    }

    $this->click("link=View and edit watchlist");
    $this->waitForPageToLoad("30000");
    $this->click("titles[]");
    $this->click("css=input[type=submit]");
    $this->waitForPageToLoad("30000");
    $this->assertTrue($this->isTextPresent("1 title was removed from your watchlist:"));
  }
}
?>