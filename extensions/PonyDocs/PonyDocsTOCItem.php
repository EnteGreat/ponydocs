<?php

/**
 * This class represents a single item in a TOC which is an actual active TOPIC;  i.e., not the section headers.  The concept
 * is to create one per item and hold them, in order, in an array so we can easily obtain our previous and next links dynamically
 * based on the current topic.  
 */
class PonyDocsTOCItem
{
	protected $mHeader;
	
	protected $mHref;
	
	public function __construct( $header, $href )
	{
		$this->mHeader = $header;
		$this->mHref = $href;
	}
	
	public function getHeader( )
	{
		return $this->mHeader;
	}
	
	public function getHref( )
	{
		return $this->mHref;
	}
};
