<?php

abstract class AbstractAction extends PHPUnit_Extensions_SeleniumTestCase
{
    /**
     * An array of one or more products to use for this test.
     */
    protected $_products = array();
    
    /**
     * An array of usergroups and whether or not they should be allowed to perform this test with a 1 or a 0.
     */
    protected $_users = array();
    
    /**
     * An array of users with their username and password combinations.
     */
    protected static $_passwords = array
    (
        'admin'          => array('username' => 'Admin',             'password' => 'useradmin'),
        'logged_in'      => array('username' => 'RandomUser',        'password' => 'userrandom'),
        'splunk_preview' => array('username' => 'SplunkPreviewUser', 'password' => 'usersplunkpreview'),
        'storm_preview'  => array('username' => 'StormPreviewUser',  'password' => 'userstormpreview'),
        'employee'       => array('username' => 'EmployeeUser',      'password' => 'useremployee'),
        'docteam'        => array('username' => 'DocteamUser',       'password' => 'userdocteam'),
        'splunk_docteam' => array('username' => 'SplunkDocteamUser', 'password' => 'usersplunkdocteam'),
        'storm_docteam'  => array('username' => 'StormDocteamUser',  'password' => 'userstormdocteam')
    );
    
    public function setUp()
    {
        $this->setBrowser("*chrome");
        $this->setBrowserUrl("http://lightswitch-ponydocs.splunk.com/");
        $this->setHost('10.1.7.225');
        $this->setPort(4444);
    }
    
    protected function _login($user)
    {
        $this->open("http://lightswitch-ponydocs.splunk.com/index.php?title=Special:UserLogin");
        $this->assertEquals("Log in / create account - PonyDocs", $this->getTitle());
        $this->type("wpName1", self::$_passwords[$user]['username']);
        $this->type("wpPassword1", self::$_passwords[$user]['password']);
        $this->click("wpLoginAttempt");
        $this->waitForPageToLoad("30000");
        $this->assertEquals("Main Page - PonyDocs", $this->getTitle());
    }
    
    protected function _logout()
    {
        $this->open("http://lightswitch-ponydocs.splunk.com/index.php?title=Special:UserLogout");
        $this->deleteAllVisibleCookies();
    }
}