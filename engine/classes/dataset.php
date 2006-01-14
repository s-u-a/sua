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
				if(!is_file($this->filename) || !is_readable($this->filename))
					$this->status = 0;
				else
				{
					$this->status = 1;
					if(!is_writeable($this->filename))
						$this->status = 2;
					elseif(!($this->file_pointer = fopen($this->filename, 'r+')))
						$this->status = 0;
					if($this->status)
					{
						flock($this->file_pointer, LOCK_EX);
						$this->read();
					}
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
			
			$this->raw = unserialize(gzuncompress(file_get_contents($this->filename)));
			$this->getDataFromRaw();
			return true;
		}
		
		function write($force=false)
		{
			if(!$this->status && (!$force || file_exists($this->filename))) return false;
			if(!$this->changed && !$force) return 2;
			
			$this->getRawFromData();
			
			if($force && !file_exists($this->filename))
			{
				if(!($this->file_pointer = fopen($this->filename, 'a+')))
					return false;
				flock($this->file_pointer, LOCK_EX);
			}
			
			fseek($this->file_pointer, 0, SEEK_SET);
			ftruncate($this->file_pointer, 0);
			fwrite($this->file_pointer, gzcompress(serialize($this->raw)));
			
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