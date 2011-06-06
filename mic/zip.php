<?php
/**
 * Unzip the source_file in the destination dir
 *
 * @param   string      The path to the ZIP-file.
 * @param   string      The path where the zipfile should be unpacked, if false the directory of the zip-file is used
 * @param   boolean     Indicates if the files will be unpacked in a directory with the name of the zip-file (true) or not (false) (only if the destination directory is set to false!)
 * @param   boolean     Overwrite existing files (true) or not (false)
 *
 * @return  boolean     Succesful or not
 */
function unzip($src_file, $dest_dir = false, $create_zip_name_dir = true, $overwrite = true)
{
	if (!@function_exists('zip_open'))
	{
		return false;
	}
	
	if (!is_resource(zip_open($src_file)))
	{
		$src_file = dirname($_SERVER['SCRIPT_FILENAME']) . '/' . $src_file;
	}
	
	if (!is_resource($zip = zip_open($src_file)))
	{
		return false;
	}
	
	$splitter = ($create_zip_name_dir === true) ? '.' : '/';
	if ($dest_dir === false) $dest_dir = substr($src_file, 0, strrpos($src_file, $splitter)) . '/';
	
	// Create the directories to the destination dir if they don't already exist
	create_dirs($dest_dir);
	
	$response_folder = '';
	// For every file in the zip-packet
	while ($zip_entry = zip_read($zip))
	{
		// Now we're going to create the directories in the destination directories
		
		// If the file is not in the root dir
		$pos_last_slash = strrpos(zip_entry_name($zip_entry), '/');
		if ($pos_last_slash !== false)
		{
			// Create the directory where the zip-entry should be saved (with a "/" at the end)
			$entry_folder = substr(zip_entry_name($zip_entry), 0, $pos_last_slash + 1);
			create_dirs($dest_dir . $entry_folder);
			if ($response_folder == '')
			{
				$response_folder = $entry_folder;
			}
		}
		
		// Open the entry
		if (zip_entry_open($zip, $zip_entry, 'r'))
		{
			// The name of the file to save on the disk
			$file_name = $dest_dir.zip_entry_name($zip_entry);
			
			// Check if the files should be overwritten or not
			if ($overwrite === true || $overwrite === false && !is_file($file_name))
			{
				// Get the content of the zip entry
				$fstream = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
				if (!is_dir($file_name))
					file_put_contents($file_name, $fstream);
					
				// Set the rights
				if (file_exists($file_name))
				{
					chmod($file_name, 0777);
				}
			}
			
			// Close the entry
			zip_entry_close($zip_entry);
		}
	}
	
	// Close the zip-file
	zip_close($zip);
	return $response_folder;
}

function create_dirs($path)
{
	if (!is_dir($path))
	{
		$directory_path = '';
		$directories = explode('/', $path);
		array_pop($directories);
		
		foreach ($directories as $directory)
		{
			$directory_path .= $directory . '/';
			if (!is_dir($directory_path))
			{
				mkdir($directory_path);
				chmod($directory_path, 0777);
			}
		}
	}
}

/**
 * Class to dynamically create a zip file (archive)
 *
 * @author Rochak Chauhan
 */

class createZip
{
	private $compressedData = array();
	private $centralDirectory = array(); // central directory
	private $endOfCentralDirectory = "\x50\x4b\x05\x06\x00\x00\x00\x00"; //end of Central directory record
	private $oldOffset = 0;
	
	/**
	 * Function to create the directory where the file(s) will be unzipped
	 *
	 * @param $directoryName string 
	 *
	 */
	
	public function addDirectory($directoryName)
	{
		$directoryName = str_replace("\\", '/', $directoryName);
		
		$feedArrayRow = "\x50\x4b\x03\x04";
		$feedArrayRow .= "\x0a\x00";
		$feedArrayRow .= "\x00\x00";
		$feedArrayRow .= "\x00\x00";
		$feedArrayRow .= "\x00\x00\x00\x00";
		
		$feedArrayRow .= pack('V',0);
		$feedArrayRow .= pack('V',0);
		$feedArrayRow .= pack('V',0);
		$feedArrayRow .= pack('v', strlen($directoryName) );
		$feedArrayRow .= pack('v', 0 );
		$feedArrayRow .= $directoryName;
		
		$feedArrayRow .= pack('V',0);
		$feedArrayRow .= pack('V',0);
		$feedArrayRow .= pack('V',0);
		
		$this->compressedData[] = $feedArrayRow;
		
		$newOffset = strlen(implode('', $this->compressedData));
		
		$addCentralRecord = "\x50\x4b\x01\x02";
		$addCentralRecord .="\x00\x00";
		$addCentralRecord .="\x0a\x00";
		$addCentralRecord .="\x00\x00";
		$addCentralRecord .="\x00\x00";
		$addCentralRecord .="\x00\x00\x00\x00";
		$addCentralRecord .= pack('V',0);
		$addCentralRecord .= pack('V',0);
		$addCentralRecord .= pack('V',0);
		$addCentralRecord .= pack('v', strlen($directoryName));
		$addCentralRecord .= pack('v', 0);
		$addCentralRecord .= pack('v', 0);
		$addCentralRecord .= pack('v', 0);
		$addCentralRecord .= pack('v', 0);
		$ext = "\x00\x00\x10\x00";
		$ext = "\xff\xff\xff\xff";
		$addCentralRecord .= pack('V', 16);
		
		$addCentralRecord .= pack('V', $this->oldOffset);
		$this->oldOffset = $newOffset;
		
		$addCentralRecord .= $directoryName;
		
		$this->centralDirectory[] = $addCentralRecord;
	}
	
	/**
	 * Function to add file(s) to the specified directory in the archive
	 *
	 * @param $directoryName string
	 *
	 */
	
	public function addFile($data, $directoryName)
	{
		$directoryName = str_replace("\\", '/', $directoryName);
	
		$feedArrayRow = "\x50\x4b\x03\x04";
		$feedArrayRow .= "\x14\x00";
		$feedArrayRow .= "\x00\x00";
		$feedArrayRow .= "\x08\x00";
		$feedArrayRow .= "\x00\x00\x00\x00";
		
		$uncompressedLength = strlen($data);
		$compression = crc32($data);
		$gzCompressedData = gzcompress($data);
		$gzCompressedData = substr(substr($gzCompressedData, 0, strlen($gzCompressedData) - 4), 2);
		$compressedLength = strlen($gzCompressedData);
		$feedArrayRow .= pack('V', $compression);
		$feedArrayRow .= pack('V', $compressedLength);
		$feedArrayRow .= pack('V', $uncompressedLength);
		$feedArrayRow .= pack('v', strlen($directoryName));
		$feedArrayRow .= pack('v', 0);
		$feedArrayRow .= $directoryName;
		
		$feedArrayRow .= $gzCompressedData;
		
		$feedArrayRow .= pack('V', $compression); 
		$feedArrayRow .= pack('V', $compressedLength); 
		$feedArrayRow .= pack('V', $uncompressedLength); 
		
		$this->compressedData[] = $feedArrayRow;
		
		$newOffset = strlen(implode('', $this->compressedData));
		
		$addCentralRecord = "\x50\x4b\x01\x02";
		$addCentralRecord .="\x00\x00";
		$addCentralRecord .="\x14\x00";
		$addCentralRecord .="\x00\x00";
		$addCentralRecord .="\x08\x00";
		$addCentralRecord .="\x00\x00\x00\x00";
		$addCentralRecord .= pack('V', $compression);
		$addCentralRecord .= pack('V', $compressedLength);
		$addCentralRecord .= pack('V', $uncompressedLength);
		$addCentralRecord .= pack('v', strlen($directoryName));
		$addCentralRecord .= pack('v', 0);
		$addCentralRecord .= pack('v', 0);
		$addCentralRecord .= pack('v', 0);
		$addCentralRecord .= pack('v', 0);
		$addCentralRecord .= pack('V', 32); 
		
		$addCentralRecord .= pack('V', $this->oldOffset);
		$this->oldOffset = $newOffset;
		
		$addCentralRecord .= $directoryName;
		
		$this->centralDirectory[] = $addCentralRecord;
	}
	
	/**
	 * Fucntion to return the zip file
	 *
	 * @return zipfile (archive)
	 */
	
	public function getZippedfile()
	{
		$data = implode('', $this->compressedData);
		$controlDirectory = implode('', $this->centralDirectory);
		
		return $data . $controlDirectory . $this->endOfCentralDirectory . pack('v', sizeof($this->centralDirectory)) . pack('v', sizeof($this->centralDirectory)) . pack('V', strlen($controlDirectory)) . pack('V', strlen($data)) . "\x00\x00";
	}
	
	/**
	 *
	 * Function to force the download of the archive as soon as it is created
	 *
	 * @param archiveName string - name of the created archive file
	 */
	
	public function forceDownload($archiveName)
	{
		if (!$archiveName || !@file_exists($archiveName))
		{
			exit;
		}
		
		if (ini_get('zlib.output_compression'))
		{
			ini_set('zlib.output_compression', 'Off');
		}
		
		header('Pragma: public');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Cache-Control: private', false);
		header('Content-Type: application/zip');
		header('Content-Disposition: attachment; filename=' . basename($archiveName) . ';');
		header('Content-Transfer-Encoding: binary');
		header('Content-Length: ' . filesize($archiveName));
		readfile($archiveName);
	}
}

?>