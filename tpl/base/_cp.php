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

class __cp extends xmd
{
	private $tags;
	
	public function __construct()
	{
		parent::__construct();
		
		$this->_m(_array_keys(w('create modify remove')));
		$this->tags = w();
	}
	
	public function home()
	{
		_fatal();
	}
	
	private function init()
	{
		global $core;
		
		$v = $this->__(w('page'));
		if (!f($v['page']))
		{
			_fatal();
		}
		
		$v['field'] = (!is_numb($v['page'])) ? 'alias' : 'id';
		
		$sql = 'SELECT *
			FROM _tree
			WHERE tree_?? = ?
			LIMIT 1';
		if (!$tree = _fieldrow(sql_filter($sql, $v['field'], $v['page'])))
		{
			_fatal();
		}
		
		return $tree;
	}
	
	public function create()
	{
		if (!isset($this->arg['zmode']))
		{
			$this->rvar('zmode', 'create');
		}
		
		$this->method();
	}
	
	protected function _create_home()
	{
		$v = $tree = $this->init();
		$z = $this->__(w('zmode'));
		
		if (_button())
		{
			$v = $this->__(array(
				'node' => 0,
				'parent' => 0,
				'level' => 0,
				'module' => 0,
				'alias',
				'child_hide' => 0,
				'child_order',
				'nav' => 0,
				'nav_hide' => 0,
				'css_parent' => 0,
				'css_var',
				'quickload' => 0,
				'dynamic' => 0,
				'tags',
				'template',
				'redirect',
				'subject',
				'content',
				'description',
				'allow_comments' => 0,
				'approve_comments' => 0,
				'form' => 0,
				'form_email',
				'published',
				'move'
			));
			
			//
			$v['edited'] = time();
			
			foreach (w('node level parent module') as $row)
			{
				$v[$row] = $tree['tree_' . $row];
			}
			
			if ($z['zmode'] == 'create')
			{
				$v['parent'] = $tree['tree_id'];
				$v['level']++;
				
				if (!$v['node']) $v['node'] = $v['parent'];
			}
			
			// Parse vars
			foreach ($v as $row_k => $row_v)
			{
				switch ($row_k)
				{
					case 'subject':
						$row_v = $this->html($row_v, 'strong');
						break;
					case 'content':
						$row_v = $this->html($row_v);
						break;
					case 'alias':
						$row_v = _alias($row_v, w('_'), '-');
						break;
					case 'checksum':
						$row_v = _hash($v['content']);
						break;
					case 'published':
						$row_v = dvar($row_v, date('d m Y'));
						
						$e_date = explode(' ', $row_v);
						$row_v = _timestamp($e_date[1], $e_date[0], $e_date[2]);
						break;
				}
				$v[$row_k] = $row_v;
			}
			
			if ($z['zmode'] == 'modify' && $tree['tree_alias'] == 'home' && $v['alias'] != 'home')
			{
				$v['alias'] = 'home';
			}
			
			if (f($v['alias']))
			{
				$sql = 'SELECT tree_id
					FROM _tree
					WHERE tree_alias = ?
						AND tree_id <> ?';
				if (_fieldrow(sql_filter($sql, $v['alias'], $tree['tree_id'])))
				{
					$this->_error('#ALIAS_IN_USE');
				}
			}
			
			if ($z['zmode'] == 'modify')
			{
				if ($v['move'])
				{
					$mv_field = (!is_numb($v['move'])) ? 'alias' : 'id';
					
					$sql = 'SELECT *
						FROM _tree
						WHERE tree_?? = ?';
					if ($mv_tree = _fieldrow(sql_filter($sql, $mv_field, $v['move'])))
					{
						$mv_insert = array(
							'module' => $mv_tree['module_id'],
							'node' => $mv_tree['tree_node'],
							'parent' => $mv_tree['tree_id'],
							'level' => ($mv_tree['tree_level'] + 1)
						);
						$sql = 'UPDATE _tree SET ' . _build_array('UPDATE', prefix('tree', $mv_insert)) . sql_filter('
							WHERE article_id = ?', $tree['tree_id']);
						_sql($sql);
						
						$sql = 'UPDATE _tree SET tree_childs = tree_childs - 1
							WHERE tree_id = ?';
						_sql(sql_filter($sql, $tree['tree_parent']));
						
						$sql = 'UPDATE _tree SET tree_childs = tree_childs + 1
							WHERE tree_id = ?';
						_sql(sql_filter($sql, $mv_tree['tree_id']));
					}
				}
				unset($v['move']);
				
				// Check input values against database
				foreach ($v as $row_k => $row_v)
				{
					if ($tree['tree_' . $row_k] == $row_v)
					{
						unset($v[$row_k]);
					}
				}
				
				if (!(count($v) - 1))
				{
					unset($v['edited']);
				}
			}
			else
			{
				unset($v['move']);
			}
			
			//
			$u_tree = _rewrite($tree);
			
			if (count($v))
			{
				if (isset($v['content']) && $v['content'])
				{
					$v['content'] = str_replace(w('&lt; &gt;'), w('< >'), $v['content']);
				}
				
				if ($z['zmode'] == 'create')
				{
					$sql = 'INSERT INTO _tree' . _build_array('INSERT', prefix('tree', $v));
				}
				else
				{
					$sql = 'UPDATE _tree SET ' . _build_array('UPDATE', prefix('tree', $v)) . sql_filter('
						WHERE tree_id = ?', $tree['tree_id']);
				}
				_sql($sql);
				
				if ($z['zmode'] == 'create')
				{
					$u_tree = (f($v['alias'])) ? $v['alias'] : _nextid();
					
					$sql = 'UPDATE _tree
						SET tree_childs = tree_childs + 1
						WHERE tree_id = ?';
					_sql(sql_filter($sql, $tree['tree_id']));
				}
			}
			
			redirect(_link($u_tree));
		}
		
		//
		// Show fieldset
		$v_fieldset = array(
			'subject',
			'content',
			'description',
			'alias',
			'child_hide' => 0,
			'child_order',
			'nav' => 0,
			'nav_hide' => 0,
			'css_parent',
			'css_var',
			'quickload' => 0,
			'dynamic' => 0,
			'tags',
			'template',
			'redirect',
			'allow_comments' => 0,
			'approve_comments' => 0,
			'form' => 0,
			'form_email',
			'published'
		);
		
		$is_modify = ($z['zmode'] == 'modify');
		
		foreach (_array_keys($v_fieldset, '') as $k => $row)
		{
			$name = 'tree_' . $k;
			$cp_lang = _lang('CP_' . $k);
			$value = ($is_modify) ? (isset($v[$k])) ? $v[$k] : ((isset($tree[$name])) ? $tree[$name] : '') : '';
			$checked = (is_numb($row) && $is_modify && $tree[$name]) ? ' checked="checked"' : '';
			
			if (f($value))
			{
				switch ($k)
				{
					case 'published':
						$value = date('d m Y', $value);
						break;
				}
			}
			
			$type = 'text';
			if (is_numb($row))
			{
				$value = 1;
				$type = 'checkbox';
			}
			
			$tag = 'input';
			if ($k == 'content')
			{
				$tag = 'textarea';
			}
			
			_style('field', array(
				'NAME' => $k,
				'ID' => $k,
				'TAG' => $tag,
				'TYPE' => $type,
				'VALUE' => $value,
				'LANG' => $cp_lang,
				'CHECKED' => $checked)
			);
			
			if ($k == 'template')
			{
				$i = 0;
				$fp = @opendir('./style/custom/');
				while ($row_d = @readdir($fp))
				{
					if (_extension($row_d) != 'htm')
					{
						continue;
					}
					
					if (!$i)
					{
						_style('field.templated');
						_style('field.templated.row', array(
							'V' => '',
							'FILE' => _lang('NONE'))
						);
					}
					
					$v_file = str_replace('.htm', '', $row_d);
					
					_style('field.templated.row', array(
						'V' => $v_file,
						'FILE' => $v_file)
					);
					$i++;
				}
				@closedir($fp);
			}
			
			//
		}
		
		return;
	}
	
	public function modify()
	{
		$this->rvar('zmode', 'modify');
		$this->x(1, 'create');
		
		$this->create();
	}
	
	public function remove()
	{
		$this->method();
	}
	
	protected function _remove_home()
	{
		return;
	}
	
	/*
	function delete_search($parent_id, $first = false)
	{
		if ($first)
		{
			$sql = 'SELECT article_id, article_parent, article_childs
				FROM _articles
				WHERE article_id = ?';
			}
		else
		{
			$sql = 'SELECT *
				FROM _articles
				WHERE article_parent = ?
				ORDER BY article_id';
		}
		$articles = _rowset(sql_filter($sql, $parent_id));
		
		foreach ($articles as $rows)
		{
			$this->delete_items[$row['article_parent']][] = $row['article_id'];
			
			if ($row['article_childs'])
			{
				$this->delete_search($row['article_id']);
			}
		}
	}
	
	function delete()
	{
		global $core;
		
		$confirm = (isset($_POST['confirm'])) ? true : false;
		$cancel = (isset($_POST['cancel'])) ? true : false;
		
		if ($confirm || $cancel)
		{
			$return_id = $this->data['article_id'];
			if ($cancel)
			{
				redirect(_link($return_id));
			}
			
			$a_sql = w();
			$image_path = array($this->path['articles'], $this->path['thumbnails']);
			
			$this->delete_search($this->data['article_id'], true);
			foreach ($this->delete_items as $parent => $items)
			{
				if ($parent)
				{
					$a_sql[] = sql_filter('UPDATE _articles
						SET article_childs = article_childs - ??
						WHERE article_id = ?', count($items), $parent);
				}
				
				foreach ($items as $item)
				{
					//
					$sql = 'SELECT *
						FROM _downloads
						WHERE article_id = ?';
					$a_downloads = _rowset(sql_filter($sql, $item));
					
					$downloads = w();
					foreach ($a_downloads as $row)
					 {
						 $this_path = $this->path['downloads'] . _filename($row['download_id'], $row['download_extension']);
						 if (@file_exists($this_path) && @unlink($this_path))
						 {
							 $downloads[] = $row['download_id'];
						 }
					 }
					
					if (count($downloads))
					{
						$a_sql[] = 'DELETE FROM _downloads
							WHERE download_id IN (' . implode(',', $downloads) . ')';
					}
					
					//
					$sql = 'SELECT *
						FROM _images
						WHERE article_id = ?';
					$images = _rowset(sql_filter($sql, $item));
					
					$delete_images = w();
					foreach ($images as $row)
					{
						$delete_row = false;
						$prev_path = false;
						
						foreach ($image_path as $path)
						{
							$current = $path . _filename($row['image_id'], $row['image_extension']);
							if (@file_exists($current) && @unlink($current))
							{
								if ($prev_path) $delete_row = true;
								
								$prev_path = true;
							}
						}
						
						if ($delete_row)
						{
							$delete_images[] = $row['image_id'];
						}
					}
					
					if (count($delete_images))
					{
						$a_sql[] = 'DELETE FROM _images
							WHERE image_id IN (' . implode(',', $delete_images) . ')';
					}
					
					$a_sql[] = sql_filter('DELETE FROM _form_fields
						WHERE article_id = ?', $item);
					$a_sql[] = sql_filter('DELETE FROM _articles
						WHERE article_id = ?', $item);
				}
			}
			_sql($a_sql);
			$core->cache_unload('news', 'in_nav');
			
			$return_id = ($return_id) ? (($this->data['article_parent']) ? $this->data['article_parent'] : '') : '';
			redirect(_link($return_id));
		}
		
		$template_vars = array(
			'ARTICLE_ID' => $core->v('def_css'),
			'L_DELETE_LEGEND' => sprintf(_lang('SECTION_DELETE_ARTICLE_LEGEND'), $this->data['article_title']),
			'S_NAV' => $this->nav(),
			'S_ACTION' => s_link($this->data['article_id'], array('delete', $this->data['module_id'], $this->data['article_node'], $this->data['article_parent'], $this->data['article_level']))
		);
		_layout('delete', $this->data['article_title'], $template_vars);
	}
	*/
	
	// Internal process
	private function html($str, $tags = false)
	{
		if (!count($this->tags))
		{
			$this->tags = w('br a strong h1 div span img ul ol li em table tr td th small');
		}
		
		if ($tags !== false)
		{
			$save_tags = $this->tags;
			$this->tags = (is_array($tags)) ? $tags : array($tags);
		}
		
		if (count($this->tags))
		{
			$ptags = str_replace('*', '.*?', implode('|', $this->tags));
			$ptags = '.*?';
			$str = str_replace('&quot;', '"', preg_replace('#&lt;(\/?)(' . $ptags . ')&gt;#is', '<$1$2>', $str));
			
			$str = str_replace(array('“', '”'), '"', $str);
			
			if (preg_match_all('#&lt;(' . $ptags . ') (.*?)&gt;#is', $str, $in_quotes))
			{
				$repl = array('&lt;' => '<', '&gt;' => '>', '&quot;' => '"');
				
				foreach ($in_quotes[0] as $item)
				{
					$str = preg_replace('#' . preg_quote($item, '#') . '#is', str_replace(array_keys($repl), array_values($repl), $item), $str);
				}
			}
		}
		
		if ($tags !== false)
		{
			$this->tags = $save_tags;
		}
		
		return $str;
	}
}

?>