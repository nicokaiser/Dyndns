<?php

require 'vendor/autoload.php';

$dyndns = new Dyndns\Server();

// Configuration
$dyndns
  ->setConfig('hostsFile', __DIR__ . '/../conf/dyndns.hosts') // hosts database
  ->setConfig('userFile', __DIR__ . '/../conf/dyndns.user')   // user database
  ->setConfig('debug', true)  // enable debugging
  ->setConfig('debugFile', '/tmp/dyndns.log') // debug file
  ->setConfig('bind.keyfile', __DIR__ . '/../conf/dyn.example.com.key') // secret key for BIND nsupdate ("<keyname>:<secret>")
  ->setConfig('bind.server', 'localhost') // address of the BIND server
  ->setConfig('bind.zone', 'dyndns.example.com') // BIND zone for the updates
  ->setConfig('bind.ttl', '300') // TTL for DNS entries
;

$dyndns->init();
