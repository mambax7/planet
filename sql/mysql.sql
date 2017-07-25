## phpMyAdmin SQL Dump
## version 2.6.3-pl1
## http://www.phpmyadmin.net
## 
## Host: localhost
## Generation Time: Nov 13, 2005 at 08:24 PM
## Server version: 4.1.13
## PHP Version: 5.1.0RC1
## 
## Database: `forge`
## 

## --------------------------------------------------------

## 
## Table structure for table `planet_article`
## 

CREATE TABLE `planet_article` (
  `art_id`       INT(10) UNSIGNED      NOT NULL AUTO_INCREMENT,
  `blog_id`      MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0',
  `art_author`   VARCHAR(255)          NOT NULL DEFAULT '',
  `art_title`    VARCHAR(255)          NOT NULL DEFAULT '',
  `art_link`     VARCHAR(255)          NOT NULL DEFAULT '',
  `art_content`  TEXT,
  `art_time`     INT(10) UNSIGNED      NOT NULL DEFAULT '0',
  `art_views`    INT(10) UNSIGNED      NOT NULL DEFAULT '0',
  `art_rating`   INT(10) UNSIGNED      NOT NULL DEFAULT '0',
  `art_rates`    INT(10) UNSIGNED      NOT NULL DEFAULT '0',
  `art_comments` INT(10) UNSIGNED      NOT NULL DEFAULT '0',
  PRIMARY KEY (`art_id`),
  KEY `blog_id`  (`blog_id`),
  KEY `art_title`  (`art_title`)
)
  ENGINE = MyISAM;

## --------------------------------------------------------

## 
## Table structure for table `planet_blog`
## 

CREATE TABLE `planet_blog` (
  `blog_id`        MEDIUMINT(8) UNSIGNED NOT NULL AUTO_INCREMENT,
  `blog_title`     VARCHAR(255)          NOT NULL DEFAULT '',
  `blog_desc`      VARCHAR(255)          NOT NULL DEFAULT '',
  `blog_feed`      VARCHAR(255)          NOT NULL DEFAULT '',
  `blog_language`  VARCHAR(32)           NOT NULL DEFAULT '',
  `blog_charset`   VARCHAR(32)           NOT NULL DEFAULT '',
  `blog_link`      VARCHAR(255)          NOT NULL DEFAULT '',
  `blog_image`     VARCHAR(255)          NOT NULL DEFAULT '',
  `blog_trackback` VARCHAR(255)          NOT NULL DEFAULT '',
  `blog_submitter` VARCHAR(255)          NOT NULL DEFAULT '',
  `blog_status`    TINYINT(1) UNSIGNED   NOT NULL DEFAULT '1',
  `blog_key`       VARCHAR(32)           NOT NULL DEFAULT '',
  `blog_time`      INT(10) UNSIGNED      NOT NULL DEFAULT '0',
  `blog_rating`    INT(10) UNSIGNED      NOT NULL DEFAULT '0',
  `blog_rates`     INT(10) UNSIGNED      NOT NULL DEFAULT '0',
  `blog_marks`     INT(10) UNSIGNED      NOT NULL DEFAULT '0',
  PRIMARY KEY (`blog_id`),
  KEY `blog_title`  (`blog_title`),
  KEY `blog_feed`  (`blog_feed`)
)
  ENGINE = MyISAM;

## --------------------------------------------------------

## 
## Table structure for table `planet_blogcat`
## 

CREATE TABLE `planet_blogcat` (
  `bc_id`   INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `blog_id` INT(11) UNSIGNED NOT NULL DEFAULT '0',
  `cat_id`  INT(11) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`bc_id`),
  KEY `art_id`    (`blog_id`, `cat_id`)
)
  ENGINE = MyISAM;

## --------------------------------------------------------

## 
## Table structure for table `planet_bookmark`
## 

CREATE TABLE `planet_bookmark` (
  `bm_id`   INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `blog_id` INT(11) UNSIGNED NOT NULL DEFAULT '0',
  `bm_uid`  INT(11) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`bm_id`),
  KEY `blog_id`  (`blog_id`),
  KEY `bm_uid`    (`bm_uid`)
)
  ENGINE = MyISAM;

## --------------------------------------------------------

## 
## Table structure for table `planet_category`
## 

CREATE TABLE `planet_category` (
  `cat_id`    MEDIUMINT(4) UNSIGNED NOT NULL AUTO_INCREMENT,
  `cat_title` VARCHAR(255)          NOT NULL DEFAULT '',
  `cat_order` MEDIUMINT(4) UNSIGNED NOT NULL DEFAULT '1',
  PRIMARY KEY (`cat_id`),
  KEY `cat_title`  (`cat_title`)
)
  ENGINE = MyISAM;

## --------------------------------------------------------

## 
## Table structure for table `planet_rate`
## 

CREATE TABLE `planet_rate` (
  `rate_id`     INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `art_id`      INT(11) UNSIGNED    NOT NULL DEFAULT '0',
  `blog_id`     INT(11) UNSIGNED    NOT NULL DEFAULT '0',
  `rate_uid`    INT(11) UNSIGNED    NOT NULL DEFAULT '0',
  `rate_ip`     INT(11) UNSIGNED    NOT NULL DEFAULT '0',
  `rate_rating` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
  `rate_time`   INT(10) UNSIGNED    NOT NULL DEFAULT '0',
  PRIMARY KEY (`rate_id`),
  KEY `art_id`    (`art_id`),
  KEY `blog_id`  (`blog_id`)
)
  ENGINE = MyISAM;
