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

class __email extends xmd
{
	function __construct()
	{
		parent::__construct();
		
		$this->_m(_array_keys(w('create clear check report total edit')));
		$this->auth(false);
	}
	
	function home()
	{
		global $user;
		
		$sql = 'SELECT *
			FROM _email
			WHERE email_active = ??
			LIMIT ??';
		if (!$email = _fieldrow(sql_filter($sql, 1, 1)))
		{
			$this->e('No queue emails.');
		}
		
		set_time_limit(0);
		
		if (!$email['email_start'])
		{
			$sql = 'UPDATE _email SET email_start = ?
				WHERE email_id = ?';
			_sql(sql_filter($sql, time(), $email['email_id']));
		}
		
		$sql = 'SELECT user_id, user_username, user_email
			FROM _members
			WHERE user_type = ?
				AND user_id <> ?
			ORDER BY user_username
			LIMIT ??, ??';
		$members = _rowset(sql_filter($sql, 1, 1, $email['email_last'], 100));
		
		$i = 0;
		foreach ($members as $row)
		{
			if (!preg_match('/^[a-z0-9\.\-_\+]+@[a-z0-9\-_]+\.([a-z0-9\-_]+\.)*?[a-z]+$/is', $row['user_email']))
			{
				continue;
			}
			
			if (!$i)
			{
				include(XFS . 'core/emailer.php');
				$emailer = new emailer();
			}
			
			$emailer->use_template('mass');
			
			$emailer->format('plain');
			$emailer->from('TWC Kaulitz <twc_princess@twckaulitz.com>');
			$emailer->set_subject(entity_decode($email['email_subject']));
			$emailer->email_address($row['user_email']);
			
			$emailer->assign_vars(array(
				'USERNAME' => $row['user_username'],
				'MESSAGE' => entity_decode($email['email_message']))
			);
			$emailer->send();
			$emailer->reset();
			
			sleep(2);
			$i++;
		}
		
		if ($i)
		{
			$email['email_last'] += $i;
			
			$sql = 'UPDATE _email SET email_last = ?
				WHERE email_id = ?';
			_sql(sql_filter($sql, $email['email_last'], $email['email_id']));
		}
		else
		{
			$sql = 'UPDATE _email SET email_active = ?, email_end = ?
				WHERE email_id = ?';
			_sql(sql_filter($sql, 0, time(), $email['email_id']));
			
			$this->e('Finished processing [' . $email['email_id'] . '] emails.');
		}
		
		$this->e('Processed ' . $i . ' emails.');
		
		return;
	}
	
	function check()
	{
		$this->method();
	}
	
	function _check_home()
	{
		global $user;
		
		$v = $this->__(array('id' => 0));
		
		$sql = 'SELECT *
			FROM _email
			WHERE email_id = ?';
		if (!$email = _fieldrow(sql_filter($sql, $v['id'])))
		{
			$this->e('El registro de email no existe.');
		}
		
		foreach (w('start end') as $k)
		{
			$email['email_' . $k] = ($email['email_' . $k]) ? $user->format_date($email['email_' . $k]) : '';
		}
		
		foreach ($email as $k => $v)
		{
			if (is_numb($k)) unset($email[$k]);
		}
		
		$this->e($email);
	}
	
	function create()
	{
		$this->method();
	}
	
	function _create_home()
	{
		if (_button())
		{
			$v = $this->__(w('subject message'));
			
			if (!f($v['subject']) || !f($v['message']))
			{
				$this->e('Debes completar los campos.');
			}
			
			$sql = 'SELECT email_id
				FROM _email
				WHERE email_subject = ?
					AND email_message = ?';
			if (_fieldrow(sql_filter($sql, $v['subject'], $v['message'])))
			{
				$this->e('El email ya esta programado para envio, no se puede duplicar.');
			}
			
			// d m y 
			$v['active'] = 1;
			
			$sql = 'INSERT INTO _email' . _build_array('INSERT', prefix('email', $v));
			_sql($sql);
			
			$this->e('El mensaje fue programado para envio de email.');
		}
		
		v_style(array(
			'SUBJECT' => '',
			'MESSAGE' => '')
		);
	}
	
	function edit()
	{
		$this->method();
	}
	
	function _edit_home()
	{
		global $user;
		
		$v = $this->__(array('id' => 0));
		
		$sql = 'SELECT *
			FROM _email
			WHERE email_id = ?';
		if (!$email = _fieldrow(sql_filter($sql, $v['id'])))
		{
			$this->e('El registro de email no existe.');
		}
		
		if (_button())
		{
			$v = array_merge($v, $this->__(w('subject message')));
			
			$sql = 'UPDATE _email SET ' . _build_array('UPDATE', prefix('email', $v)) . sql_filter('
				WHERE email_id = ?', $v['id']);
			_sql($sql);
			
			$this->e('El mensaje programado fue actualizado.');
		}
		
		v_style(array(
			'SUBJECT' => $email['email_subject'],
			'MESSAGE' => $email['email_message'])
		);
	}
	
	function clear()
	{
		$this->method();
	}
	
	function _clear_home()
	{
		global $user;
		
		$v = $this->__(array('id' => 0));
		
		if ($v['id'])
		{
			$sql = 'SELECT *
				FROM _email
				WHERE email_id = ?';
			if (!$email = _fieldrow(sql_filter($sql, $v['id'])))
			{
				$this->e('El registro de email no existe.');
			}
			
			$sql = 'UPDATE _email SET email_active = ?, email_start = ?, email_end = ?, email_last = ?
				WHERE email_id = ?';
			_sql(sql_filter($sql, 1, 0, 0, 0, $v['id']));
			
			$this->e('El registro de email fue reiniciado.');
		}
		
		$sql = 'SELECT email_id, email_subject
			FROM _email
			ORDER BY email_id';
		$emails = _rowset($sql);
		
		$response = '';
		foreach ($emails as $row)
		{
			$response .= '<a href="/faddr/email/x1:clear.id:' . $row['email_id'] . '">' . $row['email_subject'] . '</a><br />';
		}
		
		$this->e($response);
	}
	
	function total()
	{
		$this->method();
	}
	
	function _total_home()
	{
		$v = $this->__(array('id' => 0));
		
		$sql = 'SELECT *
			FROM _email
			WHERE email_id = ?';
		if (!$email = _fieldrow(sql_filter($sql, $v['id'])))
		{
			$this->e('El registro de email no existe.');
		}
		
		$sql = 'SELECT COUNT(user_id) AS total
			FROM _members
			WHERE user_active = ?
				AND user_id <> ?';
		$total = _field(sql_filter($sql, 1, 1), 'total');
		
		$sql = 'SELECT COUNT(user_id) AS total
			FROM _members';
		$all = _field($sql, 'total');
		
		$this->e($total . ' . ' . $all);
	}
}

?>