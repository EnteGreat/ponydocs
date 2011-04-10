CREATE TABLE `ponydocs_doclinks` (
	  `from_link` varchar(80) collate utf8_unicode_ci NOT NULL default '',
	  `to_link` varchar(80) collate utf8_unicode_ci NOT NULL default '',
	  KEY `from` (`from_link`,`to_link`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
