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

interface i_home
{
	public function home();
	public function like();
	public function status();
}

class __home extends xmd implements i_home
{
	public function __construct()
	{
		parent::__construct();
		
		$this->auth(false);
		$this->_m(_array_keys(w('like')));
	}
	
	public function home()
	{
		global $core, $bio;
		
		// Friends birthday
		$page = 15;
		$today = _htimestamp('md');
		
		if ($bio->v('auth_member'))
		{
			$sql = "SELECT bio_id, bio_alias, bio_name
				FROM _bio
				WHERE bio_id IN (
						SELECT fan_of
						FROM _bio_fans
						WHERE fan_assoc = ?
					)
					AND bio_active = ?
					AND bio_birth LIKE '%??'
				ORDER BY bio_name";
			$birthday = _rowset(sql_filter($sql, $bio->v('bio_id'), 1, $today));
		}
		else
		{
			$sql = "SELECT bio_id, bio_alias, bio_name, bio_avatar, bio_avatar_up
				FROM _bio
				WHERE bio_level = ?
					AND bio_birth LIKE '%??'
				ORDER BY bio_name";
			$birthday = _rowset(sql_filter($sql, 1, $today));
		}
		
		foreach ($birthday as $i => $row)
		{
			if (!$i) _style('birthday');
			
			_style('birthday.row', array(
				'NAME' => $row['bio_name'])
			);
		}
		
		//
		// News column
		$v = $this->__(array('t' => 0, 's' => 0, 'm' => 0, 'w'));
		
		$ref_type = '';
		if ($v['t'])
		{
			$ref_type = sql_filter(' WHERE ref_type = ?', $v['t']);
		}
		
		$sql = 'SELECT COUNT(ref_id) AS total
			FROM _reference
			??';
		$total = _field(sql_filter($sql, $ref_type), 'total');
		
		$sql = 'SELECT *
			FROM _reference r
			??
			LEFT JOIN _reference_likes k
				ON r.ref_id = k.like_ref
			ORDER BY ref_important DESC, ref_time DESC
			LIMIT ??, ??';
		$ref = _rowset(sql_filter($sql, $ref_type, $v['s'], $page));
		
		if (!count($ref) && $total)
		{
			redirect(_link());
		}
		
		foreach ($ref as $i => $row)
		{
			if (!$i)
			{
				_style('ref', _pagination(_link('home', array('t' => $v['t'])), 's:%s', $total, $page, $v['s']));
			}
			
			$row['ref_time'] = _format_date($row['ref_time'], 'F j');
			$row['ref_link'] = _link(array('m' => $row['ref_id']));
			
			_style('ref.row', _vs($row));
		}
		
		if (!$type = $core->cache_load('reference_type'))
		{
			$sql = 'SELECT type_id, type_name
				FROM _reference_type
				ORDER BY type_order';
			$type = $core->cache_store(_rowset($sql));
		}
		
		foreach ($type as $i => $row)
		{
			if (!$i) _style('ref.type');
			
			_style('ref.type.row', _vs($row));
		}
		
		//
		// Shortcut column
		//
		
		if ($bio->v('auth_member'))
		{
			//
			// Private messages
			$sql = 'SELECT *
				FROM _bio_messages m
				LEFT JOIN _bio b ON m.message_from_uid = b.bio_id 
				WHERE m.message_to_uid = ?
					AND m.message_unread = ?
				ORDER BY m.message_time DESC';
			// TODO: Finish query, private_messages
			$private = _rowset(sql_filter($sql, $bio->v('bio_id'), 1));
			
			foreach ($private as $i => $row)
			{
				if (!$i) _style('private');
				
				_style('private.row', array(
					'U_MESSAGE' => _link('my', array('messages', 'm' => $row['message_id'])),
					'SUBJECT' => $row['message_subject'],
					'NICKNAME' => $row['bio_name'])
				);
			}
			
			//
			// Public messages
			$sql = 'SELECT *
				FROM _bio_messages m, _bio b
				WHERE m.message_to = ?
					AND m.message_unread = ?
					AND m.message_from = b.bio_id
				ORDER BY m.message_time DESC';
			// TODO: Finish query, public_messages
			$public = _rowset(sql_filter($sql));
			
			foreach ($public as $i => $row)
			{
				if (!$i) _style('public');
				
				_style('public.row', array(
					'MESSAGE_CONTENT' => _message($row['message_content']),
					'NICKNAME' => $row['bio_name'])
				);
			}
		}
		
		//
		// Banners
		$this->announce('home');
		
		//
		// Board topics
		$topics = w();
		if ($bio->v('auth_member'))
		{
			$sql = 'SELECT *
				FROM _topics
				WHERE ';
			// TODO: Finish query, topics
			$topics = _rowset(sql_filter($sql));
		}
		else
		{
			/*
			$sql = 'SELECT *
				FROM _topics
				WHERE ';
			*/
			// TODO: Finish query, topics
			//$topics = _rowset(sql_filter($sql));
		}
		
		foreach ($topics as $i => $row)
		{
			if (!$i) _style('board_topics');
			
			_style('board_topics.row', _vs(array(
				'ID' => $row['topic_id'],
				'TITLE' => $row['topic_title']
			), 'TOPIC'));
		}
		
		//
		// Users birthday
		
		/*
		fan_id
		fan_of
		fan_uid
		fan_time
		*/
		
		return;
	}
	
	public function status()
	{
		$this->method();
	}
	
	protected function _status_home()
	{
		gfatal();
		
		global $bio;
		
		if (!$bio->v('auth_logged'))
		{
			_fatal();
		}
		
		$v = $this->__(array('status', 'bio' => 0));
		
		if (!$v['bio'])
		{
			$v['bio'] = $bio->v('bio_id');
		}
		
		if ($v['bio'] !== $bio->v('bio_id'))
		{
			if (!$this->bio_exists($v['bio']))
			{
				_fatal();
			}
			
			if (!$bio->v('auth_status_update_others', false, $v['bio']))
			{
				_fatal();
			}
		}
		
		$sql_insert = array(
			'bio' => $v['bio'],
			'time' => time(),
			'text' => _prepare($v['status']),
			'ip' => $bio->v('session_ip')
		);
		$sql = 'INSERT _bio_status' . _build_array('INSERT', prefix('status', $sql_insert));
		_sql($sql);
		
		$response = array(
			'time' => $sql_insert['time'],
			'text' => $sql_insert['text']
		);
		return $this->e(json_encode($response));
	}
	
	public function like()
	{
		$thsi->method();
	}
	
	protected function _like_home()
	{
		global $bio;
		
		if (!is_ghost())
		{
			_fatal();
		}
		
		$v = $this->__(array('ref' => 0));
		
		if (!$v['ref'])
		{
			_fatal();
		}
		
		if (!$bio->v('auth_member'))
		{
			_login();
		}
		
		// like_time
		
		$sql = 'SELECT *
			FROM _reference
			WHERE ref_id = ?';
		if (!$ref = _fieldrow(sql_filter($sql, $v['ref'])))
		{
			_fatal();
		}
		
		$sql = 'SELECT like_id
			FROM _reference_likes
			WHERE like_ref = ?
				AND like_uid = ?';
		if (!_field(sql_filter($sql, $ref['ref_id'], $bio->v('bio_id')), 'like_id', 0))
		{
			$sql_insert = array(
				'ref' => $ref['ref_id'],
				'uid' => $bio->v('bio_id')
			);
			$sql = 'INSERT INTO _reference_likes' . _build_array('INSERT', prefix('like', $sql_insert));
			_sql($sql);
		}
		
		return $this->e('~OK');
	}
}

?>