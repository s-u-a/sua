<?php
/*
    This file is part of Stars Under Attack.

    Stars Under Attack is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Stars Under Attack is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with Stars Under Attack.  If not, see <http://www.gnu.org/licenses/>.
*/

	class PublicMessageDatabase extends SQLite
	{
		protected $tables = array("public_messages" => array("message_id PRIMARY KEY", "last_view INT", "sender", "text", "parsed", "subject", "html INT", "receiver", "time", "type"));

		function getField($message_id, $field_name)
		{
			if(!$this->messageExists($message_id)) return false;

			$result = $this->singleQuery("SELECT ".$field_name." FROM public_messages WHERE message_id = ".$this->escape($message_id)." LIMIT 1;");
			if($result) $result = $result[$field_name];
			return $result;
		}

		function setField($message_id, $field_name, $field_value)
		{
			if(!$this->messageExists($message_id)) return false;

			return $this->query("UPDATE public_messages SET ".$field_name." = ".$this->escape($field_value)." WHERE message_id = ".$this->escape($message_id).";");
		}

		function getNewName()
		{
			do $name = substr(md5(rand()), 0, 16);
				while($this->messageExists($name));
			return $name;
		}

		function createNewMessage($message_id)
		{
			$this->query("INSERT INTO public_messages ( message_id, last_view ) VALUES ( ".$this->escape($message_id).", ".$this->escape(time())." );");
		}

		function messageExists($message_id)
		{
			return ($this->singleField("SELECT COUNT(*) FROM public_messages WHERE message_id = ".$this->escape($message_id)." LIMIT 1;") > 0);
		}

		function messagesCount()
		{
			return ($this->singleField("SELECT COUNT(*) FROM public_messages;"));
		}

		function cleanUp()
		{
			global $public_messages_time;

			$count = $this->singleField("SELECT COUNT(*) FROM public_messages WHERE last_view < ".(time()-$public_messages_time*86400).";");
			$this->query("DELETE FROM public_messages WHERE last_view < ".(time()-$public_messages_time*86400).";");
			return $count;
		}
	}

	class PublicMessage
	{
		protected static $database = false;
		protected $name = false;

		static protected function databaseInstance()
		{
			if(!self::$database)
				self::$database = new PublicMessageDatabase();
		}

		static function publicMessageExists($name)
		{
			self::databaseInstance();

			return self::$database->messageExists($name);
		}

		function create()
		{
			self::$database->createNewMessage($this->name);
			return true;
		}

		function __construct($name=false)
		{
			self::databaseInstance();

			if(!$name)
				$name = self::$database->getNewName();
			$this->name = $name;
		}

		function createFromMessage($message)
		{
			if(!$this->create()) return false;

			$html = $message->html();
			$this->html($html);
			$text = $message->rawText();
			$this->text($text);

			$this->subject($message->subject());
			$this->time($message->getTime());
			$this->from($message->from());

			return true;
		}

		function text($text=false, $filter=true)
		{
			// last_view erneuern
			$this->_read();

			if($text === false)
			{
				$text = self::$database->getField($this->name, "parsed");
				if($this->html() && $filter)
				{
					$text = preg_replace('/ ?<span class="koords">.*?<\\/span>/', '', $text);
					$text = preg_replace('/ ?<span class="angreifer-name">.*?<\\/span>/', 'Ein Angreifer', $text);
					$text = preg_replace('/ ?<span class="verteidiger-name">.*?<\\/span>/', 'Ein Verteidiger', $text);
				}
				return $text;
			}

			self::$database->setField($this->name, "text", $text);
			$this->_createParsed();
			return true;
		}

		protected function _createParsed()
		{
			if($this->html())
				self::$database->setField($this->name, "parsed", self::$database->getField($this->name, "text"));
			else
				self::$database->setField($this->name, "parsed", F::parse_html(self::$database->getField($this->name, "text")));
			return true;
		}

		function from($from=false)
		{
			if($from === false)
				return self::$database->getField($this->name, "sender");

			self::$database->setField($this->name, "sender", $from);
			return true;
		}

		function subject($subject=false)
		{
			if($subject === false)
				return self::$database->getField($this->name, "subject");

			self::$database->setField($this->name, "subject", $subject);
			return true;
		}

		function html($html=-1)
		{
			if($html === -1)
				return (self::$database->getField($this->name, "html") == true);

			self::$database->setField($this->name, "html", $html);
			return true;
		}

		protected function _read()
		{
			self::$database->setField($this->name, "last_view", time());
			return true;
		}

		function type($type=false)
		{
			if($type === false)
				return self::$database->getField($this->name, "type");

			self::$database->setField($this->name, "type", $type);
			return true;
		}

		function time($time=false)
		{
			if($time === false)
				return self::$database->getField($this->name, "time");

			self::$database->setField($this->name, "time", $time);
			return true;
		}

		function to($to=false)
		{
			if($to === false)
				return self::$database->getField($this->name, "receiver");

			self::$database->setField($this->name, "receiver", $to);
			return true;
		}

		function getLastViewTime()
		{
			return self::$database->getField($this->name, "last_view");
		}
	}
