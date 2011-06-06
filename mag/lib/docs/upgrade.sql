CREATE TABLE IF NOT EXISTS _bio_auth_fields (
  field_id int(11) NOT NULL auto_increment,
  field_alias varchar(50) NOT NULL,
  field_name varchar(50) NOT NULL,
  field_global tinyint(1) NOT NULL,
  PRIMARY KEY (field_id)
) ENGINE=InnoDB;

CREATE TABLE _reference_likes (
	like_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
	like_ref INT(11) NOT NULL,
	like_uid INT(11) NOT NULL,
	like_time INT(11) NOT NULL
) ENGINE = InnoDB;

CREATE TABLE _pages (
	page_id INT( 11 ) NOT NULL ,
	page_alias VARCHAR( 100 ) NOT NULL ,
	page_subject VARCHAR( 255 ) NOT NULL ,
	page_content TEXT NOT NULL ,
	page_tags VARCHAR( 255 ) NOT NULL ,
	page_author INT( 11 ) NOT NULL ,
	page_time INT( 11 ) NOT NULL
) ENGINE = InnoDB;

UPDATE _config SET config_value = '15' WHERE config_name = 'topics_per_page';
