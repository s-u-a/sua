<?php
	class Message extends Dataset
	{
		protected $save_dir = DB_MESSAGES;
		private $datatype = 'message';
		
		function create()
		{
			if(file_exists($this->filename)) return false;
			$this->raw = array('time' => time());
			$this->write(true);
			$this->__construct($this->name);
			return true;
		}
		
		function text($text=false)
		{
			if(!$this->status) return false;
			
			if($text === false)
			{
				if(!isset($this->raw['text'])) return '';
				else
				{
					$html = $this->html();
					if($html) return preg_replace('/(<a [^>]*href=")([^"]*)(")/ei', 'message_repl_links("\\1", "\\2", "\\3")', utf8_htmlentities($this->raw['text'], true))."\n";
					else return "<p>\n\t".preg_replace('/[\n]+/e', 'message_repl_nl(\'$0\');', utf8_htmlentities($this->raw['text']))."\n</p>\n";
				}
			}
			
			$this->raw['text'] = $text;
			$this->changed = true;
			return true;
		}
		
		function rawText()
		{
			if(!$this->status) return false;
			
			if(!isset($this->raw['text'])) return '';
			else return $this->raw['text'];
		}
		
		function from($from=false)
		{
			if(!$this->status) return false;
			
			if($from === false)
			{
				if(!isset($this->raw['from'])) return '';
				else return $this->raw['from'];
			}
			
			$this->raw['from'] = $from;
			$this->changed = true;
			return true;
		}
		
		function subject($subject=false)
		{
			if(!$this->status) return false;
			
			if($subject === false)
			{
				if(!isset($this->raw['subject']) || trim($this->raw['subject']) == '') return 'Kein Betreff';
				else return $this->raw['subject'];
			}
			
			$this->raw['subject'] = $subject;
			$this->changed = true;
			return true;
		}
		
		function html($html=-1)
		{
			if(!$this->status) return false;
			
			if($html === -1)
			{
				if(!isset($this->raw['html'])) return false;
				else return $this->raw['html'];
			}
			
			$this->raw['html'] = (bool) $html;
			$this->changed = true;
			return true;
		}
		
		function addUser($user, $type=6)
		{
			if(!$this->status) return false;
			
			if(!isset($this->raw['users']))
				$this->raw['users'] = array();
			if(isset($this->raw['users'][$user]))
				return false;
			
			$user_obj = Classes::User($user);
			if(!$user_obj->getStatus()) return false;
			$user_obj->addMessage($this->name, $type);
			unset($user_obj);
			
			$this->raw['users'][$user] = $type;
			$this->changed = true;
			return true;
		}
		
		function removeUser($user, $edit_user=true)
		{
			if(!$this->status) return false;
			
			if(!isset($this->raw['users']) || !isset($this->raw['users'][$user]))
				return 2;
			
			unset($this->raw['users'][$user]);
			$this->changed = true;
			
			if($edit_user)
			{
				$user = Classes::User($user);
				$user->removeMessage($this->name, $type, false);
			}
			
			if(count($this->raw['users'][$user]) == 0)
			{
				if(!unlink($this->filename)) return false;
				else $this->status = false;
			}
			
			return true;
		}
		
		function getTime()
		{
			if(!$this->status) return false;
			
			if(!isset($this->raw['time'])) return false;
			
			return $this->raw['time'];
		}
		
		function delete()
		{
			if(!$this->status) return false;
			
			foreach($this->raw['users'] as $user=>$type)
				$this->removeUser($user);
		}
		
		protected function getDataFromRaw(){}
		protected function getRawFromData(){}
	}
?>