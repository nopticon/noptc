CREATE TABLE IF NOT EXISTS `_comments` (
  `comment_id` int(11) NOT NULL auto_increment,
  `comment_tree` int(11) NOT NULL,
  `comment_uid` int(11) NOT NULL,
  `comment_username` varchar(50) NOT NULL,
  `comment_email` varchar(50) NOT NULL,
  `comment_website` varchar(255) NOT NULL,
  `comment_ip` varchar(50) NOT NULL,
  `comment_status` tinyint(1) NOT NULL,
  `comment_time` int(11) NOT NULL,
  `comment_message` text NOT NULL,
  PRIMARY KEY  (`comment_id`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `_config` (
  `config_name` varchar(255) NOT NULL,
  `config_value` varchar(255) NOT NULL,
  PRIMARY KEY  (`config_name`)
) ENGINE=InnoDB;

INSERT INTO `_config` (`config_name`, `config_value`) VALUES('session_last_gc', '1238532403');
INSERT INTO `_config` (`config_name`, `config_value`) VALUES('default_lang', 'es');
INSERT INTO `_config` (`config_name`, `config_value`) VALUES('address', '');
INSERT INTO `_config` (`config_name`, `config_value`) VALUES('cookie_name', 'xi');
INSERT INTO `_config` (`config_name`, `config_value`) VALUES('session_length', '3600');
INSERT INTO `_config` (`config_name`, `config_value`) VALUES('cookie_domain', '');
INSERT INTO `_config` (`config_name`, `config_value`) VALUES('cookie_path', '/');
INSERT INTO `_config` (`config_name`, `config_value`) VALUES('session_gc', '3600');
INSERT INTO `_config` (`config_name`, `config_value`) VALUES('xs_def_template', '');
INSERT INTO `_config` (`config_name`, `config_value`) VALUES('xs_use_cache', '0');
INSERT INTO `_config` (`config_name`, `config_value`) VALUES('xs_auto_compile', '1');
INSERT INTO `_config` (`config_name`, `config_value`) VALUES('xs_check_switches', '0');
INSERT INTO `_config` (`config_name`, `config_value`) VALUES('xs_warn_includes', '1');
INSERT INTO `_config` (`config_name`, `config_value`) VALUES('max_login_attempts', '5');
INSERT INTO `_config` (`config_name`, `config_value`) VALUES('mail_server', '');
INSERT INTO `_config` (`config_name`, `config_value`) VALUES('mail_port', '110');
INSERT INTO `_config` (`config_name`, `config_value`) VALUES('mail_ticket_login', '');
INSERT INTO `_config` (`config_name`, `config_value`) VALUES('mail_ticket_key', '');
INSERT INTO `_config` (`config_name`, `config_value`) VALUES('default_email', '');
INSERT INTO `_config` (`config_name`, `config_value`) VALUES('site_title', '');
INSERT INTO `_config` (`config_name`, `config_value`) VALUES('default_email_comments', '');

CREATE TABLE IF NOT EXISTS `_downloads` (
  `download_id` int(11) NOT NULL auto_increment,
  `article_id` int(11) NOT NULL default '0',
  `download_alias` varchar(255) NOT NULL default '',
  `download_order` mediumint(5) NOT NULL default '0',
  `download_es_title` varchar(255) NOT NULL default '',
  `download_en_title` varchar(255) NOT NULL default '',
  `download_desc` varchar(255) NOT NULL default '',
  `download_checksum` varchar(50) NOT NULL default '',
  `download_extension` varchar(5) NOT NULL default '',
  `download_filetype` varchar(20) NOT NULL default '',
  `download_filesize` int(11) NOT NULL default '0',
  `download_count` int(11) NOT NULL default '0',
  PRIMARY KEY  (`download_id`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `_form_fields` (
  `form_id` int(11) NOT NULL auto_increment,
  `form_tree` int(11) NOT NULL default '0',
  `form_order` smallint(5) NOT NULL default '0',
  `form_required` tinyint(1) NOT NULL default '0',
  `form_legend` varchar(255) NOT NULL,
  `form_regex` varchar(255) NOT NULL,
  `form_name` varchar(255) NOT NULL,
  `form_type` varchar(255) NOT NULL,
  `form_key` char(2) NOT NULL,
  PRIMARY KEY  (`form_id`)
) ENGINE=InnoDB;

INSERT INTO `_form_fields` (`form_id`, `form_tree`, `form_order`, `form_required`, `form_legend`, `form_regex`, `form_name`, `form_type`, `form_key`) VALUES(1, 0, 10, 1, 'Nombre y apellido', '^(.*?)$', 'nombre', 'text', 'n');
INSERT INTO `_form_fields` (`form_id`, `form_tree`, `form_order`, `form_required`, `form_legend`, `form_regex`, `form_name`, `form_type`, `form_key`) VALUES(2, 0, 20, 1, 'Correo Electr&oacute;nico', '^.*?@(.*?\\.)?[a-z0-9\\-]+\\.[a-z]{2,4}$', 'email', 'text', 'e');
INSERT INTO `_form_fields` (`form_id`, `form_tree`, `form_order`, `form_required`, `form_legend`, `form_regex`, `form_name`, `form_type`, `form_key`) VALUES(3, 0, 30, 1, 'Asunto', '^(.*?)$', 'subject', 'text', 's');
INSERT INTO `_form_fields` (`form_id`, `form_tree`, `form_order`, `form_required`, `form_legend`, `form_regex`, `form_name`, `form_type`, `form_key`) VALUES(4, 0, 40, 1, 'Mensaje', '', 'message', 'textarea', 'm');
INSERT INTO `_form_fields` (`form_id`, `form_tree`, `form_order`, `form_required`, `form_legend`, `form_regex`, `form_name`, `form_type`, `form_key`) VALUES(14, 0, 11, 0, 'Tel&eacute;fono', '', 'telefono', 'text', '');
INSERT INTO `_form_fields` (`form_id`, `form_tree`, `form_order`, `form_required`, `form_legend`, `form_regex`, `form_name`, `form_type`, `form_key`) VALUES(15, 0, 21, 0, 'Pa&iacute;s', '', 'pais', 'text', '');

CREATE TABLE IF NOT EXISTS `_images` (
  `image_id` int(11) NOT NULL auto_increment,
  `image_tree` int(11) NOT NULL default '0',
  `image_footer` text NOT NULL,
  `image_en_footer` text NOT NULL,
  `image_width` mediumint(5) NOT NULL default '0',
  `image_height` mediumint(5) NOT NULL default '0',
  `image_extension` varchar(5) NOT NULL default '',
  PRIMARY KEY  (`image_id`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `_members` (
  `user_id` int(11) NOT NULL auto_increment,
  `user_type` smallint(2) NOT NULL,
  `user_active` tinyint(1) NOT NULL,
  `user_internal` tinyint(1) NOT NULL,
  `user_mtype` tinyint(1) NOT NULL,
  `user_login` varchar(25) NOT NULL,
  `user_username` varchar(25) NOT NULL,
  `user_password` varchar(255) NOT NULL,
  `user_lastvisit` int(11) NOT NULL,
  `user_auto_session` tinyint(1) NOT NULL,
  `user_current_ip` varchar(50) NOT NULL,
  `user_lastpage` varchar(255) NOT NULL,
  `user_firstname` varchar(255) NOT NULL,
  `user_lastname` varchar(255) NOT NULL,
  `user_name_show` varchar(255) NOT NULL,
  `user_email` varchar(255) NOT NULL,
  `user_gender` tinyint(1) NOT NULL,
  `user_date` int(11) NOT NULL,
  `user_dateformat` varchar(15) NOT NULL,
  `user_timezone` tinyint(3) NOT NULL,
  `user_dst` tinyint(1) NOT NULL,
  `user_login_tries` smallint(2) NOT NULL,
  `user_stats` tinyint(1) NOT NULL,
  PRIMARY KEY  (`user_id`)
) ENGINE=InnoDB;

INSERT INTO `_members` (`user_id`, `user_type`, `user_active`, `user_internal`, `user_mtype`, `user_login`, `user_username`, `user_password`, `user_lastvisit`, `user_auto_session`, `user_current_ip`, `user_lastpage`, `user_firstname`, `user_lastname`, `user_name_show`, `user_email`, `user_gender`, `user_date`, `user_dateformat`, `user_timezone`, `user_dst`, `user_login_tries`, `user_stats`) VALUES(1, 0, 0, 1, 0, '', 'nobody', 'nobody', 0, 0, '', '', 'nobody', 'nobody', '', '', 0, 0, 'd M Y H:i', -6, 0, 0, 1);
INSERT INTO `_members` (`user_id`, `user_type`, `user_active`, `user_internal`, `user_mtype`, `user_login`, `user_username`, `user_password`, `user_lastvisit`, `user_auto_session`, `user_current_ip`, `user_lastpage`, `user_firstname`, `user_lastname`, `user_name_show`, `user_email`, `user_gender`, `user_date`, `user_dateformat`, `user_timezone`, `user_dst`, `user_login_tries`, `user_stats`) VALUES(2, 3, 1, 0, 0, 'adm', 'adm', 'd0276f1b6b2a8e9b69e10034b6f28941cdf24043', 0, 0, '', '', '', '', '', '', 0, 0, 'd M Y H:i', -6, 0, 0, 0);

CREATE TABLE IF NOT EXISTS `_sessions` (
  `session_id` varchar(50) NOT NULL default '',
  `session_user_id` mediumint(5) NOT NULL default '0',
  `session_last_visit` int(11) NOT NULL default '0',
  `session_start` int(11) NOT NULL default '0',
  `session_time` int(11) NOT NULL default '0',
  `session_ip` varchar(40) NOT NULL default '',
  `session_browser` varchar(255) NOT NULL,
  `session_page` varchar(100) NOT NULL default ''
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `_stats` (
  `stats_id` int(11) NOT NULL auto_increment,
  `stats_page` mediumint(8) NOT NULL default '0',
  `stats_time` int(11) NOT NULL default '0',
  `stats_internal` int(11) NOT NULL default '0',
  `stats_external` int(11) NOT NULL default '0',
  PRIMARY KEY  (`stats_id`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `_tree` (
  `tree_id` int(11) NOT NULL auto_increment,
  `tree_alias` varchar(255) NOT NULL,
  `tree_order` int(11) NOT NULL,
  `tree_module` int(11) NOT NULL,
  `tree_node` int(11) NOT NULL,
  `tree_parent` int(11) NOT NULL,
  `tree_level` int(11) NOT NULL,
  `tree_childs` mediumint(8) NOT NULL,
  `tree_child_hide` tinyint(1) NOT NULL,
  `tree_child_order` varchar(100) NOT NULL,
  `tree_nav` tinyint(1) NOT NULL,
  `tree_nav_hide` tinyint(1) NOT NULL,
  `tree_css_parent` tinyint(1) NOT NULL,
  `tree_css_var` varchar(25) NOT NULL,
  `tree_quickload` tinyint(1) NOT NULL,
  `tree_dynamic` tinyint(1) NOT NULL,
  `tree_tags` varchar(255) NOT NULL,
  `tree_template` varchar(50) NOT NULL,
  `tree_redirect` varchar(255) NOT NULL,
  `tree_subject` varchar(255) NOT NULL,
  `tree_content` text NOT NULL,
  `tree_description` varchar(255) NOT NULL,
  `tree_downloads` int(11) NOT NULL,
  `tree_images` int(11) NOT NULL,
  `tree_views` int(11) NOT NULL,
  `tree_allow_comments` tinyint(1) NOT NULL,
  `tree_approve_comments` tinyint(1) NOT NULL,
  `tree_comments` int(11) NOT NULL,
  `tree_form` tinyint(1) NOT NULL,
  `tree_form_email` varchar(255) NOT NULL,
  `tree_edited` int(11) NOT NULL,
  `tree_published` int(11) NOT NULL,
  `tree_checksum` varchar(50) NOT NULL,
  PRIMARY KEY  (`tree_id`)
) ENGINE=InnoDB;

INSERT INTO `_tree` (`tree_id`, `tree_alias`, `tree_order`, `tree_module`, `tree_node`, `tree_parent`, `tree_level`, `tree_childs`, `tree_child_hide`, `tree_child_order`, `tree_nav`, `tree_nav_hide`, `tree_css_parent`, `tree_css_var`, `tree_quickload`, `tree_dynamic`, `tree_tags`, `tree_template`, `tree_redirect`, `tree_subject`, `tree_content`, `tree_description`, `tree_downloads`, `tree_images`, `tree_views`, `tree_allow_comments`, `tree_approve_comments`, `tree_comments`, `tree_form`, `tree_form_email`, `tree_edited`, `tree_published`, `tree_checksum`) VALUES(1, 'home', 1, 0, 0, 0, 0, 0, 0, '', 1, 0, 0, '', 0, 0, '', '', '', 'Inicio', '<p></p>', '', 0, 0, 0, 0, 0, 0, 0, '', 0, 0, '');