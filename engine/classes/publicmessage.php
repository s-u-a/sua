<?php
	class PublicMessage extends Dataset
	{
		protected $save_dir = DB_MESSAGES_PUBLIC;
		private $datatype = 'public_message';
		
		function publicMessageExists($name)
		{
			return (is_file(DB_MESSAGES_PUBLIC.'/'.urlencode($name)) && is_readable(DB_MESSAGES_PUBLIC.'/'.urlencode($name)));
		}
		
		function create()
		{
			if(file_exists($this->filename)) return false;
			$this->raw = array('last_view' => time());
			$this->write(true);
			$this->__construct($this->name);
			return true;
		}
		
		function __construct($name=false)
		{
			Dataset::__construct($name);
			if($this->status)
			{
				$this->raw['last_view'] = time();
				$this->changed = true;
			}
		}
		
		function createFromMessage($message)
		{
			if(!$this->create()) return false;
			
			$html = $message->html();
			$this->html($html);
			if($html)
			{
				$text = preg_replace('/ ?<span class="koords">.*?<\\/span>/', '', $text);
				$text = preg_replace('/ ?<span class="angreifer-name">.*?<\\/span>/', 'Der Angreifer', $text);
				$text = preg_replace('/ ?<span class="verteidiger-name">.*?<\\/span>/', 'Der Verteidiger', $text);
			}
			$this->text($text);
			
			$this->subject($message->subject());
			$this->time($message->getTime());
			$this->from($message->from());
			
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
					if($html) return preg_replace('/<\\/?a[^>]*>/i', '', utf8_htmlentities($this->raw['text'], true))."\n";
					else return "<p>\n\t".preg_replace('/[\n]+/e', 'message_repl_nl(\'$0\');', utf8_htmlentities($this->raw['text']))."\n</p>\n";
				}
			}
			
			$this->raw['text'] = $text;
			$this->changed = true;
			return true;
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
		
		function type($type=false)
		{
			if(!$this->status) return false;
			
			if($type === false)
			{
				if(!isset($this->raw['type'])) return false;
				else return $this->raw['type'];
			}
			
			$this->raw['type'] = $type;
			$this->changed = true;
			return true;
		}
		
		
		function time($time=false)
		{
			if(!$this->status) return false;
			
			if($time === false)
			{
				if(!isset($this->raw['time'])) return false;
				else return $this->raw['time'];
			}
			
			$this->raw['time'] = $time;
			$this->changed = true;
			return true;
		}
		
		function to($to=false)
		{
			if(!$this->status) return false;
			
			if($to === false)
			{
				if(!isset($this->raw['to'])) return '';
				else return $this->raw['to'];
			}
			
			$this->raw['to'] = $to;
			$this->changed = true;
			return true;
		}
		
		function getLastViewTime()
		{
			if(!$this->status) return false;
			
			return $this->raw['last_view'];
		}
		
		protected function getDataFromRaw(){}
		protected function getRawFromData(){}
	}
?>