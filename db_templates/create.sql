
SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

CREATE TABLE IF NOT EXISTS `###PREFIX###elements` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `modified` timestamp NOT NULL default '0000-00-00 00:00:00' on update CURRENT_TIMESTAMP,
  `title` varchar(32) collate ###COLLATION### NOT NULL,
  `form` varchar(64) collate ###COLLATION### NOT NULL,
  `icon` varchar(127) collate ###COLLATION### NOT NULL default 'glossy_3d_blue_orbs2_072.png',
  `description` varchar(255) collate ###COLLATION### NOT NULL,
  `help` varchar(1024) collate ###COLLATION### NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `form` (`form`)
) ENGINE=MyISAM  DEFAULT CHARSET=###CHARSET### COLLATE=###COLLATION### ;


CREATE TABLE IF NOT EXISTS `###PREFIX###pages` (
  `id` smallint(5) unsigned NOT NULL auto_increment,
  `attach_id` varchar(256) collate ###COLLATION### NOT NULL default '0' COMMENT 'parent document',
  `tocpos` mediumint(9) NOT NULL default '999',
  `type` text collate ###COLLATION### NOT NULL COMMENT 'page/embed/txt/?',
  `isdraft` tinyint(1) NOT NULL default '1',
  `alias` varchar(41) collate ###COLLATION### NOT NULL,
  `keywords` text collate ###COLLATION### NOT NULL COMMENT 'relevance count, keywords in body',
  `custom_keywords` text collate ###COLLATION### NOT NULL COMMENT 'keywords by user',
  `body` mediumtext collate ###COLLATION### NOT NULL COMMENT 'PageBody entity encoded html',
  `title` varchar(31) collate ###COLLATION### NOT NULL,
  `creator` varchar(32) collate ###COLLATION### NOT NULL,
  `created` datetime NOT NULL,
  `lasteditedby` varchar(32) collate ###COLLATION### NOT NULL,
  `lastmodified` timestamp NOT NULL default '0000-00-00 00:00:00' on update CURRENT_TIMESTAMP,
  `editors` varchar(512) collate ###COLLATION### NOT NULL,
  `showtitle` tinyint(1) NOT NULL,
  `ft_indexed` mediumtext collate ###COLLATION### NOT NULL COMMENT 'Regenerated cron-wise',
  `ft_lastmodified` timestamp NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `alias` (`alias`),
  FULLTEXT KEY `ft_indexed` (`ft_indexed`)
) ENGINE=MyISAM  DEFAULT CHARSET=###CHARSET### COLLATE=###COLLATION###;


CREATE TABLE IF NOT EXISTS `###PREFIX###products` (
  `id` mediumint(8) unsigned NOT NULL auto_increment,
  `type` varchar(32) character set ###CHARSET### collate ###COLLATION### NOT NULL default 'product',
  `lastmodified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `creator` varchar(32) character set ###CHARSET### collate ###COLLATION### NOT NULL,
  `created` datetime NOT NULL,
  `title` varchar(64) character set ###CHARSET### collate ###COLLATION### NOT NULL,
  `keywords` text character set ###CHARSET### collate ###COLLATION### NOT NULL,
  `ft_indexed` text character set ###CHARSET### collate ###COLLATION### NOT NULL,
  `ft_lastmodified` timestamp NOT NULL default '0000-00-00 00:00:00',
  `thumbs` varchar(1023) character set ###CHARSET### collate ###COLLATION### NOT NULL default 'fileadmin/products/nothumb.jpg',
  `images` varchar(1023) character set ###CHARSET### collate ###COLLATION### NOT NULL default 'fileadmin/products/nothumb.jpg',
  `thumbnail` varchar(16192) character set ###CHARSET### collate ###COLLATION### NOT NULL default 'R0lGODlhUABQAOYAALOzs93d3dPT08zMzPLy8vT09Pr6+vj4+O7u7vb29vDw8K2trf7+/uzs7Ojo 6Orq6ubm5ru7u8LCwtnZ2eTk5OLi4uDg4P3+/v79/v39/v79/fLx8vf49/z7+/Dv8PHy8vLx8fTz 8/z7/P3+/f7+/ff4+PTz9Pv8/Ovs6/Ly8fb29ezs6/v8+/z8++/w8O/v7vb19e3u7urp6fX19e3u 7e7u7e/v7/T08/Pz8/P09PP08+nq6fj49/b19vn6+ubl5ff39/r6+fHy8evs7PDw7/v7++fn5+Pk 4/r5+fX29e3t7ezr7Onp6eTj5OHi4ufo6OTk4+fo5+jo5+nq6urp6u7t7uvr6uLh4vn6+fj3+Pf3 9u/w7+3t7OTj4+Xm5ubm5eHi4ePj4/n5+efn5/X29urq6eXm5eLi4eDg3/Hx8evr69/f3uHh4eLh 4d/g4N/f3+Pk5OXl5fr5+tvb29fW1uDf4K+wr8jIyNrb29zc3Obl5t/g3/z8/P39/cbGxv///yH5 BAAAAAAALAAAAABQAFAAAAf/gH6Cg4SFhoeIiYqLjIN/j5CRkpOUDH8MlpeYFxoMfRp9oaF8pHxF p6cGBkhirQdiBwdAWgm1WjMFM7ozgpS+v5GWmMMkJBckDBoYonzMqKkGrbCxsbO1QAm6ubgFvcDf k8PiDBgkIyMZfc3qpc+q0a+w09hatNna3Dje4MDC48PoRohi1u5ZkWjSqMkCgg2btgIQc+AgsI+f JH/DNHHCoEGgBlCjSpk6eFCVtISyrtV6mKvARIp+LFbS9I8BiQznRoAkFRJaSVcKD9iqdW8XxJcw ZQZ7VBMDA3ShLjAjWNIAtHgKGc5yiIubS6QVlUISh+HCCAYXLqQb2KxgVZOx/6aVKEF0JUuXBPJu ABFW7LiyGUAODEnqhNV3BoJMowakhL0EKrJBLGBCItK9ffvRHJc2lAapbEWKUAUNXlC69maQeZjj a968adJk/uWPZrlz6aCqIyxCxNtV8qhl4ZCAIeQeXm+4JvBhw4Y0Cmb7yogJQ4a0awny5oP4HdAD PDiUsAZZMsQQIXBMBEEgxXMF0WPKrPn5s8B1PAu34P779AHi9qiQRHI4SJSXECB8AJ0HREg31ljD lKOWMoOJVMoJvyn23Xh1wTDgZCao9xJ7sbmggA3xKbUJMbqNslspHZCkihyKBcUBDzzUpYIKrFnG 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 MdsITvu0UBu1hBAIADs=',
  `specifications` text character set ###CHARSET### collate ###COLLATION### NOT NULL,
  `features` text character set ###CHARSET### collate ###COLLATION### NOT NULL,
  `description` text character set ###CHARSET### collate ###COLLATION### NOT NULL,
  `discount_price` mediumint(9) NOT NULL default '0',
  `price` mediumint(9) NOT NULL default '0',
  `category` varchar(128) character set ###CHARSET### collate ###COLLATION### default NULL,
  PRIMARY KEY  (`id`),
  FULLTEXT KEY `ft_indexed` (`ft_indexed`)
) ENGINE=MyISAM  DEFAULT CHARSET=###CHARSET### ;


CREATE TABLE IF NOT EXISTS `###PREFIX###resources` (
  `attach_id` varchar(128) collate ###COLLATION### NOT NULL,
  `id` int(11) NOT NULL auto_increment,
  `creator` varchar(32) collate ###COLLATION### NOT NULL,
  `created` datetime NOT NULL,
  `lastmodified` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `lasteditedby` varchar(32) collate ###COLLATION### NOT NULL,
  `type` varchar(12) collate ###COLLATION### NOT NULL COMMENT 'script/image/media/html',
  `mimetype` varchar(64) collate ###COLLATION### NOT NULL,
  `media` varchar(32) collate ###COLLATION### NOT NULL COMMENT 'css target media',
  `uri` varchar(1500) collate ###COLLATION### NOT NULL COMMENT 'absolute URL',
  `alias` varchar(41) collate ###COLLATION### NOT NULL,
  `body` text collate ###COLLATION### NOT NULL,
  `comment` varchar(146) collate ###COLLATION### NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=###CHARSET### COLLATE=###COLLATION###;


CREATE TABLE IF NOT EXISTS `###PREFIX###sessions` (
  `userid` varchar(32) NOT NULL,
  `ip` tinytext NOT NULL,
  `useragent` tinytext NOT NULL,
  `sessionid` tinytext NOT NULL,
  `lastvisit` tinytext NOT NULL,
  `expires` timestamp NOT NULL default '0000-00-00 00:00:00'
) ENGINE=MyISAM DEFAULT CHARSET=###CHARSET### COLLATE=###COLLATION###;


CREATE TABLE IF NOT EXISTS `###PREFIX###users` (
  `userid` varchar(64) NOT NULL,
  `username` varchar(64) NOT NULL,
  `email` varchar(75) NOT NULL,
  `firstname` varchar(128) NOT NULL,
  `surname` varchar(128) NOT NULL,
  `password` varchar(128) NOT NULL,
  `lastlogin` timestamp NOT NULL default '0000-00-00 00:00:00',
  `inactive` tinyint(4) NOT NULL,
  UNIQUE KEY `userid` (`userid`)
) ENGINE=MyISAM  DEFAULT CHARSET=###CHARSET### COLLATE=###COLLATION###;

