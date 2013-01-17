-- MySQL version of the database schema for the EtherEditor extension.
-- License: GNU GPL v2+
-- Author: Mark Holmquist < mtraceur@member.fsf.org >
-- Apparently there's some magic with /*$wgDBprefix*/ and /*$wgDBTableOptions*/

-- Pads
CREATE TABLE IF NOT EXISTS /*$wgDBprefix*/ethereditor_pads (
  pad_id                   INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT,
  ep_pad_id                VARCHAR(255)        NOT NULL,
  group_id                 VARCHAR(18)         NOT NULL,
  page_title               VARCHAR(255)        NOT NULL,
  admin_user               VARCHAR(255)        NOT NULL,
  base_revision            INTEGER             NOT NULL default '0',
  time_created             VARBINARY(14)       NOT NULL default '20120726000000',
  public_pad               TINYINT             NOT NULL default '1'
) /*$wgDBTableOptions*/;

-- Contributors
CREATE TABLE IF NOT EXISTS /*$wgDBprefix*/ethereditor_contribs (
  contrib_id               INTEGER PRIMARY KEY NOT NULL AUTO_INCREMENT,
  pad_id                   INTEGER             NOT NULL,
  username                 VARCHAR(255)        NOT NULL,
  ep_user_id               VARCHAR(18)         NOT NULL,
  kicked                   TINYINT             NOT NULL default '0',
  has_contributed          TINYINT             NOT NULL default '0'
) /*$wgDBTableOptions*/;
