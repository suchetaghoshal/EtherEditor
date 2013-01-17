-- Update the database schema for the EtherEditor extension.
-- License: GNU GPL v2+
-- Author: Mark Holmquist < mtraceur@member.fsf.org >

-- Add group_id
ALTER TABLE /*$wgDBprefix*/ethereditor_pads ADD COLUMN base_revision INTEGER NOT NULL DEFAULT '0';
