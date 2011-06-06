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

interface i_contest
{
	public function home();
	public function view();
	public function in();
}

class __contest extends xmd implements i_contest
{
	public function __construct()
	{
		parent::__construct();
		
		$this->_m(_array_keys(w('view')));
		$this->auth(false);
	}
	
	public function home()
	{
		$now = time();
		
		$sql = 'SELECT *
			FROM _contest
			WHERE contest_start > ??
				AND contest_end < ??
			ORDER BY contest_start';
		$contest = _rowset(sql_filter($sql, $now, $now));
		
		foreach ($contest as $i => $row)
		{
			if (!$i) _style('contest');
			
			_style('contest.row', array(
				'URL' => _link('contest', $row['contest_alias']),
				'SUBJECT' => $row['contest_subject'],
				'END' => _format_date($row['contest_end']))
			);
		}
		
		return;
	}
	
	public function view()
	{
		$this->method();
	}
	
	protected function _view_home()
	{
		global $core, $bio;
		
		$v = $this->__(w('alias'));
		if (!f($v['alias']))
		{
			_fatal();
		}
		
		$sql = 'SELECT *
			FROM _contest
			WHERE contest_alias = ?';
		if (!$contest = _fieldrow(sql_filter($sql, $v['alias'])))
		{
			_fatal();
		}
		
		$contest['expired'] = (time() > $contest['contest_end']);
		
		$is_contestant = false;
		if ($bio->v('auth_member'))
		{
			$sql = 'SELECT contestant_id
				FROM _contest_contestant
				WHERE contestant_contest = ?
					AND contestant_uid = ?';
			if (_fieldrow(sql_filter($sql, $contest['contest_id'], $bio->v('bio_id'))))
			{
				$is_contestant = true;
			}
		}
		
		if ($bio->v('auth_contest_view_stock'))
		{
			$sql = 'SELECT *
				FROM _contest_stock
				WHERE stock_contest = ?
				ORDER BY stock_name';
			$stock = _rowset(sql_filter($sql, $contest['contest_id']));
			
			$sql = 'SELECT *
				FROM _contest_contestant c, _bio b
				WHERE contestant_contest = ?
					AND contestant_uid = b.bio_id
				ORDER BY b.bio_alias';
			$contestants = _rowset(sql_filter($sql, $contest['contest_id']));
			
			foreach ($contestants as $i => $row)
			{
				if (!$i) _style('contestants');
				
				_style('contestants.row', array(
					
				));
			}
		}
		
		if ($contest['expired'])
		{
			if ($contest['contest_auto_win'] && !$contest['contest_has_win'])
			{
				
			}
		}
		else
		{
			
		}
		
		$sql = 'SELECT *
			FROM _contest_stock
			WHERE stock_contest = ?
			ORDER BY stock_name';
		$stock = _rowset(sql_filter($sql, $contest['contest_id']));
		
		$sql = 'SELECT b.bio_alias, b.bio_name
			FROM _contest_contestant c, _bio b
			WHERE c.contestant_contest = ?
				AND c.contestant_stock > 0
				AND c.contestant_uid=  b.bio_id
			ORDER BY c.contestant_stock';
		$contestant = _rowset(sql_filter($sql, $content['contest_id']), 'contestant_stock', false, true);
		
		foreach ($stock as $i => $row)
		{
			if (!$i) _style('stock');
			
			_style('stock.row', array(
				'NAME' => $row['stock_name'],
				'VALUE' => $row['stock_value'])
			);
			
			if ($v['expired'] && isset($contestant[$row['stock_id']]))
			{
				foreach ($contestant[$row['stock_id']] as $j => $row_contestant)
				{
					if (!$j) _style('stock.row.contestant');
					
					_style('stock.row.contestant.uid', array(
						'NAME' => $row_contestant['bio_name'],
						'LINK' => _link_bio($row_contestant['bio_alias']))
					);
				}
			}
		}
		
		v_style(array(
			'CONTEST_SUBJECT' => $contest['contest_subject'],
			'CONTEST_CONTENT' => _message($contest['contest_content']))
		);
		
		return;
	}
	
	public function in()
	{
		$this->method();
	}
	
	protected function _in_home()
	{
		return;
	}
}

?>