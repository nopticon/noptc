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

/*

### my/messages

Conversaciones
Mensajes en perfil, fotos
Solicitud de amigos
Noticias
Eventos e invitaciones
Concursos
Nuevos artistas, fotos, musica y video
Nuevos usuarios por preferencias
Mensajes en foro (diario)
Mensajes en artistas
Mensajes en descargas
Grupos de usuarios

---

# FRIEND REQUESTS

SELECT mr.request_id, mr.request_message, mr.request_active, b.bio_id, b.bio_alias, b.bio_name
	FROM _bio_request mr, _bio b
	WHERE mr.request_from = b.bio_id
		AND mr.request_block = 0
		AND mr.private_id IN (
			SELECT tl.list_item
			FROM _today_list tl, _today t
			WHERE t.today_id = tl.list_today
				AND t.today_alias = 'frequest'
				AND tl.list_uid = {UID}
		)
	ORDER BY mr.request_time DESC

# EVENT INVITATIONS

SELECT i.invite_id, i.invite_accept, i.invite_time, i.invite_message, b.bio_id, b.bio_alias, b.bio_name
	FROM _events e, _events_invite i, _bio b
	WHERE e.event_id = i.invite_event
		AND i.invite_from = b.bio_id
		AND i.invite_id IN (
			SELECT tl.list_item
			FROM _today t, _today_list
			WHERE t.today_id = tl.list_today
				AND t.today_alias = 'eventinvite'
				AND tl.list_uid = {UID}
		)
	ORDER BY i.invite_time DESC

*/

interface i_my
{
	public function home();
	public function page();
	public function messages();
	public function account();
	public function password();
}

class __my extends xmd implements i_my
{
	private $messages_a;
	
	public function __construct()
	{
		parent::__construct();
		
		$this->auth(false);
		$this->_m(_array_keys(w('page messages account password')));
		$this->m(w('home write sent draft'), 'messages_a');
		
		return;
	}
	
	public function home()
	{
		global $bio;
		
		$v = $this->__(array('s' => 0, 'area' => 0, 'ls' => array(0)));
		
		if (_button('delete'))
		{
			if (!$bio->v('auth_member'))
			{
				_fatal();
			}
			
			if (count($v['ls']))
			{
				$v_assoc = _implode(',', $v['ls']);
				
				$sql = 'SELECT n.notify_id
					FROM _bio_notify n, _bio_notify_area a
					WHERE a.area_id = ?
						AND n.notify_bio = ?
						AND n.notify_assoc = ?
						AND n.notify_area = a.area_id';
				if (!_field(sql_filter($sql, $v['area'], $bio->v('bio_id'), $v_assoc), 'notify_id', 0))
				{
					_fatal();
				}
				
				$sql = 'DELETE FROM _bio_notify
					WHERE notify_area = ?
						AND notify_bio = ?
						AND notify_assoc IN (??)';
				_sql(sql_filter($sql, $v['area'], $bio->v('bio_id'), $v_assoc));
			}
			
			if (is_ghost())
			{
				return $this->e('~OK');
			}
			
			redirect(_link('my', 'home'));
		}
		
		//
		// Show notifications
		$notify = w();
		
		if ($bio->v('auth_bio'))
		{
			$sql = 'SELECT a.area_alias, n.notify_assoc
				FROM _bio_notify n, _bio_notify_area a
				WHERE n.notify_bio = ?
				ORDER BY a.area_alias, n.notify_time
				LIMIT ??, ??';
			$notify = _rowset(sql_filter($sql, $bio->v('bio_id'), $v['s'], $core->v('notify_pagination')), 'area_alias', 'notify_assoc', true);
		}
		
		if (count($notify))
		{
			$i = 0;
			foreach ($notify as $notify_area => $notify_assoc)
			{
				$notify_set = _implode(',', $notify_assoc);
				
				// TODO: Improve select fields
				
				switch ($notify_area)
				{
					case 'talk':
						$sql = 'SELECT *
							FROM _bio_talk
							WHERE talk_id IN (??)
							ORDER BY talk_time DESC';
						$talk = _rowset(sql_filter($sql, $notify_set));
						break;
					case 'friends':
						$sql = 'SELECT b.bio_alias, b.bio_name
							FROM _bio_requests r, _bio b
							WHERE r.request_from IN (??)
								AND r.request_from = b.bio_id
							ORDER BY r.request_time';
						$requests = _rowset(sql_filter($sql, $notify_set));
						break;
					case 'images':
					case 'posts':
						break;
					case 'reference':
						$sql = 'SELECT *
							FROM _reference
							WHERE ref_id IN (??)
							ORDER BY ref_time';
						$reference = _rowset(sql_filter($sql, $notify_set));
						break;
					case 'events':
						$sql = 'SELECT *
							FROM _bio b, _events e, _events_invite i
							WHERE i.invite_id IN (??)
								AND i.invite_event = e.event_id
								AND i.invite_from = b.bio_id';
						$events = _rowset(sql_filter($sql, $notify_set));
						break;
					case 'contest':
						$sql = 'SELECT *
							FROM _contest
							WHERE contest_id IN (??)
							ORDER BY contest_time';
						$contest = _rowset(sql_filter($sql, $notify_set));
						break;
					case 'board':
						break;
					case 'groups':
						break;
					case '':
						// TODO: Nuevos artistas, fotos, musica y video
						break;
				}
				
				$i++;
			}
		}
		else
		{
			// Default data for guests and if bio has no notifications.
		}
		
		return;
	}
	
	public function page()
	{
		global $bio;
		
		return redirect(_link($this->bio($bio->v('bio_alias'))));
	}
	
	public function messages()
	{
		$this->method();
	}
	
	protected function _messages_home()
	{
		global $bio, $core;
		
		$v = $this->__(array('i' => 'home', 'u' => '', 'a' => array(0 => ''), 's' => 0));
		
		if (!in_array($v['i'], $this->messages_a))
		{
			_fatal();
		}
		
		switch ($v['i'])
		{
			case 'write':
				if (!$bio->v('bio_active'))
				{
					$this->_error('PLEASE_CONFIRM_ACCOUNT');
				}
				
				if (f($v['u']) && $v['u'] != $bio->v('bio_alias'))
				{
					$v['a'][] = $v['u'];
				}
				unset($v['u']);
				
				if (_button())
				{
					$v = array_merge($v, $this->__(array('subject', 'message', 'parent' => 0)));
					
					if ($v['parent'])
					{
						$sql = 'SELECT *
							FROM _bio_talk
							WHERE talk_id = ?';
						if (!$talk = _fieldrow(sql_filter($sql, $v['parent'])))
						{
							_fatal();
						}
						
						$sql = 'SELECT *
							FROM _bio_talkers
							WHERE talker_talk = ?
								AND talker_bio = ?';
						if (!$talkers = _rowset(sql_filter($sql, $v['parent'], $bio->v('bio_id'))))
						{
							_fatal();
						}
					}
					else
					{
						if (!f($v['subject']))
						{
							$this->_error('#TALK_NO_SUBJECT');
						}
						
						$sql = 'SELECT bio_alias, bio_name, bio_email
							FROM bio
							WHERE bio_id IN (??)
							ORDER BY bio_alias';
						if (!$talkers = _rowset(sql_filter($sql, _implode(',', $v['a']))))
						{
							$this->_error('#TALK_NO_TALKERS');
						}
					}
					
					if (!f($v['message']))
					{
						$this->_error('#NO_MESSAGE');
					}
					
					$sql_insert = array(
						'parent' => $v['parent'],
						'subject' => _prepare($v['subject']),
						'message' => _prepare($v['message']),
						'time' => time()
					);
					$sql = 'INSERT INTO _bio_talk' . _build_array('INSERT', $sql_insert);
					$v['talk_id'] = _sql_nextid($sql);
					
					foreach ($talkers as $row)
					{
						$sql_insert = array(
							'talk' => ($v['parent']) ? $v['parent'] : $v['talk_id'],
							'bio' => $row['bio_id']
						);
						$sql = 'INSERT INTO _bio_talkers' . _build_array('INSERT', $sql_insert);
						_sql($sql);
						
						$properties = array(
							'from' => 'info',
							'to' => $row['bio_email'],
							'subject' => '',
							'body' => '',
							'template' => ''
						);
						_sendmail($properties);
					}
					
					if (is_ghost() && $v['parent'])
					{
						$response = array(
							'message_id' => $message_id,
							'message_content' => $v['message'],
							'message_time' => _format_date()
						);
						return $this->e(json_encode($response));
					}
					
					redirect('my', array('messages', 'm' => $message_id));
				}
				break;
			default:
				$v = array_merge($v, $this->__(array('m' => 0)));
				
				if ($v['m'])
				{
					$sql = 'SELECT *
						FROM _bio_talk t, _bio_talkers r
						WHERE t.talk_id = ?
							AND r.talker_bio = ?
							AND t.talk_id = r.talker_talk';
					if (!$talk = _fieldrow(sql_filter($sql, $v['m'], $bio->v('bio_id'))))
					{
						_fatal();
					}
					
					$sql = 'SELECT t.*, b.bio_id, b.bio_alias, b.bio_name
						FROM _bio_talk t, _bio_talkers r, _bio b
						WHERE t.talk_parent = ?
							AND t.talk_id = r.talker_talk
							AND t.talk_author = r.talker_bio
							AND r.talker_bio = b.bio_id
						ORDER BY t.talk_time';
					$messages = _rowset(sql_filter($sql, $talk['talk_parent']));
					
					foreach ($messages as $i => $row)
					{
						if (!$i) _style('messages');
						
						_style('messages.row');
					}
					
					return;
				}
				
				//
				// Message lists
				$is_draft = 0;
				
				switch ($v['i'])
				{
					case 'sent':
						$sql_total = 'SELECT COUNT(talk_id) AS total
							FROM _bio_talk
							WHERE talk_author = ?
								AND talk_draft = ?
								AND talk_id = talk_parent';
						
						$sql_list = 'SELECT *
							FROM _bio_talk
							WHERE talk_author = ?
								AND talk_draft = ?
								AND talk_id = t.talk_parent
							ORDER BY talk_lasttime DESC
							LIMIT ??, ??';
						break;
					case 'draft':
						$sql_total = 'SELECT COUNT(talk_id) AS total
							FROM _bio_talk
							WHERE talk_author = ?
								AND talk_draft = ?
								AND talk_id = talk_parent';
						
						$sql_list = 'SELECT *
							FROM _bio_talk
							WHERE talk_author = ?
								AND talk_draft = ?
								AND talk_id = talk_parent
							ORDER BY talk_lasttime DESC
							LIMIT ??, ??';
						
						$is_draft = 1;
						break;
					default:
						$sql_total = 'SELECT COUNT(talk_id) AS total
							FROM _bio_talk t, _bio_talkers r
							WHERE r.talker_bio = ?
								AND t.talk_draft = ?
								AND t.talk_id = t.talk_parent
								AND t.talk_id = r.talker_talk';
						
						$sql_list = 'SELECT *
							FROM _bio_talk t, _bio_talkers r
							WHERE r.talker_bio = ?
								AND t.talk_draft = ?
								AND t.talk_id = t.talk_parent
								AND t.talk_id = r.talker_talk
							ORDER BY t.talk_lasttime DESC
							LIMIT ??, ??';
						break;
				}
				
				$talk_total = _field(sql_filter($sql_total, $bio->v('bio_id'), $is_draft), 'total', 0);
				$talk_list = _rowset(sql_filter($sql_list, $bio->v('bio_id'), $is_draft, $v['s'], $core->v('talk_pager')));
				
				if ($talk_total && !count($talk_list))
				{
					redirect(_link($this->m(), array('messages', 'i' => $v['i'])));
				}
				
				foreach ($messages as $i => $row)
				{
					if (!$i) _style('talks', _pagination(_link('my', array('messages', 'i' => $v['i'])), 's:%d', $messages_total, $core->v('talk_pager'), $v['s']));
					
					if (!$row['message_last'])
					{
						$row['message_last'] = $row['message_id'];
						$row['message_last_time'] = $row['message_time'];
					}
					
					_style('talks.row', _vs(array(
						'PARENT' => $row['talk_parent'],
						'SUBJECT' => $row['talk_subject'],
						'READ' => _link($this->m(), array('messages', 'i' => $v['i'], 'm' => $row['talk_last'])),
						'TIME' => _format_date($row['talk_lasttime']),
						'ROOT' => $row['talk_root']
					), 'TALK'));
				}
				
				break;
		}
		
		return;
	}
	
	public function account()
	{
		$this->method();
	}
	
	protected function _account_home()
	{
		global $bio, $core;
		
		if (_button())
		{
			$sql = 'SELECT *
				FROM _bio_fields
				ORDER BY field_alias';
			$fields = _rowset($sql, 'field_alias');
			
			$v = $this->__(array_merge(w('address password password_verify gender' . _implode(' ', array_subkey($fields, 'field_alias'))), array('timezone' => 0, 'birthday' => array(0))));
			
			$field_error = array(
				'address' => 'NO_ADDRESS',
				'password' => 'NO_PASSWORD',
				'password_verify' => 'NO_PASSWORD_VERIFY'
			);
			
			foreach ($v as $k => $vv)
			{
				if (!f($vv))
				{
					$this->error('#');
				}
			}
			
			if (is_ghost() && $this->errors())
			{
				$this->e('!');
			}
			
			redirect(_link('my', 'page'));
		}
		
		return;
		
		/*
		if (_button())
		{
			if (!$this->errrors())
			{
				$avatar_changed = (isset($this->data['old_avatar'])) ? true : false;
				
				// Update DB if something was changed
				if (sizeof($sql_update))
				{
					if (isset($sql_update['bio_avatar']))
					{
						if (f($bio->v('bio_avatar')))
						{
							@unlink('..' . $core->v('avatar_path') . $bio->v('bio_avatar'));
						}
						@rename($this->data['old_avatar'], '..' . $core->v('avatar_path') . $sql_update['bio_avatar']);
					}
				}
				
				// Redirect to userpage
			}
		} // IF submit
		
		// Selects
		$this->ss_build('dateformat', 'timezone', 'gender', 'birthday', 'topic_order', 'mark_items');
		
		// Vars
		$sv = array(
			'AVATAR_MAXSIZE' => $core->v('avatar_filesize'),
			'L_AVATAR_EXPLAIN' => sprintf(_lang('AVATAR_EXPLAIN'), $core->v('avatar_max_width'), $core->v('avatar_max_height'))
		)
		+ $this->fields_fvars();
		*/
	}
	
	public function password()
	{
		$this->method();
	}
	
	protected function _password_home()
	{
		global $bio;
		
		$v = $this->__(w('k'));
		
		if (f($v['k']))
		{
			// TODO: Password reset from email link
		}
		
		if (_button())
		{
			$v = $this->__(w('address'));
			
			if (!f($v['address']))
			{
				$this->_error('#NO_SUCH_BIO');
			}
			
			$v['field'] = (email_format($v['address']) !== false) ? 'address' : 'alias';
			
			if ($v['field'] == 'alias' && !_low($v['address']))
			{
				$this->_error('#NO_SUCH_BIO');
			}
			
			$sql = 'SELECT bio_alias, bio_name, bio_email, bio_lang
				FROM _bio
				WHERE bio_?? = ?
					AND bio_active = ?
					AND bio_level NOT IN (??)';
			if (!$_bio = _fieldrow(sql_filter($sql, $v['field'], $v['address'], 1, _implode(',', w(U_INACTIVE . ' ' . U_FOUNDER)))))
			{
				$this->_error('#NO_SUCH_BIO');
			}
			
			$actkey = substr(unique_id(), 0, 6);
			
			$sql = 'UPDATE _bio SET bio_actkey = ?
				WHERE bio_id = ?';
			_sql(sql_filter($sql, $actkey, $_bio['bio_id']));
			
			//
			$properties = array(
				'to' => $userdata['bio_address'],
				'template' => 'user_activate_passwd',
				
				'vars' => array(
					'USERNAME' => $userdata['username'],
					'PASSWORD' => $user_password,
					'U_ACTIVATE' => _link('my', array('password', 'k' => $user_actkey))
				)
			);
			_sendmail($properties);
			
			$this->_error('PASSWD_SENT');
		}
		
		return;
	}
}

?>