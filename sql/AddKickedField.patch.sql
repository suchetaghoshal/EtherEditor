-- Update the database schema for the EtherEditor extension.
-- License: GNU GPL v2+
-- Author: Mark Holmquist < mtraceur@member.fsf.org >

-- Add kicked
ALTER TABLE /*$wgDBprefix*/ethereditor_contribs ADD COLUMN kicked TINYINT NOT NULL DEFAULT 0;
