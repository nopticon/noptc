CREATE TABLE IF NOT EXISTS _alarms (
	alarm_id INT(11) NOT NULL AUTO_INCREMENT,
	alarm_bio INT(11) NOT NULL,
	alarm_start INT(11) NOT NULL,
	alarm_end INT(11) NOT NULL,
	alarm_bubble INT(11) NOT NULL,
	alarm_email TINYINT(1) NOT NULL,
	PRIMARY KEY (`alarm_id`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS _bio_newsletter (
	newsletter_id INT(11) NOT NULL AUTO_INCREMENT,
	newsletter_bio INT(11) NOT NULL,
	newsletter_receive INT(11) NOT NULL,
	PRIMARY KEY (`newsletter_id`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS _bio_friends (
	friend_id INT(11) NOT NULL AUTO_INCREMENT,
	friend_assoc INT(11) NOT NULL,
	friend_bio INT(11) NOT NULL,
	friend_active INT(11) NOT NULL,
	friend_time INT(11) NOT NULL,
	friend_message VARCHAR(255) NOT NULL,
	PRIMARY KEY (`friend_id`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS _bio_group (
	group_id INT(11) NOT NULL AUTO_INCREMENT,
	group_assoc INT(11) NOT NULL,
	group_bio INT(11) NOT NULL,
	group_time INT(11) NOT NULL,
	PRIMARY KEY (`group_id`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS _bio_rate (
	rate_id INT(11) NOT NULL AUTO_INCREMENT,
	rate_assoc INT(11) NOT NULL,
	rate_bio INT(11) NOT NULL,
	rate_value TINYINT(2) NOT NULL,
	rate_time INT(11) NOT NULL,
	PRIMARY KEY (`rate_id`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS _bio_store (
	store_id INT(11) NOT NULL AUTO_INCREMENT,
	store_bio INT(11) NOT NULL,
	store_field INT(11) NOT NULL,
	store_value TEXT NOT NULL,
	PRIMARY KEY (`store_id`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS _bio_fields (
	field_id INT(11) NOT NULL AUTO_INCREMENT,
	field_alias VARCHAR(25) NOT NULL,
	field_name VARCHAR(50) NOT NULL,
	field_required TINYINT(1) NOT NULL,
	field_show TINYINT(1) NOT NULL,
	field_type VARCHAR(25) NOT NULL,
	field_relation VARCHAR(50) NOT NULL,
	PRIMARY KEY (`field_id`)
) ENGINE=InnoDB;

INSERT INTO _bio_fields (field_alias, field_name, field_required, field_show, field_type, field_relation) VALUES ('email_0', 'Email', 0, 1, 'text', '');
INSERT INTO _bio_fields (field_alias, field_name, field_required, field_show, field_type, field_relation) VALUES ('icq', 'ICQ', 0, 1, 'text', '');
INSERT INTO _bio_fields (field_alias, field_name, field_required, field_show, field_type, field_relation) VALUES ('blog', 'Blog', 0, 1, 'text', '');
INSERT INTO _bio_fields (field_alias, field_name, field_required, field_show, field_type, field_relation) VALUES ('website', 'Sitio web', 0, 1, 'text', '');
INSERT INTO _bio_fields (field_alias, field_name, field_required, field_show, field_type, field_relation) VALUES ('location', 'Ubicaci&oacute;n', 0, 1, 'text', '');
INSERT INTO _bio_fields (field_alias, field_name, field_required, field_show, field_type, field_relation) VALUES ('wlive', 'Windows Live', 0, 1, 'text', '');
INSERT INTO _bio_fields (field_alias, field_name, field_required, field_show, field_type, field_relation) VALUES ('lastfm', 'Last FM', 0, 1, 'text', '');
INSERT INTO _bio_fields (field_alias, field_name, field_required, field_show, field_type, field_relation) VALUES ('occ', 'Ocupaci&oacute;n', 0, 1, 'text', '');
INSERT INTO _bio_fields (field_alias, field_name, field_required, field_show, field_type, field_relation) VALUES ('interests', 'Intereses', 0, 1, 'text', '');
INSERT INTO _bio_fields (field_alias, field_name, field_required, field_show, field_type, field_relation) VALUES ('os', 'Sistema operativo', 0, 1, 'text', '');
INSERT INTO _bio_fields (field_alias, field_name, field_required, field_show, field_type, field_relation) VALUES ('fgenres', 'G&eacute;neros musicales', 0, 1, 'text', '');
INSERT INTO _bio_fields (field_alias, field_name, field_required, field_show, field_type, field_relation) VALUES ('fartists', 'Artistas favoritos', 0, 1, 'text', '');
