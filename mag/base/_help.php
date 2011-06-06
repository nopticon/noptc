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

interface i_help
{
	public function home();
	public function faq();
	public function desk();
}

class __help extends xmd implements i_help
{
	public function __construct()
	{
		parent::__construct();
		
		$this->auth(false);
		$this->_m(_array_keys(w('faq desk')));
		
		return;
	}
	
	public function home()
	{
		$v = $this->__(array('category' => '', 'faq' => 0));
		
		if (f($v['category']))
		{
			$sql = 'SELECT *
				FROM _faq_categories
				ORDER BY cat_order';
			$category = _rowset($sql);
			
			if (!count($category))
			{
				_fatal();
			}
			
			foreach ($category as $i => $row)
			{
				if (!$i) _style('category');
				
				_style('category.row', array(
					'CAT_SUBJECT' => $row['cat_subject'],
					'CAT_ALIAS' => _link('help', $row['help_alias']))
				);
			}
		}
		
		if ($v['faq'])
		{
			$sql = 'SELECT *
				FROM _help_faq
				WHERE faq_id = ?';
			if (!$faq = _fieldrow(sql_filter($sql, $v['faq'])))
			{
				_fatal();
			}
			
			
		}
		
		return;
	}
	
	public function faq()
	{
		$this->method();
	}
	
	protected function _faq_home()
	{
		$sql = 'SELECT *
			FROM _help_cat c, _help_modules m
			WHERE c.help_module = m.module_id
			ORDER BY help_order';
		$cat = _rowset($sql);
		
		foreach ($cat as $i => $row)
		{
			if (!$i) _style('cat');
			
			_style('cat.item', array(
				'URL' => _link('help', $row['module_name']),
				'TITLE' => $row['help_es'])
			);
		}
		
		return;
	}
	
	protected function _faq_cat()
	{
		$v = $this->__(w('cat'));
		if (!$v['cat'])
		{
			_fatal();
		}
		
		$sql = 'SELECT *
			FROM _help_modules m, _help_faq f, _help_cat c
			WHERE module_name = ?
				AND m.module_id = c.help_module
				AND c.help_id = f.help_id
			ORDER BY f.faq_order, f.faq_question_es';
		$cat = _rowset(sql_filter($sql, $v['cat']));
		
		if (!count($cat))
		{
			_fatal();
		}
		
		foreach ($cat as $i => $row)
		{
			if (!$i) _style('module');
			
			_style('module.item', array(
				'URL' => _link('help', $row['faq_id']),
				'FAQ' => $row['faq_question_es'])
			);
		}
		
		$this->_faq_home();
		
		return;
	}
	
	protected function _faq_item()
	{
		$v = $this->__(array('help' => 0));
		
		$sql = 'SELECT *
			FROM _help_faq f, _help_cat c, _help_modules m
			WHERE f.faq_id = ?
				AND f.help_id = c.help_id
				AND c.help_module = m.module_id';
		if (!$faq = _fieldrow(sql_filter($sql, $v['help'])))
		{
			_fatal();
		}
		
		_style('faq', array(
			'CAT' => _link('help', $faq['module_name']),
			'QUESTION_ES' => $faq['faq_question_es'],
			'ANSWER_ES' => _message($faq['faq_answer_es']))
		);
		
		$this->_faq_home();
		
		return;
	}
	
	public function desk()
	{
		$this->method();
	}
	
	protected function _desk_home()
	{
		return;
	}
}

?>