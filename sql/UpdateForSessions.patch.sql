-- Update the database schema for the EtherEditor extension.
-- License: GNU GPL v2+
-- Author: Mark Holmquist < mtraceur@member.fsf.org >

-- Add time_created
ALTER TABLE /*$wgDBprefix*/ethereditor_pads ADD COLUMN time_created VARBINARY(14) NOT NULL DEFAULT '20120726000000';
