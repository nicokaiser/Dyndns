<?php
/*
 * DynDNS Server Script
 * Copyright (c) 2007 Nico Kaiser
 *  
 * http://siriux.net/
 */

/**
 * User database
 * 
 * @package Dyndns
 * @author Nico Kaiser <nico@siriux.net>
 * @version $Revision: 13 $
 */
class DyndnsUsers {
	
	/**
	 * Filename of the users file
	 * @var string 
	 * @access private
	 */
	var $_userFile;
	
	
	function DyndnsUsers($userFile) {
		$this->_userFile = $userFile;
	}
	
	/**
	 * Checks user credentials
	 *
	 * @param string user 
	 * @param string password
	 * @access private
	 */
	function checkCredentials($user, $password) {
		$lines = @file($this->_userFile);
		if (is_array($lines)) {
			foreach ($lines as $line) {
				if (preg_match("/^(.*?):(.*)/", $line, $matches)) {
					if (strtolower($matches[1]) == strtolower($user)) {
						$salt = substr($matches[2], 0, 2);				
						if (crypt($password, $salt) == $matches[2]) {
							$GLOBALS['dyndns']->debug('Login successful for user ' . $user);
							return TRUE;
						} else {
							$GLOBALS['dyndns']->debug('Wrong password for user: ' . $user);
						}
					}
				}
			}
		} else {
			$GLOBALS['dyndns']->debug('Empty user file: "' . $this->_userFile . '"');
		}
		$GLOBALS['dyndns']->debug('Unknown user: ' . $user);
		return FALSE;
	}
}
?>