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

class __home extends xmd
{
	function __construct()
	{
		parent::__construct();
		
		$this->_m(_array_keys(w('email')));
		$this->auth(false);
	}
	
	public function home()
	{
		global $user;
		
		$v = $this->__(array('faddr', 'nickname', 'address', 'birthday' => array('' => 0), 'country' => 0));
		
		foreach (w('year month day') as $name)
		{
			$v['birthday'][$name] = (isset($v['birthday'][$name])) ? $v['birthday'][$name] : '';
		}
		
		if (f($v['faddr']))
		{
			$v['address'] = $v['faddr'];
		}
		
		if (_button())
		{
			if (!f($v['address']) || !f($v['nickname']))
			{
				$this->error('COMPLETE_FIELDS');
			}
			
			if (f($v['address']))
			{
				$sql = 'SELECT user_id
					FROM _members
					WHERE user_email = ?';
				if (_field(sql_filter($sql, $v['address']), 'user_id', 0))
				{
					$this->error('EMAIL_EXISTS');
				}
				
				if (!preg_match('/^[a-z0-9&\'\.\-_\+]+@[a-z0-9\-]+\.([a-z0-9\-]+\.)*?[a-z]+$/is', $v['address']))
				{
					$this->error('EMAIL_BAD');
				}
			}
			
			$sql = 'SELECT country_id
				FROM _countries
				WHERE country_id = ?';
			if (!_field(sql_filter($sql, $v['country']), 'country_id', 0))
			{
				$this->error('NO_COUNTRY');
			}
			
			if (!$this->errors())
			{
				$v['birthday'] = _zero($v['birthday']['year']) . _zero($v['birthday']['month']) . _zero($v['birthday']['day']);
				
				$sql_insert = array(
					'type' => 1,
					'active' => 0,
					'username' => $v['nickname'],
					'password' => '',
					'registration' => time(),
					'lastvisit' => '',
					'lastpage' => '',
					'country' => $v['country'],
					'email' => $v['address'],
					'birthday' => $v['birthday'],
					'gender' => 0,
					'dateformat' => 'd M Y H:i',
					'timezone' => 0,
					'dst' => 0
				);
				$sql = 'INSERT INTO _members' . _build_array('INSERT', prefix('user', $sql_insert));
				_sql($sql);
				
				require(XFS . 'core/emailer.php');
				$emailer = new emailer();
				
				$emailer->format('plain');
				$emailer->from('TWC Kaulitz <twc_princess@twckaulitz.com>');
				$emailer->use_template('welcome');
				$emailer->email_address($v['address']);
	
				$emailer->assign_vars(array(
					'USERNAME' => $v['nickname'])
				);
				$emailer->send();
				$emailer->reset();
				
				//
				redirect('http://www.twckaulitz.com/', false);
			}
		}
		
		if ($this->errors())
		{
			_style('errors', array(
				'MSG' => $this->get_errors())
			);
		}
		
		for ($i = 1; $i < 32; $i++)
		{
			_style('days', array(
				'DAY' => $i)
			);
		}
		
		$months = w('Enero Febrero Marzo Abril Mayo Junio Julio Agosto Septiembre Octubre Noviembre Diciembre');
		
		foreach ($months as $i => $row)
		{
			_style('months', array(
				'VALUE' => ($i + 1),
				'MONTH' => $row)
			);
		}
		
		for ($i = 2005; $i > 1899; $i--)
		{
			_style('years', array(
				'YEAR' => $i)
			);
		}
		
		//
		// GeoIP
		//
		include(XFS . 'core/geoip.php');
		$gi = geoip_open(XFS . 'core/GeoIP.dat', GEOIP_STANDARD);
		$geoip_code = strtolower(geoip_country_code_by_addr($gi, $user->ip));

		$sql = 'SELECT *
			FROM _countries
			ORDER BY country_name';
		$countries = _rowset($sql);
		
		$codes = w();
		foreach ($countries as $row)
		{
			$codes[$row['country_short']] = $row['country_id'];
			
			_style('countries', array(
				'VALUE' => $row['country_id'],
				'NAME' => $row['country_name'])
			);
		}
		
		if (!$v['country'])
		{
			$v['country'] = (isset($codes[$geoip_code])) ? $codes[$geoip_code] : $codes['gt'];
		}
		
		v_style(array(
			'NICKNAME' => $v['nickname'],
			'ADDRESS' => $v['address'],
			'COUNTRY' => $v['country'],
			'BIRTHDAY_YEAR' => $v['birthday']['year'],
			'BIRTHDAY_MONTH' => $v['birthday']['month'],
			'BIRTHDAY_DAY' => $v['birthday']['day'])
		);
		
		return;
	}
}

?>