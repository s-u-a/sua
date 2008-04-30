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

	abstract class Gui
	{
		protected $options = array();
		protected $init_run = false;
		protected $end_run = false;

		function setOption($name, $value)
		{
			$this->options[$name] = $value;
		}

		function getOption($name)
		{
			if(!isset($this->options[$name]))
				return null;
			return $this->options[$name];
		}

		abstract protected function htmlHead();
		abstract protected function htmlFoot();

		function init()
		{
			if($this->init_run) return false;
			$return = $this->htmlHead();
			if($return)
				$this->init_run = true;
			return $return;
		}

		function end()
		{
			if(!$this->init_run) return false;
			if($this->end_run) return false;
			$return = $this->htmlFoot();
			if($return)
				$this->end_run = true;
			return $return;
		}

		function __destruct()
		{
			if($this->init_run && !$this->end_run)
				$this->end();
		}
	}