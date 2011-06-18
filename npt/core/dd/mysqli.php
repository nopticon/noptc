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
//if (!defined('XFS')) exit;

class db
{
	private $m;
	private $result;
	private $history = array();
	private $row = array();
	private $rowset = array();
	private $total = 0;
	private $return_on_error = false;
	
	public function __construct($d = false)
	{
		$di = connect_driver($d);
		if (!f($di['server']) || !f($di['user']) || !f($di['name']))
		{
			exit();
		}
		
		ob_start();
		$this->m = new mysqli($di['server'], $di['user'], $di['ukey'], $di['name']);
		ob_end_clean();
		
		if ($this->m->connect_error)
		{
			exit('330');
		}
		unset($di);
		
		return true;
	}
	
	public function __destruct()
	{
		return $this->sql_close();
	}
	
	function sql_close()
	{
		if (!$this->m)
		{
			return false;
		}
		
		if ($this->query_result && @is_resource($this->query_result))
		{
			@mysql_free_result($this->query_result);
		}
		
		if (is_resource($this->m))
		{
			return $this->m->close();
		}
		
		return false;
	}
	
	function sql_query($query = '', $transaction = false)
	{
		if (is_array($query))
		{
			foreach ($query as $sql)
			{
				$this->sql_query($sql);
			}
			return;
		}
		
		// Remove any pre-existing queries
		unset($this->query_result);
		
		if (f($query))
		{
			$this->num_queries++;
			$this->history[] = $query;
			
			if (!$this->result = $this->m->query($query))
			{
				$this->sql_error($query);
			}
			
			if (!$this->query_result = @mysql_query($query, $this->db_connect_id))
			{
				$this->sql_error($query);
			}
		}
		
		if ($this->query_result)
		{
			$this->_log($query);
			unset($this->row[$this->query_result], $this->rowset[$this->query_result]);
			
			return $this->query_result;
		}
		
		return false;
	}
	
	function sql_query_limit($query, $total, $offset = 0)
	{
		if (!f($query))
		{
			return false;
		}
		
		// if $total is set to 0 we do not want to limit the number of rows
		if (!$total)
		{
			$total = -1;
		}
		
		$query .= "\n LIMIT " . ((f($offset)) ? $offset . ', ' . $total : $total);
		return $this->sql_query($query);
	}
	
	function _sql_transaction($status = 'begin')
	{
		switch ($status)
		{
			case 'begin':
				return @mysql_query('BEGIN', $this->db_connect_id);
				break;
			case 'commit':
				return @mysql_query('COMMIT', $this->db_connect_id);
				break;
			case 'rollback':
				return @mysql_query('ROLLBACK', $this->db_connect_id);
				break;
		}
		
		return true;
	}
	
	// Idea for this from Ikonboard
	function sql_build_array($query, $assoc_ary = false, $update_field = false)
	{
		if (!is_array($assoc_ary))
		{
			return false;
		}
		
		$fields = w();
		$values = w();
		
		switch ($query)
		{
			case 'INSERT':
				foreach ($assoc_ary as $key => $var)
				{
					$fields[] = $key;
					
					if (is_null($var))
					{
						$values[] = 'NULL';
					}
					elseif (is_string($var))
					{
						$values[] = "'" . $this->sql_escape($var) . "'";
					}
					else
					{
						$values[] = (is_bool($var)) ? intval($var) : $var;
					}
				}
				
				$query = ' (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $values) . ')';
				break;
			case 'UPDATE':
			case 'SELECT':
				$values = w();
				
				foreach ($assoc_ary as $key => $var)
				{
					if (is_null($var))
					{
						$values[] = "$key = NULL";
					}
					elseif (is_string($var))
					{
						if ($update_field && strpos($var, $key) !== false)
						{
							$values[] = $key . ' = ' . $this->sql_escape($var);
						}
						else
						{
							$values[] = "$key = '" . $this->sql_escape($var) . "'";
						}
					}
					else
					{
						$values[] = (is_bool($var)) ? "$key = " . intval($var) : "$key = $var";
					}
				}
				$query = implode(($query == 'UPDATE') ? ', ' : ' AND ', $values);
				break;
		}
		
		return $query;
	}
	
	function sql_num_queries()
	{
		return $this->num_queries;
	}
	
	function sql_numrows($query_id = 0)
	{
		if (!$query_id)
		{
			$query_id = $this->query_result;
		}
		
		return ($query_id) ? @mysql_num_rows($query_id) : false;
	}
	
	function sql_affectedrows()
	{
		return ($this->db_connect_id) ? @mysql_affected_rows($this->db_connect_id) : false;
	}
	
	function sql_numfields($query_id = 0)
	{
		if (!$query_id)
		{
			$query_id = $this->query_result;
		}
		
		return ($query_id) ? @mysql_num_fields($query_id) : false;
	}
	
	function sql_fieldname($offset, $query_id = 0)
	{
		if (!$query_id)
		{
			$query_id = $this->query_result;
		}
		
		return ($query_id) ? @mysql_field_name($query_id, $offset) : false;
	}
	
	function sql_fieldtype($offset, $query_id = 0)
	{
		if (!$query_id)
		{
			$query_id = $this->query_result;
		}
		
		return ($query_id) ? @mysql_field_type($query_id, $offset) : false;
	}
	function sql_fetchrow($query_id = 0, $result_type = MYSQL_BOTH)
	{
		if (!$query_id)
		{
			$query_id = $this->query_result;
		}
		if (!$query_id)
		{
			return false;
		}
		
		$this->row['' . $query_id . ''] = @mysql_fetch_array($query_id, $result_type);
		return @$this->row['' . $query_id . ''];
	}
	
	function sql_fetchrowset($query_id = 0, $result_type = MYSQL_BOTH)
	{
		if (!$query_id)
		{
			$query_id = $this->query_result;
		}
		
		if (!$query_id)
		{
			return false;
		}
		
		unset($this->rowset[$query_id]);
		unset($this->row[$query_id]);
		$result = w();
		
		while ($this->rowset['' . $query_id . ''] = @mysql_fetch_array($query_id, $result_type))
		{
			$result[] = $this->rowset['' . $query_id . ''];
		}
		return $result;
	}
	
	function sql_fetchfield($field, $rownum = -1, $query_id = 0)
	{
		if (!$query_id)
		{
			$query_id = $this->query_result;
		}
		
		if (!$query_id)
		{
			return false;
		}
		
		if ($rownum > -1)
		{
			$result = @mysql_result($query_id, $rownum, $field);
		}
		else
		{
			if (empty($this->row[$query_id]) && empty($this->rowset[$query_id]))
			{
				if ($this->sql_fetchrow())
				{
					$result = $this->row['' . $query_id . ''][$field];
				}
			}
			else
			{
				if ($this->rowset[$query_id])
				{
					$result = $this->rowset[$query_id][0][$field];
				}
				elseif ($this->row[$query_id])
				{
					$result = $this->row[$query_id][$field];
				}
			}
		}
		return (isset($result)) ? $result : false;
	}
	
	function sql_rowseek($rownum, $query_id = 0)
	{
		if (!$query_id)
		{
			$query_id = $this->query_result;
		}
		
		return ($query_id) ? @mysql_data_seek($query_id, $rownum) : false;
	}
	
	function sql_nextid()
	{
		return ($this->db_connect_id) ? @mysql_insert_id($this->db_connect_id) : false;
	}
	
	function sql_freeresult($query_id = false)
	{
		if (!$query_id)
		{
			$query_id = $this->query_result;
		}
		
		if (!$query_id)
		{
			return false;
		}
		
		unset($this->row[$query_id]);
		unset($this->rowset[$query_id]);
		$this->query_result = false;
		
		@mysql_free_result($query_id);
		return true;
	}
	
	function sql_escape($msg)
	{
		return mysql_real_escape_string($msg, $this->db_connect_id);
	}
	
	//
	function sql_cache($a_sql, $sid = '', $private = true)
	{
		global $user;
		
		$filter_values = array($sid);
		
		$sql = 'SELECT cache_query
			FROM _search_cache
			WHERE cache_sid = ?';
		if ($private)
		{
			$sql .= ' AND cache_uid = ?';
			$filter_values[] = $user->v('user_id');
		}
		
		$query = _field(sql_filter($sql, $filter_values), 'cache_query', '');
		
		if (f($sid) && !f($query))
		{
			_fatal();
		}
		
		if (!f($query) && f($a_sql))
		{
			$sid = md5(unique_id());
			
			$insert = array(
				'cache_sid' => $sid,
				'cache_query' => $a_sql,
				'cache_uid' => $user->v('user_id'),
				'cache_time' => time()
			);
			$sql = 'INSERT INTO _search_cache' . _build_array('INSERT', $insert);
			_sql($sql);
			
			$query = $a_sql;
		}
		
		$all_rows = 0;
		if (!empty($query))
		{
			$result = $this->sql_query($query);
			
			$all_rows = $this->sql_numrows($result);
			$this->sql_freeresult($result);
		}
		
		$has_limit = false;
		if (preg_match('#LIMIT ([0-9]+)(\, ([0-9]+))?#is', $query, $limits))
		{
			$has_limit = $limits[1];
		}
		
		return array('sid' => $sid, 'query' => $query, 'limit' => $has_limit, 'total' => $all_rows);
	}
	
	function sql_cache_limit(&$arr, $start, $end = 0)
	{
		if ($arr['limit'] !== false)
		{
			$arr['query'] = preg_replace('#(LIMIT) ' . $arr['limit'] . '#is', '\\1 ' . $start, $arr['query']);
		}
		else
		{
			$arr['query'] .= ' LIMIT ' . $start . (($end) ? ', ' . $end : '');
		}
		
		return;
	}
	
	function sql_history()
	{
		return $this->history;
	}
	
	function _log($action, $uid = false)
	{
		$method = preg_replace('#^(INSERT|UPDATE|DELETE) (.*?)$#is', '\1', $action);
		$method = strtolower($method);
		
		if (!in_array($method, w('insert update delete')))
		{
			return;
		}
		
		if (!$whitelist = get_file('./base/sql_history'))
		{
			return;
		}
		
		if (!count($whitelist))
		{
			return;
		}
		
		$action = str_replace(array("\n", "\t", "\r"), array('', '', ' '), $action);
		$table = preg_replace('#^(INSERT\ INTO|UPDATE|DELETE\ FROM) (\_[a-z\_]+) (.*?)$#is', '\2', $action);
		
		if (!in_array($table, $whitelist))
		{
			return;
		}
		
		$actions = '';
		switch ($method)
		{
			case 'insert':
				if (!preg_match('#^INSERT INTO (\_[a-z\_]+) \((.*?)\) VALUES \((.*?)\)$#is', $action, $s_action))
				{
					return;
				}
				
				$keys = array_map('trim', explode(',', $s_action[2]));
				$values = array_map('trim', explode(',', $s_action[3]));
				
				foreach ($values as $i => $row)
				{
					$values[$i] = preg_replace('#^\'(.*?)\'$#i', '\1', $row);
				}
				
				if (count($keys) != count($values))
				{
					return;
				}
				
				$query = array(
					'table' => $s_action[1],
					'query' => array_combine($keys, $values)
				);
				break;
			case 'update':
				if (!preg_match('#^UPDATE (\_[a-z\_]+) SET (.*?) WHERE (.*?)$#is', $action, $s_action))
				{
					return;
				}
				
				$all = array(
					'set' => array_map('trim', explode(',', $s_action[2])),
					'where' => array_map('trim', explode('AND', $s_action[3]))
				);
				
				foreach ($all as $j => $v)
				{
					foreach ($v as $i => $row)
					{
						$v_row = array_map('trim', explode('=', $row));
						
						$all[$j][$v_row[0]] = preg_replace('#^\'(.*?)\'$#i', '\1', $v_row[1]);
						unset($all[$j][$i]);
					}
				}
				
				$query = array(
					'table' => $s_action[1],
					'set' => $all['set'],
					'where' => $all['where']
				);
				break;
			case 'delete':
				if (!preg_match('#^DELETE FROM (\_[a-z\_]+) WHERE (.*?)$#is', $action, $s_action))
				{
					return;
				}
				
				$all = array('where' => array_map('trim', explode('AND', $s_action[2])));
				
				foreach ($all as $j => $v)
				{
					foreach ($v as $i => $row)
					{
						$v_row = array_map('trim', explode('=', $row));
						
						$all[$j][$v_row[0]] = preg_replace('#^\'(.*?)\'$#i', '\1', $v_row[1]);
						unset($all[$j][$i]);
					}
				}
				
				$query = array(
					'table' => $s_action[1],
					'where' => $all['where']
				);
				break;
		}
		
		global $user;
		
		$sql_insert = array(
			'time' => time(),
			'uid' => $user->v('user_id'),
			'method' => $method,
			'actions' => json_encode($query)
		);
		$sql = 'INSERT INTO _log' . _build_array('INSERT', prefix('log', $sql_insert));
		_sql($sql);
		
		return;
	}
	
	function sql_error($sql = '')
	{
		$sql_error = @mysql_error($this->db_connect_id);
		$sql_errno = @mysql_errno($this->db_connect_id);
		
		if (!$this->return_on_error)
		{
			_fatal(507, '', '', array('sql' => $sql, 'message' => $sql_error), $sql_errno);
		}
		
		return array('message' => $sql_error, 'code' => $sql_errno);
	}
}

?>