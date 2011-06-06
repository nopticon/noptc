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

interface i_scores
{
	public function create();
}

class __scores extends xmd implements i_scores
{
	function __construct()
	{
		parent::__construct();
		
		$this->_m(array(
			'create' => w()
		));
		$this->auth(false);
	}
	
	public function home()
	{
		$first_grade = 0;
		
		$sql = 'SELECT g.grade_id, g.grade_name, s.section_id, s.section_name
			FROM _grades g, _sections s
			WHERE g.grade_id = s.section_grade
				AND g.grade_status = 1
			ORDER BY g.grade_order, s.section_name';
		$grades = _rowset($sql);
		
		foreach ($grades as $i => $row)
		{
			if (!$first_grade)
			{
				$first_grade = $row['grade_id'];
			}
			
			if (!$i) _style('grades');
			
			_style('grades.row', array(
				'GRADE_NAME' => $row['grade_name'],
				'SECTION_ID' => $row['section_id'],
				'SECTION_NAME' => $row['section_name'])
			);
		}
		
		$sql = 'SELECT subject_id, subject_name
			FROM _subjects
			WHERE subject_grade = ?
			ORDER BY subject_order';
		$subjects = _rowset(sql_filter($sql, $first_grade));
		
		foreach ($subjects as $i => $row)
		{
			if (!$i) _style('subjects');
			
			_style('subjects.row', array(
				'SUBJECT_ID' => $row['subject_id'],
				'SUBJECT_NAME' => $row['subject_name'])
			);
		}
		
		$sql = 'SELECT *
			FROM _exams
			WHERE exam_status = 1
			ORDER BY exam_order';
		$exams = _rowset($sql);
		
		foreach ($exams as $i => $row)
		{
			if (!$i) _style('exams');
			
			_style('exams.row', array(
				'EXAM_ID' => $row['exam_id'],
				'EXAM_NAME' => $row['exam_name'])
			);
		}
		
		foreach ($this->year_list() as $i => $row)
		{
			if (!$i) _style('years');
			
			_style('years.row', array(
				'YEAR' => $row['year'],
				'OPTION' => $row['option']
			));
		}
		
		return;
	}
	
	public function create()
	{
		return $this->method();
	}
	
	protected function _create_home()
	{
		if (is_post())
		{
			//_pre('a', true);
		}
		
		$v = $this->__(array(
			'grade' => 0,
			'subject' => 0,
			'exam' => 0,
			'year' => 0)
		);
		
		$sql = 'SELECT *
			FROM _grades g, _sections s
			WHERE s.section_id = ?
				AND s.section_grade = g.grade_id';
		if (!$grade = _fieldrow(sql_filter($sql, $v['grade'])))
		{
			_fatal();
		}
		
		$sql = 'SELECT *
			FROM _subjects
			WHERE subject_id = ?';
		if (!$subject = _fieldrow(sql_filter($sql, $v['subject'])))
		{
			_fatal();
		}
		
		$sql = 'SELECT *
			FROM _exams
			WHERE exam_id = ?';
		if (!$exam = _fieldrow(sql_filter($sql, $v['exam'])))
		{
			_fatal();
		}
		
		if (!$this->check_year($v['year']))
		{
			_fatal();
		}
		
		$sql = 'SELECT s.student_id, s.student_carne, s.student_firstname, s.student_lastname
			FROM _students s, _registrations r
			WHERE r.registration_grade = ?
				AND r.registration_section = ?
				AND r.registration_year = ?
				AND r.registration_student = s.student_id
			ORDER BY s.student_lastname, s.student_firstname';
		if (!$students = _rowset(sql_filter($sql, $grade['grade_id'], $grade['section_id'], $v['year'])))
		{
			_style('students_none');
		}
		
		$sql = 'SELECT t.student_id, s.score_points
			FROM _scores s, _students t, _registrations r
			WHERE s.score_grade = ?
				AND r.registration_section = ?
				AND s.score_subject = ?
				AND s.score_exams = ?
				AND r.registration_year = ?
				AND s.score_student = t.student_id
				AND s.score_student = r.registration_student
				AND s.score_grade = r.registration_grade
			ORDER BY t.student_lastname, t.student_firstname';
		$scores = _rowset(sql_filter($sql, $grade['grade_id'], $grade['section_id'], $v['subject'], $v['exam'], $v['year']), 'student_id', 'score_points');
		
		foreach ($students as $i => $row)
		{
			if (!$i) _style('students');
			
			_style('students.row', array(
				'ID' => $row['student_id'],
				'CARNE' => $row['student_carne'],
				'FIRSTNAME' => $row['student_firstname'],
				'LASTNAME' => $row['student_lastname'])
			);
			
			if (!isset($scores[$row['student_id']]))
			{
				_style('students.row.input');
			}
			else
			{
				_style('students.row.text', array('POINTS' => $scores[$row['student_id']]));
			}
		}
		
		return;
	}
}

?>