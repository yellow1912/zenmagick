#
# Create ZenMagick block admin tables
#

DROP TABLE IF EXISTS block_groups;
CREATE TABLE block_groups (
  block_group_id int(11) NOT NULL auto_increment,
  group_name varchar(32) NOT NULL,
  description text,
  PRIMARY KEY (block_group_id),
  UNIQUE(group_name)
) TYPE=MyISAM;

DROP TABLE IF EXISTS blocks_to_groups;
CREATE TABLE blocks_to_groups (
  blocks_to_groups_id int(11) NOT NULL auto_increment,
  block_group_id int(11),
  block_name varchar(32) NOT NULL,
  PRIMARY KEY (blocks_to_groups_id),
  FOREIGN KEY (block_group_id) REFERENCES block_groups (block_group_id) ON DELETE CASCADE
) TYPE=MyISAM;

DROP TABLE IF EXISTS block_config;
CREATE TABLE block_config (
  block_config_id int(11) NOT NULL auto_increment,
  blocks_to_groups_id int(11),
  value text,
  definition text,
  PRIMARY KEY (block_config_id),
  FOREIGN KEY (blocks_to_groups_id) REFERENCES blocks_to_groups (blocks_to_groups_id) ON DELETE CASCADE
) TYPE=MyISAM;
