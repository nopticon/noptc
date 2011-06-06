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

interface i_student
{
	public function create();
	public function view();
	public function search();
}

class __student extends xmd implements i_student
{
	function __construct()
	{
		parent::__construct();
		
		$this->_m(array(
			'search' => w(),
			'view' => w(),
			'create' => w()
		));
		$this->auth(false);
	}
	
	public function home()
	{
		$sql = 'SELECT *
			FROM _students s, _registrations r, _grades g, _sections e
			WHERE s.student_id = r.registration_student
				AND r.registration_grade = g.grade_id
				AND r.registration_section = e.section_id
			ORDER BY s.student_id DESC';
		$result = _rowset($sql);
		
		foreach ($result as $i => $row)
		{
			if (!$i) _style('student');
			
			_style('student.row', array(
				'CARNE' => $row['student_carne'],
				'FECHA' => $row['student_datetime'],
				'APELLIDOS' => $row['student_lastname'],
				'NOMBRES' => $row['student_firstname'],
				'GRADO' => $row['grade_name'],
				'SECCION' => $row['section_name'],
				'COMPROMISO' => _link('report', array('x1' => 'compromiso', 's' => $row['student_id'])),
				'PERFIL' => _link('student', array('view' => $row['student_id'])))
			);
		}
		return;
	}
	
	public function create()
	{
		$this->method();
	}
	
	protected function _create_home()
	{
		if (_button())
		{
			$v = $this->__(array(
				'codigo',
				'nombre',
				'apellido',
				'direccion',
				'edad',
				'sexo',
				'email',
				'telefono',
				'identificacion',
				
				'status',
				'carne',
				'carne_auto' => 0)
			);
			
			/*
			padre_nombre
			padre_apellido
			
			madre_nombre
			madre_apellido
			
			encargado_nombre
			encargado_apellido
			encargado_profesion
			encargado_labora
			encargando_labora_direccion
			
			encargado_identificacion
			encargado_emergencia
			*/
			
			$sql = 'INSERT INTO _students' . _build_array('INSERT', $v);
			$v['id'] = _sql_nextid($sql);
			
			if ($v['carne_auto'])
			{
				$v['carne'] = date('Y') . $v['id'];
				
				$sql = 'UPDATE _students SET carne = ?
					WHERE id_alumno = ?';
				_sql(sql_filter($sql, $v['carne'], $v['id']));
			}
			
			$sql_insert = '';
			
			// TODO: Build query
			$sql = 'INSERT INTO _registrations' . _build_array('INSERT', '');
		}
		
		$sql = 'SELECT grade_id, grade_name
			FROM _grades
			WHERE grade_status = 1
			ORDER BY grade_order';
		$grades = _rowset($sql);
		
		foreach ($grades as $i => $row)
		{
			if (!$i) _style('grades');
			
			_style('grades.row', array(
				'GRADE_ID' => $row['grade_id'],
				'GRADE_NAME' => $row['grade_name'])
			);
		}
		
		$sql = 'SELECT section_id, section_name
			FROM _sections
			WHERE section_grade = 1';
		$sections = _rowset($sql);
		
		foreach ($sections as $i => $row)
		{
			if (!$i) _style('sections');
			
			_style('sections.row', array(
				'SECTION_ID' => $row['section_id'],
				'SECTION_NAME' => $row['section_name'])
			);
		}
		
		return;
	}
	
	public function search()
	{
		return $this->method();
	}
	
	protected function _search_home()
	{
		if (is_post())
		{
			$v = $this->__(w('carne code firstname lastname'));
			
			if (($key = array_least_key($v)) === false) _fatal();
			
			$sql = 'SELECT student_carne, student_firstname, student_lastname
				FROM _students
				WHERE student_?? ' . "LIKE '??%'
				ORDER BY student_lastname, student_firstname";
			
			if (!$students = _rowset(sql_filter($sql, $key, $v[$key])))
			{
				_style('results_none');
			}
			
			foreach ($students as $i => $row)
			{
				if (!$i) _style('results');
				
				_style('results.row', array(
					'STUDENT_FIRSTNAME' => $row['student_firstname'],
					'STUDENT_LASTNAME' => $row['student_lastname'],
					'U_STUDENT' => _link($this->m(), array('x1' => 'view', 's' => $row['student_carne']))
				));
			}
		}
		
		return;
	}
	
	public function view()
	{
		return $this->method();
	}
	
	protected function _view_home()
	{
		$v = $this->__(w('s'));
		
		if (!$v['s']) _fatal();
		
		$sql = 'SELECT *
			FROM _students s, _gender g
			WHERE s.student_carne = ?
				AND s.student_gender = g.gender_id';
		if (!$student = _fieldrow(sql_filter($sql, $v['s'])))
		{
			_fatal();
		}
		
		_pre($student, true);
	}
}

?>