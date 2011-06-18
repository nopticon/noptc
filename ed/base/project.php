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

abstract class project
{
	final protected function year_list()
	{
		global $core;
		
		$list = w();
		$current = date('Y');
		
		for ($i_year = date('Y'); $i_year >= $core->v('first_year'); $i_year--)
		{
			$list[] = array(
				'year' => $i_year,
				'option' => '<option value="' . $i_year . '"' . (($i_year == $current) ? ' selected="selected"' : '') . '>' . $i_year . '</option>'
			);
		}
		
		return $list;
	}
	
	final protected function check_year($y)
	{
		global $core;
		
		$response = false;
		
		if ($y >= $core->v('first_year') && $y <= date('Y'))
		{
			$response = true;
		}
		
		return $response;
	}
}

?>