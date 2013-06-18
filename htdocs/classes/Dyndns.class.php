<?php
/*
 * DynDNS Server Script
 * Copyright (c) 2007 Nico Kaiser
 *  
 * http://siriux.net/
 */
 
require_once(dirname(__FILE__) . '/DyndnsHelper.class.php');
require_once(dirname(__FILE__) . '/DyndnsHosts.class.php');
require_once(dirname(__FILE__) . '/DyndnsUsers.class.php');

/**
 * Simple Dynamic DNS 
 * 
 * @package Dyndns
 * @author Nico Kaiser <nico@siriux.net>
 * @version $Revision: 13 $
 */
class Dyndns {
	
	/**
     * Storage for all configuration variables, set in config.php
     * @var array 
     * @access private
     */
	var $_config;
	
	/**
	 * The user logged in 
	 * @var string
	 * @access private
	 */
	var $_user;
	
	/**
	 * IP the hostnames should point to
	 * @var string
	 * @access private
	 */
	var $_myIp;
	
	/**
	 * Hostnames that should be updated
	 * @var array 
	 * @access private
	 */
	var $_hostnames;
	
	/**
	 * Debug buffer
	 * @var string
	 * @access private
	 */ 
	var $_debugBuffer;
	
	
	function Dyndns() {
		/* Default config settings */
		$this->_config = array (
			'hostsFile' => 'dyndns.hosts',		/* Location of the hosts database */
			'userFile' => 'dyndns.user',		/* Location of the user database */
			'debugFile' => 'dyndns.log',		/* Debug file */
			'debug' => false,					/* Enable debugging */
			
			'bind.server' => false,
			'bind.zone' => '',
			'bind.ttl' => 300,
			'bind.key' => '',
		);
	}
	
	/**
	 * Initializes the Dyndns script
	 */
	function init() {
		$this->_users = new DyndnsUsers($this->_config['userFile']);
		$this->_hosts = new DyndnsHosts($this->_config['hostsFile']);
		
		$this->_checkHttpMethod();
		$this->_checkAuthentication();
		
		/* Get IP address, fallback to REMOTE_ADDR */
		$this->_myIp = DyndnsHelper::getMyIp();
		if (array_key_exists('myip', $_REQUEST)) {
			if (DyndnsHelper::checkValidIp($_REQUEST['myip'])) {
				$this->_myIp = $_REQUEST['myip'];
			} else {
				$this->debug('Invalid parameter myip. Using default REMOTE_ADDR');
			}
		}
		
		/* Get hostnames to be updated */
		$this->_hostnames = array ();
		if (array_key_exists('hostname', $_REQUEST) && ($_REQUEST['hostname'] != '')) {
			$this->_hostnames = explode(',', strtolower($_REQUEST['hostname']));
			$this->_checkHostnames();
		} else {
			$this->_returnCode('notfqdn');
		}
		
		$this->_updateHosts();
				
		/* Return "good" code as everything seems to be ok now */
		$this->_returnCode('good');
	}
	
	/**
	 * Store a value in the config table
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	function setConfig($key, $value) {
		$this->_config[$key] = $value;
	}
	
	/**
	 * Get a value from the config table
     *
	 * @return mixed an arbitrary value
	 */
    function getConfig($key) {
		return $this->_config[$key];
    }
    
	/**
	 * Checks if the HTTP method is supported. Currently, only GET is supported,
	 * all other methods will result in a "badagent" code.
	 * 
	 * @access private
	 */
	function _checkHttpMethod() {
		/* Only HTTP method "GET" is allowed here */
		if ($_SERVER['REQUEST_METHOD'] != 'GET') {
			$this->debug('ERROR: HTTP method ' . $_SERVER['REQUEST_METHOD'] . ' is not allowed.');
			$this->_returnCode('badagent', Array ('HTTP/1.0 405 Method Not Allowed'));
		}
	}
	
	/**
	 * Handles authentication. Requests HTTP authentication and if user/pw is submitted
	 * check if they are valid
	 * 
	 * @access private
	 */ 
	function _checkAuthentication() {
		/* Request user/pw if not submitted yet */
		if (!isset($_SERVER['PHP_AUTH_USER'])) {
			$this->debug('No authentication data sent');
			$this->_returnCode('badauth', Array (
				'WWW-Authenticate: Basic realm="DynDNS API Access"',
				'HTTP/1.0 401 Unauthorized')
			);
		}
		$user = strtolower($_SERVER['PHP_AUTH_USER']);
		$password = $_SERVER['PHP_AUTH_PW'];
		if (! $this->_users->checkCredentials($user, $password)) {
			$this->_returnCode('badauth', Array ('HTTP/1.0 403 Forbidden'));
		}
		$this->_user = $user;
	}
	
	/**
	 * Checks if all hostnames are valid (FQDN) and belong to the user
	 * 
	 * @access private
	 */
	function _checkHostnames() {
		foreach ($this->_hostnames as $hostname) {
			if (! DyndnsHelper::checkValidHost($hostname)) {
				$this->_returnCode('notfqdn');
			}
			if (! $this->_hosts->checkUserHost($this->_user, $hostname)) {
				$this->_returnCode('nohost');
			}
		}
	}
	
	/**
	 * Updates all hosts
	 * 
	 * @param string hostname Hostname
	 * @param string myip IP address
	 */
	function _updateHosts() {
		foreach ($this->_hostnames as $hostname) {
			if (! $this->_hosts->update($hostname, $this->_myIp) ) {
				$this->_returnCode('dnserr');
			}
		}
		/* Flush host database (write to hosts file) */
		if (! $this->_hosts->flush()) {
			$this->_returnCode('dnserr');
		}
	}
	
	/**
	 * Returns a "Return code". The program exits after output.
	 * 
	 * @param string code Return code (like "notfqdn")
	 * @param array additionalHeaders HTTP headers to be added
	 * @param string debugMessage Message for the debug log
	 */
	function _returnCode($code, $additionalHeaders = Array (), $debugMessage = "") {
		foreach ($additionalHeaders as $header) {
			header($header);
		}
		$this->debug('Sending return code: ' . $code);
		echo $code;
		$this->_shutdown();
	}
	
	/**
	 * Shuts down, closes files, writes debug output, etc.
	 *
	 * @access private
	 */
	function _shutdown() {
		/* Write debug buffer */
		if ( ($this->_debugBuffer != "") && ($this->_config['debug'])) {
			if ($fh = @fopen($this->_config['debugFile'], 'a')) {
				fwrite($fh, $this->_debugBuffer);
				fclose($fh);
			}
		}
		exit;
	}
	
	/**
	 * Saves a debug message (if debugging is turned on)
	 * 
	 * @param string message Debug message
	 */
	function debug($message) {
		$this->_debugBuffer .= date('M j G:i:s') . ' Dyndns: ' . $message . "\n";
	}
}
?>