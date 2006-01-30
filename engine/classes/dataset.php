<?php
	abstract class Dataset
	{
		private $datatype = 'dataset';
		protected $save_dir = false;
		protected $name = false;
		protected $filename = false;
		protected $changed = false;
		protected $status = false;
		protected $raw = false;
		protected $file_pointer = false;
		protected $cache = array();
		protected $location = false;
		
		abstract function create();
		
		function __construct($name=false)
		{
			if($name === false)
			{
				do $name = substr(md5(rand()), 0, 16); while(file_exists($this->save_dir.'/'.$name));
			}
			
			if($this->save_dir === false)
				$this->status = 0;
			else
			{
				$this->name = $name;
				$this->filename = $this->save_dir.'/'.urlencode($this->name);
				$this->location = $this->filename;
				if(!is_file($this->filename) || !is_readable($this->filename))
					$this->status = 0;
				else
				{
					$this->status = 1;
					if(!is_writeable($this->filename))
					{
						$this->file_pointer = fopen($this->location, 'rb');
						$this->status = 2;
					}
					else $this->file_pointer = fopen($this->location, 'r+b');
					
					if(!$this->file_pointer || !fancy_flock($this->file_pointer, LOCK_EX))
						$this->status = 0;
					if($this->status)
						$this->read();
				}
			}
		}
		
		function __destruct()
		{
			if($this->status)
			{
				$this->write();
				
				flock($this->file_pointer, LOCK_UN);
				fclose($this->file_pointer);
				
				$this->status = 0;
			}
		}
		
		function read($force=false)
		{
			if(!$this->status) return false;
			if($this->changed && !$force) $this->write();
			
			clearstatcache();
			$filesize = filesize($this->filename);
			fseek($this->file_pointer, 0, SEEK_SET);
			$this->raw = unserialize(bzdecompress(fread($this->file_pointer, $filesize)));
			$this->getDataFromRaw();
			return true;
		}
		
		function write($force=false, $getraw=true)
		{
			if(!$this->status && (!$force || file_exists($this->filename))) return false;
			if(!$this->changed && !$force) return 2;
			
			if($getraw)
				$this->getRawFromData();
			
			clearstatcache();
			if($force && !file_exists($this->filename))
			{
				if(!($this->file_pointer = fopen($this->location, 'a+')))
					return false;
				if(!fancy_flock($this->file_pointer, LOCK_EX))
					return false;
			}
			
			fseek($this->file_pointer, 0, SEEK_SET);
			$new_data = bzcompress(serialize($this->raw));
			
			$act_filesize = filesize($this->filename);
			$new_filesize = strlen($new_data);
			
			if($new_filesize > $act_filesize)
			{
				$diff = $new_filesize-$act_filesize;
				$fname = $this->filename;
				while(is_link($fname)) $fname = readlink($fname);
				$df = disk_free_space(dirname($fname));
				if($df < $diff)
				{
					echo "Error writing user array: No space left on disk.\n";
					exit(1);
				}
			}
			else ftruncate($this->file_pointer, $new_filesize);
			
			fwrite($this->file_pointer, $new_data);
			
			return true;
		}
		
		abstract protected function getDataFromRaw();
		abstract protected function getRawFromData();
		
		function getStatus()
		{
			return $this->status;
		}
		
		function getName()
		{
			return $this->name;
		}
	}
?>