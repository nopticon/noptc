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

-----
board /board/
	forums /board/$forum > cat:$1
		topics /board/topic/$id > x1:topic.t:$1
			posts /board/post/$post > x1:topic.is_post:1.p:$1
*/
if (!defined('XFS')) exit;

interface i_board
{
	public function home();
	public function publish();
	public function topic();
}

class __board extends xmd implements i_board
{
	public function __construct()
	{
		parent::__construct();
		
		$this->auth(false);
		$this->_m(_array_keys(w('publish topic')));
	}
	
	public function home()
	{
		global $core, $bio;
		
		$v = $this->__(array('f', 's' => 0));
		
		if (f($v['f']))
		{
			$sql = 'SELECT *
				FROM _board_forums
				WHERE forum_alias = ?';
			if (!$forum = _fieldrow(sql_filter($sql, $v['f'])))
			{
				_fatal();
			}
			
			// TODO: Thinking if it's really necessary this condition
			if (!$forum['forum_topics'])
			{
				$forum['forum_topics'] = 1;
			}
			
			/*
			$sql = 'SELECT t.topic_id, t.topic_title, t.forum_id, t.topic_replies, f.forum_alias, f.forum_name, p.post_id, p.post_username, p.post_time, b.bio_id, b.bio_alias, b.bio_name
				FROM _forums f, _forum_topics t, _forum_posts p, _bio b
				WHERE t.forum_id = f.forum_id
					AND t.topic_featured = 1
					AND p.post_deleted = 0
					AND p.post_id = t.topic_last_post_id
					AND p.poster_id = b.bio_id
				ORDER BY t.topic_important DESC, p.post_time DESC
				LIMIT ??';
			$latest = _rowset(sql_filter($sql, $core->v('main_topics')));
			*/
			
			//
			// Topic list
			$sql = 'SELECT t.*
				FROM _board_topics t, _board_posts p, _board_posts p2
				WHERE t.forum_id = ?
					AND p.post_id = t.topic_first_post_id
					AND p2.post_id = t.topic_last_post_id
				ORDER BY t.topic_important DESC, t.topic_last_post_id DESC
				LIMIT ??, ??';
			$topics = _rowset(sql_filter($sql, $forum['forum_id'], $v['s'], $core->v('topics_per_page')));
			
			//
			// Popular topics
			$sql = 'SELECT topic_id, topic_title, topic_views, topic_replies
				FROM _forum_topics
				WHERE forum_id = ?
				ORDER BY topic_replies DESC, topic_views DESC
				LIMIT ??';
			$popular = _rowset(sql_filter($sql, $forum['forum_id'], $core->v('topics_popular')));
			
			foreach ($topics as $i => $row)
			{
				if (!$i)
				{
					$pagination = _pagination(_link('board', $forum['forum_alias']), 's:%d', $forum['forum_topics'], $core->v('topics_per_page'), $v['s']);
					
					_style('topics', array_merge($pagination, array(
						'ALIAS' => $forum['forum_alias']))
					);
				}
				
				_style('topics.row', _vs(array(
					'ID' => $row['topic_id'],
					'TITLE' => $row['topic_title'],
					'REPLIES' => $row['topic_replies'],
					'URL' => _link('board', array('topic', $row['topic_id']))
				), 'TOPIC'));
			}
			
			foreach ($popular as $i => $row)
			{
				if (!$i) _style('popular');
				
				_style('popular.row', _vs(array(
					'TITLE' => $row['topic_title'],
					'REPLIES' => $row['topic_replies'],
					'URL' => _link('board', array('topic', $row['topic_id']))
				)), 'TOPIC');
			}
			
			if (!$forum['forum_locked'] && $this->auth_forum($forum, 'create'))
			{
				_style('create', array(
					'U_POST_ACTION' => _link($this->m(), $forum['forum_alias']))
				);
				
				// NOTE: Has to be premium
				if ($this->bio_premium() && $bio->v('auth_poll_create_' . $forum['forum_id']))
				{
					_style('post_poll');
				}
			}
			
			v_style(array(
				'FORUM_TITLE' => $forum['forum_name'],
				'FORUM_DESC' => $forum['forum_desc'],
				
				'IS_TOPICS' => $ghost_topics)
			);
			
			$this->monetize();
			
			if (is_ghost())
			{
				return;
			}
		}
		
		//
		// Forums
		$sql = 'SELECT f.*, t.topic_id, t.topic_title, p.post_id, p.post_time, b.bio_id, b.bio_alias, b.bio_name, b.bio_color
			FROM ((_board_forums f
			LEFT JOIN _board_topics t ON t.topic_id = f.forum_last_topic_id
			LEFT JOIN _board_posts p ON p.post_id = t.topic_last_post_id)
			LEFT JOIN _bio b ON b.bio_id = p.post_bio)
			ORDER BY f.cat_id, f.forum_order';
		$forums = _rowset($sql);
		
		foreach ($forums as $i => $row)
		{
			if (!$i) _style('forums');
			
			_style('forums.row',	array(
				'FORUM_NAME' => $row['forum_name'],
				'FORUM_DESC' => $row['forum_desc'],
				'POSTS' => $row['forum_posts'],
				'TOPICS' => $row['forum_topics'],
				
				'U_FORUM' => _link('board', $row['forum_alias']))
			);
		}
		
		v_style(array(
			'IS_TOPICS' => $ghost_topics)
		);
		
		return;
	}
	
	public function topic()
	{
		$this->method();
	}
	
	protected function _topic_home()
	{
		global $bio;
		
		$v = $this->__(_array_keys(w('t p s'), 0));
		
		if (!$v['t'] && !$v['p'])
		{
			_fatal();
		}
		
		$sql_from = $sql_where = $sql_count = $sql_order = '';
		
		if ($v['p'])
		{
			$sql_count = ', COUNT(p2.post_id) AS prev_posts, p.post_deleted';
			$sql_from = ', _board_posts p, _board_posts p2, _bio b ';
			$sql_where = sql_filter('p.post_id = ? AND p.poster_id = b.bio_id AND t.topic_id = p.topic_id AND p2.topic_id = p.topic_id AND p2.post_id <= ?', $v['p'], $v['p']);
			$sql_order = ' GROUP BY p.post_id, t.topic_id, t.topic_title, t.topic_locked, t.topic_replies, t.topic_time, t.topic_important, t.topic_vote, t.topic_last_post_id, f.forum_name, f.forum_locked, f.forum_id, f.auth_view, f.auth_read, f.auth_post, f.auth_reply, f.auth_announce, f.auth_pollcreate, f.auth_vote ORDER BY p.post_id ASC';
		}
		else
		{
			$sql_where = sql_filter('t.topic_id = ?', $v['t']);
		}
		
		$sql = 'SELECT t.*, f.*' . $sql_count . '
			FROM _board_topics t, _board_forums f' . $sql_from . '
			WHERE ' . $sql_where . ' AND f.forum_id = t.forum_id' . $sql_order;
		if (!$topic_data = _fieldrow($sql))
		{
			_fatal();
		}
		
		$v['f'] = $topic_data['forum_id'];
		$v['t'] = $topic_data['topic_id'];
		
		//
		if ($v['p'])
		{
			$v['s'] = floor(($topic_data['prev_posts'] - 1) / (int) $core->v('posts_per_page')) * (int) $core->v('posts_per_page');
		}
		
		//
		// Update the topic views
		/*
		if (!$v['offset'] && !$bio->v('auth_founder') && $bio->v('auth_member') && ($topic_data['topic_poster'] != $bio->v('bio_id')))
		{
			$sql = 'UPDATE _forum_topics SET topic_views = topic_views + 1
				WHERE topic_id = ?';
			_sql(sql_filter($sql, $v['t']));
		}
		*/
		
		//
		// Get topic data
		$sql = 'SELECT p.*, b.bio_id, b.bio_alias, b.bio_name, b.bio_color, b.bio_avatar, b.bio_avatar_up, b.bio_sig
			FROM _board_posts p, _bio b
			WHERE p.post_topic = ?
				AND p.post_bio = b.bio_id
			ORDER BY p.post_time ASC
			LIMIT ??, ??';
		if (!$posts = _rowset(sql_filter($sql, $v['t'], $v['offset'], $core->v('posts_per_page'))))
		{
			_fatal();
		}
		
		$allow_posts = ($topic_data['forum_locked']);
		
		if ($allow_posts)
		{
			_style('publish');
		}
		
		foreach ($posts as $i => $row)
		{
			if (!$i) _style('posts', _pagination(_link('board', array('topic', $v['t'], 's%d')), ($topic_data['topic_replies'] + 1), $core->v('posts_per_page'), $start));
			
			$_row = array(
				'ID' => $row['post_id'],
				'BIO' => $row['post_bio'],
				'TIME' => _format_date($row['post_time']),
				'CONTENT' => _message($row['post_content']),
				'PLAYING' => $row['post_playing']
			);
			_style('posts.row', array_merge($_row, $this->_profile($row)));
			
			if ($allow_posts)
			{
				_style('posts.row.publish');
			}
		}
		
		$this->monetize();
		
		// TODO: Include social networks buttons
		
		$this->set_nav($v['f'], $topic_data['forum_name'], 'forum');
		$this->set_nav($v['t'], $topic_data['topic_title'], 'topic');
		
		//
		$_v = ($v['p']) ? 'p' : 'f';
		$_w = ($v['p']) ? 'p' : 't';
		
		v_style(array(
			'U_PUBLISH' => _link('board publish'),
			'H_PUBLISH' => _hidden(array($_v => $v[$_w])))
		);
		
		return;
	}
	
	//
	// Post a reply on this topic
	// --
	// System should be capable of save replies of specific posts and display a tree of it.
	// Also notify the poster of current post about new replies.
	//
	// 	*Posting* If guest then allow posting a message but first ask for their current account or show registration form.
	// Save draft message until user has confirmed the account. Then make public the messages posted as guest.
	//
	// System can register replies directed to main topic and separately to conversations inside top replies.
	//
	// First post on topic should not able to accept conversations, only replies
	// (First post is a big conversation! :P)
	//
	
	public function publish()
	{
		$this->method();
	}
	
	protected function _publish_home()
	{
		global $bio;
		
		$v = $this->__(array_merge(w('address key subject content playing'), _array_keys(w('f p'), 0)));
		
		// TODO: Implement bio authorization
		$this->_bio_publish($v['address'], $v['key']);
		
		//
		if (!$v['forum'] && !$v['post'])
		{
			_fatal();
		}
		
		if ($v['forum'])
		{
			if (!f($v['subject']))
			{
				$this->_error('NO_TOPIC_SUBJECT');
			}
			
			$sql = 'SELECT *
				FROM _board_forums
				WHERE forum_id = ?';
			if (!$forum = _fieldrow(sql_filter($sql, $v['forum'])))
			{
				_fatal();
			}
			
			$v['subject'] = _subject($v['subject']);
		}
		else
		{
			$sql = 'SELECT *
				FROM _board_posts
				WHERE post_id = ?';
			if (!$post = _fieldrow(sql_filter($sql, $v['post'])))
			{
				_fatal();
			}
			
			$sql = 'SELECT *
				FROM _board_topics
				WHERE topic_id = ?';
			if (!$topic = _fieldrow(sql_filter($sql, $post['post_topic'])))
			{
				_fatal();
			}
		}
		
		if ($v['forum'])
		{
			if ($forum['forum_locked'] && !$this->auth_forum($forum, 'create'))
			{
				_fatal();
			}
		}
		
		if (!f($v['content']))
		{
			$this->_error('NO_TOPIC_CONTENT');
		}
		
		$v['content'] = _prepare($v['content']);
		
		// Start insert transaction
		_sql_trans();
		
		$sql_commit = false;
		if ($v['forum'])
		{
			// Insert topic
			$sql_insert = array(
				'forum' => $v['forum'],
				'subject' => $v['subject'],
				'author' => $bio->v('bio_id'),
				'time' => time(),
				'active' => $bio->v('bio_confirmed')
			);
			$sql = 'INSERT INTO _board_topics' . _build_array('INSERT', prefix('topic', $sql_insert));
			$v['topic_next'] = _sql_nextid($sql);
			
			// Insert post
			$sql_insert = array(
				'forum' => $v['forum'],
				'topic' => $v['topic_next'],
				'parent' => 0,
				'bio' => $bio->v('bio_id'),
				'time' => time(),
				'active' => $bio->v('bio_confirmed'),
				'message' => $v['content'],
				'playing' => $v['playing']
			);
			$sql = 'INSERT INTO _board_posts' . _build_array('INSERT', prefix('post', $sql_insert));
			$v['post_next'] = _sql_nextid($sql);
			
			if ($v['topic_next'] && $v['post_next'])
			{
				$sql_commit = true;
			}
		}
		else
		{
			$sql_insert = array(
				'forum' => $topic['topic_forum'],
				'topic' => $topic['topic_id'],
				'parent' => $v['post'],
				'bio' => $bio->v('bio_id'),
				'time' => time(),
				'active' => $bio->v('bio_confirmed'),
				'message' => $v['content'],
				'playing' => $v['playing']
			);
			$sql = 'INSERT INTO _board_posts' . _build_array('INSERT', prefix('post', $sql_insert));
			$v['post_next'] = _sql_nextid($sql);
			
			$sql_update = array();
			$sql = 'UPDATE _board_topics SET topic_replies = topic_replies + 1' . _build_array('UPDATE', $sql_update) . sql_filter('
				WHERE topic_id = ?', $topic['topic_id']);
			$updated = _sql_affected($sql);
			
			if ($v['post_next'] && $updated)
			{
				$sql_commit = true;
			}
		}
		
		if (!$sql_commit)
		{
			_sql_trans('rollback');
			$this->_error('ROLLBACK_MESSAGE');
		}
		
		_sql_trans('commit');
		
		if (is_ghost() && $v['post'])
		{
			if ($bio->v('bio_confirmed'))
			{
				$response = array(
					'show' => 1,
					'parent' => $v['post'],
					'post' => $v['post_next'],
					'content' => _message($v['content']),
					'time' => _format_date(),
					
					'profile' => array(
						'link' => _link_bio($bio->v('bio_alias')),
						'name' => $bio->v('bio_name')
					)
				);
			}
			else
			{
				$response = array(
					'show' => 0,
					'legend' => _lang('PUBLISH_TOPIC_GUEST')
				);
			}
			$this->e(json_encode($response));
		}
		
		return redirect(_link('board', array('topic', $v['topic'])));
	}
}

?>