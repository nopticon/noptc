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

class __home extends xmd
{
	public function __construct()
	{
		parent::__construct();
		
		$this->auth(false);
	}
	
	public function home()
	{
		global $core;
		
		$v = $this->__(array('a', 'p' => 0));
		
		if (f($v['a']))
		{
			$sql = 'SELECT area_id
				FROM _reference_area
				WHERE area_alias = ?';
			if (!_field(sql_filter($sql, $v['a']), 'area_id', 0))
			{
				_fatal();
			}
			
			$sql = 'SELECT COUNT(r.ref_id) AS total
				FROM _reference r, _reference_area a
				WHERE a.area_alias = ?
					AND r.ref_area = a.area_id
				ORDER BY r.ref_time DESC';
			$ref_total = _field(sql_filter($sql, $v['a']), 'total', 0);
			
			$sql = 'SELECT *
				FROM _reference r, _reference_area a
				WHERE a.area_alias = ?
					AND r.ref_area = a.area_id
				ORDER BY r.ref_time DESC
				LIMIT ??, ??';
			$ref = _rowset(sql_filter($sql, $v['a'], $v['p'], $core->v('ref_pages')));
		}
		else
		{
			$sql = 'SELECT COUNT(ref_id) AS total
				FROM _reference
				ORDER BY ref_time DESC';
			$ref_total = _field($sql, 'total', 0);
			
			$sql = 'SELECT *
				FROM _reference r, _reference_area a
				WHERE r.ref_area = a.area_id
				ORDER BY r.ref_time DESC
				LIMIT ??, ??';
			$ref = _rowset(sql_filter($sql, $v['p'], $core->v('ref_pages')));
		}
		
		if ($v['p'] && $ref_total)
		{
			redirect(_link());
		}
		else
		{
			_style('noref');
		}
		
		foreach ($ref as $i => $row)
		{
			if (!$i) _style('ref');
			
			if ($this->has_plugin($row['ref_content']))
			{
				$this->parse_plugin($row);
				continue;
			}
			
			_style('ref.row', _vs(array(
				'id' => $row['ref_id'],
				'link' => _link($row['ref_alias']),
				
				'subject' => $row['ref_subject'],
				'content' => _message($row['ref_content']),
				'time' => _format_date($row['ref_time'])
			), 'ref'));
		}
		
		return;
	}
}

?>