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

class __cvm extends xmd
{
	public function __construct()
	{
		parent::__construct();
		
		$this->auth(false);
		$this->_m();
	}
	
	function home()
	{
		global $core;
		
		error_reporting(0);
		
		$v = $this->__(w('v'));
		
		if (!$v['v'])
		{
			$sql = 'SELECT media_id
				FROM _bio_media
				WHERE media_type = ?
					AND media_mp3 = ?
				LIMIT 1';
			$v['v'] = _field(sql_filter($sql, 1, 0), 'media_id', 0);
		}
		
		$tag_format = 'UTF-8';
		$relative_path = '/data/artists/%s/media/';
		$absolute_path = '/var/www/vhosts/rockrepublik.net/www' . $relative_path;
		
		$sql = 'SELECT m.*, b.bio_id, b.bio_name
			FROM _bio_media m
			LEFT JOIN _bio b ON m.media_bio = b.bio_id
			WHERE m.media_id = ?';
		
		//$spaths = '/data/artists/' . $songd['ub'] . '/media/';
		//$spath = '/var/www/vhosts/rockrepublik.net/httpdocs' . $spaths;
		
		if ($media = _fieldrow(sql_filter($sql, $v['v'])))
		{
			$row_relative = sprintf($relative_path, $media['bio_id']);
			$row_absolute = $absolute_path . $row_relative;
			
			$row_wma = $row_absolute . $media['media_id'] . '.wma';
			$row_mp3 = $row_absolute . $media['media_id'] . '.mp3';
			
			$rel_wma = '.' . $row_relative . $media['media_id'] . '.wma';
			$rel_mp3 = '.' . $row_relative . $media['media_id'] . '.mp3';
			
			if (@file_exists($rel_wma) && !@file_exists($rel_mp3) && !$media['media_mp3'])
			{
				exec('ffmpeg -i ' . $row_wma . ' -vn -ar 44100 -ac 2 -ab 64kb -f mp3 ' . $row_mp3);
				
				include_once(XFS . 'core/getid3/getid3.php');
				$getID3 = new getID3;
				$getID3->setOption(array('encoding' => $tag_format));
				getid3_lib::IncludeDependency(GETID3_INCLUDEPATH.'write.php', __FILE__, true);
				
				$tagwriter = new getid3_writetags;
				$tagwriter->filename = getid3_lib::SafeStripSlashes($row_mp3);
				$tagwriter->tagformats = array('id3v1');
				$tagwriter->overwrite_tags = true;
				$tagwriter->tag_encoding = $tag_format;
				$tagwriter->remove_other_tags = true;
				$tag_comment = 'Visita www.rockrepublik.net';
				
				$media['album'] = (!empty($media['media_album'])) ? $media['media_album'] : 'Single';
				$media['genre'] = (!empty($media['media_genre'])) ? $media['media_genre'] : 'Rock';
				
				$media_f = array('title', 'name', 'album', 'genre');
				foreach ($media_f as $mr)
				{
					$media['media_' . $mr] = getid3_lib::SafeStripSlashes(utf8_encode(html_entity_decode($media['media_' . $mr])));
				}
				
				$tagwriter->tag_data = array(
					'title' => array($media['media_title']),
					'artist' => array($media['media_name']),
					'album' => array($media['media_album']),
					'year' => array(getid3_lib::SafeStripSlashes($media['media_year'])),
					'genre' => array($media['media_genre']),
					'comment' => array(getid3_lib::SafeStripSlashes($tag_comment)),
					'tracknumber' => array('')
				);
				$tagwriter->WriteTags();
				
				$sql = 'UPDATE _bio_media SET media_mp3 = ?
					WHERE media_id = ?';
				_sql(sql_filter($sql, 1, $media['media_id']));
				
				$fp = @fopen('./conv.txt', 'a+');
				fwrite($fp, $row_mp3 . "\n");
				fclose($fp);
			}
			
			if (!@file_exists($rel_wma))
			{
				$sql = 'UPDATE _bio_media SET media_mp3 = ?
					WHERE media_id = ?';
				_sql(sql_filter($sql, 2, $media['media_id']));
			}
		}
		
		$sql = 'SELECT media_id
			FROM _bio_media
			WHERE media_type = ?
				AND media_mp3 = ?
			LIMIT 1';
		if ($v_next = _field(sql_filter($sql, 1, 0), 'media_id', 0))
		{
			sleep(1);
			
			_redirect(_link($this->m(), array('v' => $v_next)));
		}
		else
		{
			$this->e('no_next');
		}
		
		return $this->e('.');
	}
}

?>