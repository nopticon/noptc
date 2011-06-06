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

interface i_skin
{
	public function home();
	public function commit();
	public function update();
	public function share();
}

class __skin extends xmd implements i_skin
{
	public function __construct()
	{
		parent::__construct();
		
		$this->_m(_array_keys(w('commit update share')));
	}
}

?>