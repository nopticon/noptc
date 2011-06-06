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

if (class_exists('_mail'))
{
	return;
}

class _mail
{
	protected $all;
	protected $data = array();
	
	protected function parse_header($header)
	{
		$last_header = '';
		$parsed_header = array();
		for ($j = 0, $end = count($header); $j < $end; $j++)
		{
			$hd = split(':', $header[$j], 2);
			if (preg_match_all("/\s/", $hd[0], $matches) || !isset($hd[1]) || !$hd[1])
			{
				if ($last_header)
				{
					$parsed_header[$last_header] .= "\r\n" . trim($header[$j]);
				}
			}
			else
			{
				$last_header = strtolower($hd[0]);
				if (!isset($parsed_header[$last_header]))
				{
					$parsed_header[$last_header] = '';
				}
				$parsed_header[$last_header] .= (($parsed_header[$last_header]) ? "\r\n" : '') . trim($hd[1]);
			}
		}
		
		foreach ($parsed_header as $hd_name => $hd_content)
		{
			$start_enc_tag = $stop_enc_tag = 0;
			$pre_text = $enc_text = $post_text = "";
			
			while(1)
			{
				if (strstr($hd_content, '=?') && strstr($hd_content, '?=') && substr_count($hd_content,'?') > 3)
				{
					$start_enc_tag = strpos($hd_content, '=?');
					$pre_text = substr($hd_content, 0, $start_enc_tag);
					do
					{
						$stop_enc_tag = ($stop_enc_tag > strlen($hd_content)) ? 0 : $stop_enc_tag;
						$stop_enc_tag = strpos($hd_content, '?=', $stop_enc_tag) + 2;
						$enc_text = substr($hd_content, $start_enc_tag, $stop_enc_tag);
					}
					while (!(substr_count($enc_text, '?') > 3));
					
					$enc_text = explode('?', $enc_text, 5);
					switch (strtoupper($enc_text[2]))
					{
						case "B":
							$dec_text = base64_decode($enc_text[3]);
							break;
						case "Q":
						default:
							$dec_text = quoted_printable_decode($enc_text[3]);
							$dec_text = str_replace('_', ' ', $dec_text);
							break;
					}
					
					$post_text = substr($hd_content, $stop_enc_tag);
					if (substr(ltrim($post_text), 0, 2) == '=?')
					{
						$post_text = ltrim($post_text);
					}
					
					$hd_content = $pre_text . $dec_text . $post_text;
					$parsed_header[$hd_name] = $hd_content;
				}
				else
				{
					break;
				}
			}
		}
		
		return $parsed_header;
	}
	
	protected function parse_address($addr)
	{
		$atpos = strpos($addr, '@');
		$minpos = strpos($addr, '<');
		$majpos = strpos($addr, '>');
		$fromstart = 0;
		$fromend = strlen($addr);
		if ($minpos < $atpos && $majpos > $atpos)
		{
			$fromstart = $minpos + 1;
			$fromend = $majpos;
		}
		
		return substr($addr, $fromstart, $fromend - $fromstart);
	}
	
	protected function parse_date($ddate)
	{
		$dmonths = w('Jan Feb Mar Apr May Jun Jul Aug Sep Oct Nov Dec');
		
		if (strpos($ddate, ','))
		{
			$ddate = trim(substr($ddate, strpos($ddate, ',')+1, strlen($ddate)));
		}
		
		$date_arr = explode(' ', $ddate);
		$date_time = explode(':', $date_arr[3]);
		
		$ddate_H = $date_time[0];
		$ddate_i = $date_time[1];
		$ddate_s = $date_time[2];
		
		$ddate_m = $date_arr[1];
		$ddate_d = $date_arr[0];
		$ddate_Y = $date_arr[2];
		
		for ($j = 0; $j < 12; $j++)
		{
			if ($ddate_m == $dmonths[$j])
			{
				$ddate_m = $j + 1;
			}
		}
		
		$time_zn = intval($date_arr[4]) * 36;
		$ddate_U = gmmktime($ddate_H, $ddate_i, $ddate_s, $ddate_m, $ddate_d, $ddate_Y);
		return ($ddate_U - $time_zn);
	}
	
	protected function parse_ip($value)
	{
		$result = '127.0.0.1';
		if ($count = preg_match_all('#from \[([0-9\.]+)\]#is', $value, $part))
		{
			$result = $part[1][($count - 1)];
		}
		
		return $result;
	}
	
	protected function body($header, $message, $clean = false)
	{
		if ($clean)
		{
			$this->data = array();
		}
		
		if (!isset($header['content-transfer-encoding']))
		{
			$header['content-transfer-encoding'] = '';
		}
		$content_transfer_encoding = strtolower(trim($header['content-transfer-encoding']));
		if (empty($content_transfer_encoding))
		{
			$content_transfer_encoding = '8bit';
		}
		
		$message = $this->text_decode($content_transfer_encoding, $message);
		
		if (!isset($header['content-type']))
		{
			$header['content-type'] = '';
		}
		$content_type = split(';', $header['content-type']);
		for ($i = 0, $end = count($content_type); $i < $end; $i++)
		{
			$content_type[$i] = trim(strtolower($content_type[$i]));
		}
		
		if (empty($content_type[0]))
		{
			$content_type[0] = 'text/plain';
		}
		
		if (strstr($content_type[0], 'multipart/') || strstr($content_type[0], 'message/'))
		{
			$content_type[0] = 'multipart';
		}
		
		switch ($content_type[0])
		{
			case 'text/plain':
				$this->data['text-plain'] = implode("\n", $message);
				
				if (isset($content_type[1]) && $content_type[1] == 'charset=utf-8')
				{
					$this->data['text-plain'] = utf8_decode($this->data['text-plain']	);
				}
				
				$this->data['text-plain'] = htmlentities($this->data['text-plain']);
				break;
			case 'text/html':
				$this->data['text-html'] = implode("\n", $message);
				break;
			case 'multipart':
				$content_type[1] = split(';', $content_type[1]);
				$boundary = '';
				foreach($content_type[1] as $ct_pars)
				{
					$ct_pars = split('=', $ct_pars, 2);
					if (strtolower($ct_pars[0]) == 'boundary')
					{
						$boundary = str_replace('"', '', $ct_pars[1]);
					}
				}
				
				if ($boundary)
				{
					$parts = $this->split_multipart($boundary, $message);
					foreach ($parts as $part)
					{
						$this->parse_part($part);
					}
				}
				break;
			default:
				$this->data['attachments'][] = array('header' => $header, 'content' => @implode("\n", $message));
				break;
		}
		
		return $this->data;
	}
	
	protected function text_decode($encoding, $text)
	{
		switch ($encoding)
		{
			case 'quoted-printable':
				$dec_text = explode("\n", quoted_printable_decode(implode("\n", $text)));
				break;
			case 'base64':
				for($i = 0, $end = count($text); $i < $end; $i++)
				{
					$text[$i] = trim($text[$i]);
				}
				$dec_text = explode("\n", base64_decode(@implode('', $text)));
				break;
			case '7bit':
			case '8bit':
			case 'binary':
			default:
				$dec_text = $text;
			break;
		}
		
		return $dec_text;
	}
	
	protected function split_multipart($boundary, $text)
	{
		$parts = array();
		$tmp = array();
		foreach ($text as $line)
		{
			if (strstr(strtolower($line), "--" . strtolower($boundary)))
			{
				$parts[] = $tmp;
				$tmp = array();
			}
			else
			{
				$tmp[] = $line;
			}
		}
		
		for ($i = 0, $end = count($parts); $i < $end; $i++)
		{
			$parts[$i] = explode("\n", trim(implode("\n", $parts[$i])));
		}
		
		return $parts;
	}
	
	protected function parse_part($text)
	{
		$headerpart = array();
		$contentpart = array();
		$noheader = 0;
		foreach ($text as $riga)
		{
			if (!$riga)
			{
				$noheader++;
			}
			
			if ($noheader)
			{
				$contentpart[] = $riga;
			}
			else
			{
				$headerpart[] = $riga;
			}
		}
		
		$this->body($this->parse_header($headerpart), explode("\n", trim(implode("\n", $contentpart))));
	}
}

?>