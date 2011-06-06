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

class __colors extends xmd
{
	public function __construct()
	{
		parent::__construct();
	}
	
	public function home()
	{
		global $core;
		
		//if (_button())
		{
			$v = $this->__(w('url'));
			if (!f($v['url']))
			{
				$v['url'] = $core->v('address');
			}
			
			if (f($v['url']))
			{
				if (preg_match('/.*?\.css/i', $v['url']))
				{
					$css = array($v['url']);
				}
				else
				{
					$parse = parse_url($v['url']);
					$f = netsock($parse['host'], $parse['path']);
					preg_match_all('#<link .*? href="(.*?\.css.*?)".*?\/>#i', $f, $css);
					$css = $css[1];
				}
				
				foreach ($css as $row)
				{
					$a_parse = parse_url($row);
					if (!isset($a_parse['host']))
					{
						$a_parse['host'] = $parse['host'];
					}
					
					$fcss = netsock($a_parse['host'], $a_parse['path'], 80, true);
					
					$pat = '(#([0-9A-Fa-f]{3,6})\b)|(rgb\(\s*\b([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])\b\s*,\s*\b([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])\b\s*,\s*\b([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])\b\s*\))|(rgb\(\s*(\d?\d%|100%)+\s*,\s*(\d?\d%|100%)+\s*,\s*(\d?\d%|100%)+\s*\))';
					preg_match_all('/' . $pat . '/i', $fcss, $all_color);
					
					$group = w();
					foreach ($all_color[1] as $pat_row)
					{
						$pat_row = strtoupper($pat_row);
						
						if (!isset($group[$pat_row])) $group[$pat_row] = 1;
					}
					
					_style('list', array(
						'FILE' => $row)
					);
					
					$group = array_keys($group);
					sort($group);
					
					foreach ($group as $color_row)
					{
						_style('list.row', array(
							'COLOR' => str_replace('#', '', $color_row))
						);
					}
				}
			}
		}
		
		return;
	}
}

?>