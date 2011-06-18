<?php
/**
* mail_fetch/setup.php
*
* Copyright (c) 1999-2006 The SquirrelMail Project Team
*
* Copyright (c) 1999 CDI (cdi@thewebmasters.net) All Rights Reserved
* Modified by Philippe Mingo 2001 mingo@rotedic.com
* Improved by Guillermo Azurdia 2007 gazurdia@unis.edu.gt
* An RFC 1939 compliant wrapper class for the POP3 protocol.
*
* Licensed under the GNU GPL. For full terms see the file COPYING.
*
* pop3 class
*
* $Id: class-pop3.php 4945 2007-02-25 18:19:21Z ryan $
*/

class pop3
{
	var $ERROR      = '';		// Error string.
	var $TIMEOUT    = 60;       // Default timeout before giving up on a
						// network operation.
	var $COUNT      = -1;	// Mailbox msg count
	var $BUFFER     = 512;      // Socket buffer for socket fgets() calls.
						// Per RFC 1939 the returned line a POP3
						// server can send is 512 bytes.
	var $FP         = '';      	// The connection to the server's
						// file descriptor
	var $MAILSERVER = '';	// Set this to hard code the server name
	var $DEBUG      = false;	// set to true to echo pop3
						// commands and responses to error_log
						// this WILL log passwords!
	var $BANNER     = '';		// Holds the banner returned by the
						// pop server - used for apop()
	var $ALLOWAPOP  = false;// Allow or disallow apop()
						// This must be set to true manually
	
	function pop3($server = '', $timeout = '')
	{
		settype($this->BUFFER, 'integer');
		
		// Do not allow programs to alter MAILSERVER
		// if it is already specified. They can get around
		// this if they -really- want to, so don't count on it.
		if (!empty($server) && empty($this->MAILSERVER))
		{
			$this->MAILSERVER = $server;
		}
		
		if (!empty($timeout))
		{
			settype($timeout, 'integer');
			$this->TIMEOUT = $timeout;
			if (!ini_get('safe_mode'))
			{
				set_time_limit($timeout);
			}
		}
		
		return true;
	}
	
	function update_timer()
	{
		if (!ini_get('safe_mode'))
		{
			set_time_limit($this->TIMEOUT);
		}
		
		return true;
	}
	
	//  Opens a socket to the specified server. Unless overridden,
	//  port defaults to 110. Returns true on success, false on fail
	// If MAILSERVER is set, override $server with it's value
	function connect($server = '', $port = 110)
	{
		$port = ($port) ? $port : 110;
		if (!empty($this->MAILSERVER))
		{
			$server = $this->MAILSERVER;
		}
		
		if (empty($server))
		{
			$this->ERROR = 'POP3 connect: No server specified';
			unset($this->FP);
			
			return false;
		}
		
		$fp = @fsockopen($server, $port, $errno, $errstr);
		if (!$fp)
		{
			$this->ERROR = 'POP3 connect: Error [' . $errno . '] [' . $errstr . ']';
			unset($this->FP);
			
			return false;
		}
		
		socket_set_blocking($fp, -1);
		$this->update_timer();
		
		$reply = fgets($fp, $this->BUFFER);
		$reply = $this->strip_clf($reply);
		if ($this->DEBUG)
		{
			error_log("POP3 SEND [connect: $server] GOT [$reply]", 0);
		}
		
		if (!$this->is_ok($reply))
		{
			$this->ERROR = 'POP3 connect: Error [' . $reply . ']';
			unset($this->FP);
			
			return false;
		}
		
		$this->FP = $fp;
		$this->BANNER = $this->parse_banner($reply);
		return true;
	}
	
	// Sends the USER command, returns true or false
	function user($user = '')
	{
		if (!empty($user) && isset($this->FP))
		{
			$reply = $this->send_cmd("USER $user");
			if ($this->is_ok($reply))
			{
				return true;
			}
			
			$this->ERROR = 'POP3 user: Error [' . $reply . ']';
			return false;
		}
		
		if (empty($user))
		{
			$this->ERROR = 'POP3 user: no login ID submitted';
			return false;
		}
		elseif (!isset($this->FP))
		{
			$this->ERROR = 'POP3 user: connection not established';
			return false;
		}
	}
	
	function d($m = false)
	{
		if ($m !== false)
		{
			echo $m . '<br />';
		}
		exit('user');
		$this->quit();
	}
	
	// Sends the PASS command, returns # of msgs in mailbox,
	// returns false (undef) on Auth failure
	function pass($pass = '', $fetch_count = true)
	{
		if (empty($pass))
		{
			$this->ERROR = 'POP3 pass: No password submitted';
			return false;
		}
		elseif (!isset($this->FP))
		{
			$this->ERROR = 'POP3 pass: connection not established';
			return false;
		}
		
		$reply = $this->send_cmd("PASS $pass");
		if (!$this->is_ok($reply))
		{
			$this->ERROR = 'POP3 pass: Authentication failed [' . $reply . ']';
			$this->quit();
			
			return false;
		}
		
		if (!$fetch_count)
		{
			return true;
		}
		
		//  Auth successful.
		$count = $this->last('count');
		$this->COUNT = $count;
		return $count;
	}
	
	//  Attempts an APOP login. If this fails, it'll
	//  try a standard login. YOUR SERVER MUST SUPPORT
	//  THE USE OF THE APOP COMMAND!
	//  (apop is optional per rfc1939)
	function apop($login, $pass)
	{
		if (!isset($this->FP))
		{
			$this->ERROR = 'POP3 apop: No connection to server';
			return false;
		}
		elseif (!$this->ALLOWAPOP)
		{
			return $this->login($login, $pass);
		}
		elseif (empty($login))
		{
			$this->ERROR = 'POP3 apop: No login ID submitted';
			return false;
		}
		elseif (empty($pass))
		{
			$this->ERROR = 'POP3 apop: No password submitted';
			return false;
		}
		
		$banner = $this->BANNER;
		if (empty($banner) || !$banner)
		{
			$this->ERROR = 'POP3 apop: No server banner abort';
			return $this->login($login, $pass);
		}
		
		$AuthString = $banner;
		$AuthString .= $pass;
		$APOPString = md5($AuthString);
		$reply = $this->send_cmd("APOP $login $APOPString");
		
		if ($this->is_ok($reply))
		{
			$count = $this->last('count');
			$this->COUNT = $count;
			return $count;
		}
		
		$this->ERROR = 'POP3 apop: apop authentication failed abort';
		return $this->login($login, $pass);
	}
	
	// Sends both user and pass. Returns # of msgs in mailbox or
	// false on failure (or -1, if the error occurs while getting
	// the number of messages.)
	function login($login = '', $pass = '', $fetch_count = true)
	{
		if (!isset($this->FP))
		{
			$this->ERROR = 'POP3 login: No connection to server';
			return false;
		}
		
		$fp = $this->FP;
		if (!$this->user($login))
		{
			return false;
		}
		
		$result = false;
		$count = $this->pass($pass, $fetch_count);
		if ($count > 0)
		{
			$result = $count;
		}
			
		return $result;
	}
	
	//  Gets the header and first $numLines of the msg body
	//  returns data in an array with each returned line being
	//  an array element. If $numLines is empty, returns
	//  only the header information, and none of the body.
	function top($msgNum, $numLines = 0)
	{
		if (!isset($this->FP))
		{
			$this->ERROR = 'POP3 top: No connection to server';
			return false;
		}
		
		$this->update_timer();
		
		$fp = $this->FP;
		$buffer = $this->BUFFER;
		$cmd = "TOP $msgNum $numLines";
		fwrite($fp, "$cmd\r\n");
		$reply = fgets($fp, $buffer);
		$reply = $this->strip_clf($reply);
		if ($this->DEBUG)
		{
			@error_log("POP3 SEND [$cmd] GOT [$reply]", 0);
		}
		
		if (!$this->is_ok($reply))
		{
			$this->ERROR = 'POP3 top: Error [' . $reply . ']';
			return false;
		}
		
		$result = array();
		$line = fgets($fp, $buffer);
		while (!ereg("^\.\r\n", $line))
		{
			$result[] = $line;
			$line = fgets($fp, $buffer);
			if (empty($line))
			{
				break;
			}
		}
		
		return $result;
	}
	
	//  If called with an argument, returns that msgs' size in octets
	//  No argument returns an associative array of undeleted
	//  msg numbers and their sizes in octets
	function pop_list($msgNum = '')
	{
		if (!isset($this->FP))
		{
			$this->ERROR = 'POP3 pop_list: No connection to server';
			return false;
		}
		
		$fp = $this->FP;
		$Total = $this->COUNT;
		if (!$Total || ($Total == -1))
		{
			return false;
		}
		
		if ($Total == 0)
		{
			return array('0', '0');
			// return -1;   // mailbox empty
		}
		
		$this->update_timer();
		
		if (!empty($msgNum))
		{
			$cmd = "LIST $msgNum";
			fwrite($fp, "$cmd\r\n");
			$reply = fgets($fp, $this->BUFFER);
			$reply = $this->strip_clf($reply);
			if ($this->DEBUG)
			{
				@error_log("POP3 SEND [$cmd] GOT [$reply]", 0);
			}
			
			if (!$this->is_ok($reply))
			{
				$this->ERROR = 'POP3 pop_list: Error [' . $reply . ']';
				return false;
			}
			
			list($junk, $num, $size) = preg_split('/\s+/', $reply);
			return $size;
		}
		
		$cmd = 'LIST';
		$reply = $this->send_cmd($cmd);
		if (!$this->is_ok($reply))
		{
			$reply = $this->strip_clf($reply);
			$this->ERROR = 'POP3 pop_list: Error [' . $reply . ']';
			return false;
		}
		
		$MsgArray = array();
		$MsgArray[0] = $Total;
		for ($msgC = 1; $msgC <= $Total; $msgC++)
		{
			if ($msgC > $Total) { break; }
			
			$line = fgets($fp, $this->BUFFER);
			$line = $this->strip_clf($line);
			if (ereg("^\.", $line))
			{
				$this->ERROR = 'POP3 pop_list: Premature end of list';
				return false;
			}
			
			list($thisMsg, $msgSize) = preg_split('/\s+/', $line);
			settype($thisMsg, 'integer');
			if ($thisMsg != $msgC)
			{
				$MsgArray[$msgC] = 'deleted';
			}
			else
			{
				$MsgArray[$msgC] = $msgSize;
			}
		}
		
		return $MsgArray;
	}
	
	function fbody($el)
	{
		if(!isset($this->FP))
		{
			$this->ERROR = 'POP3 get: No connection to server';
			return false;
		}
		
		$this->update_timer();
		
		$fp = $this->FP;
		$buffer = $this->BUFFER;
		$reply = $this->send_cmd("RETR $el");
		
		if(!$this->is_ok($reply))
		{
			$this->ERROR = ("POP3 get:") . ' ' . ("Error ") . "[$reply]";
			return false;
		}
		
		$message = array();
		
		while(($rs = @fgets($fp, $buffer)) != "\r\n") { }
		while(($rs = @fgets($fp, $buffer)) != ".\r\n") $message[] = trim($rs);
		
		return $message;
	}
	
	//  Retrieve the specified msg number. Returns an array
	//  where each line of the msg is an array element.
	function get($msgNum)
	{
		if (!isset($this->FP))
		{
			$this->ERROR = 'POP3 get: No connection to server';
			return false;
		}
		
		$this->update_timer();
		
		$fp = $this->FP;
		$buffer = $this->BUFFER;
		$cmd = "RETR $msgNum";
		$reply = $this->send_cmd($cmd);
		if (!$this->is_ok($reply))
		{
			$this->ERROR = 'POP3 get: Error [' . $reply . ']';
			return false;
		}
		
		$count = 0;
		$result = array();
		
		$line = '';
		while (!ereg("^\.\r\n", $line))
		{
			$line = fgets($fp, $buffer);
			if (preg_match("/^\s+/", $line) && $count > 0)
			{
				$result[$count-1] .= $line;
				continue;
			}
			
			if (empty($line)) { break; }
			$result[$count] = $line;
			$count++;
		}
		
		return $result;
	}
	
	 //  Returns the highest msg number in the mailbox.
	 //  returns -1 on error, 0+ on success, if type != count
	 //  results in a popstat() call (2 element array returned)
	function last($type = 'count')
	{
		$last = -1;
		if (!isset($this->FP))
		{
			$this->ERROR = 'POP3 last: No connection to server';
			return $last;
		}
		
		$reply = $this->send_cmd('STAT');
		if (!$this->is_ok($reply))
		{
			$this->ERROR = 'POP3 last: Error [' . $reply . ']';
			return $last;
		}
		
		$Vars = preg_split('/\s+/', $reply);
		$count = $Vars[1];
		$size = $Vars[2];
		settype($count, 'integer');
		settype($size, 'integer');
		if ($type != 'count')
		{
			return array($count, $size);
		}
		
		return $count;
	}
	
	//  Resets the status of the remote server. This includes
	//  resetting the status of ALL msgs to not be deleted.
	//  This method automatically closes the connection to the server.
	function reset()
	{
		if (!isset($this->FP))
		{
			$this->ERROR = 'POP3 reset: No connection to server';
			return false;
		}
		
		$reply = $this->send_cmd('RSET');
		if (!$this->is_ok($reply))
		{
			//  The POP3 RSET command -never- gives a -ERR
			//  response - if it ever does, something truely
			//  wild is going on.
			
			$this->ERROR = 'POP3 reset: Error [' . $reply . ']';
			@error_log("POP3 reset: ERROR [$reply]", 0);
		}
		
		$this->quit();
		return true;
	}
	
	//  Sends a user defined command string to the
	//  POP server and returns the results. Useful for
	//  non-compliant or custom POP servers.
	//  Do NOT includ the \r\n as part of your command
	//  string - it will be appended automatically.
	
	//  The return value is a standard fgets() call, which
	//  will read up to $this->BUFFER bytes of data, until it
	//  encounters a new line, or EOF, whichever happens first.
	
	//  This method works best if $cmd responds with only
	//  one line of data.
	function send_cmd($cmd = '')
	{
		if (!isset($this->FP))
		{
			$this->ERROR = 'POP3 send_cmd: No connection to server';
			return false;
		}
		
		if (empty($cmd))
		{
			$this->ERROR = 'POP3 send_cmd: Empty command string';
			return '';
		}
		
		$fp = $this->FP;
		$buffer = $this->BUFFER;
		$this->update_timer();
		
		fwrite($fp, "$cmd\r\n");
		$reply = fgets($fp, $buffer);
		$reply = $this->strip_clf($reply);
		if ($this->DEBUG)
		{
			@error_log("POP3 SEND [$cmd] GOT [$reply]", 0);
		}
		
		return $reply;
	}
	
	//  Closes the connection to the POP3 server, deleting
	//  any msgs marked as deleted.
	function quit()
	{
		if (!isset($this->FP))
		{
			$this->ERROR = 'POP3 quit: connection does not exist';
			return false;
		}
		
		$fp = $this->FP;
		$cmd = 'QUIT';
		fwrite($fp, "$cmd\r\n");
		$reply = fgets($fp, $this->BUFFER);
		$reply = $this->strip_clf($reply);
		if ($this->DEBUG)
		{
			@error_log("POP3 SEND [$cmd] GOT [$reply]", 0);
		}
		
		fclose($fp);
		unset($this->FP);
		return true;
	}
	
	//  Returns an array of 2 elements. The number of undeleted
	//  msgs in the mailbox, and the size of the mbox in octets.
	function popstat()
	{
		$PopArray = $this->last('array');
		if ($PopArray == -1 || empty($PopArray) || !$PopArray)
		{
			return false;
		}
		
		return $PopArray;
	}
	
	//  Returns the UIDL of the msg specified. If called with
	//  no arguments, returns an associative array where each
	//  undeleted msg num is a key, and the msg's uidl is the element
	//  Array element 0 will contain the total number of msgs
	function uidl($msgNum = '')
	{
		if (!isset($this->FP))
		{
			$this->ERROR = 'POP3 uidl: No connection to server';
			return false;
		}
		
		$fp = $this->FP;
		$buffer = $this->BUFFER;
		if (!empty($msgNum))
		{
			$cmd = "UIDL $msgNum";
			$reply = $this->send_cmd($cmd);
			if (!$this->is_ok($reply))
			{
				$this->ERROR = 'POP3 uidl: Error [' . $reply . ']';
				return false;
			}
			
			list($ok, $num, $myUidl) = preg_split('/\s+/', $reply);
			return $myUidl;
		}
		
		$this->update_timer();
		
		$UIDLArray = array();
		$Total = $this->COUNT;
		$UIDLArray[0] = $Total;
		
		if ($Total < 1)
		{
			return $UIDLArray;
		}
		
		$cmd = "UIDL";
		fwrite($fp, "UIDL\r\n");
		$reply = fgets($fp, $buffer);
		$reply = $this->strip_clf($reply);
		if ($this->DEBUG)
		{
			@error_log("POP3 SEND [$cmd] GOT [$reply]", 0);
		}
		
		if (!$this->is_ok($reply))
		{
			$this->ERROR = 'POP3 uidl: Error [' . $reply . ']';
			return false;
		}
		
		$line = '';
		$count = 1;
		$line = fgets($fp, $buffer);
		while (!ereg("^\.\r\n", $line))
		{
			if (ereg("^\.\r\n", $line))
			{
				break;
			}
			
			list($msg, $msgUidl) = preg_split('/\s+/', $line);
			$msgUidl = $this->strip_clf($msgUidl);
			if ($count == $msg)
			{
				$UIDLArray[$msg] = $msgUidl;
			}
			else
			{
				$UIDLArray[$count] = 'deleted';
			}
			
			$count++;
			$line = fgets($fp, $buffer);
		}
		
		return $UIDLArray;
	}
	
	//  Flags a specified msg as deleted. The msg will not
	//  be deleted until a quit() method is called.
	function delete($msgNum = '')
	{
		if (!isset($this->FP))
		{
			$this->ERROR = 'POP3 delete: No connection to server';
			return false;
		}
		
		if (empty($msgNum))
		{
			$this->ERROR = 'POP3 delete: No msg number submitted';
			return false;
		}
		
		$reply = $this->send_cmd("DELE $msgNum");
		if (!$this->is_ok($reply))
		{
			$this->ERROR = 'POP3 delete: Command failed [' . $reply . ']';
			return false;
		}
		
		return true;
	}
	
	//  *********************************************************
	//  The following methods are internal to the class.
	
	function is_ok($cmd = '')
	{
		//  Return true or false on +OK or -ERR
		$result = false;
		if (!empty($cmd))
		{
			$result = ereg("^\+OK", $cmd);
		}
		
		return $result;
	}
	
	function strip_clf($text = '')
	{
		// Strips \r\n from server responses
		$result = '';
		if (!empty($text))
		{
			$stripped = str_replace("\r", '', $text);
			$result = str_replace("\n", '', $stripped);
		}
		
		return $result;
	}
	
	function parse_banner($server_text)
	{
		$outside = true;
		$banner = '';
		$length = strlen($server_text);
		
		for ($count = 0; $count < $length; $count++)
		{
			$digit = substr($server_text, $count, 1);
			if (!empty($digit))
			{
				if (!$outside && ($digit != '<') && ($digit != '>'))
				{
					$banner .= $digit;
				}
				
				if ($digit == '<')
				{
					$outside = false;
				}
				
				if ($digit == '>')
				{
					$outside = true;
				}
			}
		}
		
		$banner = $this->strip_clf($banner);    // Just in case
		return "<$banner>";
	}
}
// End class

?>