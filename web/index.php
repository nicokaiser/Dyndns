<?php

/**
 * This script takes the same parameters as the original members.dyndns.org 
 * server does. It can update a BIND DNS server.
 * 
 * The syntax is described here:
 * http://www.dyndns.com/developers/specs/syntax.html
 * 
 * Remember: This script must be run as 
 *     http://members.dyndns.org/nic/update
 * 
 * @author  Nico Kaiser <nico@kaiser.me>
 */

error_reporting(E_ALL);

require_once __DIR__ . '/lib/Dyndns/Helper.php';
require_once __DIR__ . '/lib/Dyndns/Hosts.php';
require_once __DIR__ . '/lib/Dyndns/Users.php';
require_once __DIR__ . '/lib/Dyndns/Server.php';

$GLOBALS['dyndns'] = new Dyndns\Server();
$dyndns = $GLOBALS['dyndns'];

require __DIR__ . '/config.php';

$dyndns->init();
