CREATE TABLE `ponydocs_doclinks` (
	  `from_link` varchar(80) collate utf8_unicode_ci NOT NULL default '',
	  `to_link` varchar(80) collate utf8_unicode_ci NOT NULL default '',
	  KEY `from` (`from_link`,`to_link`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `ponydocs_cache` (
	  `cachekey` varchar(255) collate utf8_unicode_ci NOT NULL default '',
	  `expires` INT NOT NULL default 0,
	  `data` longtext collate utf8_unicode_ci,
	  PRIMARY KEY  (`cachekey`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
