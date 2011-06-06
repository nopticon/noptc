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

class __todo extends xmd
{
	public function __construct()
	{
		parent::__construct();
	}
	
	public function home()
	{
		$lines = w();
		$this->proc('./', $lines, w('php htm css'), w('. .. .svn'));
		$this->proc(XFS, $lines, w('php htm css'), w('. .. .svn'));
		
		$total = 0;
		foreach ($lines as $row) {
			$total += $row;
		}
		$lines['total'] = $total;
		
		exit;
	}
	
	private function proc($base, &$lines, $ext, $exc)
	{
		$fp = @opendir($base);
		while ($row = @readdir($fp))
		{
			if (in_array($row, $exc) || preg_match('/.*~/i', $row)) continue;
			
			$dbase = $base . (($base != './' && $base != XFS) ? '/' : '') . $row;
			if (@is_dir($dbase)) $this->proc($dbase, $lines, $ext, $exc);
			
			$f_ext = _extension($row);
			if (is_file($dbase))
			{
				if (!isset($lines[$f_ext])) $lines[$f_ext] = 0;
				
				$jj = 0;
				foreach (@file($dbase) as $i_line => $line)
				{
					if ($p_line = strpos($line, 'TO'.'DO'))
					{
						if (!$jj)
						{
							echo '<hr /><strong>' . $dbase . '</strong><br /><br />' . "\n";
						}
						echo ($i_line + 1) . ' > ' . trim(substr($line, $p_line + 5)) . '<br />' . "\n";
						
						$jj++;
					}
				}
				
				$lines[$f_ext] += count(@file($dbase));
			}
		}
		@closedir($fp);
		return;
	}
}

?>