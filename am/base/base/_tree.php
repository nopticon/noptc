<?php
/*
$Id: _tree.php,v 3.0 2009/01/05 12:32:00 Psychopsia Exp $

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

class __tree extends xmd
{
	var $methods = array(
		'comments' => array('validation', 'xcf' => array('f'), 'xcfcron'),
		'download' => array(),
		'form' => array()
	);
	
	function valid_tree()
	{
		$v = $this->__(array('page'));
		if (empty($v['page']))
		{
			_fatal();
		}
		
		$v['field'] = 'alias';
		if (preg_match('#^\d+$#is', $v['page']))
		{
			$v['field'] = 'id';
		}
		
		$sql = 'SELECT *
			FROM _tree
			WHERE tree_' . $this->_escape($v['field']) . " = '" . $this->_escape($v['page']) . "'
			LIMIT 1";
		if (!$tree = $this->_fieldrow($sql))
		{
			if ($v['field'] == 'alias' && $v['page'] != 'tree')
			{
				$_REQUEST['module'] = $v['page'];
				_xfs();
			}
			_fatal();
		}
		
		return $tree;
	}
	
	function home()
	{
		global $core, $user, $style;
		
		$tree = $this->valid_tree();
		$v = $this->__(array('is_comment' => 0));
		
		// Comment posting enabled and form submitted.
		if ($v['is_comment'] && $this->submit)
		{
			if (!$tree['tree_allow_comments'])
			{
				_fatal();
			}
			
			$cv = $this->__(array('comment_username', 'comment_address', 'comment_website', 'comment_message', 'comment_security'));
			$comment_time = time();
			
			if (!$user->d('is_member'))
			{
				foreach ($cv as $cv_k => $cv_v)
				{
					if (empty($cv_v))
					{
						$this->error('E_COMMENT_FILL_FIELDS');
						break;
					}
				}
				
				if (!$this->errors())
				{
					$sql = "SELECT comment_time
						FROM _comments
						WHERE comment_ip = '" . $this->_escape($user->ip) . "'
							AND comment_status = 0";
					if ($row_flood = $this->_fieldrow($sql))
					{
						if (($comment_time - $row_flood['comment_time']) < 30)
						{
							$this->error('E_COMMENT_FLOOD_TIME');
						}
					}
				}
				
				// CAPTCHA verification
				include(XFS . 'core/xcf.php');
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
				$approve_comments = (!$user->d('is_member')) ? $tree['tree_approve_comments'] : 1;
				
				$sql_insert = array(
					'tree' => (int) $tree['tree_id'],
					'uid' => (int) $user->d('user_id'),
					'username' => $cv['comment_username'],
					'email' => $cv['comment_address'],
					'website' => $cv['comment_website'],
					'ip' => $user->ip,
					'status' => (int) $approve_comments,
					'time' => (int) $comment_time,
					'message' => $cv['comment_message']
				);
				$sql = 'INSERT INTO _comments' . $this->_build_array('INSERT', ksql('comment', $sql_insert));
				$this->_sql($sql);
				
				if ($approve_comments)
				{
					$sql = 'UPDATE _tree SET tree_comments = tree_comments + 1
						WHERE tree_id = ' . (int) $tree['tree_id'];
					$this->_sql($sql);
				}
				
				// Send new comment email notification for approval.
				if (!$approve_comments)
				{
					unset($cv['comment_security']);
					
					include(XFS . 'core/emailer.php');
					$emailer = new emailer();
					
					$emailer->from($cv['comment_address']);
					$emailer->use_template('comment_approval');
					
					if (empty($tree['tree_form_email']))
					{
						$tree['tree_form_email'] = $core->v('default_comments_email');
					}
					
					foreach (explode(';', $tree['tree_form_email']) as $i => $row)
					{
						$row_f = (!$i) ? 'email_address' : 'cc';
						$emailer->$row_f($row);
					}
					
					$input = array();
					foreach ($cv as $row_k => $row_v)
					{
						if (empty($row_v)) continue;
						
						if ($row_k == 'comment_message')
						{
							$row_v = str_replace("\r\n", '<br />', $row_v);
						}
						
						$input[] = '&lt; ' . $row_v;
					}
					
					$emailer->assign_vars(array(
						'U_APPROVAL' => _link($this->alias_id($tree), array('x1' => 'comments')),
						
						'INPUT_FIELDS' => implode('<br /><br />', $input),
						'FROM_USERNAME' => $cv['comment_username'])
					);
					$emailer->send();
					$emailer->reset();
				}
				
				redirect(_link($this->alias_id($tree)));
			}
			
			if ($this->errors())
			{
				if (is_ghost()) $this->e('!');
				
				$style->assign_block_vars('comments_error', array(
					'MESSAGE' => $this->get_errors())
				);
			}
		}
		
		//
		if (!empty($tree['tree_redirect']))
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
				WHERE tree_id = ' . (int) $tree['tree_parent'];
			$parent = $this->_fieldrow($sql);
			
			if ($tree['tree_level'] > 2)
			{
				$sql = 'SELECT *
					FROM _tree
					WHERE tree_id = ' . (int) $parent['tree_parent'];
				$subparent = $this->_fieldrow($sql);
			}
		}
		
		if ($tree['tree_node'])
		{
			$sql = 'SELECT *
				FROM _tree
				WHERE tree_id = ' . (int) $tree['tree_node'];
			$node = $this->_fieldrow($sql);
		}
		
		//
		if (@method_exists($this, 'cf_' . $this->alias_id($tree)))
		{
			$this->{'cf_' . $this->alias_id($tree)}($tree);
		}
		
		//
		$sql = 'SELECT *
			FROM _tree
			WHERE tree_parent = ' . (int) $tree['tree_id'] . '
				AND tree_child_hide = 0
			ORDER BY ' . $this->child_order($tree);
		$childs = $this->_rowset($sql);
		
		foreach ($childs as $i => $row)
		{
			if (!$i)
			{
				$sql = 'SELECT image_id, image_tree, image_extension
					FROM _images
					WHERE image_tree IN (' . implode(',', array_keys($childs)) . ')
					ORDER BY RAND()';
				$images_child = $this->_rowset($sql, 'tree_id');
				
				$style->assign_block_vars('tree_child', array(
					'ORDER_URL' => _link($tree['tree_id'], array('order', 0, 0, 0, 0)))
				);
			}
			
			$style->assign_block_vars('tree_child.row', array(
				'ITEM' => $row['tree_id'],
				'URL' => _link($this->alias_id($row)),
				'SUBJECT' => $row['tree_subject'],
				'CONTENT' => $row['tree_content'],
				'EDITED' => $user->format_date($row['tree_edited']),
				'IMAGE' => (isset($images_child[$row['tree_id']])) ? $images_child[$row['tree_id']]['image_id'] . '.' . $images_child[$row['tree_id']]['image_extension'] : 'default.gif')
			);
		}
		
		// Comments
		if ($tree['tree_allow_comments'] && $tree['tree_comments'])
		{
			$sql = 'SELECT c.comment_id, c.comment_username, c.comment_website, c.comment_time, c.comment_message, m.user_username
				FROM _comments c, _members m
				WHERE c.comment_tree = ' . (int) $tree['tree_id'] . '
					AND c.comment_status = 1
					AND c.comment_uid = m.user_id
				ORDER BY c.comment_time DESC';
			$comments = $this->_rowset($sql);
			
			foreach ($comments as $i => $row)
			{
				if (!$i) $style->assign_block_vars('comments', array());
				
				$style->assign_block_vars('comments.row', array(
					'ID' => $row['comment_id'],
					'SUSERNAME' => $row['user_username'],
					'USERNAME' => $row['comment_username'],
					'WEBSITE' => $row['comment_website'],
					'TIME' => $user->format_date($row['comment_time']),
					'MESSAGE' => str_replace("\n", '<br />', $row['comment_message']))
				);
			}
		}
		
		//
		if ($this->css_parent($tree))
		{
			$sql = 'SELECT *
				FROM _tree
				WHERE tree_parent = ' . (int) $this->css_var($tree) . '
					AND tree_child_hide = 0
				ORDER BY ' . $this->child_order($tree);
			$childs_parent = $this->_rowset($sql);
			
			foreach ($childs_parent as $i => $row)
			{
				if (!$i)
				{
					$sql = 'SELECT image_id, image_tree, image_extension
						FROM _images
						WHERE image_tree IN (' . implode(',', array_keys($childs_parent)) . ')
						ORDER BY RAND()';
					$images_child_parent = $this->_rowset($sql, 'tree_id');
					
					$style->assign_block_vars('tree_child', array(
						'ORDER_URL' => _link($tree['tree_id'], array('order', 0, 0, 0, 0)))
					);
				}
				
				$style->assign_block_vars('tree_child_parent.row', array(
					'ITEM' => $row['tree_id'],
					'URL' => _link($this->alias_id($row)),
					'TITLE' => $row['tree_subject'],
					'IMAGE' => (isset($images_child_parent[$row['tree_id']])) ? $images_child_parent[$row['tree_id']]['image_id'] . '.' . $images_child_parent[$row['tree_id']]['image_extension'] : 'default.gif')
				);
			}
		}
		
		if ($tree['tree_downloads'])
		{
			$sql = 'SELECT *
				FROM _downloads
				WHERE download_tree = ' . (int) $tree['tree_id'] . '
				ORDER BY download_order';
			$downloads = $this->_rowset($sql);
			
			foreach ($downloads as $i => $row)
			{
				if (!$i)
				{
					$style->assign_block_vars('downloads', array(
						'ORDER_URL' => _link($tree['tree_id'], array('orderd', 0, 0, 0, 0)))
					);
				}
				
				$style->assign_block_vars('downloads.row', array(
					'ITEM' => $row['download_id'],
					'DOWNLOAD' => _link('get', $row['download_alias'] . '.' . $row['download_extension']),
					'TITLE' => $row['download_title'])
				);
			}
		}
		
		//
		if ($tree['tree_form'])
		{
			$style->assign_block_vars('form', array(
				'URL' => _link($this->alias_id($tree), 'form'))
			);
		}
		
		$s_css_page = '';
		if (@file_exists('./style/css/_tree_' . $this->alias_id($tree) . '.css'))
		{
			$s_css_page = $this->alias_id($tree) . '/';
		}
		elseif ($this->css_parent($tree))
		{
			if (empty($tree['tree_css_var']))
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
					if (is_numeric($tree['tree_css_var']))
					{
						$sql = 'SELECT *
							FROM _tree
							WHERE tree_id = ' . (int) $tree['tree_css_var'];
						if ($css_var_row = $this->_fieldrow($sql))
						{
							$ary_css_var = $css_var_row;
						}
					}
					break;
			}
			
			if ($ary_css_var !== false)
			{
				$s_css_page = $this->alias_id($ary_css_var) . '/';
			}
		}
		
		//$tree['tree_content'] = $this->parse($tree['tree_content']);
		
		$tv = array(
			'ADI' => $core->v('address') . 'container/images/a_' . ($this->css_parent($tree) ? $this->css_var($tree) : $tree['tree_id']) . '/',
			
			'V_TREE' => $tree['tree_id'],
			'V_CSS' => $s_css_page,
			'V_SUBJECT' => $tree['tree_subject'],
			'V_CONTENT' => $tree['tree_content'],
			'V_COMMENTS' => $tree['tree_comments'],
			'V_ALLOW_COMMENTS' => $tree['tree_allow_comments'],
			
			'U_COMMENTS' => _link($this->alias_id($tree)),
			'U_XCF' => _link($this->alias_id($tree) . '-xs.jpg', false, false)
		);
		$this->as_vars($tv);
		
		$tree['tree_subject'] = strip_tags($tree['tree_subject']);
		
		//
		if ($tree['tree_alias'] != 'home')
		{
			if ($node['tree_id'] != $parent['tree_id'])
			{
				$this->navigation($node['tree_subject'], $this->alias_id($node));
			}
			
			if ($tree['tree_level'] > 2)
			{
				if ($parent['tree_id'] && $node['tree_id'] && $tree['tree_level'] > 3)
				{
					$this->navigation('...');
				}
				
				$this->navigation($subparent['tree_subject'], $this->alias_id($subparent));
			}
			
			if ($parent['tree_id'])
			{
				$this->navigation($parent['tree_subject'], $this->alias_id($parent));
			}
			
			$this->navigation($tree['tree_subject'], $this->alias_id($tree));
		}
		
		if ($user->d('is_member'))
		{
			$i = 0;
			$auth_tree = array('create', 'modify', 'remove');
			foreach ($auth_tree as $row)
			{
				if ($user->auth_get('cp_' . $row))
				{
					if (!$i) $style->assign_block_vars('auth', array());
					
					$lang = 'CP_AUTH_' . strtoupper($row);
					
					$style->assign_block_vars('auth.row', array(
						'U_LINK' => _link('cp', array($row, $this->alias_id($tree, false, false, false))),
						'V_NAME' => _lang($lang))
					);
					
					$i++;
				}
			}
		}
		
		//
		$this->template = 'tree';
		if (!empty($tree['tree_template']) && @file_exists('./style/pages/' . $tree['tree_template'] . '.htm'))
		{
			$this->template = 'pages/' . $tree['tree_template'];
		}
		
		// TODO: 304 header response
		
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $tree['tree_edited']) . ' GMT');
		return;
	}
	
	//
	function css_parent($tree)
	{
		return ($tree['tree_css_parent'] && $tree['tree_parent']) ? true : false;
	}
	
	//
	function css_var($tree)
	{
		$postfix = ($tree['tree_css_var'] != '') ? $tree['tree_css_var'] : 'parent';
		return (is_numeric($postfix)) ? $tree['tree_css_var'] : $tree['tree_' . $postfix];
	}
	
	//
	function childs($use_child = true)
	{
		global $user, $style;
		
		$svar = ($use_child) ? 'child' : 'child2';
		if (!$s_child = count($this->$svar))
		{
			return;
		}
		
		$d_images = array();
		if ($this->data['tree_schilds'])
		{
			$sql = 'SELECT tree_id, image_id, image_extension
				FROM _images
				WHERE tree_id IN (' . implode(',', array_keys($this->$svar)) . ')
				ORDER BY RAND()';
			$images = $this->_rowset($sql);
			
			foreach ($images as $rows)
			{
				if (!isset($d_images[$row['tree_id']])) $d_images[$row['tree_id']] = _filename($row['image_id'], $row['image_extension']);
			}
		}
		
		$style->assign_block_vars($svar, array(
			'ORDER_URL' => _link($this->data['tree_id'], array('order', 0, 0, 0, 0)))
		);
		
		// TODO: Replace _linkf funcion to _link_alias
		
		foreach ($this->$svar as $a => $row)
		{
			$style->assign_block_vars($svar . '.item', array(
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
	function child_order($a)
	{
		if (empty($a['tree_child_order']))
		{
			$a['tree_child_order'] = 'order ASC';
		}
		
		$sql = '';
		foreach (explode(', ', $a['tree_child_order']) as $row)
		{
			$sql .= (($sql != '') ? ', ' : '') . 'tree_' . $row;
		}
		
		return $sql;
	}
	
	function comments()
	{
		$this->method();
	}
	
	function _comments_home()
	{
		global $user, $style;
		
		$tree = $this->valid_tree();
		if (!$tree['tree_allow_comments'])
		{
			_fatal();
		}
		
		$sql = 'SELECT *
			FROM _comments c, _members m
			WHERE c.comment_tree = ' . (int) $tree['tree_id'] . '
				AND c.comment_uid = m.user_id
			ORDER BY comment_time DESC';
		$comments = $this->_rowset($sql);
		
		foreach ($comments as $i => $row)
		{
			if (!$i) $style->assign_block_vars('comments', array());
			
			$style->assign_block_vars('comments.row', array(
				'ID' => $row['comment_id'],
				'USERNAME' => $row['comment_username'],
				'EMAIL' => $row['comment_email'],
				'WEBSITE' => $row['comment_website'],
				'IP' => $row['comment_ip'],
				'STATUS' => $row['comment_status'],
				'TIME' => $user->format_date($row['comment_time']),
				'MESSAGE' => $row['comment_message'],
				
				'U_VALIDATION' => _link($this->alias_id($tree), array('x1' => 'comments', 'x2' => 'validation')))
			);
		}
		
		if (!count($comments))
		{
			$style->assign_block_vars('no_comments', array());
		}
		
		$this->template = 'tree_comments';
	}
	
	function _comments_validation()
	{
		$tree = $this->valid_tree();
	}
	
	function _comments_xcf()
	{
		_fatal();
	}
	
	function _comments_xcf_home()
	{
		$tree = $this->valid_tree();
		if (!$tree['tree_allow_comments'])
		{
			_fatal();
		}
		
		include(XFS . 'core/xcf.php');
		$xcf = new captcha();
		
		$xcf->do_image();
		unset($xcf);
		return;
	}
	
	function _comments_xcf_f()
	{
		include(XFS . 'core/xcf.php');
		$xcf = new captcha();
		
		$xcf->do_image();
		unset($xcf);
		return;
	}
	
	function _comments_xcfcron()
	{
		include(XFS . 'core/xcf.php');
		$xcf = new captcha();
		
		$xcf->prune();
		unset($xcf);
		
		$this->e('xcf');
		return;
	}
	
	function download()
	{
		$this->method();
	}
	
	function _download_home()
	{
		global $user;
		
		$v = $this->__(array('f'));
		if (empty($v['f']))
		{
			_fatal();
		}
		
		$sql = "SELECT *
			FROM _downloads
			WHERE download_alias = '" . $this->_escape($v['f']) . "'";
		if (!$download = $this->_fieldrow($sql))
		{
			_fatal();
		}
		
		$sql = 'UPDATE _downloads
			SET download_count = download_count + 1
			WHERE download_id = ' . (int) $download['download_id'];
		$this->_sql($sql);
		
		$this->sql_close();
		
		$orig = array('#\.#', '#\&([A-Za-z])(acute|tilde)\;#');
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
	
	function form()
	{
		$this->method();
	}
	
	function _form_home()
	{
		global $core, $user, $style;
		
		$tree = $this->valid_tree();
		if (!$tree['tree_form'])
		{
			_fatal();
		}
		
		if ($tree['tree_parent'])
		{
			$sql = 'SELECT *
				FROM _tree
				WHERE tree_id = ' . (int) $tree['tree_parent'];
			$parent = $this->_fieldrow($sql);
			
			if ($tree['tree_level'] > 2)
			{
				$sql = 'SELECT *
					FROM _tree
					WHERE tree_id = ' . (int) $parent['tree_parent'];
				$subparent = $this->_fieldrow($sql);
			}
		}
      
		if ($tree['tree_node'])
		{
			$sql = 'SELECT *
				FROM _tree
				WHERE tree_id = ' . (int) $tree['tree_node'];
			$node = $this->_fieldrow($sql);
		}
		
		//
		$sql = 'SELECT *
			FROM _form_fields
			WHERE form_tree = ' . (int) $tree['tree_id'] . '
			ORDER BY form_order';
		$form = $this->_rowset($sql, 'form_alias');
		
		if (!count($form))
		{
			$sql = 'SELECT *
				FROM _form_fields
				WHERE form_tree = 0
				ORDER BY form_order';
			$form = $this->_rowset($sql, 'form_alias');
		}
		
		$form['ctkey'] = array(
			'form_required' => 1,
			'form_regex' => '^([a-zA-Z]+)$',
			'form_alias' => 'ctkey',
			'form_type' => 'text',
			'form_legend' => 'Imagen de seguridad'
		);
		
		if (_button())
		{
			$va = array();
			foreach ($form as $row)
			{
				$va[] = $row['form_alias'];
			}
			$v = $this->__($va);
			
			foreach ($form as $row)
			{
				if (empty($v[$row['form_alias']]))
				{
					if ($row['form_required'])
					{
						$this->error(sprintf(_lang('E_COMMENT_FIELD_EMPTY'), $row['form_legend']), false);
					}
					continue;
				}
				
				if (!empty($row['form_regex']) && !preg_match('#' . $row['form_regex'] . '#is', $v[$row['form_alias']]))
				{
					$this->error(sprintf(_lang('E_COMMENT_FIELD_BAD'), $row['form_legend']), false);
					
					if ($row['form_alias'] == 'ctkey')
					{
						$v[$row['form_alias']] = '';
					}
				}
			}
			
			if (!$this->errors())
			{
				include(XFS . 'core/xcf.php');
				$xcf = new captcha();
				
				if ($xcf->check($v['ctkey']) === false)
				{
					$v['ctkey'] = '';
					$this->error('E_COMMENT_INVALID_CAPTCHA');
				}
				unset($xcf);
			}
			
			if (!$this->errors())
			{
				include(XFS . 'core/emailer.php');
				$emailer = new emailer();
				
				$v['subject'] = preg_replace('#\&([A-Za-z]+){1}(.*?)\;#e', "substr('\\1', 0, 1)", $v['subject']);
				
				$emailer->from($v['email']);
				$emailer->set_subject($v['subject']);
				
				$emailer->use_template('contact_email', $core->v('default_lang'));
				
				foreach (explode(';', $tree['tree_form_email']) as $i => $address)
				{
					$row_f = (!$i) ? 'email_address' : 'cc';
					$emailer->$row_f($address);
				}
				
				$emailer->cc($core->v('default_email'));
				unset($v['ctkey']);
				
				$html = array();
				foreach ($form as $row)
				{
					if (empty($v[$row['form_alias']]))
					{
						continue;
					}
					
					if ($row['form_alias'] == 'message')
					{
						$v['message'] = str_replace("\r\n", '<br />', $v['message']);
					}
					$html[] = '<strong>' . $row['form_legend'] . ':</strong><br />' . $v[$row['form_alias']];
				}
				
				$emailer->assign_vars(array(
					'HTML_FIELDS' => implode('<br /><br />', $html),
					'FROM_USERNAME' => $v['nombre'],
					'FORM_ARTICLE' => $tree['tree_subject'])
				);
				$emailer->send();
				$emailer->reset();
				
				//
				$style->assign_block_vars('sent', array(
					'THANKS' => _lang('CONTACT_THANKS'))
				);
			}
		}
		
		if (!_button() || $this->errors())
		{
			if ($this->errors())
			{
				$style->assign_block_vars('error', array(
					'MESSAGE' => error_list($this->error))
				);
			}
			
			$ff = 'form_';
			$fff = 'alias|type';
			
			$style->assign_block_vars('form', array());
			
			foreach ($form as $row)
			{
				$style->assign_block_vars('form.row', array(
					'ALIAS' => $row[$ff . 'alias'],
					'REQUIRED' => $row[$ff . 'required'],
					'LEGEND' => $row[$ff . 'legend'],
					'TYPE' => $row[$ff . 'type'],
					'ERROR' => isset($error[$row[$ff . 'alias']]),
					'VALUE' => (isset($v[$row[$ff . 'alias']]) ? $v[$row[$ff . 'alias']] : '')
				));
					
				foreach ($row as $row_k => $row_v)
				{
					if (preg_match('#^' . $ff . '(' . $fff . ')$#is', $row_k))
					{
						if ($row_k == 'form_alias') $row_k = 'name';
						
						$style->assign_block_vars('form.row.attrib', array(
							'ATTRIB' => str_replace($ff, '', $row_k),
							'VALUE' => $row_v)
						);
					}
				}
			}
		}
		
		//
		$s_css_page = '';
		if (@file_exists('./style/css/_tree_' . $this->alias_id($tree) . '.css'))
		{
			$s_css_page = $this->alias_id($tree) . '/';
		}
		elseif ($this->css_parent($tree))
		{
			if (empty($tree['tree_css_var']))
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
					if (is_numeric($tree['tree_css_var']))
					{
						$sql = 'SELECT *
							FROM _tree
							WHERE tree_id = ' . (int) $tree['tree_css_var'];
						if ($css_var_row = $this->_fieldrow($sql))
						{
							$ary_css_var = $css_var_row;
						}
					}
					break;
			}
			
			if ($ary_css_var !== false)
			{
				$s_css_page = $this->alias_id($ary_css_var) . '/';
			}
		}
		
		//
		$tv = array(
			'ADI' => $core->v('address') . 'container/images/a_' . ($this->css_parent($tree) ? $this->css_var($tree) : $tree['tree_id']) . '/',
			
			'V_TREE' => $tree['tree_id'],
			'V_CSS' => $s_css_page,
			'V_SUBJECT' => $tree['tree_subject']
		);
		$this->as_vars($tv);
		
		//
		if ($tree['tree_alias'] != 'home')
		{
			if ($node['tree_id'] != $parent['tree_id'])
			{
				$this->navigation($node['tree_subject'], $this->alias_id($node));
			}
			
			if ($tree['tree_level'] > 2)
			{
				if ($parent['tree_id'] && $node['tree_id'] && $tree['tree_level'] > 3)
				{
					$this->navigation('...');
				}
				
				$this->navigation($subparent['tree_subject'], $this->alias_id($subparent));
			}
			
			if ($parent['tree_id'])
			{
				$this->navigation($parent['tree_subject'], $this->alias_id($parent));
			}
			
			$this->navigation($tree['tree_subject'], $this->alias_id($tree));
		}
		
		//$tree['tree_subject'] = strip_tags($tree['tree_subject']);
		
		//
		$this->template = 'default.form';
		if (!empty($tree['tree_template']) && @file_exists('./style/pages/form.' . $tree['tree_template'] . '.htm'))
		{
			$this->template = 'pages/form.' . $tree['tree_template'];
		}
		
		return;
	}
}

?>