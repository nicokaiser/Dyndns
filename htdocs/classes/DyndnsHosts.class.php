<?php
/*
 * DynDNS Server Script
 * Copyright (c) 2007 Nico Kaiser
 *  
 * http://siriux.net/
 */

/**
 * Host database
 * 
 * @package Dyndns
 * @author Nico Kaiser <nico@siriux.net>
 * @version $Revision: 13 $
 */
class DyndnsHosts {
	
	/**
	 * Filename of the hosts file (dyndns.hosts)
	 * @var string 
	 * @access private
	 */
	var $_hostsFile;
	
	/**
	 * Host/Users array:  'hostname' => array ('user1', 'user2', ...)
	 * @var array 
	 * @access private
	 */
	var $_hosts;
	
	/**
	 * List of updates in the format 'hostname' => 'ip'
	 * @var array
	 * @access
	 */
	var $_updates;
	
	/**
	 * This is true if the status / user files were read
	 * @var boolean
	 * @access private
	 */
	var $_initialized;
		
	
	function DyndnsHosts($hostsFile) {
		$this->_hostsFile = $hostsFile;
		$this->_initialized = false;
		$this->_updates = array ();
	}
	
	/**
	 * Adds an update to the list
	 */
	function update($hostname, $ip) {
		if (! $this->_initialized)
			$this->_init();
		
		$GLOBALS['dyndns']->debug('Update: '.$hostname . ':'.$ip);
		$this->_updates[$hostname] = $ip;
		return true;
	}
	
	/**
	 * Checks if the host belongs to the user 
	 * 
	 * @param string user Username
	 * @param string hostname Hostname
	 * @return boolean TRUE if the host belongs to the user
	 */
	function checkUserHost($user, $hostname) {
		if ($hostname == 'members.dyndns.org') {
			$GLOBALS['dyndns']->debug('Cannot change members.dyndns.org');
			return false;
		}
		if (! DyndnsHelper::checkValidHost($hostname)) {
			$GLOBALS['dyndns']->debug('Invalid host: ' . $hostname);
			return false;
		}
		if (! $this->_initialized)
			$this->_init();
		if (is_array($this->_hosts)) {
			foreach ($this->_hosts as $line) {
				if (preg_match("/^(.*?):(.*)/", $line, $matches)) {
					if (DyndnsHelper::compareHosts($matches[1], $hostname, '*') && 
						    in_array($user, explode(',', strtolower($matches[2])))) {
						return true;
					}
				}
			}
		}
		$GLOBALS['dyndns']->debug('Host '.$hostname.' does not belong to user '.$user);
		return false;
	}
	
	/**
	 * Write cached changes to the status file 
	 * 
	 * @access public
	 */
	function flush() {
		return $this->_updateBind();
	}
	
	/**
	 * Initializes the user and status list from the file 
	 * 
	 * @access private
	 */
	function _init() {
		if ($this->_initialized) return;
		$this->_readHostsFile();
		if (! is_array($this->_hosts)) {
			$this->_hosts = Array ();
		}
		$this->_initialized = true;
	}
	
	/**
	 * Reads the contents of $_hostsFile into $_hosts
	 * 
	 * @access private
	 */
	function _readHostsFile() {
		$lines = @file($this->_hostsFile);
		if (is_array($lines)) {
			$this->_hosts = $lines;
		} else {
			$GLOBALS['dyndns']->debug('Empty hosts file: "' . $this->hostsFile . '"');
		}
	}
	
	/**
	 * Sends DNS Updates to BIND server
	 * 
	 * @access private
	 */
	function _updateBind() {
		$server = $GLOBALS['dyndns']->getConfig('bind.server');
		$zone = $GLOBALS['dyndns']->getConfig('bind.zone');
		$ttl = $GLOBALS['dyndns']->getConfig('bind.ttl') * 1;
		$key = $GLOBALS['dyndns']->getConfig('bind.key');
		
		if (! DyndnsHelper::checkValidHost($server)) {
			$GLOBALS['dyndns']->debug('ERROR: Invalid bind.server config value');
			return false;
		}
		if (! DyndnsHelper::checkValidHost($zone)) {
			$GLOBALS['dyndns']->debug('ERROR: Invalid bind.zone config value');
			return false;
		}
		if (! is_int($ttl)) {
			$GLOBALS['dyndns']->debug('Invalid bind.ttl config value. Setting to default 300.');
			$ttl = 300;
		}
		if ($ttl < 60) {
			$GLOBALS['dyndns']->debug('bind.ttl is too low. Setting to default 300.');
			$ttl = 300;
		}
		if (! eregi('^[a-z0-9.-=/]+$', $key)) {
			$GLOBALS['dyndns']->debug('ERROR: Invalid bind.key config value');
			return false;
		}
		
		/* Create temp file with nsupdate commands */
		$tempfile = tempnam('/tmp', 'Dyndns');
		$fh = @fopen($tempfile, 'w');
		if (! $fh) {
			$GLOBALS['dyndns']->debug('ERROR: Could not open temporary file');
			return false;
		}
		fwrite($fh, "server $server\n");
		fwrite($fh, "zone $zone\n");
		$ttl = $GLOBALS['dyndns']->getConfig('bind.ttl');
		foreach ($this->_updates as $host => $ip) {
			fwrite($fh, "update delete $host A\n");
			fwrite($fh, "update add $host $ttl A $ip\n");
		}
		fwrite($fh, "send\n");
		fclose($fh);
		
		/* Execute nsupdate */
		$result = exec('/usr/bin/nsupdate -y ' . $key . ' ' . $tempfile . ' 2>&1');
		unlink($tempfile);
		if ($result != '') {
			$GLOBALS['dyndns']->debug('ERROR: nsupdate returns: ' . $result);
			return false;
		}
		
		return true;
	}
}
?>