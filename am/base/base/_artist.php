<?php
/*
$Id: _artist.php,v 1.0 2009/01/30 12:21:00 Psychopsia Exp $

<Ximod, a web development framework.>
Copyright (C) <2009>  <Nopticon>

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

class __artist extends xmd
{
	var $methods = array(
		'config' => array(),
		'fixnews' => array(),
		'getnews' => array()
	);
	
	function home()
	{
		_fatal();
	}
	
	function config()
	{
		$this->method();
	}
	
	function _config_home()
	{
		$v = $this->__('a');
		
		$sql = "SELECT *
			FROM _artists
			WHERE subdomain = '" . $this->_escape($v['a']) . "'";
		if (!$artist = $this->_fieldrow($sql))
		{
			$this->e('El artista no existe.');
		}
	}
	
	function fixnews()
	{
		$this->method();
	}
	
	function _fixnews_home()
	{
		$sql = 'SELECT ub, subdomain, news
			FROM _artists
			ORDER BY ub';
		$artists = $this->_rowset($sql);
		
		//$this->e($artists);
		
		$d = '';
		foreach ($artists as $row)
		{
			$sql = 'SELECT COUNT(topic_id) AS total
				FROM _forum_topics
				WHERE topic_ub = ' . (int) $row['ub'];
			$total = $this->_field($sql, 'total');
			
			if ($row['news'] != $total)
			{
				$sql = 'UPDATE _artists SET news = ' . $total . '
					WHERE ub = ' . (int) $row['ub'];
				//$this->_sql($sql);
				
				$d .= $row['subdomain'] . ' . ' . $row['news'] . ' . ' . $total . '<br />';
			}
		}
		
		$this->e($d);
		
		return;
	}
	
	function getnews()
	{
		$this->method();
	}
	
	function _getnews_home()
	{
		global $user;
		
		$v = $this->__(array('a'));
		
		$sql = "SELECT ub
			FROM _artists
			WHERE subdomain = '" . $this->_escape($v['a']) . "'";
		if (!$artist = $this->_field($sql, 'ub'))
		{
			$this->e('El artista no existe.');
		}
		
		$sql = 'SELECT topic_title
			FROM _forum_topics
			WHERE topic_ub = ' . (int) $artist . '
			ORDER BY topic_id';
		$news = $this->_rowset($sql, false, 'topic_title');
		
		$d = '';
		foreach ($news as $row)
		{
			$d .= $row . '<br />';
		}
		
		$this->e($d);
		
		return;
	}
}

?>