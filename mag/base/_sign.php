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

interface i_sign
{
	public function home();
	public function up();
	public function ed();
	public function in();
	public function out();
}

class __sign extends xmd implements i_sign
{
	public function __construct()
	{
		parent::__construct();
		
		$this->auth(false);
		$this->_m(_array_keys(w('up ed in out')));
	}
	
	public function home()
	{
		_fatal();
	}
	
	public function up()
	{
		$this->method();
	}
	
	protected function _up_home()
	{
		/*
		BIO
		
		$v = $this->__(array('stype' => 0, 'address', 'name', 'key', 'country' => 0, 'gender' => 0, 'birthday'));
		
		if (!validate_email($v['address']))
		{
			$this->_error('#EMAIL_ERROR');
		}
		
		$sql = 'SELECT bio_id
			FROM _bio
			WHERE bio_address = ?
			LIMIT 1';
		if (_fieldrow(sql_filter($sql, $v['address'])))
		{
			$this->_error('#EMAIL_EXISTS');
		}
		
		if (!f($v['name']))
		{
			$this->_error('#NAME_EMPTY');
		}
		
		$v['alias'] = _alias($v['name'], true);
		
		if ($v['alias'] === false || !f($v['alias']))
		{
			$this->_error('#ALIAS_ERROR');
		}
		
		$sql = 'SELECT country_id
			FROM _countries
			WHERE country_id = ?';
		if (!_fieldrow(sql_filter($sql, $v['country'])))
		{
			$this->_error('#COUNTRY_ERROR');
		}
		
		$sql = 'SELECT bio_id
			FROM _bio b, _countries c
			WHERE b.bio_alias = ?
				AND c.country_id = ?
				AND b.bio_country = c.country_id
			LIMIT 1';
		if (_fieldrow(sql_filter($sql, $v['alias'], $v['country'])))
		{
			$this->_error('#BIO_EXISTS');
		}
		
		$insert_bio = array(
			'type' => 0,
			'stype' => $v['stype'],
			'active' => 0,
			'alias' => $v['alias'],
			'name' => $v['name'],
			'key' => $v['key'],
			'address' => $v['address'],
			'realname' => '',
			'country' => $v['country'],
			'registered' => time(),
			'utc' => 0,
			'dst' => 0,
			'lastlogin' => 0,
			'lastpage' => '',
			'gender' => $v['gender'],
			'birthday' => $v['birthday'],
			'dateformat' => '',
			'logintries' => 0,
			'massemail' => 1,
			'tags' => ''
		);
		$sql = 'INSERT INTO _bio' . _build_array('INSERT', prefix('bio', $insert_bio));
		_sql($sql);
		
		$v['bio_id'] = _nextid();
		
		//
		if (!$tab_home = $core->cache_load('tab_home'))
		{
			$sql = 'SELECT rel_id
				FROM _bio_tab_rel
				WHERE rel_alias = ?';
			$tab_home = $core->cache_store(_field(sql_filter($sql, 'home'), 'rel_id'));
		}
		
		$insert_tab = array(
			'bio' => $v['bio_id'],
			'rel' => $tab_home,
			'order' => 1
		);
		$sql = 'INSERT INTO _bio_tab' . _build_array('INSERT', prefix('tab', $insert_tab));
		_sql($sql);
		*/
		
		/*
		$fields_error = array(
			'username' => 'EMPTY_USERNAME',
			'email' => 'EMPTY_EMAIL',
			'email_confirm' => 'EMPTY_EMAIL_CONFIRM',
			'birthday_month' => 'EMPTY_BIRTHDAY',
			'birthday_day' => 'EMPTY_BIRTHDAY',
			'birthday_year' => 'EMPTY_BIRTHDAY',
			'agreetos' => 'AGREETOS_ERROR'
		);
		$fields_prev = array('email_confirm' => 'email');
		
		//
		$v = array('name', 'email', 'email_confirm', 'gender' => 0, 'birth_day' => 0, 'birth_month' => 0, 'birth_year' => 0, 'country' => 0, 'dateformat' => 0, 'timezone' => 0, 'tos' => 0);
		
		if (_button())
		{
			$this->__($v);
		}
		
		
		if (_button())
		{
			foreach ($fields as $key => $def)
			{
				$this->fields[$key] = request_var($key, $def);
				
				if (($this->fields[$key] === $def) && isset($fields_error[$key]))
				{
					if (isset($fields_prev[$key]) && $this->fields[$fields_prev[$key]] === $def)
					{
						continue;
					}
					$this->error($fields_error[$key]);
				}
			}
			
			if (!$this->errors())
			{
				$this->fs_fields('username', 'email', 'birthday');
			}
			
			if (!$this->errors())
			{
				$member_data = array(
					'bio_type' => 0,
					'bio_level' => 0,
					'bio_active' => 1,
					'bio_name' => $this->fields['bio_name'],
					'bio_alias' => $this->fields['bio_alias'],
					'bio_regip' => $bio->ip,
					'bio_session_time' => 0,
					'bio_lastpage' => '',
					'bio_lastvisit' => 0,
					'bio_regdate' => time(),
					'bio_posts' => 0,
					'bio_page_posts' => 0,
					'bio_color' => '4D5358',
					'bio_timezone' => $core->v('board_timezone'),
					'bio_dst' => $core->v('board_dst'),
					'bio_lang' => $core->v('default_lang'),
					'bio_dateformat' => $core->v('default_dateformat'),
					'bio_rank' => 0,
					'bio_avatar_up' => '',
					'bio_email' => $this->fields['email'],
					'bio_gender' => (int) $this->fields['gender'],
					'bio_birth' => (string) (_zero($this->fields['birthday_year']) . _zero($this->fields['birthday_month']) . _zero($this->fields['birthday_day'])),
					'bio_mark_items' => 0,
					'bio_topic_order' => 0
				);
				$sql = 'INSERT INTO _bio' . _build_array('INSERT', $member_data);
				_sql($sql);
				
				$this->fields['bio_id'] = _nextid();
				
				// Updates
				$core->v('max_users', $core->v('max_users') + 1);
				$bio->notify_store('members_newest', $this->fields['bio_id']);
				
				// Send email
				require_once(XFS . 'core/emailer.php');
				$emailer = new emailer();
				
				$emailer->from($core->v('board_email'));
				$emailer->use_template('user_welcome');
				$emailer->email_address($this->fields['email']);
				
				$emailer->assign_vars(array(
					'WELCOME_MSG' => _lang('WELCOME_SUBJECT'),
					'USERNAME' => $this->fields['bio_name'],
					'U_PROFILE' => _link_bio($this->fields['bio_alias']))
				);
				$emailer->send();
				$emailer->reset();
				
				// Redirect
				redirect(_link('my', 'registered'));
			}
		}
		
		if ($this->errors())
		{
			_style('error', array(
				'MESSAGE' => $this->get_errors())
			);
		}
		
		foreach (_lang('MEMBERSHIP_BENEFITS_LIST') as $item)
		{
			_style('list_benefits', array(
				'ITEM' => $item)
			);
		}
		
		// Selects
		$this->ss_build('dateformat', 'timezone', 'gender', 'birthday');
		
		$sv = array(
			'AGREETOS_SELECTED' => ($this->fields['agreetos']) ? ' checked="true"' : '',
			'S_ACTION' => _link('my', 'register')
		);
		$sv += $this->fields_fvars();
		v_style($sv);
		*/
		
		$v = $this->__(w('address'));
		
		if (_button())
		{
			$v = array_merge($v, $this->__(array_merge(w('alias nickname ref_in'), _array_keys(w('gender country birth_day birth_month birth_year aup ref'), 0))));
			
			if (!f($v['nickname']) && f($v['address']) && email_format($v['address']) === false)
			{
				$v['nickname'] = $v['address'];
			}
			
			if (!f($v['nickname']))
			{
				$this->_error('EMPTY_USERNAME');
			}
			
			if (!$v['alias'] = _low($v['nickname']))
			{
				$this->_error('BAD_ALIAS');
			}
			
			$nickname_len = strlen($v['nickname']);
			
			if (($nickname_len < 1) || ($nickname_len > 20))
			{
				$this->_error('LEN_ALIAS');
			}
			
			$sql = 'SELECT *
				FROM _alias
				WHERE alias_name = ?';
			if (_fieldrow(sql_filter($sql, $v['alias'])))
			{
				$this->_error('RECORD_ALIAS');
			}
			
			$sql = 'SELECT country_id
				FROM _countries
				WHERE country_id = ?';
			if (!_fieldrow(sql_filter($sql, $v['country'])))
			{
				$this->_error('BAD_COUNTRY');
			}
			
			if (!$v['birth_day'] || !$v['birth_month'] || !$v['birth_year'])
			{
				$this->_error('BAD_BIRTH');
			}
			
			$v['birth'] = _timestamp($v['birth_month'], $v['birth_day'], $v['birth_year']);
			
			$sql_insert = array(
				'alias' => $v['alias'],
				'nickname' => $v['nickname'],
				'address' => $v['address'],
				'gender' => $v['gender'],
				'country' => $v['country'],
				'birth' => $v['birth']
			);
			$sql = 'INSERT INTO _bio' . _build_array('INSERT', prefix('user', $sql_insert));
			_sql($sql);
		}
		
		// GeoIP
		require_once(XFS . 'core/geoip.php');
		$gi = geoip_open(XFS . 'core/GeoIP.dat', GEOIP_STANDARD);
		
		$geoip_code = strtolower(geoip_country_code_by_addr($gi, $bio->ip));
		
		$sql = 'SELECT *
			FROM _countries
			ORDER BY country_name';
		$countries = _rowset($sql);
		
		$v2['country'] = ($v2['country']) ? $v2['country'] : ((isset($country_codes[$geoip_code])) ? $country_codes[$geoip_code] : $country_codes['gt']);
		
		foreach ($countries as $i => $row)
		{
			if (!$i) _style('countries');
			
			_style('countries.row', array(
				'V_ID' => $row['country_id'],
				'V_NAME' => $row['country_name'],
				'V_SEL' => 0)
			);
		}
		
		return;
	}
	
	public function ed()
	{
		$this->method();
	}
	
	protected function _ed_home()
	{
		global $bio;
		
		$v = $this->__(array('k'));
		
		if (!f($v['k']))
		{
			_fatal();
		}
		
		if (!$rainbow = _rainbow_check($v['k']))
		{
			_fatal();
		}
		
		$sql = 'UPDATE _bio SET bio_active = 1
			WHERE bio_id = ?';
		_sql(sql_filter($sql, $rainbow['rainbow_uid']));
		
		_rainbow_remove($rainbow['rainbow_code']);
		
		if (!$bio->v('auth_member'))
		{
			$bio->session_create($rainbow['rainbow_uid']);
		}
		
		redirect(_link('my', 'page'));
		return;
	}
	
	public function in()
	{
		$this->method();
	}
	
	/*
	Si email y clave existe > login
	Si email no clave > recuperacion clave
	Si no email, no clave > crear cuenta
	*/
	
	protected function _in_home()
	{
		global $bio;
		
		$v = $this->__(array('page', 'address', 'key'));
		
		if ($bio->v('auth_member'))
		{
			redirect($v['page']);
		}
		
		if (!f($v['address']))
		{
			$this->_error('LOGIN_ERROR');
		}
		
		if (_button('recovery'))
		{
			$sql = 'SELECT bio_id, bio_name, bio_address, bio_recovery
				FROM _bio
				WHERE bio_address = ?
					AND bio_id <> ?
					AND bio_id NOT IN (
						SELECT ban_userid
						FROM _banlist
					)';
			if ($recovery = _fieldrow(sql_filter($sql, $v['address'], U_GUEST)))
			{
				$new_key = _rainbow_create($recovery['bio_id']);
				
				require_once(XFS . 'core/emailer.php');
				$emailer = new emailer();
				
				$emailer->from($core->v('email_info'));
				$emailer->use_template('bio_recovery');
				$emailer->email_address($recovery['bio_address']);
				
				$emailer->assign_vars(array(
					'USERNAME' => $this->fields['username'],
					'U_RECOVERY' => _link('my', array('recovery', 'k' => $new_key)),
					'U_PROFILE' => _link('m', $recovery['bio_nickname']))
				);
				$emailer->send();
				$emailer->reset();
				
				$sql = 'UPDATE _bio SET bio_recovery = bio_recovery + 1
					WHERE bio_id = ?';
				_sql(sql_filter($sql, $recovery['bio_id']));
			}
			
			$this->_stop('RECOVERY_LEGEND');
		}
		
		if (!f($v['key']))
		{
			$this->_error('LOGIN_ERROR');
		}
		
		$v['register'] = false;
		$v['field'] = (email_format($v['address']) !== false) ? 'address' : 'nickname';
		
		$sql = 'SELECT bio_id, bio_key
			FROM _bio
			WHERE bio_?? = ?
				AND bio_id <> ?
				AND bio_id NOT IN (
					SELECT ban_assoc
					FROM _bio_ban
				)';
		if ($userdata = _fieldrow(sql_filter($sql, $v['field'], $v['address'], U_GUEST)))
		{
			if ($userdata['bio_key'] === _password($v['key']))
			{
				if ($userdata['bio_fails'])
				{
					$sql = 'UPDATE _bio SET bio_fails = 0
						WHERE bio_id = ?';
					_sql(sql_filter($sql, $userdata['bio_id']));
				}
				
				$bio->session_create($userdata['bio_id']);
				redirect($v['page']);
			}
			
			if ($userdata['bio_fails'] == $core->v('account_failcount'))
			{
				// TODO: Captcha system if failcount reached
				// TODO: Notification about blocked account
				_fatal(508);
			}
			
			$sql = 'UPDATE _bio SET bio_fails = bio_fails + 1
				WHERE bio_id = ?';
			_sql(sql_filter($sql, $userdata['bio_id']));
			
			sleep(5);
			$this->_error('LOGIN_ERROR');
		}
		else
		{
			$v['register'] = true;
		}
		
		if ($v['register'])
		{
			$this->_up_home();
		}
		
		return;
	}
	
	public function out()
	{
		$this->method();
	}
	
	protected function _out_home()
	{
		global $bio;
		
		if ($bio->v('auth_member'))
		{
			$bio->session_kill();
			
			$bio->v('auth_member', false);
			$bio->v('session_page', '');
			$bio->v('session_time', time());
		}
		
		redirect(_link());
	}
	
	/*
	function register()
	{
		$this->method();
	}
	
	function _register_home()
	{
		global $bio;
		
		$fields_error = array(
			'username' => 'EMPTY_USERNAME',
			'email' => 'EMPTY_EMAIL',
			'email_confirm' => 'EMPTY_EMAIL_CONFIRM',
			'birthday_month' => 'EMPTY_BIRTHDAY',
			'birthday_day' => 'EMPTY_BIRTHDAY',
			'birthday_year' => 'EMPTY_BIRTHDAY',
			'agreetos' => 'AGREETOS_ERROR'
		);
		$fields_prev = array('email_confirm' => 'email');
		
		//
		$v = array('name', 'email', 'email_confirm', 'gender' => 0, 'birth_day' => 0, 'birth_month' => 0, 'birth_year' => 0, 'country' => 0, 'dateformat' => 0, 'timezone' => 0, 'tos' => 0);
		
		if (_button())
		{
			$this->__($v);
		}
		
		
		if (_button())
		{
			foreach ($fields as $key => $def)
			{
				$this->fields[$key] = request_var($key, $def);
				
				if (($this->fields[$key] === $def) && isset($fields_error[$key]))
				{
					if (isset($fields_prev[$key]) && $this->fields[$fields_prev[$key]] === $def)
					{
						continue;
					}
					$this->error($fields_error[$key]);
				}
			}
			
			if (!$this->errors())
			{
				$this->fs_fields('username', 'email', 'birthday');
			}
			
			if (!$this->errors())
			{
				$member_data = array(
					'bio_type' => 0,
					'bio_level' => 0,
					'bio_active' => 1,
					'bio_name' => $this->fields['username'],
					'bio_alias' => $this->fields['username_base'],
					'bio_regip' => $bio->ip,
					'bio_session_time' => 0,
					'bio_lastpage' => '',
					'bio_lastvisit' => 0,
					'bio_regdate' => time(),
					'bio_posts' => 0,
					'bio_page_posts' => 0,
					'bio_color' => '4D5358',
					'bio_timezone' => $core->v('board_timezone'),
					'bio_dst' => $core->v('board_dst'),
					'bio_lang' => $core->v('default_lang'),
					'bio_dateformat' => $core->v('default_dateformat'),
					'bio_rank' => 0,
					'bio_avatar' => '',
					'bio_avatar_up' => '',
					'bio_email' => $this->fields['email'],
					'bio_gender' => (int) $this->fields['gender'],
					'bio_birth' => (string) (_zero($this->fields['birthday_year']) . _zero($this->fields['birthday_month']) . _zero($this->fields['birthday_day'])),
					'bio_mark_items' => 0,
					'bio_topic_order' => 0
				);
				$sql = 'INSERT INTO _bio' . _build_array('INSERT', $member_data);
				_sql($sql);
				
				$this->fields['bio_id'] = _nextid();
				
				// Updates
				$core->v('max_users', $core->v('max_users') + 1);
				$bio->notify_store('members_newest', $this->fields['bio_id']);
				
				// Send email
				require_once(XFS . 'core/emailer.php');
				$emailer = new emailer();
				
				$emailer->from($core->v('board_email'));
				$emailer->use_template('user_welcome');
				$emailer->email_address($this->fields['email']);
				
				$emailer->assign_vars(array(
					'WELCOME_MSG' => _lang('WELCOME_SUBJECT'),
					'USERNAME' => $this->fields['username'],
					'U_PROFILE' => _link('m', $this->fields['username_base']))
				);
				$emailer->send();
				$emailer->reset();
				
				// Redirect
				redirect(_link('my', 'registered'));
			}
		}
		
		if ($this->errors())
		{
			_style('error', array(
				'MESSAGE' => $this->get_errors())
			);
		}
		
		foreach (_lang('MEMBERSHIP_BENEFITS_LIST') as $item)
		{
			_style('list_benefits', array(
				'ITEM' => $item)
			);
		}
		
		// Selects
		$this->ss_build('dateformat', 'timezone', 'gender', 'birthday');
		
		$sv = array(
			'AGREETOS_SELECTED' => ($this->fields['agreetos']) ? ' checked="true"' : '',
			'S_ACTION' => _link('my', 'register')
		);
		$sv += $this->fields_fvars();
		v_style($sv);
		
		return;
	}*/
}

?>