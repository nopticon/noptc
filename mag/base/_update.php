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

interface i_update
{
	public function import();
	public function members();
	public function subdomain();
	public function bios();
}

class __update extends xmd implements i_update
{
	public function __construct()
	{
		parent::__construct();
		
		$this->auth(false);
		$this->_m(array(
			'import' => w('news'),
			'members' => w('profile'),
			'subdomain' => w('artist member'),
			'bios' => w('rand'))
		);
	}
	
	public function home()
	{
		_fatal();
	}
	
	public function import()
	{
		$this->method();
	}
	
	protected function _import_news()
	{
		$sql = 'SELECT *
			FROM _news
			ORDER BY post_time';
		$news = _rowset($sql);
		
		foreach ($news as $row)
		{
			$sql_insert = array(
				'type' => 1,
				'title' => $row['post_subject'],
				'desc' => $row['post_desc'],
				'link' => _link('news', $row['news_id']),
				'time' => $row['post_time'],
				'author' => $row['poster_id']
			);
			$sql = 'INSERT INTO _reference' . _build_array('INSERT', prefix('ref', $sql_insert));
			_sql($sql);
		}
		
		return $this->e('~OK:' . count($news));
	}
	
	public function members()
	{
		$this->method();
	}
	
	protected function _members_home()
	{
		_fatal();
	}
	
	protected function _members_profile()
	{
		global $bio;
		
		$sql = 'SELECT *
			FROM _bio_store
			ORDER BY a_field';
		$profiles = _rowset($sql);
		
		foreach ($profiles as $row)
		{
			$field_id = 0;
			if (isset($cache[$row['a_field']]))
			{
				$field_id = $cache[$row['a_field']];
			}
			
			if (!$field_id)
			{
				$sql = 'SELECT *
					FROM _bio_fields
					WHERE field_display = ?';
				if ($members_fields = _fieldrow(sql_filter($sql, $row['a_field'])))
				{
					$field_id = $members_fields['field_id'];
					$cache[$row['a_field']] = $field_id;
				}
			}
			
			if (!$field_id)
			{
				$insert = array(
					'field_alias' => $row['a_field'],
					'field_name' => $row['a_field'],
					'field_display' => $row['a_field'],
					'field_required' => 0,
					'field_unique' => 0,
					'field_unique_global' => 0,
					'field_show' => 1,
					'field_length' => 0,
					'field_type' => 'text',
					'field_relation' => '',
					'field_function' => '',
					'field_js' => ''
				);
				$sql = 'INSERT INTO _bio_fields' . _build_array('INSERT', $insert);
				_sql($sql);
				
				$field_id = _nextid();
			}
			
			$sql = 'UPDATE _bio_store SET a_field = ?
				WHERE a_id = ?';
			_sql(sql_filter($sql, $field_id, $row['a_id']));
		}
		
		$sql = "ALTER TABLE _bio_store
			CHANGE a_field a_field INT(11) NOT NULL DEFAULT '0'";
		_sql($sql);
		
		$this->_e('Done!');
		
		return;
	}
	
	public function subdomain()
	{
		$this->method();
	}
	
	protected function _subdomain_home()
	{
		_fatal();
	}
	
	protected function _subdomain_artist()
	{
		$sql = 'SELECT *
			FROM _artists
			ORDER BY a_name';
		$artists = _rowset($sql);
		
		foreach ($artists as $row)
		{
			$insert = array(
				's_name' => $row['a_alias'],
				's_type' => 1,
				's_enable' => 1
			);
			$sql = 'INSERT INTO _subdomains' . _build_array('INSERT', $insert);
			_sql($sql);
		}
		
		return $this->e('~OK');
	}
	
	public function _subdomain_member()
	{
		return;
	}
	
	public function bios()
	{
		$this->method();
	}
	
	protected function _bios_home()
	{
		$sql = 'SELECT field_alias, field_id
			FROM _bio_fields
			ORDER BY field_alias';
		$store_fields = _rowset($sql, 'field_alias', 'field_id');
		
		$current_fields = array(
			'public_email' => 'email_0',
			'fav_artists' => 'fartists',
			'fav_genres' => 'fgenres',
			'icq' => 'icq',
			'interests' => 'interests',
			'lastfm' => 'lastfm',
			'location' => 'location',
			'occ' => 'occ',
			'os' => 'os',
			'website' => 'website',
			'msnm' => 'wlive'
		);
		
		$sql = 'SELECT *
			FROM _members
			WHERE user_id <> 1
			ORDER BY user_id';
		$members = _rowset($sql);
		
		foreach ($members as $row)
		{
			$user_firstname = '';
			$user_lastname = '';
			
			switch ($row['user_id'])
			{
				case 2:
					$user_firstname = 'Guillermo';
					$user_lastname = 'Azurdia';
					break;
				case 3:
					$user_firstname = 'Gerardo';
					$user_lastname = 'Medina';
					break;
			}
			
			$sql_insert = array(
				'bio_type' => 2,
				'bio_level' => ($row['user_type']) ? $row['user_type'] : 0,
				'bio_active' => ($row['user_active']) ? $row['user_active'] : 0,
				'bio_alias' => ($row['username_base']) ? $row['username_base'] : '',
				'bio_name' => ($row['username']) ? $row['username'] : '',
				'bio_first' => $user_firstname,
				'bio_last' => $user_lastname,
				'bio_key' => ($row['user_password']) ? $row['user_password'] : '',
				'bio_address' => ($row['user_email']) ? strtolower($row['user_email']) : '',
				'bio_gender' => ($row['user_gender']) ? $row['user_gender'] : '',
				'bio_birth' => ($row['user_birthday']) ? $row['user_birthday'] : 0,
				'bio_birthlast' => ($row['user_birthday_last']) ? $row['user_birthday_last'] : 0,
				'bio_regip' => ($row['user_regip']) ? $row['user_regip'] : '',
				'bio_regdate' => ($row['user_regdate']) ? $row['user_regdate'] : 0,
				'bio_lastvisit' => ($row['user_lastvisit']) ? $row['user_lastvisit'] : 0,
				'bio_session_time' => ($row['user_session_time']) ? $row['user_session_time'] : 0,
				'bio_lastpage' => '',
				'bio_timezone' => ($row['user_timezone']) ? $row['user_timezone'] : 0.00,
				'bio_dst' => ($row['user_dst']) ? $row['user_dst'] : 0,
				'bio_dateformat' => ($row['user_dateformat']) ? $row['user_dateformat'] : '',
				'bio_lang' => ($row['user_lang']) ? $row['user_lang'] : 'es',
				'bio_country' => (!$row['user_country']) ? 90 : $row['user_country'],
				'bio_avatar' => ($row['user_avatar']) ? $row['user_avatar'] : '',
				'bio_avatar_up' => ($row['user_avatar']) ? substr(md5(unique_id()), 0, 10) : '',
				'bio_actkey' => '',
				'bio_recovery' => '',
				'bio_fails' => 0
			);
			$sql = 'INSERT INTO _bio' . _build_array('INSERT', $sql_insert);
			$bio_id = _sql_nextid($sql);
			
			foreach ($current_fields as $current_field => $new_field)
			{
				if (isset($row['user_' . $current_field]) && f($row['user_' . $current_field]))
				{
					$sql_insert = array(
						'bio' => $bio_id,
						'field' => $store_fields[$new_field],
						'value' => $row['user_' . $current_field]
					);
					$sql = 'INSERT INTO _bio_store' . _build_array('INSERT', prefix('store', $sql_insert));
					_sql($sql);
				}
			}
			
			if (isset($row['user_send_mass']) && $row['user_send_mass'])
			{
				$sql_insert = array(
					'bio' => $bio_id,
					'receive' => $row['user_send_mass']
				);
				$sql = 'INSERT INTO _bio_newsletter' . _build_array('INSERT', prefix('newsletter', $sql_insert));
				_sql($sql);
			}
		}
		
		/*
		$sql = 'SELECT *
			FROM _members_friends
			ORDER BY user_id, buddy_id';
		$friends = _rowset($sql);
		
		foreach ($friends as $row)
		{
			$sql_insert = array(
				'assoc' => $row['buddy_id'],
				'bio' => $row['user_id'],
				'active' => 1,
				'time' => $row['friend_time'],
				'message' => ''
			);
			$sql = 'INSERT INTO _bio_friends' . _build_array('INSERT', prefix('friend', $sql_insert));
			_sql($sql);
		}
		*/
		
		return $this->e('~OK');
	}
	
	protected function _bios_rand()
	{
		$a = substr(sha1(unique_id()), 0, 10);
		_pre($a);
		exit;
	}
}

?>