<?php

@ini_set('display_errors', 0);

if (!isset($dyndns) || !method_exists($dyndns, 'setConfig')) {
	exit;
}

/* 
 * Location of the hosts database
 */
$dyndns->setConfig('hostsFile', __DIR__ . '/../conf/dyndns.hosts');

/* 
 * Location of the user database
 */
$dyndns->setConfig('userFile', __DIR__ . '/../conf/dyndns.user');

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
$dyndns->setConfig('bind.keyfile', __DIR__ . '/../conf/dyn.example.com.key');

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
