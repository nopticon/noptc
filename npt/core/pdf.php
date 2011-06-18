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

class _pdf
{
	public $cp;
	private $document_top = 0;
	
	function __construct()
	{
		require_once(XFS . 'core/pdf/class.ezpdf.php');
		$this->cp = new Cezpdf();
		
		return;
	}
	
	function new_page()
	{
		$this->cp->ezNewPage();
		$this->top(0, true);
	}
	
	function page_width($a = 0)
	{
		return $this->cp->ez['pageWidth'] - $a;
	}
	
	function page_height($a = 0)
	{
		return $this->cp->ez['pageHeight'] - $a;
	}
	
	function top($n = false, $r = false)
	{
		if ($n !== false)
		{
			if ($r !== false)
			{
				$this->document_top = 0;
			}
			$this->document_top += $n;
		}
		return $this->document_top;
	}
	
	function right($width, $size, $text)
	{
		return ($width - $this->cp->getTextWidth($size, $text));
	}
	
	function center($width, $size, $text)
	{
		return ($this->right($width, $size, $text) / 2);
	}
	
	function _blocks($d, $fontsize, $y, $line_height)
	{
		$max = array(1);
		foreach ($d as $row)
		{
			$max[] = $this->text_wrap($row['text'], $fontsize, $row['width'], $row['left'], $y, $line_height);
		}
		
		return max($max);
	}
	
	function dynamic_table($table, $left, $top, $padding = 0, $cols = 2, $fontsize = 10, $border = 0, $debug = false)
	{
		$td_def = array('text' => '', 'align' => '', 'words' => 0);
		
		$viewport = $this->page_width(30);
		$cell_width = ceil($viewport / $cols) - ceil($left / $cols);
		$cell_area = $cell_width - ($padding * 4);
		
		$fix = 1.5;
		$min_padding = ($padding > $fix) ? $padding - $fix : 0;
		$min_fontsize = ($fontsize - ($padding - $fix));
		$line_height = $min_fontsize + ($min_padding * 2);
		
		$pos_left = $left;
		$prev_top = $this->top() + $top;
		
		$lines_count = $lines_arr = $u = 0;
		$arr = w();
		
		foreach ($table as $i => $row)
		{
			if (!is_array($row))
			{
				$row = array('text' => $row);
			}
			
			if (!is_array($row['text']))
			{
				$row['text'] = array($row['text']);
			}
			
			foreach ($td_def as $i_td => $d_td)
			{
				if (!isset($row[$i_td])) $row[$i_td] = $d_td;
			}
			
			foreach ($row['text'] as $line)
			{
				$lines_count += count($this->calculate_lines($cell_area, $fontsize, $line, $row['words']));
			}
			
			$table[$i] = $row;
		}
		
		if ($lines_count % $cols)
		{
			$lines_count2 = round($lines_count / $cols) * $cols;
			$lines_count = ($lines_count - 1 == $lines_count2) ? round(($lines_count + 1) / $cols) * $cols : $lines_count2;
		}
		
		$lines_col = $lines_count / $cols;
		$cell_height = $lines_col * ($line_height + $fix) - 1;
		
		foreach ($table as $row)
		{
			foreach ($row['text'] as $line)
			{
				$arr[$u][] = array(
					'text' => $line,
					'align' => $row['align'],
					'words' => $row['words']
				);
				
				$lines_arr += count($this->calculate_lines($cell_area, $fontsize, $line, $row['words']));
				if ($lines_arr >= $lines_col)
				{
					$u++;
					$lines_arr = 0;
				}
			}
		}
		
		foreach ($arr as $i => $void)
		{
			if ($i) $pos_left += $cell_width;
			
			$pos_top = $this->top($prev_top, true);
			
			if ($border)
			{
				$v_tmp = $min_fontsize + $min_padding;
				$v_top_tmp = $pos_top - $v_tmp;
				
				$v_top = $this->cp->cy($v_top_tmp);
				$v_right = $this->cp->cy($v_top_tmp + $cell_height);
				$v_left = $pos_left + $cell_width;
				
				$this->cp->line($pos_left, $v_top, $pos_left + $cell_width, $v_top);
				$this->cp->line($pos_left, $v_top, $pos_left, $v_right);
				$this->cp->line($v_left, $v_top, $v_left, $v_right);
				$this->cp->line($pos_left, $v_right, $pos_left + $cell_width, $v_right);
			}
			
			$void_size = count($void);
			foreach ($void as $j => $row)
			{
				$pos_text = $pos_left + $padding;
				
				$text_lines = $this->text_wrap($row['text'], $fontsize, $cell_area, $pos_text, $pos_top, $line_height, $row['align'], $row['words']);
				
				if ($border && $row['align'] == 'center')
				{
					$v_end = $pos_left + $cell_width;
					
					if ($j)
					{
						$v_top = $this->cp->cy($pos_top - $fontsize - 1);
						$this->cp->line($pos_left, $v_top, $v_end, $v_top);
					}
					
					if ($j + 1 < $void_size)
					{
						$v_top = $this->cp->cy($pos_top + $padding - 1);
						$this->cp->line($pos_left, $v_top, $v_end, $v_top);
					}
				}
				
				$pos_top += ($fontsize + $padding) * $text_lines;
			}
		}
		
		$this->top($prev_top + ($cell_height - $line_height), true);
		return;
	}
	
	function table($table, $left, $top, $padding, $fontsize = 10, $border = 0)
	{
		foreach ($table as $i => $tr)
		{
			if ($tr === false)
			{
				$tr = array();
			}
			
			if (!isset($tr[0]))
			{
				unset($table[$i]);
				$table[0][$i] = $tr;
			}
		}
		
		$td_def = array(
			'text' => '',
			'align' => '',
			'words' => 0,
			'colspan' => 0,
			'rowspan' => 0
		);
		
		$viewport = $this->page_width(30);
		$cols = count($table[0]);
		$cell_width = ceil($viewport / $cols) - ceil($left / $cols) - 1;
		$borders = w();
		$accum_top = 0;
		
		foreach ($table as $i => $tr)
		{
			$pos_left = $pos_left_orig = $left;
			$pos_top = $this->top($top) + $accum_top;
			
			$max_top = 0;
			
			foreach ($tr as $j => $td)
			{
				if (!is_array($td))
				{
					$td = array('text' => $td);
				}
				
				foreach ($td_def as $i_td => $d_td)
				{
					if (!isset($td[$i_td]))
					{
						$td[$i_td] = $d_td;
					}
				}
				
				$borders[$j] = w();
				
				if ($border)
				{
					$borders[$j]['left'] = $pos_left;
				
					// Right
					if ($j + 1 == count($tr))
					{
						$borders[$j]['right'] = $pos_left + $cell_width;
					}
					
					// Top
					$this->cp->line($pos_left, $this->cp->cy($pos_top - $fontsize - 1), $pos_left + $cell_width, $this->cp->cy($pos_top - $fontsize - 1));
				}
				
				if (f($td['text']))
				{
					$cell_area = $cell_width - ($padding * 2);
					$pos_text = $pos_left + $padding;
					
					$text_lines = $this->text_wrap($td['text'], $fontsize, $cell_area, $pos_text, $pos_top, $fontsize + 3, $td['align'], $td['words']);
					
					$max_text_top = (($text_lines > 1) ? (($fontsize + 3) * ($text_lines - 1)) : 0) + $padding;
					if ($max_text_top > $max_top)
					{
						$max_top = $max_text_top;
						$accum_top = $max_top + 1;
					}
				}
				
				$pos_left += $cell_width;
			}
			
			if ($border)
			{
				$max_top += $pos_top;
				
				// Bottom
				$this->cp->line($pos_left_orig, $this->cp->cy($max_top), $pos_left, $this->cp->cy($max_top));
				
				foreach ($borders as $j => $border)
				{
					// Left
					$this->cp->line($border['left'], $this->cp->cy($pos_top - $fontsize - 1), $border['left'], $this->cp->cy($max_top));
					
					// Right
					if (isset($border['right']))
					{
						$this->cp->line($border['right'], $this->cp->cy($pos_top - $fontsize - 1), $border['right'], $this->cp->cy($max_top));
					}
				}
			}
		}
		
		return;
	}
	
	function text($x, $y, $text, $fontsize = 10, $align = '', $width = 0)
	{
		if (!$fontsize)
		{
			$fontsize = 10;
		}
		$text = entity_decode($text, false);
		
		switch ($align)
		{
			case 'center':
				$x += $this->center($width, $fontsize, $text);
				break;
			case 'right':
				$x += $this->right($width, $fontsize, $text);
				break;
		}
		
		return $this->cp->addTextWrap($x, $this->cp->cy($y), $this->cp->getTextWidth($fontsize, $text) + 1, $fontsize, $text);
	}
	
	function calculate_lines($width, $fontsize, $text, $line_limit)
	{
		return $this->words($width, $fontsize, explode(' ', $text), $line_limit);
	}
	
	function text_wrap($text, $fontsize, $width, $x, $y, $line_height = 0, $align = '', $line_limit = false)
	{
		$line_height = (!$line_height) ? $fontsize + 2 : $line_height;
		$text_lines = $this->calculate_lines($width, $fontsize, $text, $line_limit);
		
		foreach ($text_lines as $row)
		{
			$this->text($x, $y, $row, $fontsize, $align, $width);
			$y += $line_height + 1;
		}
		
		return count($text_lines);
	}
	
	function words($width, $fontsize, $text, $maxline = false, $skip_short = true)
	{
		$part = w();
		$long = $words = $i = 0;
		
		if ($maxline !== false && !is_array($maxline))
		{
			$maxline = array($maxline);
		}
		
		foreach ($text as $j => $word)
		{
			$length = $this->cp->getTextWidth($fontsize, entity_decode($word, false));
			if ($length > $width)
			{
				continue;
			}
			
			if ($maxline !== false)
			{
				$eachline = (isset($maxline[$i])) ? $maxline[$i] : end($maxline);
			}
			
			if ((($width - $long) < $length) || ($maxline !== false && $eachline !== false && $eachline && $words == $eachline))
			{
				$long = $words = 0;
				$i++;
			}
			
			if (!isset($part[$i]))
			{
				$part[$i] = '';
			}
			
			$split_word = explode('>==', $word);
			if (count($split_word) > 1)
			{
				if ($i)
				{
					$part[$i - 1] .= (($part[$i - 1] != '') ? ' ' : '') . $split_word[0];
					$part[$i] .= (($part[$i] != '') ? ' ' : '') . $split_word[1];
					
					$length = $this->cp->getTextWidth($fontsize, entity_decode($split_word[1], false));
				}
				else
				{
					$part[$i] .= (($part[$i] != '') ? ' ' : '') . $split_word[0];
					$i++;
					$long = $words = 0;
					
					//
					$length = $this->cp->getTextWidth($fontsize, entity_decode($split_word[0], false));
					
					if (!isset($part[$i]))
					{
						$part[$i] = '';
					}
					$part[$i] .= (($part[$i] != '') ? ' ' : '') . $split_word[1];
				}
			}
			else
			{
				$part[$i] .= (($part[$i] != '') ? ' ' : '') . $split_word[0];
			}
			
			$long += $length;
			if (!$skip_short || strlen($word) > 2)
			{
				$words++;
			}
		}
		
		return $part;
	}
}

?>