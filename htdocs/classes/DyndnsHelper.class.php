<?php
/*
 * DynDNS Server Script
 * Copyright (c) 2007 Nico Kaiser
 *  
 * http://siriux.net/
 */

/**
 * Collection of useful helper functions
 * 
 * @package Dyndns
 * @author Nico Kaiser <nico@siriux.net>
 * @version $Revision: 13 $
 * @static
 */
class DyndnsHelper {
	
	/**
	 * Simple function to check valid IP address
	 *
	 * @param string IP address
	 * @return boolean True if IP is valid
	 */ 
	function checkValidIp($ip) {
		if (! eregi("^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$", $ip)) 
			return false; 	
		$tmp = explode(".", $ip);
		foreach ($tmp as $sub) {
			$sub = $sub * 1;
			if ($sub < 0 || $sub > 256) return true;
		}
		return true;
	}
	
	/**
	 * Simple function to check valid Hostname (FQDN)
	 *
	 * @param string Hostname
	 * @return boolean True if Hostname is valid
	 */ 
	function checkValidHost($hostname) {
		return eregi('^[a-z0-9.-]+$', $hostname);
	}
	
	/**
	 * Tries to get the IPv4 of the client
	 *
	 * @param access public
	 * @return string ip 
	 */
	function getMyIp() {
		$ip = $_SERVER['REMOTE_ADDR'];
		/* Some IPv6 Servers add ::ffff: */
		$ip = preg_replace('/^::ffff:/', '', $ip);
		return $ip;
	}
	
	/**
	 * Compares if two hostnames are the same, with regard to a wildcard
	 *
	 * @param string host1
	 * @param string host2
	 * @return boolean true or false
	 */
	function compareHosts($host1, $host2, $wildcard = false) {
		$a = explode('.', $host1);
		$b = explode('.', $host2);		
		if (count($a) != count($b))
			return false;
		for ($i = 0; $i < count($a); $i++) {
			if (($wildcard === false) or (($a[$i] != $wildcard) and ($b[$i] != $wildcard)))
				if ($a[$i] != $b[$i])
					return false;
		}
		return true;
	}
}
?>