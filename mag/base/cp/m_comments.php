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

class comments extends xmd
{
	public function __construct()
	{
		parent::__construct();
		
		$this->_m(array(
			'emoticons' => w('add edit delete'),
			'help' => w('add edit delete'))
		);
		
		return;
	}
	
	function _emoticons_setup()
	{
		
	}
	
	function _help_setup()
	{
		
	}
	
	function home()
	{
		global $bio;
		
		_style('menu');
		foreach ($this->_m() as $module => $void)
		{
			_style('menu.item', array(
				'URL' => _link_control('comments', array('mode' => $module)),
				'TITLE' => _lang('CONTROL_COMMENTS_' . strtoupper($module)))
			);
		}
		
		return;
	}
	
	function help()
	{
		$this->call_method();
	}
	
	function _help_home()
	{
		global $bio;
		
		$ha = $bio->v('auth_comments');
		
		if ($ha)
		{
			$ha_add = $bio->v('auth_help_create');
			$ha_edit = $bio->v('auth_help_modufy');
			$ha_delete = $bio->v('auth_help_remove');
		}
		
		$sql = 'SELECT c.*, m.*
			FROM _help_cat c, _help_modules m
			WHERE c.help_module = m.module_id
			ORDER BY c.help_order';
		$cat = _rowset($sql, 'help_id');
		
		$sql = 'SELECT *
			FROM _help_faq';
		$faq = _rowset($sql, 'faq_id');
		
		//
		// Loop
		//
		foreach ($cat as $help_id => $cdata)
		{
			_style('cat', array(
				'HELP_ES' => $cdata['help_es'],
				'HELP_EN' => $cdata['help_en'],
				
				'HELP_EDIT' => _link_control('comments', array('mode' => $this->mode)),
				'HELP_UP' => _link_control('comments', array('mode' => $this->mode)),
				'HELP_DOWN' => _link_control('comments', array('mode' => $this->mode)))
			);
			
			if ($ha_edit)
			{
				_style('cat.edit', array(
					'URL' => _link_control('comments', array('mode' => $this->mode, 'manage' => 'edit', 'sub' => 'cat', 'id' => $help_id)),
					'UP' => _link_control('comments', array('mode' => $this->mode, 'manage' => 'edit', 'sub' => 'cat', 'id' => $help_id, 'order' => '_15')),
					'DOWN' => _link_control('comments', array('mode' => $this->mode, 'manage' => 'edit', 'sub' => 'cat', 'id' => $help_id, 'order' => '15')))
				);
			}
			
			if ($ha_delete)
			{
				_style('cat.delete', array(
					'URL' => _link_control('comments', array('mode' => $this->mode, 'manage' => 'delete', 'sub' => 'cat', 'id' => $help_id)))
				);
			}
			
			foreach ($faq as $faq_id => $fdata)
			{
				if ($help_id != $fdata['help_id'])
				{
					continue;
				}
				
				_style('cat.faq', array(
					'QUESTION_ES' => $fdata['faq_question_es'],
					'ANSWER_ES' => _message($fdata['faq_answer_es']))
				);
				
				if ($ha_edit)
				{
					_style('cat.faq.edit', array(
						'URL' => _link_control('comments', array('mode' => $this->mode, 'manage' => 'edit', 'sub' => 'faq', 'id' => $fdata['faq_id'])))
					);
				}
				
				if ($ha_delete)
				{
					_style('cat.faq.delete', array(
						'URL' => _link_control('comments', array('mode' => $this->mode, 'manage' => 'delete', 'sub' => 'faq', 'id' => $fdata['faq_id'])))
					);
				}
			}
		}
		
		if ($ha_add)
		{
			_style('add', array(
				'URL' => _link_control('comments', array('mode' => $this->mode, 'manage' => 'add')))
			);
		}
		
		$this->nav();
		
		return;
	}
	
	function _help_add()
	{
		global $bio, $core;
		
		$error = array();
		$sub = $this->control->get_var('sub', '');
		$submit = (isset($_POST['submit'])) ? true : false;
		
		$menu = array('module' => 'CONTROL_COMMENTS_HELP_MODULE', 'cat' => 'CATEGORY', 'faq' => 'FAQ');
		
		switch ($sub)
		{
			case 'cat':
				$module_id = 0;
				$help_es = '';
				$help_en = '';
				break;
			case 'faq':
				$help_id = 0;
				$question_es = '';
				$question_en = '';
				$answer_es = '';
				$answer_en = '';
				break;
			case 'module':
				$module_name = '';
				break;
			default:
				_style('menu');
				
				foreach ($menu as $url => $name)
				{
					_style('menu.item', array(
						'URL' => _link_control('comments', array('mode' => $this->mode, 'manage' => $this->manage, 'sub' => $url)),
						'TITLE' => _lang($name))
					);
				}
				break;
		}
		
		if ($submit)
		{
			switch ($sub)
			{
				case 'cat':
					$module_id = $this->control->get_var('module_id', 0);
					$help_es = $this->control->get_var('help_es', '');
					$help_en = $this->control->get_var('help_en', '');
					
					if (empty($help_es) || empty($help_en))
					{
						$error[] = 'CONTROL_COMMENTS_HELP_EMPTY';
					}
					
					// Insert
					if (!sizeof($error))
					{
						$sql_insert = array(
							'help_module' => (int) $module_id,
							'help_es' => $help_es,
							'help_en' => $help_en
						);
						
						$sql = 'INSERT INTO _help_cat' . $db->sql_build_array('INSERT', $sql_insert);
					}
					break;
				case 'faq':
					$help_id = $this->control->get_var('help_id', 0);
					$question_es = $this->control->get_var('question_es', '');
					$question_en = $this->control->get_var('question_en', '');
					$answer_es = $this->control->get_var('answer_es', '');
					$answer_en = $this->control->get_var('answer_en', '');
					
					if (empty($question_es) || empty($question_en) || empty($answer_es) || empty($answer_en))
					{
						$error[] = 'CONTROL_COMMENTS_HELP_EMPTY';
					}
					
					if (!sizeof($error))
					{
						$sql_insert = array(
							'help_id' => $help_id,
							'faq_question_es' => $question_es,
							'faq_question_en' => $question_en,
							'faq_answer_es' => $answer_es,
							'faq_answer_en' => $answer_en
						);
						$sql = 'INSERT INTO _help_faq' . $db->sql_build_array('INSERT', $sql_insert);
					}
					break;
				case 'module':
					$module_name = $this->control->get_var('module_name', '');
					
					if (empty($module_name))
					{
						$error[] = 'CONTROL_COMMENTS_HELP_EMPTY';
					}
					
					if (!sizeof($error))
					{
						$sql_insert = array(
							'module_name' => $module_name
						);
						$sql = 'INSERT INTO _help_modules' . $db->sql_build_array('INSERT', $sql_insert);
					}
					break;
			}
			
			if (!sizeof($error))
			{
				$db->sql_query($sql);
				
				$cache->unload('help_cat', 'help_faq', 'help_modules');
				
				redirect(_link_control('comments', array('mode' => $this->mode)));
			}
			else
			{
				_style('error', array(
					'MESSAGE' => parse_error($error))
				);
			}
		}
		
		$this->nav();
		$this->control->set_nav(array('mode' => $this->mode, 'manage' => $this->manage), 'CONTROL_ADD');
		$this->control->set_nav(array('mode' => $this->mode, 'manage' => $this->manage, 'sub' => $sub), _lang($menu[$sub]));
		
		$sv = array(
			'SUB' => $sub,
			'S_HIDDEN' => _hidden(array('module' => $this->control->module, 'mode' => $this->mode, 'manage' => $this->manage, 'sub' => $sub))
		);
		
		switch ($sub)
		{
			case 'cat':
				$sql = 'SELECT *
					FROM _help_modules
					ORDER BY module_id';
				$result = $db->sql_query($sql);
				
				$select_mod = '';
				while ($row = $db->sql_fetchrow($result))
				{
					$selected = ($row['module_id'] == $module_id);
					$select_mod .= '<option' . (($selected) ? ' class="bold"' : '') . ' value="' . $row['module_id'] . '"' . (($selected) ? ' selected' : '') . '>' . $row['module_name'] . '</option>';
				}
				$db->sql_freeresult($result);
				
				$sv += array(
					'MODULE' => $select_mod,
					'HELP_ES' => $help_es,
					'HELP_EN' => $help_en
				);
				break;
			case 'faq':
				$sql = 'SELECT *
					FROM _help_cat
					ORDER BY help_id';
				$result = $db->sql_query($sql);
				
				$select_cat = '';
				while ($row = $db->sql_fetchrow($result))
				{
					$selected = ($row['help_id'] == $help_id);
					$select_cat .= '<option' . (($selected) ? ' class="bold"' : '') . ' value="' . $row['help_id'] . '"' . (($selected) ? ' selected' : '') . '>' . $row['help_es'] . ' | ' . $row['help_en'] . '</option>';
				}
				$db->sql_freeresult($result);
				
				$sv += array(
					'CATEGORY' => $select_cat,
					'QUESTION_ES' => $question_es,
					'QUESTION_EN' => $question_en,
					'ANSWER_ES' => $answer_es,
					'ANSWER_EN' => $answer_en
				);
				break;
			case 'module':
				$template_vars += array(
					'MODULE_NAME' => $module_name
				);
				break;
		}
		
		v_style($sv);
	}
	
	function _help_edit_move()
	{
		global $db;
		
		$sql = 'SELECT *
			FROM _help_cat
			ORDER BY help_order';
		$result = $db->sql_query($sql);
		
		$i = 10;
		while ($row = $db->sql_fetchrow($result))
		{
			$sql = 'UPDATE _help_cat
				SET help_order = ' . (int) $i . '
				WHERE help_id = ' . (int) $row['help_id'];
			$db->sql_query($sql);
			
			$i += 10;
		}
		$db->sql_freeresult($result);
	}
	
	function _help_edit()
	{
		global $bio, $core;
		
		$error = array();
		$sub = $this->control->get_var('sub', '');
		$id = $this->control->get_var('id', 0);
		$submit = (isset($_POST['submit'])) ? true : false;
		
		switch ($sub)
		{
			case 'cat':
				$sql = 'SELECT c.*, m.*
					FROM _help_cat c, _help_modules m
					WHERE c.help_id = ' . (int) $id . '
						AND c.help_module = m.module_id';
				$result = $db->sql_query($sql);
				
				if (!$cat_data = $db->sql_fetchrow($result))
				{
					fatal_error();
				}
				$db->sql_freeresult($result);
				
				$order = $this->control->get_var('order', '');
				if (!empty($order))
				{
					if (preg_match('/_([0-9]+)/', $order))
					{
						$sig = '-';
						$order = str_replace('_', '', $order);
					}
					else
					{
						$sig = '+';
					}
					
					$sql = 'UPDATE _help_cat
						SET help_order = help_order ' . $sig . ' ' . (int) $order . '
						WHERE help_id = ' . (int) $id;
					$db->sql_query($sql);
					
					$this->_help_edit_move();
					
					$cache->unload('help_cat');
					
					redirect(_link_control('comments', array('mode' => $this->mode)));
				} // IF order
				
				$module_id = $cat_data['help_module'];
				$help_es = $cat_data['help_es'];
				$help_en = $cat_data['help_en'];
				break;
			case 'faq':
				$sql = 'SELECT *
					FROM _help_faq
					WHERE faq_id = ' . (int) $id;
				$result = $db->sql_query($sql);
				
				if (!$faq_data = $db->sql_fetchrow($result))
				{
					fatal_error();
				}
				$db->sql_freeresult($result);
				
				$question_es = $faq_data['faq_question_es'];
				$question_en = $faq_data['faq_question_en'];
				$answer_es = $faq_data['faq_answer_es'];
				$answer_en = $faq_data['faq_answer_en'];
				$help_id = $faq_data['help_id'];
				break;
			default:
				redirect(_link_control('comments', array('mode' => $this->mode)));
				break;
		}
		
		// IF submit
		if ($submit)
		{
			switch ($sub)
			{
				case 'cat':
					$module_id = $this->control->get_var('module_id', 0);
					$help_es = $this->control->get_var('help_es', '');
					$help_en = $this->control->get_var('help_en', '');
					
					if (empty($help_es) || empty($help_en))
					{
						$error[] = 'CONTROL_COMMENTS_HELP_EMPTY';
					}
					
					// Update
					if (!sizeof($error))
					{
						$sql_update = array(
							'help_es' => $help_es,
							'help_en' => $help_en,
							'help_module' => (int) $module_id
						);
						
						$sql = 'UPDATE _help_cat
							SET ' . $db->sql_build_array('UPDATE', $sql_update) . '
							WHERE help_id = ' . (int) $id;
						$db->sql_query($sql);
						
						$cache->unload('help_cat');
						
						redirect(_link_control('comments', array('mode' => $this->mode)));
					}
					break;
				case 'faq':
					$question_es = $this->control->get_var('question_es', '');
					$question_en = $this->control->get_var('question_en', '');
					$answer_es = $this->control->get_var('answer_es', '');
					$answer_en = $this->control->get_var('answer_en', '');
					$help_id = $this->control->get_var('help_id', 0);
					
					if (empty($question_es) || empty($question_en) || empty($answer_es) || empty($answer_en))
					{
						$error[] = 'CONTROL_COMMENTS_HELP_EMPTY';
					}
					
					if (!sizeof($error))
					{
						$sql = 'SELECT *
							FROM _help_cat
							WHERE help_id = ' . (int) $help_id;
						$result = $db->sql_query($sql);
						
						if (!$cat_data = $db->sql_fetchrow($result))
						{
							$error[] = 'CONTROL_COMMENTS_HELP_NOCAT';
						}
					}
					
					// Update
					if (!sizeof($error))
					{
						$sql_update = array(
							'help_id' => (int) $help_id,
							'faq_question_es' => $question_es,
							'faq_question_en' => $question_en,
							'faq_answer_es' => $answer_es,
							'faq_answer_en' => $answer_en
						);
						
						$sql = 'UPDATE _help_faq
							SET ' . $db->sql_build_array('UPDATE', $sql_update) . '
							WHERE faq_id = ' . (int) $id;
						$db->sql_query($sql);
						
						$cache->unload('help_faq');
						
						redirect(_link_control('comments', array('mode' => $this->mode)));
					}
					break;
			} // switch
			
			if (sizeof($error))
			{
				_style('error', array(
					'MESSAGE' => parse_error($error))
				);
			}
		}
		
		$this->nav();
		$this->control->set_nav(array('mode' => $this->mode, 'manage' => $this->manage, 'sub' => $sub, 'id' => $id), 'CONTROL_EDIT');
		
		$template_vars = array(
			'SUB' => $sub,
			'S_HIDDEN' => _hidden(array('module' => $this->control->module, 'mode' => $this->mode, 'manage' => $this->manage, 'sub' => $sub, 'id' => $id))
		);
		
		switch ($sub)
		{
			case 'cat':
				$sql = 'SELECT *
					FROM _help_modules
					ORDER BY module_id';
				$result = $db->sql_query($sql);
				
				$select_mod = '';
				while ($row = $db->sql_fetchrow($result))
				{
					$selected = ($row['module_id'] == $module_id);
					$select_mod .= '<option' . (($selected) ? ' class="bold"' : '') . ' value="' . $row['module_id'] . '"' . (($selected) ? ' selected' : '') . '>' . $row['module_name'] . '</option>';
				}
				$db->sql_freeresult($result);
				
				$sv += array(
					'MODULE' => $select_mod,
					'HELP_ES' => $help_es,
					'HELP_EN' => $help_en
				);
				break;
			case 'faq':
				$sql = 'SELECT *
					FROM _help_cat
					ORDER BY help_id';
				$result = $db->sql_query($sql);
				
				$select_cat = '';
				while ($row = $db->sql_fetchrow($result))
				{
					$selected = ($row['help_id'] == $help_id);
					$select_cat .= '<option' . (($selected) ? ' class="bold"' : '') . ' value="' . $row['help_id'] . '"' . (($selected) ? ' selected' : '') . '>' . $row['help_es'] . ' | ' . $row['help_en'] . '</option>';
				}
				$db->sql_freeresult($result);
				
				$sv += array(
					'CATEGORY' => $select_cat,
					'QUESTION_ES' => $question_es,
					'QUESTION_EN' => $question_en,
					'ANSWER_ES' => $answer_es,
					'ANSWER_EN' => $answer_en
				);
				break;
		}
		
		v_style($sv);
		
		return;
	}
	
	function _help_delete()
	{
		
	}
}

?>
