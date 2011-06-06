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

interface i_dev
{
	public function home();
	public function artists();
	public function corp();
	public function services();
	public function tos();
	public function feed();
	public function jobs();
	public function uptime();
	public function random();
	public function emoticon();
}

class __dev extends xmd implements i_dev
{
	public function __construct()
	{
		parent::__construct();
		
		$this->auth(false);
		$this->_m(_array_keys(w('artists corp emoticon feed jobs uptime random services tos')));
	}
	
	public function home()
	{
		_fatal();
	}
	
	public function artists()
	{
		$this->method();
	}
	
	protected function _artists_home()
	{
		// TODO: Add sorting methods by genre, country & more.
		
		$v = $this->__(w('by'));
		
		switch ($v['by'])
		{
			case 'genre':
				// TODO: Add query
				$sql = 'SELECT b.bio_id, b.bio_alias, b.bio_name, b.bio_avatar, b.bio_avatar_up
					FROM _bio b, _bio_type t
					WHERE t.type_alias = ?
						AND b.bio_type = t.type_id
					ORDER BY b.bio_name';
				$artists = _rowset(sql_filter($sql, 'artist'));
				break;
			default:
				$allow_by = array(
					'country' => 'c.country_name'
				);
				
				$s_country = isset($allow_by[$v['by']]) ? $allow_by[$v['by']] . ',' : '';
				
				$sql = 'SELECT b.bio_id, b.bio_alias, b.bio_name, b.bio_avatar, b.bio_avatar_up
					FROM _bio b, _bio_type t, _countries c
					WHERE t.type_alias = ?
						AND b.bio_type = t.type_id
						AND b.bio_country = c.country_id
					ORDER BY ?? b.bio_name';
				$artists = _rowset(sql_filter($sql, 'artist', $s_country));
				break;
		}
		
		// Genres
		$sql = 'SELECT g.genre_alias, g.genre_name, r.relation_artist
			FROM _genres g, _genres_relation r
			WHERE g.genre_id = r.relation_genre
				AND r.relation_artist IN (??)
			ORDER BY g.genre_name';
		$genres = _rowset(sql_filter($sql, _implode(',', array_subkey($artists, 'bio_id'))), 'relation_artist', false, true);
		
		$i = 0;
		foreach ($artists as $row)
		{
			$first_letter = $row['bio_alias']{0};
			if (f($v['sort']) && $first_letter != $v['sort'])
			{
				continue;
			}
			
			if (!$i) _style('artists');
			
			_style('artists.row', _vs(array(
				'URL' => _link_bio($row['bio_alias']),
				'NAME' => $row['bio_name'],
				'IMAGE' => _avatar($row),
				'GENRE' => _implode(', ', $genres[$row['bio_id']])
			), 'v'));
			$i++;
		}
		
		if (!$i) _style('artists_none');
		
		return;
	}
	
	public function corp()
	{
		$this->method();
	}
	
	protected function _corp_home()
	{
		$sql = 'SELECT *
			FROM _groups
			WHERE group_special = 1
			ORDER BY group_order';
		$groups = _rowset($sql);
		
		$sql = 'SELECT g.group_id, b.bio_alias, b.bio_name, b.bio_firstname, b.bio_lastname, b.bio_life, b.bio_avatar, b.bio_avatar_up
			FROM _groups g, _group_joint j, _bio b
			WHERE g.group_id = j.joint_group
				AND j.joint_bio = b.bio_id
			ORDER BY j.joint_order, b.bio_alias';
		$members = _rowset($sql, 'group_id', false, true);
		
		$i = 0;
		foreach ($groups as $row)
		{
			if (!isset($members[$row['group_id']])) continue;
			
			if (!$i) _style('groups');
			
			_style('groups.list', array(
				'GROUP_NAME' => $row['group_name'])
			);
			
			foreach ($members[$row['group_id']] as $row2)
			{
				_style('groups.list.member', _vs(array(
					'LINK' => _link_bio($row2['bio_alias']),
					'NAME' => $row2['bio_name'],
					'REALNAME' => _fullname($row2),
					'BIO' => _message($row2['bio_life']),
					'AVATAR' => _avatar($row2))
				), 'USER');
			}
			$i++;
		}
		
		if ($corp = $this->page_query('corp'))
		{
			v_style(array(
				'CORP_CONTENT' => _message($corp['page_content']))
			);
		}
		
		return;
	}
	
	public function uptime()
	{
		global $bio;
		
		if (!$bio->v('auth_uptime') || !$uptime = @exec('uptime'))
		{
			_fatal();
		}
		
		if (strstr($uptime, 'day'))
		{
			if (strstr($uptime, 'min'))
			{
				preg_match('/up\s+(\d+)\s+(days,|days|day,|day)\s+(\d{1,2})\s+min/', $uptime, $times);
				$days = $times[1];
				$hours = 0;
				$mins = $times[3];
			}
			else
			{
				preg_match('/up\s+(\d+)\s+(days,|days|day,|day)\s+(\d{1,2}):(\d{1,2}),/', $uptime, $times);
				$days = $times[1];
				$hours = $times[3];
				$mins = $times[4];
			}
		}
		else
		{
			if (strstr($uptime, 'min'))
			{
				preg_match('/up\s+(\d{1,2})\s+min/', $uptime, $times);
				$days = 0;
				$hours = 0;
				$mins = $times[1];
			}
			else
			{
				preg_match('/up\s+(\d+):(\d+),/', $uptime, $times);
				$days = 0;
				$hours = $times[1];
				$mins = $times[2];
			}
		}
		preg_match('/averages?: ([0-9\.]+),[\s]+([0-9\.]+),[\s]+([0-9\.]+)/', $uptime, $avgs);
		$load = $avgs[1] . ', ' . $avgs[2] . ', ' . $avgs[3];
		
		$tv = array(
			'SERVER_UPTIME' => sprintf(_lang('SERVER_UPTIME'), $days, $hours, $mins),
			'SERVER_LOAD' => sprintf(_lang('SERVER_LOAD'), $load)
		);
		return v_style($tv);
	}
	
	public function tos()
	{
		$this->method();
	}
	
	protected function _tos_home()
	{
		$v = $this->__(array('view' => 'tos'));
		
		if (!$page = $this->page_query($v['view']))
		{
			_fatal();
		}
		
		return v_style(array(
			'TOS_CONTENT' => _message($page['page_content']))
		);
	}
	
	public function services()
	{
		$this->method();
	}
	
	protected function _services_home()
	{
		global $core, $bio;
		
		$v = $this->__(w('service'));
		
		if (f($v['service']))
		{
			$sql = 'SELECT *
				FROM _services
				WHERE service_alias = ?';
			if (!$service = _fieldrow(sql_filter($sql, $v['service'])))
			{
				_fatal();
			}
		}
		
		$sql = 'SELECT *
			FROM _services
			ORDER BY service_order';
		$services = _rowset($sql);
		
		foreach ($services as $i => $row)
		{
			if (!$i) _style('services');
			
			_style('services.row', array(
				
			));
		}
		
		return;
	}
	
	public function feed()
	{
		$this->method();
	}
	
	protected function _feed_home()
	{
		global $core;
		
		$format = '<?xml version="1.0" encoding="iso-8859-1"?>
<rss version="2.0">
<channel>
	<title>%s</title>
	<link>%s</link>
	<language>es-gt</language>
	<description><![CDATA[%s]]></description>
	<lastBuildDate>%s</lastBuildDate>
	<webMaster>%s</webMaster>
%s
</channel>
</rss>';
		
		$tags = w('author title link guid description pubDate');
		
		$last_entry = time();
		$feed = '';
		
		$sql = 'SELECT r.ref_subject, r.ref_content, r.ref_time, r.ref_link, b.bio_name
			FROM _reference r, _reference_type t, _bio b
			WHERE r.ref_bio = b.bio_id
				AND r.ref_type = t.type_id
			ORDER BY r.ref_time DESC
			LIMIT 20';
		$reference = _rowset($sql);
		
		foreach ($reference as $i => $row)
		{
			if (!$i) $last_entry = $row['ref_time'];
			
			$a = array(
				$row['username'],
				'<![CDATA[' . entity_decode($row['ref_subject'], false) . ']]>',
				$row['ref_link'],
				$row['ref_link'],
				'<![CDATA[' . entity_decode($row['ref_content'], false) . ']]>',
				date('D, d M Y H:i:s \G\M\T', $row['ref_time'])
			);
			
			$feed .=  "\t<item>";
			
			foreach ($a as $j => $v)
			{
				$feed .= '<' . $tags[$j] . '>' . $v . '</' . $tags[$j] . '>';
			}
			
			$feed .= "</item>\n";
		}
		
		//
		header('Content-type: text/xml');
		
		$ref_title = entity_decode(_lang('FEED_TITLE'), false);
		$ref_desc = entity_decode(_lang('FEED_DESC'), false);
		
		$this->e(sprintf($format, $ref_title, _link(), $ref_desc, date('D, d M Y H:i:s \G\M\T', $last_entry), $core->v('site_email'), $feed));
	}
	
	public function jobs()
	{
		$this->method();
	}
	
	protected function _jobs_home()
	{
		$sql = 'SELECT *
			FROM _jobs
			WHERE job_end > ??
			ORDER BY job_time';
		$jobs = _rowset(sql_filter($sql, time()));
		
		foreach ($jobs as $i => $row)
		{
			if (!$i) _style('jobs');
			
			_style('jobs.row'. _vs(array(
				'TITLE' => $row['job_title'],
				'REQUIREMENT' => _message($row['job_requirement']),
				'OFFER' => _message($row['job_offer']),
				'RANGE' => $row['job_range']
			), 'JOB'));
		}
		
		return;
	}
	
	public function random()
	{
		$this->method();
	}
	
	protected function _random_home()
	{
		global $bio;
		
		$v = $this->__(w('type'));
		
		switch ($v['type'])
		{
			case 'artist':
			case 'user':
				$sql = 'SELECT b.bio_alias
					FROM _bio b, _bio_type t
					WHERE t.type_alias = ?
						AND b.bio_type = t.type_id
					ORDER BY RAND()
					LIMIT 1';
				$alias = _field(sql_filter($sql, $v['type']), 'bio_alias', '');
				
				$link = _link('alias', $alias);
				break;
			case 'event':
				$sql = 'SELECT *
					FROM _events
					WHERE
					ORDER BY RAND()
					LIMIT 1';
				break;
			default:
				_fatal();
				break;
		}
		
		return;
	}
	
	public function emoticon()
	{
		$this->method();
	}
	
	protected function _emoticon_home()
	{
		global $core;
		
		if (!$emoticons = $core->cache_load('emoticon'))
		{
			$sql = 'SELECT *
				FROM _smilies
				ORDER BY LENGTH(code) DESC';
			$emoticons = $core->cache_store(_rowset($sql));
		}
		
		foreach ($emoticons as $i => $row)
		{
			if (!$i) _style('emoticons');
			
			_style('emoticons.row', array(
				'CODE' => $row['code'],
				'IMAGE' => _lib(LIB_VISUAL . '/emoticons', $row['smile_url']),
				'DESC' => $row['emoticon'])
			);
		}
		
		return;
	}
}

?>