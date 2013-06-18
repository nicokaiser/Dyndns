<?php

@ini_set('display_errors', 0);

if (!isset($dyndns) || !method_exists($dyndns, 'setConfig')) {
	exit;
}

/* 
 * Location of the hosts database
 */
$dyndns->setConfig('hostsFile', 'conf/dyndns.hosts');

/* 
 * Location of the user database
 */
$dyndns->setConfig('userFile', 'conf/dyndns.user');

/* 
 * Enable debugging?
 */
$dyndns->setConfig('debug', true);	

/* 
 * Debug filename
 */
$dyndns->setConfig('debugFile', '/tmp/dyndns.log');			

/*
 * Secret Key for BIND nsupdate
 * <keyname>:<secret>
 */
$dyndns->setConfig('bind.key', 'dyndns.example.com:bvZfFHkl16wNGL/LuEUAqvlBeue9lw7C8GkHnQucN6jpKDMjOu29zFR6LlO5YlpNzYquDBmDSPVddX9SuFIK5A==');

/*
 * Address of the BIND server. You can specify any remote DNS server here, 
 * if the server allows you to update data using bind.key
 */
$dyndns->setConfig('bind.server', 'localhost');

/*
 * The BIND zone which retrieves the updates
 */
$dyndns->setConfig('bind.zone', 'dyndns.example.com');

/*
 * Dynamic DNS entries will get this TTL
 */
$dyndns->setConfig('bind.ttl', '300');
