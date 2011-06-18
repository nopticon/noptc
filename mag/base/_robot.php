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

interface i_robot
{
	public function home();
	public function birthday();
	public function mfeed();
	public function optimize();
}

class __robot extends xmd implements i_robot
{
	public function __construct()
	{
		parent::__construct();
		
		$this->auth(false);
		$this->_m(array(
			'birthday' => w(),
			'mfeed' => w(),
			'optimize' => w())
		);
	}
	
	public function home()
	{
		_fatal();
	}
	
	public function birthday()
	{
		$this->method();
	}
	
	protected function _birthday_home()
	{
		global $core;
		
		$birth_start = _timestamp();
		$birth_end = _timestamp();
		
		$sql = 'SELECT bio_id, bio_nickname, bio_email, bio_birth
			FROM _bio
			WHERE bio_birth >= ?
				AND bio_birth <= ?
				AND bio_birthlast < ?
			ORDER BY bio_nickname';
		$birthdays = _rowset(sql_filter($sql, $birthday_start, $birthday_end));
		
		foreach ($birthdays as $row)
		{
			$properties = array(
				'to' => $row['bio_email'],
				'template' => 'birthday'
			);
			_sendmail($properties);
		}
		
		return;
	}
	
	public function mfeed()
	{
		$this->method();
	}
	
	protected function _mfeed_home()
	{
		global $core;
		
		//
		// TODO: Filter by: Country, age range, gender
		//
		
		$sql = 'SELECT bio_id, bio_alias, bio_name, bio_email
			FROM _bio
			WHERE bio_active = ?
				AND bio_id NOT IN (
					SELECT ban_bio
					FROM _bio_ban
				)
			ORDER BY bio_alias
			LIMIT ??, ??';
		$mfeed = _rowset(sql_filter($sql, 1, 0, $core->v('mfeed_limit')));
		
		foreach ($mfeed as $row)
		{
			// TODO: Finish adding properties
			
			$properties = array(
				'to' => $row['bio_email'],
				'subject' => $current['subject'],
				'body' => $current['body'],
				'template' => $current['template']
			);
			_sendmail($properties);
		}
		
		return;
	}
	
	public function optimize()
	{
		$this->method();
	}
	
	protected function _optimize_home()
	{
		global $core;
		
		$core->v('site_disable', 1);
		
		$sql = 'SHOW TABLES';
		$tables = _rowset($sql, false, false, false, MYSQL_NUM);
		
		foreach ($tables as $row)
		{
			$sql = 'OPTIMIZE TABLE ' . $row[0];
			_sql($sql);
		}

		$core->v('site_disable', 0);
		
		$this->e('1');
	}
}

?>