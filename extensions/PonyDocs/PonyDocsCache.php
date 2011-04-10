<?php

class PonyDocsCache 
{
	private $dbr;

	private function __construct()
	{
		$this->dbr = wfGetDB(DB_MASTER);
		$this->expire();
	}
	
	static public function & getInstance() 
	{
		if( !self::$instance )
		{
			self::$instance = new PonyDocsCache();
		}
		return self::$instance;
	}
	
	public function put( $key, $data, $expires )
	{
		if(PONYDOCS_CACHE_ENABLED) {
			$data = mysql_real_escape_string(serialize($data));
			$query = "INSERT INTO splunk_cache VALUES('$key', '$expires',  '$data')";
			try {
				$this->dbr->query($query);
			} catch (Exception $ex){
				error_log("FATAL [PonyDocsCache::put] DB call failed on Line ".$ex->getLine()." on file ".$ex->getFile().", error Message is: \n".$ex->getMessage()."Stack Trace is:".$ex->getTraceAsString());
			}
		}
		return true;		
	}
	
	public function get( $key )
	{
		if(PONYDOCS_CACHE_ENABLED) {
			$query = "SELECT *  FROM splunk_cache WHERE tag = '$key'";
			try {
				$res = $this->dbr->query($query);
				$obj = $this->dbr->fetchObject($res);
				if($obj) {
					return unserialize($obj->data);
				}
			} catch(Exception $ex) {
				error_log("FATAL [PonyDocsCache::get] DB call failed on Line " . $ex->getLine()." on file " . $ex->getFile(). ", error Message is: \n" . $ex->getMessage(). " Stack Trace Is: " . $ex->getTraceAsString());
			}
			return unserialize( $output );			
		}
		return null;
	}
	
	public function remove( $key )
	{
		if(PONYDOCS_CACHE_ENABLED) {
			$query = "DELETE *  FROM splunk_cache WHERE tag = '$key'";
			try {
				$res = $this->dbr->query($query);
			} catch(Exception $ex) {
				error_log("FATAL [PonyDocsCache::remove] DB call failed on Line " . $ex->getLine()." on file " . $ex->getFile(). ", error Message is: \n" . $ex->getMessage(). " Stack Trace Is: " . $ex->getTraceAsString());
			}
		}
		return true;
	}	

	public function expire() {
		if(PONYDOCS_CACHE_ENABLED) {
			$now = time();
			$query = "DELETE *  FROM splunk_cache WHERE expire < $now";
			try {
				$res = $this->dbr->query($query);
			} catch(Exception $ex) {
				error_log("FATAL [PonyDocsCache::expire] DB call failed on Line " . $ex->getLine()." on file " . $ex->getFile(). ", error Message is: \n" . $ex->getMessage(). " Stack Trace Is: " . $ex->getTraceAsString());
			}
		}
		return true;
	}
};
