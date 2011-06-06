<?php
/*
<NPT, a web development framework.>
Copyright (C) <2009>  <NPT>

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
if (!defined('XFS')) exit;

interface i_radio
{
	public function home();
	public function episode();
	public function publish();
	public function like();
	public function cp();
}

class __radio extends xmd implements i_radio
{
	public function __construct()
	{
		parent::__construct();
		
		$this->auth(false);
		
		$this->_m(array(
			'episode' => w(),
			'publish' => w(),
			'like' => w(),
			'cp' => array(
				'show' => w('create modify remove'),
				'episode' => w('create modify remove'),
				'publish' => w('modify remove'))
			)
		);
		
		return;
	}
	
	public function home()
	{
		global $bio, $core;
		
		$sql = 'SELECT show_alias, show_name
			FROM _radio_shows
			WHERE show_active = ?
			ORDER BY show_name';
		$shows = _rowset(sql_filter($sql, 1));
		
		//
		$sql = 'SELECT d.dj_show, b.bio_alias, b.bio_name
			FROM _radio_dj d, _bio b
			WHERE d.dj_bio = b.bio_id
			ORDER BY m.bio_name';
		$dj = _rowset($sql, 'dj_show', false, true);
		
		$sql = 'SELECT archive_id, archive_show, archive_alias, archive_name
			FROM _radio_archives
			ORDER BY article_order, archive_time';
		$archive = _rowset($sql);
		
		$sql = 'SELECT *
			FROM _radio_articles
			ORDER BY announce_show, announce_time';
		$announce = _rowset($sql, 'announce_show', false, true);
		
		return;
	}
	
	public function episode()
	{
		$this->method();
	}
	
	protected function _episode_home()
	{
		return;
	}
	
	public function publish()
	{
		$this->method();
	}
	
	protected function _publish_home()
	{
		return;
	}
	
	public function like()
	{
		$this->method();
	}
	
	protected function _like_home()
	{
		return;
	}
	
	public function cp()
	{
		$this->method();
	}
	
	protected function _cp_home()
	{
		return;
	}
	
	protected function _cp_show_create()
	{
		return;
	}
	
	protected function _cp_show_modify()
	{
		return;
	}
	
	protected function _cp_show_remove()
	{
		return;
	}
	
	protected function _cp_episode_create()
	{
		return;
	}
	
	protected function _cp_episode_modify()
	{
		return;
	}
	
	protected function _cp_episode_remove()
	{
		return;
	}
	
	protected function _cp_publish_modify()
	{
		return;
	}
	
	protected function _cp_publish_remove()
	{
		return;
	}
}

?>