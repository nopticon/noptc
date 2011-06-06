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

class __tree extends xmd
{
	public function __construct()
	{
		parent::__construct();
		
		$this->_m(array(
			'comments' => array('validation', 'xcfcron', 'xcf' => w('f')),
			'download' => w())
		);
		$this->auth(false);
	}
	
	private function valid_tree()
	{
		$v = $this->__(w('page'));
		if (!f($v['page']))
		{
			_fatal();
		}
		
		$v['field'] = (is_numb($v['page'])) ? 'id' : 'alias';
		
		$sql = 'SELECT *
			FROM _tree
			WHERE tree_?? = ?
			LIMIT ??';
		if (!$tree = _fieldrow(sql_filter($sql, $v['field'], $v['page'], 1)))
		{
			if ($v['field'] == 'alias' && $v['page'] != 'tree')
			{
				_xfs($v['page']);
			}
			_fatal();
		}
		
		return $tree;
	}
	
	public function home()
	{
		global $core, $user;
		
		$tree = $this->valid_tree();
		$v = $this->__(_array_keys(w('is_comment is_form'), 0));
		
		// Form posting enabled and form submitted
		if ($v['is_form'] && _button())
		{
			if (!is_ghost())
			{
				_fatal(405);
			}
			
			if (!$tree['tree_form'])
			{
				_fatal();
			}
			
			$sql_fields = 'SELECT form_alias, form_required, form_legend, form_regex, 
				FROM _form_fields
				WHERE form_tree = ?
				ORDER BY form_order';
			
			if (!$form = _rowset(sql_filter($sql_fields, $tree['tree_id']), 'form_alias'))
			{
				$form = _rowset(sql_filter($sql_fields, 0), 'form_alias');
			}
			
			$form['secure'] = array(
				'form_required' => 1,
				'form_regex' => '^([a-zA-Z]+)$',
				'form_alias' => 'secure',
				'form_type' => 'text',
				'form_legend' => _lang('XCF_LEGEND')
			);
			
			foreach ($form as $row)
			{
				$v = array_merge($v, $this->__(array($row['form_alias'])));
				
				if (!f($v[$row['form_alias']]))
				{
					if ($row['form_required'])
					{
						$this->_error(sprintf(_lang('E_COMMENT_FIELD_EMPTY'), $row['form_legend']), false);
					}
					continue;
				}
				
				if (f($row['form_regex']) && !preg_match('#' . $row['form_regex'] . '#is', $v[$row['form_alias']]))
				{
					$this->_error(sprintf(_lang('E_COMMENT_FIELD_BAD'), $row['form_legend']), false);
					
					if ($row['form_alias'] == 'secure')
					{
						$v[$row['form_alias']] = '';
					}
				}
			}
			
			require_once(XFS . 'core/xcf.php');
			$xcf = new captcha();
			
			if ($xcf->check($v['secure']) === false)
			{
				$v['secure'] = '';
				$this->_error('#E_COMMENT_INVALID_CAPTCHA');
			}
			unset($xcf);
			
			require_once(XFS . 'core/emailer.php');
			$emailer = new emailer();
			
			$emailer->set_decode(true);
			$emailer->format('plain');
			
			$emailer->from($v['address']);
			$emailer->set_subject(_rm_acute($v['subject']));
			$emailer->use_template('contact_email');
			
			if (f($core->v('default_email')))
			{
				$tree['tree_form_email'] .= ((f($tree['tree_form_email'])) ? ';' : '') . $core->v('default_email');
			}
			
			$form_addresses = array_map('trim', array_unique(explode(';', $tree['tree_form_email'])));
			foreach ($form_addresses as $i => $address)
			{
				$row_f = (!$i) ? 'email_address' : 'cc';
				$emailer->$row_f($address);
			}
			unset($v['secure']);
			
			$content = w();
			foreach ($form as $row)
			{
				if (!f($v[$row['form_alias']])) continue;
				
				$content[] = $row['form_legend'] . ":\n" . $v[$row['form_alias']];
			}
			
			$emailer->assign_vars(array(
				'CONTENT' => implode("\n\n", $content),
				'FORM_ARTICLE' => $tree['tree_subject'])
			);
			$emailer->send();
			$emailer->reset();
			
			$response = array(
				'lang' => _lang('FORM_SUCCESS')
			);
			$this->e(json_encode($response));
		}
		
		// Comment posting enabled and form submitted.
		if ($v['is_comment'] && _button())
		{
			if (!$tree['tree_allow_comments'])
			{
				_fatal();
			}
			
			$cv = $this->__(w('comment_username comment_address comment_website comment_message comment_security'));
			$comment_time = time();
			
			if (!$user->v('is_member'))
			{
				foreach ($cv as $cv_k => $cv_v)
				{
					if (!f($cv_v))
					{
						$this->error('E_COMMENT_FILL_FIELDS');
						break;
					}
				}
				
				if (!$this->errors())
				{
					$sql = 'SELECT comment_time
						FROM _comments
						WHERE comment_ip = ?
							AND comment_status = 0';
					if ($row_flood = _fieldrow(sql_filter($sql, $user->ip)))
					{
						if (($comment_time - $row_flood['comment_time']) < 30)
						{
							$this->error('E_COMMENT_FLOOD_TIME');
						}
					}
				}
				
				// CAPTCHA verification
				require_once(XFS . 'core/xcf.php');
				$xcf = new captcha();
				
				if ($xcf->check($cv['comment_security']) === false)
				{
					$cv['comment_security'] = '';
					$this->error('E_COMMENT_INVALID_CAPTCHA');
				}
				unset($xcf);
			}
			
			if (!$this->errors())
			{
				$approve_comments = (!$user->v('is_member')) ? $tree['tree_approve_comments'] : 1;
				
				$sql_insert = array(
					'tree' => (int) $tree['tree_id'],
					'uid' => (int) $user->v('user_id'),
					'username' => $cv['comment_username'],
					'email' => $cv['comment_address'],
					'website' => $cv['comment_website'],
					'ip' => $user->ip,
					'status' => (int) $approve_comments,
					'time' => (int) $comment_time,
					'message' => $cv['comment_message']
				);
				$sql = 'INSERT INTO _comments' . _build_array('INSERT', prefix('comment', $sql_insert));
				_sql($sql);
				
				if ($approve_comments)
				{
					$sql = 'UPDATE _tree SET tree_comments = tree_comments + 1
						WHERE tree_id = ?';
					_sql(sql_filter($sql, $tree['tree_id']));
				}
				
				// Send new comment email notification for approval.
				if (!$approve_comments)
				{
					unset($cv['comment_security']);
					
					require_once(XFS . 'core/emailer.php');
					$emailer = new emailer();
					
					$emailer->from($cv['comment_address']);
					$emailer->use_template('comment_approval');
					
					if (f($tree['tree_form_email']))
					{
						$tree['tree_form_email'] = $core->v('default_comments_email');
					}
					
					foreach (explode(';', $tree['tree_form_email']) as $i => $row)
					{
						$row_f = (!$i) ? 'email_address' : 'cc';
						$emailer->$row_f($row);
					}
					
					$input = w();
					foreach ($cv as $row_k => $row_v)
					{
						if (!f($row_v)) continue;
						
						if ($row_k == 'comment_message')
						{
							$row_v = str_replace("\r\n", '<br />', $row_v);
						}
						
						$input[] = '&lt; ' . $row_v;
					}
					
					$emailer->assign_vars(array(
						'U_APPROVAL' => _link(_rewrite($tree), array('x1' => 'comments')),
						
						'INPUT_FIELDS' => implode('<br /><br />', $input),
						'FROM_USERNAME' => $cv['comment_username'])
					);
					$emailer->send();
					$emailer->reset();
				}
				
				redirect(_link(_rewrite($tree)));
			}
			
			if ($this->errors())
			{
				if (is_ghost()) $this->e('!');
				
				_style('comments_error', array(
					'MESSAGE' => $this->get_errors())
				);
			}
		}
		
		//
		if (f($tree['tree_redirect']))
		{
			if (preg_match('#^[a-z0-9\-\_]+$#is', $tree['tree_redirect']))
			{
				$tree['tree_redirect'] = _link($tree['tree_redirect']);
			}
			redirect($tree['tree_redirect']);
		}
		
		//
		if ($tree['tree_parent'])
		{
			$sql = 'SELECT *
				FROM _tree
				WHERE tree_id = ?';
			$parent = _fieldrow(sql_filter($sql, $tree['tree_parent']));
			
			if ($tree['tree_level'] > 2)
			{
				$sql = 'SELECT *
					FROM _tree
					WHERE tree_id = ?';
				$subparent = _fieldrow(sql_filter($sql, $parent['tree_parent']));
			}
		}
		
		if ($tree['tree_node'])
		{
			$sql = 'SELECT *
				FROM _tree
				WHERE tree_id = ?';
			$node = _fieldrow(sql_filter($sql, $tree['tree_node']));
		}
		
		//
		if (@method_exists($this, 'cf_' . _rewrite($tree)))
		{
			$this->{'cf_' . _rewrite($tree)}($tree);
		}
		
		//
		$sql = 'SELECT *
			FROM _tree
			WHERE tree_parent = ?
				AND tree_child_hide = 0
			ORDER BY ??';
		$childs = _rowset(sql_filter($sql, $tree['tree_id'], $this->child_order($tree)));
		
		foreach ($childs as $i => $row)
		{
			if (!$i)
			{
				$sql = 'SELECT image_id, image_tree, image_extension
					FROM _images
					WHERE image_tree IN (??)
					ORDER BY RAND()';
				$images_child = _rowset(sql_filter($sql, _implode(',', array_keys($childs))), 'tree_id');
				
				_style('tree_child1', array(
					'ORDER_URL' => _link($tree['tree_id'], array('order', 0, 0, 0, 0)))
				);
			}
			
			_style('tree_child.row', array(
				'ITEM' => $row['tree_id'],
				'URL' => _link(_rewrite($row)),
				'SUBJECT' => $row['tree_subject'],
				'CONTENT' => $row['tree_content'],
				'EDITED' => _format_date($row['tree_edited']),
				'IMAGE' => (isset($images_child[$row['tree_id']])) ? $images_child[$row['tree_id']]['image_id'] . '.' . $images_child[$row['tree_id']]['image_extension'] : 'default.gif')
			);
		}
		
		// Comments
		if ($tree['tree_allow_comments'] && $tree['tree_comments'])
		{
			$sql = 'SELECT c.comment_id, c.comment_username, c.comment_website, c.comment_time, c.comment_message, m.user_username
				FROM _comments c, _members m
				WHERE c.comment_tree = ?
					AND c.comment_status = 1
					AND c.comment_uid = m.user_id
				ORDER BY c.comment_time DESC';
			$comments = _rowset(sql_filter($sql, $tree['tree_id']));
			
			foreach ($comments as $i => $row)
			{
				if (!$i) _style('comments');
				
				_style('comments.row', array(
					'ID' => $row['comment_id'],
					'SUSERNAME' => $row['user_username'],
					'USERNAME' => $row['comment_username'],
					'WEBSITE' => $row['comment_website'],
					'TIME' => _format_date($row['comment_time']),
					'MESSAGE' => str_replace("\n", '<br />', $row['comment_message']))
				);
			}
		}
		
		//
		if ($this->css_parent($tree))
		{
			$sql = 'SELECT *
				FROM _tree
				WHERE tree_parent = ?
					AND tree_child_hide = 0
				ORDER BY ??';
			$childs_parent = _rowset(sql_filter($sql, $this->css_var($tree), $this->child_order($tree)));
			
			foreach ($childs_parent as $i => $row)
			{
				if (!$i)
				{
					$sql = 'SELECT image_id, image_tree, image_extension
						FROM _images
						WHERE image_tree IN (??)
						ORDER BY RAND()';
					$images_child_parent = _rowset(sql_filter($sql, _implode(',', array_keys($childs_parent))), 'tree_id');
					
					_style('tree_child', array(
						'ORDER_URL' => _link($tree['tree_id'], array('order', 0, 0, 0, 0)))
					);
				}
				
				_style('tree_child_parent.row', array(
					'ITEM' => $row['tree_id'],
					'URL' => _link(_rewrite($row)),
					'TITLE' => $row['tree_subject'],
					'IMAGE' => (isset($images_child_parent[$row['tree_id']])) ? $images_child_parent[$row['tree_id']]['image_id'] . '.' . $images_child_parent[$row['tree_id']]['image_extension'] : 'default.gif')
				);
			}
		}
		
		if ($tree['tree_downloads'])
		{
			$sql = 'SELECT *
				FROM _downloads
				WHERE download_tree = ?
				ORDER BY download_order';
			$downloads = _rowset(sql_filter($sql, $tree['tree_id']));
			
			foreach ($downloads as $i => $row)
			{
				if (!$i)
				{
					_style('downloads', array(
						'ORDER_URL' => _link($tree['tree_id'], array('orderd', 0, 0, 0, 0)))
					);
				}
				
				_style('downloads.row', array(
					'ITEM' => $row['download_id'],
					'DOWNLOAD' => _link('get', $row['download_alias'] . '.' . $row['download_extension']),
					'TITLE' => $row['download_title'])
				);
			}
		}
		
		//
		if ($tree['tree_form'])
		{
			$sql = 'SELECT *
				FROM _form_fields
				WHERE form_tree = ?
				ORDER BY form_order';
			$form = _rowset(sql_filter($sql, $tree['tree_id']), 'form_alias');
			
			if (!count($form))
			{
				$sql = 'SELECT *
					FROM _form_fields
					WHERE form_tree = 0
					ORDER BY form_order';
				$form = _rowset($sql, 'form_alias');
			}
			
			$form['secure'] = array(
				'form_required' => 1,
				'form_regex' => '^([a-zA-Z]+)$',
				'form_alias' => 'secure',
				'form_type' => 'text',
				'form_legend' => 'Imagen de seguridad'
			);
			
			_style('form', array(
				'URL' => _link(_rewrite($tree)))
			);
			
			foreach ($form as $row)
			{
				_style('form.row', array(
					'ALIAS' => $row['form_alias'],
					'REQUIRED' => $row['form_required'],
					'LEGEND' => _lang($row['form_legend']),
					'TYPE' => $row['form_type'],
					'PAGE' => $tree['tree_alias'])
				);
				
				foreach ($row as $row_k => $row_v)
				{
					if (preg_match('#^form_(alias|type)$#is', $row_k))
					{
						if ($row_k == 'form_alias') $row_k = 'name';
						
						_style('form.row.attrib', array(
							'ATTRIB' => str_replace('form_', '', $row_k),
							'VALUE' => $row_v)
						);
					}
				}
			}
		}
		
		$s_css_page = '';
		if (@file_exists('./style/css/_tree_' . _rewrite($tree) . '.css'))
		{
			$s_css_page = _rewrite($tree) . '/';
		}
		elseif ($this->css_parent($tree))
		{
			if (!f($tree['tree_css_var']))
			{
				$tree['tree_css_var'] = 'parent';
			}
			
			$ary_css_var = false;
			switch ($tree['tree_css_var'])
			{
				case 'parent':
				case 'subparent':
				case 'node':
					$ary_css_var = ${$tree['tree_css_var']};
					break;
				default:
					if (is_numb($tree['tree_css_var']))
					{
						$sql = 'SELECT *
							FROM _tree
							WHERE tree_id = ?';
						if ($css_var_row = _fieldrow(sql_filter($sql, $tree['tree_css_var'])))
						{
							$ary_css_var = $css_var_row;
						}
					}
					break;
			}
			
			if ($ary_css_var !== false)
			{
				$s_css_page = _rewrite($ary_css_var) . '/';
			}
		}
		
		v_style(array(
			'S_IMAGES' => $core->v('address') . 'container/images/a_' . ($this->css_parent($tree) ? $this->css_var($tree) : $tree['tree_id']) . '/',
			
			'V_TREE' => $tree['tree_id'],
			'V_CSS' => $s_css_page,
			'V_SUBJECT' => $tree['tree_subject'],
			'V_CONTENT' => _message($tree['tree_content']),
			'V_COMMENTS' => $tree['tree_comments'],
			'V_ALLOW_COMMENTS' => $tree['tree_allow_comments'],
			'V_ALLOW_FORM' => $tree['tree_form'],
			
			'U_COMMENTS' => _link(_rewrite($tree)),
			'U_XCF' => _link(_rewrite($tree) . '-xs.jpg', false, false))
		);
		
		$tree['tree_subject'] = strip_tags($tree['tree_subject']);
		
		//
		if ($tree['tree_alias'] != 'home')
		{
			if ($node['tree_id'] != $parent['tree_id'])
			{
				$this->navigation($node['tree_subject'], _rewrite($node));
			}
			
			if ($tree['tree_level'] > 2)
			{
				if ($parent['tree_id'] && $node['tree_id'] && $tree['tree_level'] > 3)
				{
					$this->navigation('...');
				}
				
				$this->navigation($subparent['tree_subject'], _rewrite($subparent));
			}
			
			if ($parent['tree_id'])
			{
				$this->navigation($parent['tree_subject'], _rewrite($parent));
			}
			
			$this->navigation($tree['tree_subject'], _rewrite($tree));
		}
		
		if ($user->v('is_member'))
		{
			$tree['tree_cp'] = 1;
			
			$i = 0;
			$auth_tree = array('create', 'modify', 'remove');
			foreach ($auth_tree as $row)
			{
				if (_auth_get('cp_' . $row))
				{
					if (!$i) _style('auth');
					
					_style('auth.row', array(
						'U_AUTH' => _link('cp', array($row, _rewrite($tree))),
						'V_NAME' => _lang('CP_AUTH_' . $row))
					);
					$i++;
				}
			}
		}
		
		//
		$this->_template('tree');
		if (f($tree['tree_template']) && @file_exists('./style/custom/' . $tree['tree_template'] . '.htm'))
		{
			$this->_template('custom/' . $tree['tree_template']);
		}
		
		// TODO: 304 header response
		
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $tree['tree_edited']) . ' GMT');
		return;
	}
	
	//
	private function css_parent($tree)
	{
		return ($tree['tree_css_parent'] && $tree['tree_parent']) ? true : false;
	}
	
	//
	private function css_var($tree)
	{
		$postfix = ($tree['tree_css_var'] != '') ? $tree['tree_css_var'] : 'parent';
		return (is_numeric($postfix)) ? $tree['tree_css_var'] : $tree['tree_' . $postfix];
	}
	
	//
	private function childs($use_child = true)
	{
		global $user;
		
		$svar = ($use_child) ? 'child' : 'child2';
		if (!$s_child = count($this->$svar))
		{
			return;
		}
		
		$d_images = w();
		if ($this->data['tree_schilds'])
		{
			$sql = 'SELECT tree_id, image_id, image_extension
				FROM _images
				WHERE tree_id IN (??)
				ORDER BY RAND()';
			$images = _rowset(sql_filter($sql, _implode(',', array_keys($this->$svar))));
			
			foreach ($images as $rows)
			{
				if (!isset($d_images[$row['tree_id']])) $d_images[$row['tree_id']] = _filename($row['image_id'], $row['image_extension']);
			}
		}
		
		_style($svar, array(
			'ORDER_URL' => _link($this->data['tree_id'], array('order', 0, 0, 0, 0)))
		);
		
		// TODO: Replace _linkf funcion to _link_alias
		
		foreach ($this->$svar as $a => $row)
		{
			_style($svar . '.item', array(
				'ID' => $row['tree_id'],
				'U' => _linkf($a, $row['tree_furl']),
				'L' => $user->ls('tree', 'title', $row),
				'I' => (isset($d_images[$a])) ? $d_images[$a] : 'def.gif',
				'DU' => $this->u_dynamic($row))
			);
		}
		
		if ($use_child)
		{
			$this->childs(false);
		}
		
		return;
	}
	
	//
	private function child_order($a)
	{
		if (!f($a['tree_child_order']))
		{
			$a['tree_child_order'] = 'order ASC';
		}
		
		$sql = w();
		foreach (explode(', ', $a['tree_child_order']) as $row)
		{
			$sql[] = 'tree_' . $row;
		}
		return implode(', ', $sql);
	}
	
	public function comments()
	{
		$this->method();
	}
	
	protected function _comments_home()
	{
		global $user;
		
		$tree = $this->valid_tree();
		if (!$tree['tree_allow_comments'])
		{
			_fatal();
		}
		
		$sql = 'SELECT *
			FROM _comments c, _members m
			WHERE c.comment_tree = ?
				AND c.comment_uid = m.user_id
			ORDER BY comment_time DESC';
		$comments = _rowset(sql_filter($sql, $tree['tree_id']));
		
		foreach ($comments as $i => $row)
		{
			if (!$i) _style('comments');
			
			_style('comments.row', array(
				'ID' => $row['comment_id'],
				'USERNAME' => $row['comment_username'],
				'EMAIL' => $row['comment_email'],
				'WEBSITE' => $row['comment_website'],
				'IP' => $row['comment_ip'],
				'STATUS' => $row['comment_status'],
				'TIME' => _format_date($row['comment_time']),
				'MESSAGE' => $row['comment_message'],
				
				'U_VALIDATION' => _link(_rewrite($tree), array('x1' => 'comments', 'x2' => 'validation')))
			);
		}
		
		if (!count($comments))
		{
			_style('no_comments');
		}
		
		return $this->_template('tree_comments');
	}
	
	protected function _comments_validation()
	{
		$tree = $this->valid_tree();
	}
	
	protected function _comments_xcf()
	{
		$tree = $this->valid_tree();
		if (!$tree['tree_allow_comments'] && !$tree['tree_form'])
		{
			_fatal();
		}
		
		require_once(XFS . 'core/xcf.php');
		$xcf = new captcha();
		
		$xcf->do_image();
		unset($xcf);
		return;
	}
	
	protected function _comments_xcfcron()
	{
		require_once(XFS . 'core/xcf.php');
		$xcf = new captcha();
		
		$xcf->prune();
		unset($xcf);
		
		$this->e('xcf');
		return;
	}
	
	public function download()
	{
		$this->method();
	}
	
	protected function _download_home()
	{
		global $user;
		
		$v = $this->__(array('f'));
		if (!f($v['f']))
		{
			_fatal();
		}
		
		$sql = 'SELECT *
			FROM _downloads
			WHERE download_alias = ?';
		if (!$download = _fieldrow(sql_filter($sql, $v['f'])))
		{
			_fatal();
		}
		
		$sql = 'UPDATE _downloads
			SET download_count = download_count + 1
			WHERE download_id = ?';
		_sql(sql_filter($sql, $download['download_id']));
		
		sql_close();
		
		$orig = array('#\.#', '#\&(\w)(acute|tilde)\;#');
		$repl = array('', '\\1');
		$bad_chars = array("'", "\\", ' ', '/', ':', '*', '?', '"', '<', '>', '|');
		
		$filename = preg_replace($orig, $repl, $download['download_title']) . '.' . $download['download_extension'];
		$filename = preg_replace("/%(\w{2})/", '_', rawurlencode(str_replace($bad_chars, '_', $filename)));
		$filepath = LIB . 'get/' . $download['download_id'] . '.' . $download['download_extension'];
		
		// Headers
		header('Content-Type: application/octet-stream; name="' . $filename . '"');
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		header('Accept-Ranges: bytes');
		header('Pragma: no-cache');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Content-transfer-encoding: binary');
		header('Content-length: ' . @filesize($filepath));
		@readfile($filepath);
		exit();
	}
}

?>