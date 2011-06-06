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

interface i_ac
{
	public function home();
	public function connected();
}

class __ac extends xmd implements i_ac
{
	public function __construct()
	{
		parent::__construct();
		
		$this->_m(array(
			'connected' => w()
		));
		
		return;
	}
	
	public function home()
	{
		_fatal();
	}
	
	public function connected()
	{
		$this->method();
	}
	
	protected function _connected_home()
	{
		global $bio, $core;
		
		$totals = w();
		$time_today = _timestamp();
		$bots = get_bots();
		
		$sql = 'SELECT b.bio_id, b.bio_alias, b.bio_name, b.bio_level, b.bio_show, s.session_ip
			FROM _bio b, _sessions s
			WHERE b.bio_level NOT IN (??)
				((s.session_time >= ?
					AND b.bio_id = s.session_bio)
				OR (b.bio_lastvisit >= ?
					AND b.bio_lastvisit < ?)
				)
			ORDER BY b.bio_name';
		$sessions = _rowset(sql_filter($sql));
		
		$i = 0;
		foreach ($sessions as $row)
		{
			// Guest
			if ($row['bio_id'] == U_GUEST)
			{
				if ($row['session_ip'] != $last_ip)
				{
					$totals['guest']++;
				}
				
				$last_ip = $row['session_ip'];
				continue;
			}
			
			if (!$i)
			{
				_style('online', array(
					'L_TITLE' => _lang('ONLINE'))
				);
				_style('online.list');
			}
			
			// Member
			if ($row['bio_id'] != $last_bio_id)
			{
				$is_bot = isset($bots[$row['bio_id']]);
				
				if ($row['bio_show'])
				{
					if (!$is_bot)
					{
						$totals['visible']++;
					}
				}
				else
				{
					$totals['hidden']++;
				}
				
				if ((!$is_bot && ($row['bio_show'] || $bio->v('auth_founder'))) || ($is_bot && $bio->v('auth_founder')))
				{
					_style('online.list.row', array(
						'USERNAME' => $row['bio_name'],
						'PROFILE' => _link_bio($row['bio_alias']))
					);
				}
			}
			
			//
			$last_bio_id = $row['bio_id'];
			$i++;
		}
		
		if (!!$totals['visible'])
		{
			_style('online.none');
		}
		
		$online_ary = array(
			'MEMBERS_TOTAL' => array_sum($totals),
			'MEMBERS_VISIBLE' => $totals['visible'],
			'MEMBERS_GUESTS' => $totals['guests'],
			'MEMBERS_HIDDEN' => $totals['hidden'],
			'MEMBERS_BOT' => $totals['bots']
		);
		foreach ($online_ary as $lk => $vk)
		{
			if (!$vk && $lk != 'MEMBERS_TOTAL') continue;
			
			_style('online.legend.row', array(
				'L_MEMBERS' => _lang($lk . (($vk != 1) ? '2' : '')),
				'ONLINE_VALUE' => $vk)
			);
		}
		
		// Online
		$sql = 'SELECT b.bio_id, b.bio_alias, b.bio_name, b.bio_level, b.bio_show, s.session_ip
			FROM _bio b, _sessions s
			WHERE s.session_time >= ??
				AND b.bio_id = s.session_bio
			ORDER BY b.bio_name, s.session_ip';
		$this->f_connected(sql_filter($sql, ($local_time[0] - 300)), 'online', 'MEMBERS_ONLINE');
		
		// Today online
		$sql = 'SELECT bio_id, bio_alias, bio_name, bio_show, bio_level
			FROM _bio
			WHERE bio_level NOT IN (??)
				AND bio_lastvisit >= ?
				AND bio_lastvisit < ?
			ORDER BY bio_name';
		$this->f_connected(sql_filter($sql, _implode(',', w(USER_INACTIVE)), $time_today, ($time_today + 86399)), 'online', 'MEMBERS_TODAY', 'MEMBERS_VISIBLE');
		
		return;
	}
	
	private function f_connected($sql)
	{
		static $bots;
		
		if (!isset($bots))
		{
			$bots = get_bots();
		}
		
		foreach (array('last_bio_id' => 0, 'users_visible' => 0, 'users_hidden' => 0, 'users_guests' => 0, 'users_bots' => 0, 'last_ip' => '', 'users_online' => 0) as $k => $v)
		{
			$$k = $v;
		}
		
		_style($block, array('L_TITLE' => _lang($block_title)));
		_style($block . '.members');
		
		$online = _rowset($sql);
		
		foreach ($online as $row)
		{
			// Guest
			if ($row['bio_id'] == U_GUEST)
			{
				if ($row['session_ip'] != $last_ip)
				{
					$users_guests++;
				}
				
				$last_ip = $row['session_ip'];
				continue;
			}
			
			// Member
			if ($row['bio_id'] != $last_bio_id)
			{
				$is_bot = isset($user_bots[$row['bio_id']]);
				
				if ($row['bio_show'])
				{
					$username = $row['bio_name'];
					if (!$is_bot)
					{
						$users_visible++;
					}
				}
				else
				{
					$username = '*' . $row['bio_name'];
					$users_hidden++;
				}
				
				if ((($row['bio_show'] || $bio->v('auth_founder')) && !$is_bot) || ($is_bot && $bio->v('auth_founder')))
				{
					_style($block . '.members.item', array(
						'USERNAME' => $username,
						'PROFILE' => _link_bio($row['bio_alias']))
					);
				}
			}
			
			$last_bio_id = $row['bio_id'];
		}
		
		$users_total = (int) $users_visible + $users_hidden + $users_guests + $users_bots;
		
		if (!($users_visible + $users_hidden) || (!$users_visible && $users_hidden))
		{
			_style($block . '.members.none');
		}
		
		if (!$users_visible)
		{
			_style($block . '.members.none');
		}
		
		_style($block . '.legend');
		
		$online_ary = array(
			'MEMBERS_TOTAL' => $users_total,
			'MEMBERS_VISIBLE' => $users_visible,
			'MEMBERS_GUESTS' => $users_guests,
			'MEMBERS_HIDDEN' => $users_hidden,
			'MEMBERS_BOT' => $users_bots
		);
		if ($unset_legend !== false)
		{
			unset($online_ary[$unset_legend]);
		}
		foreach ($online_ary as $lk => $vk)
		{
			if (!$vk && $lk != 'MEMBERS_TOTAL')
			{
				continue;
			}
			_style($block . '.legend.item', array(
				'L_MEMBERS' => _lang($lk . (($vk != 1) ? '2' : '')),
				'ONLINE_VALUE' => $vk)
			);
		}
		
		return;
	}
}

?>