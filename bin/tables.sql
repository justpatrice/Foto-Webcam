-- phpMyAdmin SQL Dump
-- version 3.5.3
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Erstellungszeit: 29. Aug 2013 um 17:09
-- Server Version: 5.1.70-0ubuntu0.10.04.1

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Datenbank: `fotowebcam`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `webcam_bestof`
--

CREATE TABLE IF NOT EXISTS `webcam_bestof` (
  `cam` varchar(80) NOT NULL,
  `path` varchar(40) NOT NULL,
  `comment` text NOT NULL,
  `added` datetime NOT NULL,
  `add_ip` varchar(20) NOT NULL,
  `deleted` datetime NOT NULL,
  `del_ip` varchar(20) NOT NULL,
  PRIMARY KEY (`cam`,`path`),
  KEY `deleted` (`deleted`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='The best shots';

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `webcam_day`
--

CREATE TABLE IF NOT EXISTS `webcam_day` (
  `cam` varchar(80) NOT NULL,
  `path` varchar(40) NOT NULL,
  PRIMARY KEY (`cam`,`path`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Days (yyyy/mm/dd) with images stored';

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `webcam_exif`
--

CREATE TABLE IF NOT EXISTS `webcam_exif` (
  `cam` varchar(80) NOT NULL,
  `path` varchar(40) NOT NULL,
  `exif` text NOT NULL,
  PRIMARY KEY (`cam`,`path`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='EXIF data for webcam-images';

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `webcam_image`
--

CREATE TABLE IF NOT EXISTS `webcam_image` (
  `cam` varchar(80) NOT NULL,
  `path` varchar(40) NOT NULL,
  `stamp` datetime NOT NULL,
  `have_lm` tinyint(4) NOT NULL,
  `have_hu` tinyint(4) NOT NULL,
  `have_ex` tinyint(4) NOT NULL,
  PRIMARY KEY (`cam`,`path`),
  KEY `stamp` (`stamp`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='All existing webcam images';

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `webcam_labels`
--

CREATE TABLE IF NOT EXISTS `webcam_labels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `wc` varchar(80) COLLATE utf8_bin NOT NULL,
  `valid_from` datetime NOT NULL DEFAULT '2000-01-01 00:00:00',
  `valid_to` datetime NOT NULL DEFAULT '2099-01-01 00:00:00',
  `x` float NOT NULL,
  `y` float NOT NULL,
  `txt` varchar(500) CHARACTER SET utf8 NOT NULL,
  `href` varchar(200) COLLATE utf8_bin NOT NULL,
  `rev` int(11) NOT NULL COMMENT 'Flag: Textfahne nach links',
  `res` int(11) NOT NULL COMMENT 'Nur Aufloesung: 0=alle 1=hd 2=fullsize',
  PRIMARY KEY (`id`),
  KEY `wc` (`wc`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Hotspot-Labels' AUTO_INCREMENT=40 ;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `webcam_session`
--

CREATE TABLE IF NOT EXISTS `webcam_session` (
  `username` varchar(20) NOT NULL,
  `token` varchar(100) NOT NULL,
  `begin` datetime NOT NULL,
  `expires` datetime NOT NULL,
  `last_act` datetime NOT NULL,
  `ip` varchar(20) NOT NULL,
  `last_ip` varchar(20) NOT NULL,
  PRIMARY KEY (`token`),
  KEY `callsign` (`username`),
  KEY `last_act` (`last_act`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Running sessions on web interface';

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `webcam_user`
--

CREATE TABLE IF NOT EXISTS `webcam_user` (
  `username` varchar(40) NOT NULL,
  `fullname` varchar(80) NOT NULL,
  `email` varchar(200) NOT NULL,
  `pw` varchar(200) NOT NULL,
  `perm` varchar(200) NOT NULL,
  `last_login` datetime NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Webcam-Administratos';

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `webcam_wx`
--

CREATE TABLE IF NOT EXISTS `webcam_wx` (
  `wc` varchar(100) NOT NULL,
  `field` varchar(100) NOT NULL,
  `stamp` datetime NOT NULL,
  `day` date NOT NULL,
  `val` double NOT NULL,
  `raw_val` varchar(100) NOT NULL,
  KEY `field` (`field`),
  KEY `stamp` (`stamp`),
  KEY `day` (`day`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Wetterdaten';

