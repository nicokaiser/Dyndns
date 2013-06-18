<?php

namespace Dyndns;

/**
 * Host database.
 * 
 * @package Dyndns
 * @author  Nico Kaiser <nico@kaiser.me>
 */
class Hosts
{    
    /**
     * Filename of the hosts file (dyndns.hosts)
     * @var string
     */
    private $hostsFile;

    /**
     * Host/Users array:  'hostname' => array ('user1', 'user2', ...)
     * @var array
     */
    private $hosts;
    
    /**
     * List of updates in the format 'hostname' => 'ip'
     * @var array
     */
    private $updates;
    
    /**
     * This is true if the status / user files were read
     * @var boolean
     */
    private $initialized;

    /**
     * Constructor.
     *
     * @param string $hostsFile
     */
    public function __construct($hostsFile)
    {
        $this->hostsFile = $hostsFile;
        $this->initialized = false;
        $this->updates = array();
    }

    /**
     * Adds an update to the list
     *
     * @param string $hostname
     * @param string $ip
     */
    public function update($hostname, $ip)
    {
        if (! $this->initialized) {
            $this->init();
        }
        
        $this->debug('Update: ' . $hostname . ':' . $ip);
        $this->updates[$hostname] = $ip;
        return true;
    }

    /**
     * Checks if the host belongs to the user 
     *
     * @param string $user
     * @param string $hostname
     * @return boolean True if the user is allowed to update the host
     */
    function checkUserHost($user, $hostname)
    {
        if ($hostname === 'members.dyndns.org') {
            $this->debug('Cannot change members.dyndns.org');
            return false;
        }

        if (! Helper::checkValidHost($hostname)) {
            $this->debug('Invalid host: ' . $hostname);
            return false;
        }

        if (! $this->initialized) {
        	$this->init();
        }

        if (is_array($this->hosts)) {
            foreach ($this->hosts as $line) {
                if (preg_match("/^(.*?):(.*)/", $line, $matches)) {
                    if (Helper::compareHosts($matches[1], $hostname, '*') && 
                            in_array($user, explode(',', strtolower($matches[2])))) {
                        return true;
                    }
                }
            }
        }
        $this->debug('Host '.$hostname.' does not belong to user '.$user);
        return false;
    }
    
    /**
     * Write cached changes to the status file 
     */
    public function flush()
    {
        return $this->updateBind();
    }
    
    /**
     * Initializes the user and status list from the file 
     * 
     * @access private
     */
    private function init()
    {
        if ($this->initialized) return;

        $this->readHostsFile();
        if (! is_array($this->hosts)) {
            $this->hosts = array();
        }

        $this->initialized = true;
    }
    
    function readHostsFile()
    {
        $lines = @file($this->hostsFile);
        if (is_array($lines)) {
            $this->hosts = $lines;
        } else {
            $this->debug('Empty hosts file: "' . $this->hostsFile . '"');
        }
    }
    
    /**
     * Sends DNS Updates to BIND server
     * 
     * @access private
     */
    private function updateBind()
    {
        $server = $this->getConfig('bind.server');
        $zone = $this->getConfig('bind.zone');
        $ttl = $this->getConfig('bind.ttl') * 1;
        $key = $this->getConfig('bind.key');

        // sanitiy checks
        if (! Helper::checkValidHost($server)) {
            $this->debug('ERROR: Invalid bind.server config value');
            return false;
        }
        if (! Helper::checkValidHost($zone)) {
            $this->debug('ERROR: Invalid bind.zone config value');
            return false;
        }
        if (! is_int($ttl)) {
            $this->debug('Invalid bind.ttl config value. Setting to default 300.');
            $ttl = 300;
        }
        if ($ttl < 60) {
            $this->debug('bind.ttl is too low. Setting to default 300.');
            $ttl = 300;
        }
        if (! eregi('^[a-z0-9.-=/]+$', $key)) {
            $this->debug('ERROR: Invalid bind.key config value');
            return false;
        }
        
        // create temp file with nsupdate commands
        $tempfile = tempnam('/tmp', 'Dyndns');
        $fh = @fopen($tempfile, 'w');
        if (! $fh) {
            $this->debug('ERROR: Could not open temporary file');
            return false;
        }
        fwrite($fh, "server $server\n");
        fwrite($fh, "zone $zone\n");
        $ttl = $this->getConfig('bind.ttl');
        foreach ($this->updates as $host => $ip) {
            fwrite($fh, "update delete $host A\n");
            fwrite($fh, "update add $host $ttl A $ip\n");
        }
        fwrite($fh, "send\n");
        fclose($fh);
        
        // Execute nsupdate
        $result = exec('/usr/bin/nsupdate -y ' . $key . ' ' . $tempfile . ' 2>&1');
        unlink($tempfile);
        if ($result != '') {
            $this->debug('ERROR: nsupdate returns: ' . $result);
            return false;
        }
        
        return true;
    }

    private function getConfig($key)
    {
    	return $GLOBALS['dyndns']->getConfig($key);
    }

    private function debug($message)
    {
    	return $GLOBALS['dyndns']->debug($message);
    }
}
