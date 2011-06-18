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

interface i_store
{
	public function home();
	public function view();
	public function add();
	public function update();
	public function share();
	public function confirm();
	public function thanks();
}

class __store extends xmd implements i_store
{
	public function __construct()
	{
		parent::__construct();
		
		$this->_m(_array_keys(w('view add update share confirm thanks')));
	}
	
	public function home()
	{
		return;
	}
	
	public function view()
	{
		return;
	}
	
	public function add()
	{
		return;
	}
	
	public function update()
	{
		return;
	}
	
	public function share()
	{
		return;
	}
	
	public function confirm()
	{
		return;
	}
	
	public function thanks()
	{
		return;
	}
}

?>