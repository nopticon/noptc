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

interface i_newsletter
{
	public function home();
	public function create();
	public function clear();
	public function check();
	public function report();
	public function total();
	public function modifiy();
}

class __newsletter extends xmd implements i_newsletter
{
	public function __construct()
	{
		parent::__construct();
		
		$this->auth(false);
		$this->_m(w('create clear check report total modify'));
	}
	
	function home()
	{
		global $bio;
		
		/*
		email_id
		email_active
		email_subject
		email_message
		email_lastvisit
		email_last
		email_start
		email_end
		*/
		
		$sql = 'SELECT *
			FROM _newsletter
			WHERE newsletter_active = 1
			LIMIT 1';
		if (!$newsletter = _fieldrow($sql))
		{
			$this->e('No newsletter.');
		}
		
		set_time_limit(0);
		
		if (!$newsletter['newsletter_start'])
		{
			$sql = 'UPDATE _newsletter SET newsletter_start = ?
				WHERE newsletter_id = ?';
			_sql(sql_filter($sql, time(), $newsletter['newsletter_id']));
		}
		
		$sql = 'SELECT bio_id, bio_alias, bio_name, bio_address, bio_lastvisit
			FROM _bio b
			LEFT JOIN _countries ON bio_country = country_id
				AND country_name = ?
			RIGHT JOIN _bio_newsletter ON bio_id = newsletter_bio
				AND newsletter_receive = ? 
			WHERE bio_lastvisit >= ?
				AND bio_active = ?
				AND bio_id NOT IN (
					SELECT ban_bio
					FROM _banlist
				)
			ORDER BY bio_name
			LIMIT ??, ??';
		$members = _rowset(sql_filter($sql, 'Guatemala', 1, $newsletter['newsletter_lastvisit'], 1));
		
		/*
		$sql = 'SELECT user_id, username, username_base, user_email, user_lastvisit, user_public_email
			FROM _members
			WHERE user_lastvisit >= ' . (int) $email['email_lastvisit'] . '
				AND user_country = 90
				AND user_active = 1
				AND user_id <> 1
				AND user_send_mass = 1
				AND user_id NOT IN (
					SELECT ban_userid
					FROM _banlist
				)
			ORDER BY username
			LIMIT ' . (int) $email['email_last'] . ', 200';
		$members = $this->_rowset($sql);
		*/
		
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
			$emailer->from('Rock Republik <press@rockrepublik.net>');
			$emailer->set_subject(entity_decode($email['email_subject']));
			$emailer->email_address($row['user_email']);
			
			if (!empty($row['user_public_email']) && $row['user_email'] != $row['user_public_email'] && preg_match('/^[a-z0-9\.\-_\+]+@[a-z0-9\-_]+\.([a-z0-9\-_]+\.)*?[a-z]+$/is', $row['user_public_email']))
			{
				$emailer->cc($row['user_public_email']);
			}
			
			$emailer->assign_vars(array(
				'USERNAME' => $row['username'],
				'MESSAGE' => entity_decode($email['email_message']))
			);
			$emailer->send();
			$emailer->reset();
			
			fwrite_line('./mass.txt', $row['username'] . ' . ' . $row['user_email'] . ' . ' . $row['user_public_email'] . ' . ' . $user->format_date($row['user_lastvisit']));
			
			sleep(2);
			
			$i++;
		}
		
		if ($i)
		{
			$email['email_last'] += $i;
			
			$sql = 'UPDATE _email SET email_last = ' . $email['email_last'] . '
				WHERE email_id = ' . (int) $email['email_id'];
			$this->_sql($sql);
		}
		else
		{
			$sql = 'UPDATE _email SET email_active = 0, email_end = ' . (int) time() . '
				WHERE email_id = ' . (int) $email['email_id'];
			$this->_sql($sql);
			
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
			WHERE email_id = ' . (int) $v['id'];
		if (!$email = $this->_fieldrow($sql))
		{
			$this->e('El registro de email no existe.');
		}
		
		/*
		email_id
		email_active
		email_subject
		email_message
		email_lastvisit
		email_last
		email_start
		email_end
		*/
		
		foreach (w('lastvisit start end') as $k)
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
		global $style;
		
		if (_button())
		{
			$v = $this->__(array('subject', 'message', 'lastvisit'));
			
			$sql = "SELECT email_id
				FROM _email
				WHERE email_subject = '" . $this->_escape($v['subject']) . "'
					AND email_message = '" . $this->_escape($v['message']) . "'";
			if ($this->_fieldrow($sql))
			{
				$this->e('El email ya esta programado para envio, no se puede duplicar.');
			}
			
			// d m y 
			$vs = explode(' ', $v['lastvisit']);
			$v['lastvisit'] = mktime(0, 0, 0, $vs[1], $vs[0], $vs[2]);
			$v['active'] = 1;
			
			$sql = 'INSERT INTO _email' . $this->_build_array('INSERT', ksql('email', $v));
			$this->_sql($sql);
			
			$this->e('El mensaje fue programado para envio de email.');
		}
		
		$sv = array(
			'SUBJECT' => '',
			'MESSAGE' => '',
			'LASTVISIT' => ''
		);
		$this->as_vars($sv);
	}
	
	function edit()
	{
		$this->method();
	}
	
	function _edit_home()
	{
		global $user, $style;
		
		$v = $this->__(array('id' => 0));
		
		$sql = 'SELECT *
			FROM _email
			WHERE email_id = ' . (int) $v['id'];
		if (!$email = $this->_fieldrow($sql))
		{
			$this->e('El registro de email no existe.');
		}
		
		if (_button())
		{
			$v = array_merge($v, $this->__(array('subject', 'message', 'lastvisit')));
			
			$vs = explode(' ', $v['lastvisit']);
			$v['lastvisit'] = mktime(0, 0, 0, $vs[1], $vs[0], $vs[2]);
			
			$sql = 'UPDATE _email SET ' . $this->_build_array('UPDATE', ksql('email', $v)) . '
				WHERE email_id = ' . (int) $v['id'];
			$this->_sql($sql);
			
			$this->e('El mensaje programado fue actualizado.');
		}
		
		$lastvisit = $user->format_date($email['email_lastvisit'], 'j n Y');
		
		$sv = array(
			'SUBJECT' => $email['email_subject'],
			'MESSAGE' => $email['email_message'],
			'LASTVISIT' => $lastvisit
		);
		$this->as_vars($sv);
	}
	
	function clear()
	{
		$this->method();
	}
	
	function _clear_home()
	{
		global $user;
		
		$v = $this->__(array('id'));
		
		if ($v['id'])
		{
			$sql = 'SELECT *
				FROM _email
				WHERE email_id = ' . (int) $v['id'];
			if (!$email = $this->_fieldrow($sql))
			{
				$this->e('El registro de email no existe.');
			}
			
			$sql = 'UPDATE _email SET email_active = 1, email_start = 0, email_end = 0, email_last = 0
				WHERE email_id = ' . (int) $v['id'];
			$this->_sql($sql);
			
			$this->e('El registro de email fue reiniciado.');
		}
		
		$sql = 'SELECT email_id, email_subject
			FROM _email
			ORDER BY email_id';
		$emails = $this->_rowset($sql);
		
		$response = '';
		foreach ($emails as $row)
		{
			$response .= '<a href="/nijad/email/x1:clear.id:' . $row['email_id'] . '">' . $row['email_subject'] . '</a><br />';
		}
		
		$this->e($response);
	}
	
	function report()
	{
		$this->method();
	}
	
	function _report_home()
	{
		$report = $this->implode('', @file('./mass.txt'));
		
		$list = explode("\n", $report);
		
		$a = '';
		foreach ($list as $i => $row)
		{
			$a .= ($i + 1) . ' > ' . $row . '<br />';
		}
		
		$this->e($a);
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
			WHERE email_id = ' . (int) $v['id'];
		if (!$email = $this->_fieldrow($sql))
		{
			$this->e('El registro de email no existe.');
		}
		
		$sql = 'SELECT COUNT(user_id) AS total
			FROM _members
			WHERE user_lastvisit >= ' . (int) $email['email_lastvisit'] . '
				AND user_country = 90
				AND user_active = 1
				AND user_id <> 1
				AND user_id NOT IN (
					SELECT ban_userid
					FROM _banlist
				)';
		$total = $this->_field($sql, 'total');
		
		$sql = 'SELECT COUNT(user_id) AS total
			FROM _members';
		$all = $this->_field($sql, 'total');
		
		$this->e($total . ' . ' . $all);
	}
}

?>
