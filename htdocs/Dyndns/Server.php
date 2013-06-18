<?php

namespace Dyndns;

/**
 * Simple Dynamic DNS server.
 * 
 * @package Dyndns
 * @author  Nico Kaiser <nico@kaiser.me>
 */
class Server
{    
    /**
     * Storage for all configuration variables, set in config.php
     * @var array
     */
    private $config;

    /**
     * The user currently logged in
     * @var string
     */
    private $user;

    /**
     * IP the hostnames should point to
     * @var string
     */
    private $myIp;

    /**
     * Hostnames that should be updated
     * @var array
     */
    private $hostnames;

    /**
     * Debug buffer
     * @var string
     */ 
    private $debugBuffer;

    public function __construct()
    {
        $this->config = array (
            'hostsFile' => 'dyndns.hosts',	// Location of the hosts database
            'userFile' => 'dyndns.user',    // Location of the user database
            'debugFile' => 'dyndns.log',   	// Debug file
            'debug' => false,               // Enable debugging
            
            'bind.server' => false,
            'bind.zone' => '',
            'bind.ttl' => 300,
            'bind.key' => '',
        );
    }

    public function init()
    {
        $this->users = new Users($this->config['userFile']);
        $this->hosts = new Hosts($this->config['hostsFile']);
        
        $this->checkHttpMethod();
        $this->checkAuthentication();
        
        // Get IP address, fallback to REMOTE_ADDR
        $this->myIp = Helper::getMyIp();
        if (array_key_exists('myip', $_REQUEST)) {
            if (Helper::checkValidIp($_REQUEST['myip'])) {
                $this->myIp = $_REQUEST['myip'];
            } else {
                $this->debug('Invalid parameter myip. Using default REMOTE_ADDR');
            }
        }
        
        // Get hostnames to be updated
        $this->hostnames = array ();
        if (array_key_exists('hostname', $_REQUEST) && ($_REQUEST['hostname'] != '')) {
            $this->hostnames = explode(',', strtolower($_REQUEST['hostname']));
            $this->checkHostnames();
        } else {
            $this->returnCode('notfqdn');
        }
        
        $this->updateHosts();
                
        // Return "good" code as everything seems to be ok now
        $this->returnCode('good');
    }
    
    public function setConfig($key, $value)
    {
        $this->config[$key] = $value;
    }
    
    public function getConfig($key)
    {
        return $this->config[$key];
    }

    private function checkHttpMethod()
    {
        // Only HTTP method "GET" is allowed here
        if ($_SERVER['REQUEST_METHOD'] != 'GET') {
            $this->debug('ERROR: HTTP method ' . $_SERVER['REQUEST_METHOD'] . ' is not allowed.');
            $this->returnCode('badagent', array('HTTP/1.0 405 Method Not Allowed'));
        }
    }

    private function checkAuthentication()
    {
        // Request user/pw if not submitted yet
        if (!isset($_SERVER['PHP_AUTH_USER'])) {
            $this->debug('No authentication data sent');
            $this->returnCode('badauth', array(
                'WWW-Authenticate: Basic realm="DynDNS API Access"',
                'HTTP/1.0 401 Unauthorized')
            );
        }

        $user = strtolower($_SERVER['PHP_AUTH_USER']);
        $password = $_SERVER['PHP_AUTH_PW'];
        if (! $this->users->checkCredentials($user, $password)) {
            $this->returnCode('badauth', array('HTTP/1.0 403 Forbidden'));
        }

        $this->user = $user;
    }

    private function checkHostnames()
    {
        foreach ($this->hostnames as $hostname) {
        	// check if the hostname is valid FQDN
            if (! Helper::checkValidHost($hostname)) {
                $this->returnCode('notfqdn');
            }

            // check if the user is allowed to update the hostname
            if (! $this->hosts->checkUserHost($this->user, $hostname)) {
                $this->returnCode('nohost');
            }
        }
    }

    private function updateHosts()
    {
        foreach ($this->hostnames as $hostname) {
            if (! $this->hosts->update($hostname, $this->myIp) ) {
                $this->returnCode('dnserr');
            }
        }
        
        // Flush host database (write to hosts file)
        if (! $this->hosts->flush()) {
            $this->returnCode('dnserr');
        }
    }

    private function returnCode($code, $additionalHeaders = array(), $debugMessage = "")
    {
        foreach ($additionalHeaders as $header) {
            header($header);
        }
        $this->debug('Sending return code: ' . $code);
        echo $code;
        $this->shutdown();
    }

    private function shutdown()
    {
        // Flush debug buffer
        if (($this->debugBuffer != "") && ($this->config['debug'])) {
            if ($fh = @fopen($this->config['debugFile'], 'a')) {
                fwrite($fh, $this->debugBuffer);
                fclose($fh);
            }
        }

        exit;
    }

    private function debug($message)
    {
        $this->debugBuffer .= @date('M j G:i:s') . ' Dyndns: ' . $message . "\n";
    }
}
