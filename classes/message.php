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

	import("Dataset/SQLite");
	import("Dataset/Classes");

	class MessageDatabase extends SQLite
	{
		protected $tables = array("messages" => array("message_id PRIMARY KEY", "time INT", "text", "parsed_text", "sender", "users", "subject", "html INT"), "messages_recipients" => array("message_id", "recipient"));

		function setField($message_id, $field_name, $field_value)
		{
			if(!$this->messageExists($message_id)) return false;

			return $this->query("UPDATE messages SET ".$field_name." = ".$this->escape($field_value)." WHERE message_id = ".$this->escape($message_id).";");
		}

		function getField($message_id, $field_name)
		{
			if(!$this->messageExists($message_id)) return false;

			$result = $this->singleQuery("SELECT ".$field_name." FROM messages WHERE message_id = ".$this->escape($message_id)." LIMIT 1;");
			if($result) $result = $result[$field_name];
			return $result;
		}

		function getNewName()
		{
			do $name = substr(md5(rand()), 0, 16);
				while($this->messageExists($name));
			return $name;
		}

		function createNewMessage($message_id, $time=null)
		{
			if($this->messageExists($message_id)) return false;

			return $this->query("INSERT INTO messages ( message_id, time ) VALUES ( ".$this->escape($message_id).", ".$this->escape($time)." );");
		}

		function messageText($message_id, $text=null)
		{
			if($text === null)
			{
				if($this->messageIsHTML($message_id))
					return $this->getField($message_id, "text");
				else
					return $this->getField($message_id, "parsed_text");
			}
			else
			{
				$return = true;

				$return = $return && $this->setField($message_id, "text", $text);
				if(!$this->messageIsHTML($message_id))
					$return = $return && $this->setField($message_id, "parsed_text", parse_html($text));
				return $return;
			}
		}

		function messageFrom($message_id, $from=null)
		{
			if($from === null)
				return $this->getField($message_id, "sender");
			else
				return $this->setField($message_id, "sender", $from);
		}

		function messageSubject($message_id, $subject=null)
		{
			if($subject === null)
				return $this->getField($message_id, "subject");
			else
				return $this->setField($message_id, "subject", $subject);
		}

		function messageUsers($message_id, $users=null)
		{
			if($users === null)
				return $this->getField($message_id, "users");
			else
				return $this->setField($message_id, "users", $users);
		}

		function messageRawText($message_id)
		{
			return $this->getField($message_id, "text");
		}

		function messageIsHTML($message_id, $is_html=null)
		{
			if($is_html === null)
				return (bool) $this->getField($message_id, 'html');
			else
			{
				$return = true;
				if($this->messageIsHTML($message_id) && !$is_html)
				{
					$return = $return && $this->setField($message_id, "parsed_text", "");
					$return = $return && $this->setField($message_id, "html", "0");
				}
				elseif(!$this->messageIsHTML($message_id) && $is_html)
				{
					$return = $return && $this->setField($message_id, "parsed_text", parse_html($this->getField($message_id, "text")));
					$return = $return && $this->setField($message_id, "html", "1");
				}
				return $return;
			}
		}

		function messageExists($message_id)
		{
			return ($this->singleField("SELECT COUNT(*) FROM messages WHERE message_id = ".$this->escape($message_id)." LIMIT 1;") > 0);
		}

		function messagesCount()
		{
			return ($this->singleField("SELECT COUNT(*) FROM messages;"));
		}

		function messageTime($message_id, $time=null)
		{
			if($time === null)
				return $this->getField($message_id, "time");
			else
				return $this->setField($message_id, "time", $time);
		}

		function removeMessage($message_id)
		{
			return $this->query("DELETE FROM messages WHERE message_id = ".$this->escape($message_id).";");
		}

		function cleanUp(&$message_ids)
		{
			$this->query("SELECT message_id FROM messages;");
			$count = 0;

			while(($f = $this->nextResult()) !== false)
			{
				$message_id = &$f['message_id'];
				if(!isset($message_ids[$message_id]))
				{
					$this->transactionQuery("DELETE FROM messages WHERE message_id = ".$this->escape($message_id).";");
					$count++;
				}
			}
			$this->endTransaction();

			return $count;
		}

		function renameUser($old_name, $new_name)
		{
			$this->query("SELECT message_id,users,sender FROM messages WHERE users LIKE ".$this->escape("%".$old_name."\r%")." OR sender = ".$this->escape($old_name).";");
			while($message = $this->nextResult())
			{
				$users = explode("\n", $message['users']);
				$users_changed = false;
				$sender_changed = false;

				if($message['sender'] == $old_name)
				{
					$message['sender'] = $new_name;
					$sender_changed = true;
				}

				foreach($users as $k=>$u)
				{
					$u = explode("\r", $u);
					if($u[0] == $old_name)
					{
						$u[0] = $new_name;
						$users[$k] = implode("\r", $u);
						$users_changed = true;
					}
				}
				if($users_changed)
					$message['users'] = implode("\n", $users);

				if($users_changed || $sender_changed)
				{
					$set = array();
					if($users_changed)
						$set[] = "users = ".$this->escape($message['users']);
					if($sender_changed)
						$set[] = "sender = ".$this->escape($message['sender']);
					$this->transactionQuery("UPDATE messages SET ".implode(", ", $set)." WHERE message_id = ".$this->escape($message['message_id']).";");
				}
			}
			$this->transactionQuery("UPDATE messages_recipients SET recipient = ".$this->escape($new_name)." WHERE recipient = ".$this->escape($old_name).";");
			$this->endTransaction();

			return true;
		}

		function getRecipients($message_id)
		{
			$this->query("SELECT recipient FROM messages_recipients WHERE message_id = ".$this->escape($message_id).";");
			$return = array();
			while($res = $this->nextResult())
				$return[] = $res["recipient"];
			return $return;
		}

		function addRecipient($message_id, $recipient)
		{
			return $this->query("INSERT INTO messages_recipients ( message_id, recipient ) VALUES ( ".$this->escape($message_id).", ".$this->escape($recipient)." );");
		}
	}

	class Message
	{
		protected $im_check_notify = array();
		protected static $database = false;
		protected $name = false;

		static $TYPE_KAEMPFE = 1;
		static $TYPE_SPIONAGE = 2;
		static $TYPE_TRANSPORT = 3;
		static $TYPE_SAMMELN = 4;
		static $TYPE_BESIEDELUNG = 5;
		static $TYPE_BENUTZERNACHRICHTEN = 6;
		static $TYPE_VERBUENDETE = 7;
		static $TYPE_POSTAUSGANG = 8;

		static protected function databaseInstance()
		{
			if(!self::$database)
				self::$database = new MessageDatabase();
		}

		static function getMessagesCount()
		{
			self::databaseInstance();

			return self::$database->messagesCount();
		}

		function __construct($name=false)
		{
			self::databaseInstance();

			if(!$name)
				$name = self::$database->getNewName();
			$this->name = $name;
		}

		function create()
		{
			return self::$database->createNewMessage($this->name, time());
		}

		function text($text=false)
		{
			if($text === false) $text = null;
			return self::$database->messageText($this->name, $text);
		}

		function rawText()
		{
			return self::$database->messageRawText($this->name);
		}

		function from($from=false)
		{
			if($from === false) $from = null;
			return self::$database->messageFrom($this->name, $from);
		}

		function renameUser($old_name, $new_name)
		{
			$changed = false;
			$users = explode("\n", self::$database->messageUsers($this->name));
			foreach($users as $k=>$l)
			{
				$l = explode("\r", $l);
				if($l[0] == $old_name)
				{
					$l[0] = $new_name;
					$users[$k] = implode("\r", $l);
					$changed = true;
				}
			}

			if($changed)
			{
				self::$database->messageUsers($this->name, implode("\n", $users));
			}
			return true;
		}

		function subject($subject=false)
		{
			if($subject === false) $subject = null;
			return self::$database->messageSubject($this->name, $subject);
		}

		function html($html=-1)
		{
			if($html === -1) $html = null;
			return self::$database->messageIsHTML($this->name, $html);
		}

		function addUser($user, $type=6)
		{
			$users = explode0("\n", self::$database->messageUsers($this->name));
			$users[] = $user."\r".$type;
			self::$database->messageUsers($this->name, implode("\n", $users));

			$user_obj = Classes::User($user);
			if(!$user_obj->getStatus()) return false;
			$user_obj->addMessage($this->name, $type);
			unset($user_obj);

			if($type != 8)
			{
				$this->im_check_notify[$user] = $type;
				self::$database->addRecipient($this->name, $user);
			}

			return true;
		}

		function removeUser($user, $edit_user=true)
		{
			$users = explode("\n", self::$database->messageUsers($this->name));
			$new_users = array();
			$remove_type = false;

			foreach($users as $l)
			{
				$le = explode("\r", $l);
				if($le[0] == $user)
				{
					$remove_type = $le[1];
					continue;
				}
				$new_users[] = $l;
			}

			if(count($new_users) == 0)
				$return = self::$database->removeMessage($this->name);
			else
				$return = self::$database->messageUsers(implode("\n", $users));

			if($edit_user)
			{
				$user = Classes::User($user);
				if(!$remove_type)
					$remove_type = $user->findMessageType($this->name);
				$user->removeMessage($this->name, $remove_type, false);
			}

			return $return;
		}

		function getTime()
		{
			return self::$database->messageTime($this->name);
		}

		function destroy()
		{
			$users = explode("\n", self::$database->getMessageUsers($this->name));
			foreach($users as $l)
			{
				$l = explode("\r", $l);
				$user = Classes::User($l[0]);
				$user->removeMessage($this->name, $l[1], false);
			}

			return self::$database->removeMessage($this->name);
		}

		function getUsersList()
		{
			$return = array();
			$users = explode("\n", self::$database->getMessageUsers($this->name));
			foreach($users as $l)
			{
				$l = explode("\r", $l);
				$return[] = $l[0];
			}
			return $return;
		}

		function getRecipients()
		{
			return self::$database->getRecipients($this->name);
		}

		function __destruct()
		{
			if(count($this->im_check_notify) > 0)
			{
				$imfile = Classes::IMFile();
				foreach($this->im_check_notify as $user=>$type)
				{
					$user_obj = Classes::User($user);
					$im_settings = $user_obj->getNotificationType();
					if($im_settings)
					{
						$im_receive = $user_obj->checkSetting('messenger_receive');
						if($im_receive['messages'][$type])
							$imfile->addMessage($im_settings[0], $im_settings[1], $user, sprintf($this->from() ? $user_obj->_("Sie haben eine neue Nachricht der Sorte %s von %s erhalten. Der Betreff lautet: %s") : $user_obj->_("Sie haben eine neue Nachricht der Sorte %s erhalten. Der Betreff lautet: %3\$s"), $user_obj->_("[message_".$type."]"), $this->from(), $this->subject()));
					}
					unset($this->im_check_notify[$user]);
				}
			}
		}

		function getStatus()
		{
			return self::$database->messageExists($this->name);
		}
	}
