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

/*
events
events/gallery
events/thumbnail
events/future
events/preview
events/contrib
*/

// lights performance time people place security

interface i_events
{
	public function home();
	public function view();
	public function tag();
	public function comment();
	public function star();
	public function acp();
	public function attend();
}

class __events extends xmd implements i_events
{
	private $day = array();
	
	public function __construct()
	{
		parent::__construct();
		
		$this->_m(array(
			'view' => w(),
			'star' => w(),
			'attend' => w(),
			'tag' => w(),
			'acp' => w('edit'))
		);
		$this->auth(false);
		
		$g = getdate();
		$d = explode(' ', gmdate('j n Y', _localtime()));
		$v_week = ($d[0] + (7 - ($g['wday'] - 1))) - (!$g['wday'] ? 7 : 0);
		
		$this->day['midnight'] = _timestamp($d[1], $d[0], $d[2]);
		$this->day['week'] = _timestamp($d[1], $v_week, $d[2]);
		$this->day['midnight_one'] = $this->day['midnight'] + 86400;
		$this->day['midnight_two'] = $this->day['midnight'] + (86400 * 2);
	}
	
	private function _when($d, $e)
	{
		if ($d < $this->day['midnight'] || $e) {
			$type = ($e) ? 'gallery' : 'contrib';
		} elseif ($d >= $this->day['midnight'] && $d < $this->day['midnight_one']) {
			$type = 'today';
		} elseif ($d >= $this->day['midnight_one'] && $d < $this->day['midnight_two']) {
			$type = 'tomorrow';
		} elseif ($d >= $this->day['midnight_two'] && $d < $this->day['week']) {
			$type = 'week';
		} else {
			$type = 'future';
		}
		
		return $type;
	}
	
	public function home()
	{
		global $core;
		
		// all today tomorrow week future
		
		$v = $this->__(array('f', 'p' => 0));
		
		if (!$event_type = $core->cache_load('events_type'))
		{
			$sql = 'SELECT type_id, type_alias
				FROM _events_type
				ORDER BY type_order';
			$event_type = $core->cache_store('events_type', _rowset($sql, 'type_alias', 'type_id'));
		}
		
		if ($v['f'] && !isset($event_type[$v['f']]))
		{
			_fatal();
		}
		
		$sql = 'SELECT *
			FROM _events
			ORDER BY event_date ASC';
		$list = _rowset($sql);
		
		$events = w();
		foreach ($list as $row)
		{
			$type = $this->_when($row['event_date'], $row['event_images']);
			$events[$type][] = $row;
		}
		unset($list);
		
		if ($v['f'])
		{
			$events = array($v['f'] => $events[$v['f']]);
		}
		
		foreach ($events as $k => $z)
		{
			switch ($k)
			{
				case '':
					break;
				
			}
		}
		
		// Gallery
		if (isset($events['gallery']))
		{
			@krsort($events['gallery']);
			
			if (!$events['gallery'] = array_slice($events['gallery'], $v['g'], $core->v('gallery_pages')))
			{
				_fatal();
			}
			
			$sql = 'SELECT *
				FROM _events_images
				WHERE event_id IN (??)
				ORDER BY RAND()';
			$i_random = _rowset(sql_filter($sql, _implode(',', array_subkey($events['gallery'], 'event_id'))), 'event_id', 'image');
			
			foreach ($events['gallery'] as $i => $row)
			{
				if (!$i) _style('gallery', _pagination(_link($this->m()), 'g:%d', count($events['gallery']), $core->v('gallery_pages'), $v['g']));
				
				_style('gallery.row', array(
					'URL' => _link($this->m(), $row['event_alias']),
					'TITLE' => $row['event_subject'],
					'IMAGE' => _lib(w(LIB_EVENT . ' thumbnail ' . $row['event_id']), $i_random[$row['event_id']], 'jpg'),
					'TIME' => _format_date($row['event_date'], _lang('DATE_FORMAT')))
				);
			}
			
			unset($events['gallery']);
		}
		
		if (is_ghost())
		{
			return;
		}
		
		$attend_event = $attend_id = w();
		foreach ($events as $row)
		{
			foreach ($row as $row2)
			{
				$attend_id = array_merge($attend_id, array_subkey($row2, 'event_id'));
			}
		}
		
		if (count($attend_id))
		{
			if ($bio->v('auth_member'))
			{
				$sql = 'SELECT attend_event, attend_option
					FROM _events_attend
					WHERE attend_event IN (??)
						AND attend_uid = ?';
				$attend_event = _rowset(sql_filter($sql, _implode(',', $attend_id), $bio->v('bio_id')), 'attend_event', 'attend_option');
			}
			
			$sql = 'SELECT *, COUNT(a.attend_uid) AS attendees
				FROM _events_attend_type t, _events_attend a
				WHERE a.attend_event IN (??)
					AND a.attend_option = t.type_id
				ORDER BY type_order';
			$types = _rowset(sql_filter($sql, _implode(',', $attend_id)), 'attend_event', false, true);
		}
		
		$i = 0;
		foreach ($events as $type => $type_row)
		{
			if (!$i) _style('future');
			
			_style('future.type', array(
				'L_TITLE' => _lang('EVENTS_' . $event_type[$type]))
			);
			
			foreach ($type_row as $row)
			{
				_style('future.type.row', array(
					'URL' => _link($this->m(), $row['event_alias']),
					'TITLE' => $row['event_subject'],
					'DATE' => _format_date($row['event_date']),
					'THUMBNAIL' => _lib(w(LIB_EVENT . ' preview'), $row['event_id'], 'jpg'),
					
					'ATTEND_YES' => $row['event_attend_yes'],
					'ATTEND_NO' => $row['event_attend_no'],
					
					'U_ATTEND' => _link($this->m(), array($row['event_alias'], 'x1' => 'attend')),
					'V_ATTEND' => (isset($attend_event[$row['event_id']])) ? $attend_event[$row['event_id']] : -1)
				);
			}
			$i++;
		}
		
		$this->monetize();
		
		_style('suggest', array(
			'URL' => _link('suggest', 'event'))
		);
		
		return;
	}
	
	public function view()
	{
		$this->method();
	}
	
	protected function _view_home()
	{
		global $core, $bio;
		
		$v = $this->__(array('alias', 't' => 0, 'p' => 0));
		
		if (!f($v['alias']))
		{
			_fatal();
		}
		
		$v['field'] = (!is_numb($v['alias'])) ? 'alias' : 'id';
		
		$sql = 'SELECT *
			FROM _events
			WHERE event_?? = ?';
		if (!$event = _fieldrow(sql_filter($sql, $v['field'], $v['alias'])))
		{
			_fatal();
		}
		
		if ($v['field'] == 'id' && f($event['event_alias']))
		{
			redirect(_link($this->m(), $event['event_alias']) . _linkp(array('t' => $v['t'], 'p' => $v['p']), true));
		}
		
		// Get images
		$sql = 'SELECT *
			FROM _events_images
			WHERE image_event = ?
			ORDER BY image ASC
			LIMIT ??, ??';
		$event_images = _rowset(sql_filter($sql, $event['event_id'], $v['t'], $core->v('thumbs_per_page')));
		
		foreach ($event_images as $i => $row)
		{
			if (!$i) _style('thumbnails', _pagination(_link($this->m(), $event['event_alias']), 't:%d', $event['event_images'], $core->v('thumbs_per_page'), $v['t']));
			
			_style('thumbnails.row', array(
				'U_THUMBNAIL' => _lib(w(LIB_EVENT . ' thumbnail ' . $event['event_id'], $row['image'], 'jpg')),
				'U_IMAGE' => _lib(w(LIB_EVENT . ' gallery ' . $event['event_id'], $row['image'], 'jpg')),
				
				'V_FOOTER' => $row['image_footer'])
			);
		}
		
		if (is_ghost())
		{
			return;
		}
		
		// Statistics
		if (!$v['t'] && !$bio->v('auth_founder'))
		{
			$this->_stats_store();
		}
		
		$is_future = ($row['event_end'] > time()) ? true : false;
		
		if (!$is_future)
		{
			// Star for favourites
			if (!$star_type = $core->cache_load('star_type'))
			{
				$sql = 'SELECT type_id, type_name
					FROM _events_star_type
					ORDER BY type_order';
				$types = $core->cache_store('star_type', _rowset($sql, 'type_id', 'type_name'));
			}
			
			$i = 0;
			foreach ($types as $type_id => $type_name)
			{
				if (!$i) _style('star_type');
				
				_style('star_type.row', array(
					'TYPE_ID' => $type_id,
					'TYPE_NAME' => $type_name)
				);
				
				$i++;
			}
		}
		else
		{
			$sql = 'SELECT *
				FROM _events_reviews r, _bio b
				WHERE r.review_event = ?
					AND r.review_uid = b.bio_id
				ORDER BY r.review_avg
				LIMIT 0, 5';
			$reviews = _rowset(sql_filter($sql, $event['event_id']), 'review_id');
			
			$sql = 'SELECT *
				FROM _events_reviews_rate r, _events_reviews_fields f
				WHERE r.rate_review IN (??)
					AND r.rate_field = f.field_id
				ORDER BY f.field_order';
			$reviews_rate = _rowset(sql_filter($sql, _implode(',', array_keys($reviews))), 'rate_review', false, true);
			
			$i = 0;
			foreach ($reviews as $row)
			{
				if (!$i) _style('reviews');
				
				_style('reviews.row', array(
					'REVIEW_CONTENT' => $row['review_content'],
					'REVIEW_' => $row['review_']
				));
				
				if (isset($reviews_rate[$row['review_id']]))
				{
					foreach ($reviews_rate[$row['review_id']] as $j => $rate)
					{
						if (!$j) _style('reviews.row.rate');
						
						_style('reviews.row.rate.field', array(
							'FIELD' => $rate['field_name'],
							'RATE' => $rate['rate_value'])
						);
					}
				}
				$i++;
			}
		}
		
		// Who attend
		$sql = 'SELECT at.type_id, at.type_name_next, at.type_name_prev, b.bio_alias, b.bio_name, b.bio_avatar, b.bio_avatar_up
			FROM _events_attend a, _events_attend_type at, _bio b
			WHERE a.attend_event = ?
				AND a.attend_type = at.type_id
				AND a.attend_uid = b.bio_id
			ORDER BY a.attend_time';
		$attend = _rowset(sql_filter($sql, $event['event_id']), 'type_id', false, true);
		
		$i = 0;
		foreach ($attend as $type_name => $rows)
		{
			if (!$i) _style('attend');
			
			$type_name = ($is_future) ? 'next' : 'prev';
			
			_style('attend.type', array(
				'TYPE_NAME' => $rows[0]['type_name_' . $type_name])
			);
			
			foreach ($rows as $row)
			{
				_style('attend.type.row', array(
					'BIO_NAME' => $row['bio_name'],
					'BIO_AVATAR' => _avatar($row))
				);
			}
			
			$i++;
		}
		
		// Messages
		$ref = _link('events', $event['event_alias']);
		
		if ($event['event_publish'])
		{
			if ($event['event_comments'])
			{
				$sql = 'SELECT c.comment_id, c.comment_time, c.comment_text, b.bio_id, b.bio_alias, b.bio_name, b.bio_avatar, b.bio_avatar_up
					FROM _events_comments c, _bio b
					WHERE c.comment_event = ?
						AND c.comment_active = ?
						AND c.comment_bio = b.bio_id
					ORDER BY c.comment_time DESC
					LIMIT ??, ??';
				$comments = _rowset(sql_filter($sql, $event['event_id'], 1, $v['p'], $core->v('events_comments')));
				
				foreach ($comments as $i => $row)
				{
					if (!$i) _style('comment_area', _pagination(_link($this->m(), array($event['event_alias'], $v['t'], 's%d')), ($topic_data['topic_replies'] + 1), $core->v('posts_per_page'), $start));
					
					_style('comment_area.row', array(
						'BIO_ALIAS' => _link_bio($row['bio_alias']),
						'BIO_NAME' => $row['bio_name'],
						'BIO_AVATAR' => _avatar($row),
						
						'COMMENT_ID' => $row['comment_id'],
						'COMMENT_TIME' => _format_date($row['comment_time']),
						'COMMENT_TEXT' => _message($row['comment_text']))
					);
				}
			}
			
			_style('comment_publish', array(
				'U_PUBLISH' => _link()
			));
		}
		
		//
		if ($event['event_posts'])
		{
			$reply = array(
				'ref' => $ref,
				'start' => $v['p'],
				'start_f' => 's',
				'rows' => $event['event_posts'],
				'rows_page' => $core->v('s_posts'),
				'block' => 'posts',
				
				'sql' => 'SELECT p.post_id, p.post_time, p.post_text, b.bio_id, b.bio_alias, b.bio_name, b.bio_avatar, b.bio_avatar_up, b.bio_sig
					FROM _events_posts p, _bio b
					WHERE p.post_event = ?
						AND p.post_active = 1 
						AND p.post_uid = b.bio_id
					ORDER BY p.post_time DESC
					LIMIT {START}, {ROWS_PAGE}'
			);
			
			$reply['sql'] = sql_filter($reply['sql'], $event['event_id']);
			$this->_replies($reply);
		}
		
		v_style(_vs(array(
			'SUBJECT' => $event['event_subject'],
			'IMAGES' => $event['event_images'],
			'START' => _format_date($event['event_start'], 'd F Y'),
			'END' => _format_date($event['event_end'], 'd F Y'),
			'COMMENTS' => $event['event_posts']), 'event')
		);
		
		return;
	}
	
	public function star()
	{
		$this->method();
	}
	
	protected function _star_home()
	{
		global $bio;
		
		if (!is_ghost()) _fatal();
		
		if (!$bio->v('auth_member')) _login();
		
		$v = $this->__(_array_keys(w('event type'), 0));
		if (!$v['event'] || !$v['type'])
		{
			_fatal();
		}
		
		$sql = 'SELECT type_id
			FROM _events_star_type
			WHERE type_id = ?';
		if (!_fieldrow(sql_filter($sql, $v['type'])))
		{
			_fatal();
		}
		
		$response = 'EVENT_STAR_NONE';
		
		$sql = 'SELECT star_id
			FROM _events_star
			WHERE star_event = ?
				AND star_uid = ?';
		if (!$star = _fieldrow(sql_filter($sql, $v['event'], $bio->v('bio_id'))))
		{
			$sql_insert = array(
				'star_type' => $v['type'],
				'star_event' => $v['event'],
				'star_uid' => $bio->v('bio_id')
			);
			$sql = 'INSERT INTO _events_star' . _build_array('INSERT', $sql_insert);
			_sql($sql);
			
			$response = 'EVENT_STAR_ADD';
		}
		
		$this->e($response);
		
		return;
	}
	
	public function attend()
	{
		$this->method();
	}
	
	protected function _attend_home()
	{
		global $bio;
		
		if (!is_ghost()) _fatal();
		
		if (!$bio->v('auth_member')) _login();
		
		$v = $this->__(_array_keys(w('event option'), 0));
		if (!$v['event'] || !$v['option'])
		{
			_fatal();
		}
		
		$sql = 'SELECT event_id
			FROM _events
			WHERE event_id = ?';
		if (!_fieldrow($sql, $v['event']))
		{
			_fatal();
		}
		
		$sql = 'SELECT type_id
			FROM _events_attend_type
			WHERE type_id = ?';
		if (!_fieldrow(sql_filter($sql, $v['option'])))
		{
			_fatal();
		}
		
		$sql = 'SELECT attend_id
			FROM _events_attend
			WHERE attend_event = ?
				AND attend_uid = ?';
		if ($attend_id = _field(sql_filter($sql, $v['event'], $bio->v('bio_id')), 'attend_id', 0))
		{
			$sql = 'UPDATE _events SET attend_option = ?
				WHERE attend_id = ?';
			_sql(sql_filter($sql, $v['option'], $attend_id));
		}
		else
		{
			$sql_insert = array(
				'attend_event' => $v['event'],
				'attend_uid' => $bio->v('bio_id'),
				'attend_option' => $v['option'],
				'attend_time' => time()
			);
			$sql = 'INSERT INTO _events_attend' . _build_array('INSERT', $sql_insert);
			_sql($sql);
		}
		
		return $this->e('~OK');
	}
	
	public function tag()
	{
		$this->method();
	}
	
	protected function _tag_home()
	{
		return;
	}
	
	public function comment()
	{
		$this->method();
	}
	
	protected function _comment_home()
	{
		return;
	}
	
	public function acp()
	{
		$this->method();
	}
	
	protected function _acp_home()
	{
		return;
	}
}

?>