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

class __birthday extends xmd
{
	function __construct()
	{
		parent::__construct();
		
		$this->auth(false);
	}
	
	function home()
	{
		$sql = 'SELECT *
			FROM _members
			WHERE user_type = ?
				AND user_birthday LIKE ?
				AND user_birthday_last < ?
			ORDER BY user_username
			LIMIT ??';
		$birthday = _rowset(sql_filter($sql, 1, '%' . date('md'), date('Y'), 10));
		
		if (!$birthday)
		{
			$this->e('None.');
		}
		
		$process = w();
		foreach ($birthday as $i => $row)
		{
			if (!$i)
			{
				@set_time_limit(0);

				require(XFS . 'core/emailer.php');
				$emailer = new emailer();
			}
			
			$emailer->format('plain');
			$emailer->from('TWC Kaulitz <twc_princess@twckaulitz.com>');
			$emailer->use_template('user_birthday');
			$emailer->email_address($row['user_email']);
	
			$emailer->assign_vars(array(
				'USERNAME' => $row['user_username'])
			);
			$emailer->send();
			$emailer->reset();
			
			$process[$row['user_id']] = $row['user_username'];
		}
		
		if (count($process))
		{
			$sql = 'UPDATE _members SET user_birthday_last = ?
				WHERE user_id IN (??)';
			_sql(sql_filter($sql, date('Y'), _implode(',', array_keys($process))));
		}
		
		return $this->e('Done @ ' . implode(',', array_values($process)));
	}
}

?>
