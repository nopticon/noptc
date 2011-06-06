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

define('FTP_ASCII', 0);
define('FTP_BINARY', 1);

class ftp
{
	private $conn_id;
	
	function ftp_connect($host, $port = 21, $timeout = 10)
	{
		$this->conn_id = @ftp_connect($host, $port, $timeout);
		return $this->conn_id;
	}
	
	function ftp_login($ftp_user, $ftp_pass)
	{
		return @ftp_login($this->conn_id, $ftp_user, $ftp_pass);
	}
	
	function ftp_quit()
	{
		if ($this->conn_id)
		{
			@ftp_close($this->conn_id);
		}
		return;
	}
	
	function ftp_pwd()
	{
		return @ftp_pwd($this->conn_id);
	}
	
	function ftp_nlist($d = './')
	{
		return @ftp_nlist($this->conn_id, $d);
	}
	
	function ftp_chdir($ftp_dir)
	{
		return @ftp_chdir($this->conn_id, $ftp_dir);
	}
	
	function ftp_mkdir($ftp_dir)
	{
		return @ftp_mkdir($this->conn_id, $ftp_dir);
	}
	
	function ftp_site($cmd)
	{
		return @ftp_site($this->conn_id, $cmd);
	}
	
	function ftp_cdup()
	{
		return @ftp_cdup($this->conn_id);
	}
	
	function ftp_put($remote_file, $local_file)
	{
		if (!file_exists($local_file))
		{
			return false;
		}
		
		return @ftp_put($this->conn_id, $remote_file, $local_file, FTP_BINARY);
	}
	
	function ftp_rename($src, $dest)
	{
		return @ftp_rename($this->conn_id, $src, $dest);
	}
}

?>