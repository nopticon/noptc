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

interface i_bio
{
	public function home();
	
	public function journal();
	public function record();
	public function auth();
	public function gallery();
	public function biography();
	public function lyrics();
	public function media();
	public function analytics();
	
	public function posts();
}

/*
// PROTOTYPE > $a.domain/$m/$p || domain/$a/$m/$p

> BIO TIMELINE

TABS
	home

MODULES
	biography
	discography
	gallery
	lyrics
	interviews
	messages
	videos
	music
	private
	news

DEFAULT
	fans
	stats
	auth
	log
	
*/

class __bio extends xmd implements i_bio
{
	public function __construct()
	{
		parent::__construct();
		
		$this->auth(false);
		
		$this->_m(array(
			'analytics' => w(),
			'biography' => w('modify'),
			'fans' => w('add remove'),
			'interviews' => w('create modify remove'),
			'journal' => w('create modify remove'),
			'messages' => w('modify remove'),
			'options' => w('modify'),
			'permission' => w('create remove'),
			'record' => w('view remove'),
			
			'gallery' => array(
				'set' => w('create modify remove'),
				'photo' => w('create modify remove')
			),
			'music' => array(
				'cd' => w('create modify remove'),
				'song' => w('create modify remove'),
				'lyrics' => w('create modify remove')
			)
		));
		
		//
		// This bio!
		//
		
		$v = $this->__(w('domain alias tab:home'));
		
		if (!f($v['domain']) && !f($v['alias']))
		{
			_fatal();
		}
		
		if (f($v['domain']))
		{
			$v['domain'] = $this->strip_domain($v['domain']);
		}
		elseif (f($v['alias']))
		{
			$v['alias'] = _low($v['alias'], true);
			
			if ($v['alias'] === false)
			{
				_fatal();
			}
		}
		
		//
		$sql = 'SELECT *
			FROM _bio b, _bio_type t
			WHERE (b.bio_alias = ?
				OR b.bio_domain = ?)
				AND b.bio_active = ?
				AND b.bio_type = t.type_id
			LIMIT 1';
		if (!$_bio = _fieldrow(sql_filter($sql, $v['alias'], $v['domain'], 1)))
		{
			_fatal();
		}
		
		if ($v['tab'] != 'home')
		{
			$sql = 'SELECT relation_id
				FROM _bio_relation r, _bio_modules m, _bio_publish p
				WHERE p.publish_local = ?
					AND r.relation_alias = ?
					AND r.relation_id = m.module_relation
					AND p.publish_module = m.module_id';
			if (!_field(sql_filter($sql, $_bio['bio_id'], $v['tab']), 'relation_id', 0))
			{
				_fatal();
			}
		}
		
		switch ($_bio['type_alias'])
		{
			case 'artist':
			case 'page':
				if ($bio->v('auth_member'))
				{
					$sql = 'SELECT auth_id
						FROM _bio_auth
						WHERE auth_local = ?
							AND auth_remote = ?
						LIMIT 1';
					if (_field(sql_filter($sql, $_bio['bio_id'], $bio->v('bio_id'))))
					{
						$bio->v('auth_page', true);
					}
				}
				break;
			default:
				break;
		}
		
		//
		// Access Control
		switch ($_bio['bio_access'])
		{
			case BIO_ACCESS_FOLLOWER:
				if (!$bio->v('auth_member'))
				{
					_login();
				}
				
				if (!$this->bio_follower($_bio['bio_id']) && !$bio->v('auth_page'))
				{
					_fatal();
				}
				break;
			case BIO_ACCESS_ALL:
				break;
			default:
				break;
		}
		
		return;
	}
	
	public function home()
	{
		global $bio, $core;
		
		
		
		/*
		$sql = 'SELECT *
			FROM _bio_auth
			WHERE auth_assoc = ?
				AND auth_bio = ?';
		if (!_fieldrow(sql_filter($sql, $_bio['bio_id'], $bio->v('bio_id'))))
		{
			// TODO: Admin notification if not authed
			_fatal();
		}
		*/
		
		
		
		//
		//
		$sql = 'SELECT bio_id, bio_alias, bio_name, bio_avatar, bio_avatar_up
			FROM _bio b, _followers f
			WHERE f.follower_local = ?
				AND f.folloer_active = ?
				AND f.follower_remote = b.bio_id
			ORDER BY f.follower_top
			LIMIT ??';
		$followers = _rowset(sql_filter($sql, $_bio['bio_id'], 1, $_bio['bio_top_follow']));
		
		foreach ($followers as $i => $row)
		{
			if (!$i) _style('followers');
			
			_style('followers.row', array(
				
			));
		}
		
		/*
		SELECT *
		FROM _bio_relation r, _bio_modules m, _bio_publish p
		WHERE r.relation_alias = ?
			AND r.relation_id = m.module_relation
			AND p.publish_module = m.module_id
		*/
		
		switch ($v['tab'])
		{
			case 'home':
				$sql = 'SELECT *
					FROM _bio_timeline
					WHERE timeline_bio = ?
					ORDER BY timeline_time DESC
					LIMIT ??, ??';
				$posts = _rowset();
				break;
			default:
				$sql = 'SELECT *
					FROM _bio_timeline
					WHERE timeline_bio = ?
						AND timeline_module = ?
					ORDER BY timeline_time DESC
					LIMIT ??, ??';
				$posts = _rowset();
				break;
		}
		
		$sql = 'SELECT relation_id
			FROM _bio_relation r, _bio_modules m, _bio_publish p
			WHERE p.publish_local = ?
				AND r.relation_alias = ?
				AND r.relation_id = m.module_relation
				AND p.publish_module = m.module_id
			ORDER BY p.publish_time DESC';
		$publish = _rowset(sql_filter($sql, $_bio['bio_id'], $v['tab']));
		
		foreach ($posts as $i => $row)
		{
			if (!$i) _style('timeline');
			
			$module = 'module_' . $row['module_alias'];
			if (@method_exists($this, $module))
			{
				$this->{$module}($_bio, $row);
			}
			
			//
			$style->set_filenames(array('module' => 'modules/' . $row['module_alias'] . '.htm'));
			$style->assign_var_from_handle('S_MODULE', 'module');
			
			_style('modules.row', _style_var('S_MODULE'));
		}
		
		return;
	}
	
	private function module_video($_bio, $d)
	{
		// parse
		
		$d['video_added'] = _format_date($d['video_added']);
		return;
	}
	
	private function module_notes($a)
	{
		// parse
		
		_style('notes', _vs(array(
			'subject' => $note['note_subject'],
			'message' => _message($note['note_content'])
		), 'note'));
		
		return;
	}
	
	private function module_tweet($a)
	{
		// parse
		
		return;
	}
	
	private function module_links($a)
	{
		// parse dynamic
		
		_style('links_' . $a['rel_id'], _vs(array(
			'name' => $row['link_name'],
			'address' => $row['link_address']
		), 'link'));
		
		return;
	}
	
	private function module_picture($a)
	{
		// parse
		// get gallery id from image
		
		/*
		SELECT *
		FROM _bio_images i, _bio_images_group g
		WHERE i.image_id = ?
			AND i.image_group = g.group_id
		
		
		SELECT *
		FROM _artists_images
		WHERE image_a = ?
			AND image_group = ?
		ORDER BY image_order
		*/
		
		$sql = 'SELECT *
			FROM _module_pictures
			WHERE picture_module = ?
				AND picture_bio = ?
			ORDER BY picture_order';
		$pictures = _rowset(sql_filter($sql, $a['rel_id'], $a['rel_bio']));
		
		foreach ($pictures as $row)
		{
			
		}
		
		return;
	}
	
	//
	// ----------------------------------------------------------------------------------------------------------------
	// ----------------------------------------------------------------------------------------------------------------
	//
	
	public function journal()
	{
		$this->method();
	}
	
	protected function _journal_create()
	{
		if (!_button())
		{
			redirect(_link_bio($this->a('bio_alias')));
		}
		
		global $bio, $core;
		
		$v = $this->__(array('subject', 'content', 'locked' => 0, 'draft' => 0));
		
		foreach ($v as $k => $j)
		{
			if (!f($j)) $this->_error('#NO_' , $k);
		}
		
		$sql_insert = array(
			'bio' => (int) $this->a('bio_id'),
			'subject' => _subject($v['subject']),
			'content' => _prepare($v['content']),
			'poster' => (int) $bio->v('bio_id'),
			'time' => time(),
			'modified' => time(),
			'locked' => (int) $v['locked'],
			'draft' => (int) $v['draft'],
			'ip' => $bio->v('ip')
		);
		$sql = 'INSERT INTO _bio_journal' . _build_array('INSERT', prefix('blog', $sql_insert));
		$blog_id = _sql_nextid($sql);
		
		$bio->notify_store('blog', $blog_id);
		
		return redirect(_link('alias', $this->a('a_alias')));
	}
	
	protected function _journal_modify()
	{
		global $bio;
		
		$v = $this->__(array('id' => 0, 'subject', 'content', 'locked' => 0, 'draft' => 0));
		
		if (!$v['id']) _fatal();
		
		$sql = 'SELECT *
			FROM _bio_journal
			WHERE blog_id = ?
				AND blog_bio = ?';
		if (!$blog = _fieldrow(sql_filter($sql, $v['id'], $this->a('bio_id'))))
		{
			_fatal();
		}
		
		$subject = $blog['blog_subject'];
		$content = $blog['blog_content'];
		
		if (_button())
		{
			$sql_update = w();
			foreach ($v as $k => $vv)
			{
				if (!f($vv)) $this->_error('#NO_' . $k);
				
				if ($vv != $$k) $sql_update[$k] = _prepare($vv);
			}
			
			$sql_update = array_merge($sql_update, array(
				'blog_time' => time(),
				'blog_poster' => $bio->v('bio_id'),
				'blog_ip' => $bio->v('ip'))
			);
			
			$sql = 'UPDATE _bio_journal SET ' . _build_array('UPDATE', $sql_update) . sql_filter('
				WHERE blog_id = ?', $v['id']);
			_sql($sql);
			
			$bio->notify_store('blog', $v['id']);
			
			redirect(_link('alias', array('alias' => $this->a('bio_alias'))));
		}
		
		$this->navigation(array('alias' => $this->a['a_subdomain'], 'x1' => 'cp', 'x2' => $this->mode, 'x3' => $this->manage, 'id' => $topic_data['topic_id']), 'A_NEWS_EDIT');
		
		$response = array(
			'action' => _link_bio('', array('x1' => $this->x(1), 'x2' => $this->x(2), 'x3' => $this->x(3), 'id' => $blog['blog_id'])),
			'subject' => $subject,
			'content' => $content
		);
		return $this->e(json_encode($response));
	}
	
	protected function _journal_remove()
	{
		gfatal();
		
		$v = $this->__(array('id' => 0));
		
		if (!$v['id'])
		{
			_fatal();
		}
		
		$sql = 'SELECT blog_id
			FROM _bio_journal
			WHERE blog_id = ?';
		if (!_field(sql_filter($sql, $v['id']), 'blog_id', 0))
		{
			_fatal();
		}
		
		$sql = 'DELETE FROM _bio_journal
			WHERE blog_id = ?';
		_sql(sql_filter($sql, $v['id']));
		
		$bio->notify_remove('blog', $v['id']);
		
		redirect(_link('a', $this->a['a_alias']));
	}
	
	public function posts()
	{
		$this->method();
	}
	
	protected function _posts_modify()
	{
		gfatal();
		
		global $bio;
		
		if (!$bio->v('auth_bio_post_modify'))
		{
			fatal();
		}
		
		$v = $this->__(array('id' => 0, 'content'));
		
		foreach ($v as $k => $vv)
		{
			if (!f($vv)) $this->_error('#NO_' . $k);
		}
		
		$sql = 'SELECT *
			FROM _bio_posts
			WHERE post_id = ?
				AND post_bio = ?';
		if (!$post = _fieldrow(sql_filter($sql, $v['id'], $this->a('bio_id'))))
		{
			_fatal();
		}
		
		$sql_update = array(
			'post_content' => _prepare($v['content'])
		);
		$sql = 'UPDATE _bio_posts SET ' . _build_array('UPDATE', $sql_update) . sql_filter('
			WHERE post_id = ?', $v['id']);
		_sql($sql);
		
		return redirect(_link('alias', array('alias' => $this->a('a_alias'), 'messages', $post_data['post_id'])));
	}
	
	public function _posts_remove()
	{
		gfatal();
		
		global $bio;
		
		if (!$bio->v('auth_bio_post_remove'))
		{
			_fatal();
		}
		
		$v = $this->__(array('id' => 0));
		if (!$v['id'])
		{
			_fatal();
		}
		
		$sql = 'SELECT post_id
			FROM _bio_posts
			WHERE post_id = ?';
		if (!_field(sql_filter($sql, $v['id']), 'post_id', 0))
		{
			_fatal();
		}
		
		$sql = 'DELETE FROM _bio_posts
			WHERE post_id = ?';
		_sql(sql_filter($sql, $v['id']));
		
		$bio->notify_remove('posts', $v['id']);
		
		return redirect(_link('alias', array('alias' => $this->a('a_alias'))));
	}
	
	public function record()
	{
		$this->method();
	}
	
	protected function _record_home()
	{
		global $bio;
		
		$v = $this->__(array('alias' => 0));
		
		if (f($v['m']))
		{
			$sql = 'SELECT bio_id, bio_alias, bio_name
				FROM _bio
				WHERE bio_alias = ?';
			if (!$_bio = _fieldrow(sql_filter($sql, $v['alias'])))
			{
				$this->_error('#NO_SUCH_BIO');
			}
			
			v_style(array(
				'BIO_NAME' => $_bio['bio_name'],
				'BIO_LINK' => _link_bio($_bio['bio_alias']))
			);
			
			//
			// Get record for selected bio
			$sql = 'SELECT *
				FROM _bio_record
				WHERE record_assoc = ?
					AND record_bio = ?
				ORDER BY record_time DESC';
			$records = _rowset($sql);
			
			$index = w();
			foreach ($records as $row)
			{
				$index[$row['record_module']][] = $row['record_assoc'];
			}
			
			foreach ($records as $i => $row)
			{
				$indexes = _implode(',', $index['record_module']);
				
				switch ($row['record_module'])
				{
					case 'posts':
						$sql = 'SELECT *
							FROM _artists_posts
							WHERE post_id IN (??)';
						$sql = sql_filter($sql, $indexes);
						break;
					case 'lyrics':
						$sql = 'SELECT *
							FROM _artists_lyrics
							WHERE lyric_id IN (??)';
						$sql = sql_filter($sql, $indexes);
						break;
					case 'bio':
						break;
					case 'website':
						break;
				}
				
				if (f($sql))
				{
					$records[$i]['sources'] = _rowset($sql);
				}
			}
		}
		else
		{
			$sql = 'SELECT COUNT(r.record_id) AS total, b.bio_id, b.bio_alias, b.bio_name, b.bio_avatar, b.bio_avatar_up
				FROM _bio b, _bio_record r
				WHERE r.record_assoc = ?
					AND r.record_bio = b.bio_id
				GROUP BY b.bio_id
				ORDER BY b.bio_name';
			$records = _rowset(sql_filter($sql, $this->data['ub']));
			
			$no_results = false;
			$tcol = 0;
			
			foreach ($records as $i => $row)
			{
				if (!$i) _style('members');
				
				$profile = $this->_profile($row);
				
				if (!$tcol) _style('members.row');
				
				_style('members.row.col', array(
					'USER_ID' => $row['bio_id'],
					'USERNAME' => $row['bio_name'],
					'AVATAR' => _avatar($row),
					'U_VIEWLOG' => _link_control('a', array('a' => $this->data['subdomain'], 'mode' => $this->mode, 'manage' => $this->manage, 'm' => $row['bio_id'])),
					'TOTAL' => $row['total'],
					'ACTION' => _lang('CONTROL_A_LOG_ACTION' . (($row['total'] == 1) ? '' : 'S')))
				);
				
				$tcol = ($tcol == 3) ? 0 : $tcol + 1;
			}
		}
		
		v_style(array(
			'MEMBER' => ($member) ? $memberdata['username'] : '')
		);
	}
	
	//
	// Auth
	//
	public function permission()
	{
		$this->method();
	}
	
	protected function _permission_home()
	{
		global $bio;
		
		$sql = 'SELECT b.bio_alias, b.bio_name, b.bio_firstname, b.bio_lastname, b.bio_avatar, b.bio_avatar_up
			FROM _bio_auth a, _bio b
			WHERE a.auth_assoc = ?
				AND a.auth_bio = b.bio_id
			ORDER BY b.bio_name';
		if (!$auth = _rowset(sql_filter($sql, $this->a('bio_id'))))
		{
			_style('no_auth');
		}
		
		foreach ($auth as $i => $row)
		{
			if (!$i) _style('auth');
			
			_style('auth.row', array(
				'V_PROFILE' => _link_bio($row['bio_alias']),
				'V_USERNAME' => $row['bio_name'],
				'V_AVATAR' => _avatar($row),
				'U_REMOVE' => _link('alias', array('alias' => $this->a('bio_alias'), 'x1' => $this->x(1), 'x2' => 'remove')))
			);
		}
		
		v_style(array(
			'U_AUTH_CREATE' => _link('alias', array('alias' => $this->a['a_alias'], 'mode' => $this->mode, 'manage' => 'create')))
		);
		
		return;
	}
	
	protected function _permission_create()
	{
		gfatal();
		
		global $bio;
		
		$v = $this->__(array('alias'));
		
		$sql = 'SELECT bio_id
			FROM _bio
			WHERE bio_alias = ?
				AND bio_level NOT IN (??)
				AND bio_id NOT IN (
					SELECT ban_bio
					FROM _bio_ban
				)';
		if (!$_bio = _fieldrow(sql_filter($sql, $v['alias'], _implode(',', W(U_INACTIVE . ' ' . U_FOUNDER)))))
		{
			$this->_error('#NO_BIO');
		}
		
		$sql = 'SELECT auth_bio
			FROM _bio_auth
			WHERE auth_assoc = ?
				AND auth_bio = ?';
		if (_field(sql_filter($sql, $this->a('bio_id'), $_bio['bio_id']), 'auth_bio', 0))
		{
			return $this->e('~OK');
		}
		
		$sql_insert = array(
			'auth_assoc' => $this->a('bio_id'),
			'auth_bio' => $_bio['bio_id'],
			'auth_time' => time()
		);
		$sql = 'INSERT INTO _bio_auth' . _build_array('INSERT', $sql_insert);
		_sql($sql);
		
		redirect(_link('alias', array('alias' => $this->a('bio_alias'), 'x1' => $this->x(1), 'x2' => $this->x(2))));
	}
	
	protected function _permission_remove()
	{
		if (!is_ghost())
		{
			_fatal();
		}
		
		$v = $this->__(array('bio' => 0));
		
		$sql = 'SELECT bio_id
			FROM _bio
			WHERE bio_id = ?';
		if (!_field(sql_filter($sql, $v['bio']), 'bio_id', 0))
		{
			_fatal();
		}
		
		$sql = 'SELECT auth_bio
			FROM _bio_auth
			WHERE auth_assoc = ?
				AND auth_bio = ?';
		if (!_field(sql_filter($sql, $v['bio']), 'auth_bio', 0))
		{
			_fatal();
		}
		
		$sql = 'DELETE FROM _bio_auth
			WHERE auth_assoc = ?
				AND auth_bio = ?';
		_sql(sql_filter($sql, $this->a('bio_id'), $v['bio']));
		
		redirect(_link('alias', array('alias' => $this->a['a_alias'], 'x1' => $this->x(1), 'x2' => $this->x(2))));
	}
	
	//
	// Gallery
	//
	public function gallery()
	{
		$this->method();
	}
	
	protected function _gallery_home()
	{
		global $bio;
		
		// bio / g / [a-z0-9]{1} / [a-z0-9 ]{2} / alias / gid / size / rand.ext
		
		$sql = 'SELECT *
			FROM _bio b, _bio_images i
			WHERE b.bio_id = ?
				AND b.bio_id = i.image_bio
			ORDER BY i.image_id';
		if (!$images = _rowset(sql_filter($sql, $this->a('bio_id'))))
		{
			_style('empty', array(
				'MESSAGE' => _lang('CONTROL_A_GALLERY_EMPTY'))
			);
		}
		
		$col = 0;
		foreach ($images as $i => $row)
		{
			if (!$i) _style('gallery');
			
			if (!$col) _style('gallery.row');
			
			_style('gallery.row.col', array(
				'ITEM' => $row['image'],
				'URL' => _link('a', array($this->data['subdomain'], 4, $row['image'], 'view')),
				'IMAGE' => dd(false, true, true) . 'artists/' . $this->data['ub'] . '/thumbnails/' . $row['image'] . '.jpg',
				'WIDTH' => $row['width'], 
				'HEIGHT' => $row['height'],
				'VIEWS' => $row['views'],
				'DOWNLOADS' => $row['downloads'])
			);
			
			$col = ($col == 3) ? 0 : $col + 1;
		}
			
		return v_style(array(
			'U_CREATE' => _link_bio($this->a('bio_alias'), array('x1' => $this->x(1), 'create')),
			'U_REMOVE' => _link_bio($this->a('bio_alias'), array('x1' => $this->x(1), 'remove')))
		);
	}
	
	public function _gallery_create()
	{
		global $bio;
		
		if (_button())
		{
			require_once(XFS . 'core/upload.php');
			$upload = new upload();
			
			$sql = 'SELECT MAX(image_id) AS total
				FROM _bio_images
				WHERE image_bio = ?';
			$image = _field(sql_filter($sql, $this->a('bio_id')), 'total', 0) + 1;
			
			$upload->chmod(array(
				_lib(LIB_BIO . ' ' . $this->a('bio_id'))
			), 0777);
			
			$f = $upload->process(LIB . 'tmp/', $_FILES['picture'], w('jpg'), max_upload_size());
			
			if ($f === false && count($upload->error))
			{
				$this->error($upload->error);
			}
			
			if (!$this->errors())
			{
				$total = 0;
				foreach ($f as $row)
				{
					//$row = $upload->_row($gallery, $image);
					
					$f2 = $upload->resize($row, LIB . 'tmp', LIB . 'events/future/', $v['e_id'], array(600, 400), false, false, true);
					if ($f2 === false) continue;
					
					$f3 = $upload->resize($row, LIB . 'events/future/', LIB . 'events/preview/', $v['e_id'], array(210, 210), false, false);
					$total++;
					
					//
					$sql_insert = array(
						'bio' => (int) $this->a('bio_id'),
						'image' => (int) $image,
						'width' => $i_data['width'],
						'height' => $i_data['height'],
						'views' => 0,
						'downloads' => 0,
						'allow_dl' => (int) $allow_dl
					);
					$sql = 'INSERT INTO _bio_images' . _build_array('INSERT', prefix('image', $sql_insert));
					_sql($sql);
					
					$image++;
				}
				
				$sql = 'UPDATE _bio SET images = images + ??
					WHERE ub = ?';
				_sql(sql_filter($sql, $total, $this->a('bio_id')));
			}
			
			if (!sizeof($error))
			{
				redirect(_link_control('a', array('a' => $this->data['subdomain'], 'mode' => $this->mode)));
			}
		}
		
		return v_style(array(
			'U_CREATE' => _link_bio($this->a('bio_alias'), array('x1' => $this->x(1), 'x2' => $this->x(2))),
			'MAX_FILESIZE' => max_filesize())
		);
	}
	
	protected function _gallery_modify()
	{
		
	}
	
	protected function _gallery_remove()
	{
		global $bio;
		
		if (_button())
		{
			$v = $this->__(array('picture' => array(0)));
			
			if (!count($v['picture']))
			{
				$this->_error('#NO_IMAGES');
			}
			
			$sql = 'SELECT *
				FROM _bio_images
				WHERE image_bio = ?
					AND image_assoc IN (??)
				ORDER BY image_id';
			if (!$images = _rowset(sql_filter($sql, $this->a('bio_id'), _implode(',', $v['picture']))))
			{
				$this->_error('#NO_IMAGES');
			}
			
			$filepath = array(
				'original' => _lib(),
				'thumbnail' => _lib()
			);
			
			foreach ($images as $row)
			{
				foreach ($filepath as $path)
				{
					
				}
			}
		}
		
		if ($submit)
		{
			$v = $this->__(array('s_images' => array(0)));
			$s_images = $v['s_images'];
			
			if (sizeof($s_images))
			{
				if ($row = $db->sql_fetchrow($result))
				{
					$delete_images = w();
					do
					{
						$gfile = array($gallery_path . $row['image'] . '.jpg', $thumbs_path . $row['image'] . '.jpg');
						foreach ($gfile as $image)
						{
							if (@is_file($image) && @is_readable($image))
							{
								@chmod($image, 0777);
								
								if (@unlink($image))
								{
									if (!@file_exists($image))
									{
										if (!isset($delete_images[$row['image']]))
										{
											$delete_images[$row['image']] = true;
										}
									}
								}
							}
						}
					}
					while ($row = $db->sql_fetchrow($result));
					
					if (sizeof($delete_images))
					{
						$sql = 'DELETE FROM _artists_images 
							WHERE ub = ' . (int) $this->data['ub'] . '
								AND image IN (' . implode(',', array_keys($delete_images)) . ')';
						$db->sql_query($sql);
						
						$sql = 'UPDATE _artists
							SET image s = images - ' . $db->sql_affectedrows() . '
							WHERE ub = ' . (int) $this->data['ub'];
						$db->sql_query($sql);
					}
				}
				$db->sql_freeresult($result);
			}
		}
		
		if (!$error)
		{
			redirect(_link_control('a', array('a' => $this->data['subdomain'], 'mode' => $this->mode)));
		}
	}
	
	//
	// Biography
	//
	public function biography()
	{
		$this->method();
	}
	
	protected function _biography_home()
	{
		global $bio;
		
		$v = $this->__(w('s'));
		
		$sql = 'SELECT a_bio
			FROM _artists
			WHERE ub = ?';
		$a_bio = _field(sql_filter($sql, $this->data['ub']), 'a_bio', '');
		
		$s_hidden = array('module' => $this->control->module, 'a' => $this->data['subdomain'], 'mode' => $this->mode, 'manage' => 'edit');
		
		v_style(array(
			'MESSAGE' => $a_bio,
			'S_HIDDEN' => _hidden($s_hidden))
		);
		
		if ($v['s'] == 'u')
		{
			_style('updated');
		}
	}
	
	protected function _biography_modify()
	{
		if (_button())
		{
			$v = $this->__(w('message'));
			
			$sql = 'UPDATE _artists SET a_bio = ?
				WHERE a_id = ?';
			_sql(sql_filter($sql, _prepare($v['message']), $this->data['ub']));
		}
		
		redirect(_link_control('a', array('a' => $this->data['subdomain'], 'mode' => $this->mode, 's' => 'u')));
	}
	
	//
	// Lyrics
	//
	public function lyrics()
	{
		$this->method();
	}
	
	protected function _lyrics_create()
	{
		
	}
	
	protected function _lyrics_modify()
	{
		
	}
	
	protected function _lyrics_remove()
	{
		
	}
	
	//
	// Stats
	//
	public function analytics()
	{
		$this->method();
	}
	
	protected function _analytics_home()
	{
		global $bio;
		
		$sql = 'SELECT *, SUM(members + guests) AS total
			FROM _artists_stats
			WHERE ub = ' . (int) $this->data['ub'] . '
			GROUP BY date
			ORDER BY date DESC';
		$result = $db->sql_query($sql);
		
		$stats = w();
		while ($row = $db->sql_fetchrow($result))
		{
			$stats[$row['date']] = $row;
		}
		$db->sql_freeresult($result);
		
		$years_sum = w();
		$years_temp = w();
		$years = w();
		foreach ($stats as $date => $void)
		{
			$year = substr($date, 0, 4);
			
			if (!isset($years_temp[$year]))
			{
				$years[] = $year;
				$years_temp[$year] = true;
			}
			
			if (!isset($years_sum[$year]))
			{
				$years_sum[$year] = 0;
			}
			
			$years_sum[$year] += $void['total'];
		}
		unset($years_temp);
		
		if (sizeof($years))
		{
			rsort($years);
		}
		else
		{
			$years[] = date('Y');
		}
		
		$total_graph = 0;
		foreach ($years as $year)
		{
			_style('year', array(
				'YEAR' => $year)
			);
			
			if (!isset($years_sum[$year]))
			{
				$years_sum[$year] = 0;
			}
			
			for ($i = 1; $i < 13; $i++)
			{
				$month = (($i < 10) ? '0' : '') . $i;
				$monthdata = (isset($stats[$year . $month])) ? $stats[$year . $month] : w();
				$monthdata['total'] = isset($monthdata['total']) ? $monthdata['total'] : 0;
				$monthdata['percent'] = ($years_sum[$year] > 0) ? $monthdata['total'] / $years_sum[$year] : 0;
				$monthdata['members'] = isset($monthdata['members']) ? $monthdata['members'] : 0;
				$monthdata['guests'] = isset($monthdata['guests']) ? $monthdata['guests'] : 0;
				$monthdata['unix'] = _timestamp($i, 1, $year, 0, 0, 0);
				$total_graph += $monthdata['total'];
				
				_style('year.month', array(
					'NAME' => _format_date($monthdata['unix'], 'F'),
					'TOTAL' => $monthdata['total'],
					'MEMBERS' => $monthdata['members'],
					'GUESTS' => $monthdata['guests'],
					'PERCENT' => sprintf("%.1d", ($monthdata['percent'] * 100)))
				);
			}
		}
		
		v_style(array(
			'BEFORE_VIEWS' => number_format($this->data['views']),
			'SHOW_VIEWS_LEGEND' => ($this->data['views'] > $total_graph))
		);
		
		return;
	}
	
	public function media()
	{
		$this->method();
	}
	
	protected function _media_home()
	{
		global $bio;
		
		$sql = 'SELECT *
			FROM _dl
			WHERE ub = ' . (int) $this->data['ub'] . '
			ORDER BY title';
		$result = $db->sql_query($sql);
		
		if ($row = $db->sql_fetchrow($result))
		{
			$downloads_type = array(
				1 => '/net/icons/browse.gif',
				2 => '/net/icons/store.gif'
			);
			
			_style('downloads');
			
			$tcol = 0;
			do
			{
				if (!$tcol) _style('downloads.row');
				
				_style('downloads.row.col', array(
					'ITEM' => $row['id'],
					'URL' => _link_control('a', array('a' => $this->data['subdomain'], 'mode' => $this->mode, 'manage' => 'edit', 'd' => $row['id'])),
					'POSTS_URL' => _link('a', array($this->data['subdomain'], 9, $row['id'])) . '#dpf',
					'IMAGE_TYPE' => $downloads_type[$row['ud']],
					'DOWNLOAD_TITLE' => $row['title'],
					'VIEWS' => $row['views'],
					'DOWNLOADS' => $row['downloads'],
					'POSTS' => $row['posts'])
				);
				
				$tcol = ($tcol == 2) ? 0 : $tcol + 1;
			}
			while ($row = $db->sql_fetchrow($result));
			$db->sql_freeresult($result);
		}
		else
		{
			_style('empty', array(
				'MESSAGE' => _lang('CONTROL_A_DOWNLOADS_EMPTY'))
			);
		}
		
		return;
	}
	
	protected function _media_create()
	{
		
	}
	
	protected function _media_modify()
	{
		
	}
	
	protected function _media_remove()
	{
		
	}
}

/*
$v = $this->__(w('alias tab'));

if (!f($v['alias']))
{
	$v['alias'] = $bio->v('bio_alias');
}

$internal_function = '_page_home_' . $v['tab'];

if (!method_exists($this, $internal_function))
{
	_fatal();
}

if ($v['alias'] != $bio->v('bio_alias'))
{
	$v_profile = $bio->v();
}
else
{
	$sql = 'SELECT *
		FROM _bio
		WHERE bio_alias = ?
			AND bio_level <> ?
			AND bio_id NOT IN (SELECT ban_bio
				FROM _bio_ban)
			AND bio_id NOT IN (SELECT ban_assoc
				FROM _bio_ban n
				WHERE n.ban_assoc = ?
					AND n.ban_bio = m.user_id)';
	if (!$v_profile = _fieldrow(sql_filter($sql, $v['alias'], U_INACTIVE, $bio->v('bio_id'))))
	{
		_fatal();
	}
}

//
// User access to this profile
switch ($v_profile['bio_profile_access'])
{
	case 1:
		$sql = 'SELECT fan_id
			FROM _bio_fans
			WHERE fan_uid = ?
				AND fan_of = ?';
		if (!_fieldrow(sql_filter($sql, $bio->v('bio_id'), $v_profile['bio_id'])))
		{
			_fatal();
		}
		break;
}

//
// Global data
$sql = 'SELECT MAX(session_time) AS session_time
	FROM _sessions
	WHERE session_bio = ?';
$session_time = _field(sql_filter($sql, $memberdata['bio_id']), 'session_time', 0);

$session_online = ($v_profile['bio_show'] && $session_time >= (time() - $core->v('session_length'))) ? 'online' : 'offline';
*/

?>