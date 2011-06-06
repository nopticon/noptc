<?php
/*
<NPT, a web development framework.>
Copyright (C) <2009>  <NPT>

Securimage 0.3
Portable Security Image Script
Author: Drew Phillips
www.neoprogrammers.com
Copyright 2005 Drew Phillips

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

class captcha
{
	private $image_width;
	private $image_height;
	private $code_length = 7;
	private $ttf_file;
	private $font_size;
	private $text_angle_minimum = -20;
	private $text_angle_maximum = 20;
	private $text_x_start;
	private $text_minimum_distance = 0;
	private $text_maximum_distance = 33;
	private $image_bg_color = 0;
	private $text_color;
	private $shadow_text = false;
	private $use_transparent_text = true;
	private $text_transparency_percentage;
	private $draw_lines = true;
	private $line_color;
	private $line_distance;
	private $draw_angled_lines = true;
	private $draw_lines_over_text = true;
	private $data_directory;
	private $prune_minimum_age = 15;
	private $hash_salt = "fg_hg3y-3-fd30oi4i";
	private $path;
	
	private $im;
	private $bgimg;
	private $code;
	private $code_entered;
	private $correct_code;
	
	public function __construct()
	{
		$this->path = LIB . 'captcha/';
		$this->data_directory = XFS . 'core/cache/xcf/';
		
		$images = $fonts = array();
		
		$fp = @opendir($this->path);
		while (false !== ($file = @readdir($fp)))
		{
			if (preg_match('#\.jpg#is', $file))
			{
				$images[] = $file;
			}
			
			if (preg_match('#\.ttf#is', $file))
			{
				$fonts[] = $file;
			}
		}
		@closedir($fp);
		
		$this->bgimg = $this->path . $images[array_rand($images)];
		$this->ttf_file = $this->path . $fonts[array_rand($fonts)];
		
		$this->font_size = rand(19, 22);
		$this->image_width = rand(230, 250);
		$this->image_height = rand(40, 100);
		$this->line_color = array("red" => rand(200, 255), "green" => rand(200, 255), "blue" => rand(200, 255));
		$this->line_distance = rand(27, 30);
		$this->text_color = array("red" => rand(0, 200), "green" => rand(0, 200), "blue" => rand(0, 200));
		$this->image_bg_color = array("red" => rand(245, 255), "green" => rand(245, 255), "blue" => rand(245, 255));
		$this->text_x_start = rand(8, 12);
		$this->text_minimum_distance = rand(28, 32);
		$this->text_transparency_percentage = rand(10, 20);
	}
	
	public function check($code)
	{
		$this->code_entered = $code;
		$this->validate();
		return $this->correct_code;
	}
	
	public function do_image()
	{
		if ($this->use_transparent_text == TRUE || $this->bgimg != "")
		{
			$this->im = imagecreatetruecolor($this->image_width, $this->image_height);
			$bgcolor = imagecolorallocate($this->im, $this->image_bg_color['red'], $this->image_bg_color['green'], $this->image_bg_color['blue']);
			imagefilledrectangle($this->im, 0, 0, imagesx($this->im), imagesy($this->im), $bgcolor);
		}
		else
		{
			// no transparency
			$this->im = imagecreate($this->image_width, $this->image_height);
			$bgcolor = imagecolorallocate($this->im, $this->image_bg_color['red'], $this->image_bg_color['green'], $this->image_bg_color['blue']);
		}
		
		if($this->bgimg != "") { $this->setBackground(); }
		
		$this->code = $this->generateCode($this->code_length);
		
		if (!$this->draw_lines_over_text && $this->draw_lines) $this->drawLines();
		
		$this->drawWord();
		
		if ($this->draw_lines_over_text && $this->draw_lines) $this->drawLines();
		
		$this->saveData();
		$this->output();
	}
	
	private function setBackground()
	{
		$dat = @getimagesize($this->bgimg);
		if($dat == FALSE) { return; }
		
		switch($dat[2])
		{
			case 1: $newim = @imagecreatefromgif($this->bgimg); break;
			case 2: $newim = @imagecreatefromjpeg($this->bgimg); break;
			case 3: $newim = @imagecreatefrompng($this->bgimg); break;
			case 15: $newim = @imagecreatefromwbmp($this->bgimg); break;
			case 16: $newim = @imagecreatefromxbm($this->bgimg); break;
			default: return;
		}
		
		if (!$newim) return;
		
		imagecopy($this->im, $newim, 0, 0, 0, 0, $this->image_width, $this->image_height);
	}
	
	private function drawLines()
	{
		$linecolor = imagecolorallocate($this->im, $this->line_color['red'], $this->line_color['green'], $this->line_color['blue']);
		
		// vertical lines
		for ($x = 1; $x < $this->image_width; $x += $this->line_distance)
		{
			imageline($this->im, $x, 0, $x, $this->image_height, $linecolor);
		}
		
		// horizontal lines
		for($y = 11; $y < $this->image_height; $y += $this->line_distance)
		{
			imageline($this->im, 0, $y, $this->image_width, $y, $linecolor);
		}
		
		if ($this->draw_angled_lines == TRUE)
		{
			for ($x = -($this->image_height); $x < $this->image_width; $x += $this->line_distance)
			{
				imageline($this->im, $x, 0, $x + $this->image_height, $this->image_height, $linecolor);
			}
			
			for ($x = $this->image_width + $this->image_height; $x > 0; $x -= $this->line_distance)
			{
				imageline($this->im, $x, 0, $x - $this->image_height, $this->image_height, $linecolor);
			}
		}
	}
	
	private function drawWord()
	{
		if ($this->use_transparent_text == TRUE)
		{
			$alpha = floor($this->text_transparency_percentage / 100 * 127);
			$font_color = imagecolorallocatealpha($this->im, $this->text_color['red'], $this->text_color['green'], $this->text_color['blue'], $alpha);
		}
		else
		{
			// no transparency
			$font_color = imagecolorallocate($this->im, $this->text_color['red'], $this->text_color['green'], $this->text_color['blue']);
		}
		
		$x = $this->text_x_start;
		$strlen = strlen($this->code);
		$y_min = ($this->image_height / 2) + ($this->font_size / 2) - 2;
		$y_max = ($this->image_height / 2) + ($this->font_size / 2) + 2;
		
		
		for ($i = 0; $i < $strlen; ++$i)
		{
			$angle = rand($this->text_angle_minimum, $this->text_angle_maximum);
			$y = rand($y_min, $y_max);
			imagettftext($this->im, $this->font_size, $angle, $x, $y, $font_color, $this->ttf_file, $this->code{$i});
			if ($this->shadow_text == TRUE)
			{
				imagettftext($this->im, $this->font_size, $angle, $x + 2, $y + 2, $font_color, $this->ttf_file, $this->code{$i});
			}
			$x += rand($this->text_minimum_distance, $this->text_maximum_distance);
		}
	}
	
	private function generateCode($len)
	{
		$code = '';
		for ($i = 1; $i <= $len; ++$i)
		{
			$code .= chr(rand(65, 90));
		}
		return $code;
	}
	
	private function output()
	{
		header("Expires: Sun, 1 Jan 2000 12:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
		header("Cache-Control: no-store, no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0"/*, false*/);
		header("Pragma: no-cache");
		header("Content-Type: image/jpeg");
		imagejpeg($this->im);
		imagedestroy($this->im);
		die();
	}
	
	private function encode($str)
	{
		return sha1(md5($str));
	}
	
	private function saveData()
	{
		$filename = $this->encode($this->hash_salt . $_SERVER['REMOTE_ADDR']);
		$fp = fopen($this->data_directory . $filename, "w+");
		fwrite($fp, $this->encode($this->hash_salt . strtolower($this->code)));
		fclose($fp);
	}
	
	private function validate()
	{
		$filename = $this->encode($this->hash_salt . $_SERVER['REMOTE_ADDR']);
		
		if (!@file_exists($this->data_directory . $filename))
		{
			$this->correct_code = false;
			return;
		}
		
		$enced_code = trim(@file_get_contents($this->data_directory . $filename));
		$check = $this->encode($this->hash_salt . strtolower($this->code_entered));
		
		if ($check == $enced_code)
		{
			$this->correct_code = true;
			@unlink($this->data_directory . $filename);
		}
		else
		{
			$this->correct_code = false;
		}
	}
	
	private function checkCode()
	{
		return $this->correct_code;
	}
	
	public function prune()
	{
		$fp = @opendir($this->data_directory);
		if (!$fp)
		{
			return;
		}
		
		while ($filename = @readdir($handle))
		{
			if(time() - filemtime($this->data_directory . $filename) > $this->prune_minimum_age * 60)
			{
				@unlink($this->data_directory . $filename);
			}
		}
		@closedir($handle);
  }
}

?>