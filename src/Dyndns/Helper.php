<?php

namespace Dyndns;

/**
 * Helper functions.
 */
class Helper
{
    /**
     * Check valid IP address
     *
     * @param string IP address
     * @return boolean True if IP is valid
     */
    public static function checkValidIp($ip)
    {
        return Helper::isIPv4Addr($ip) || Helper::isIPv6Addr($ip);
    }

    /**
     * Is IP address a valid IPv4 address?
     *
     * @param string IP address
     * @return boolean True if IP is a valid IPv4 address
     */
    public static function isIPv4Addr($ip) 
    {
	return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
    }

    /**
     * Is IP address a valid IPv6 address?
     *
     * @param string IP address
     * @return boolean True if IP is a valid IPv6 address
     */
    public static function isIPv6Addr($ip) 
    {
	return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
    }

    
    /**
     * Returns DNS record type depending on the IP version
     *
     * @param string IP address
     * @return String record type or FALSE
     */    
    public static function getRecordType($ip)
    {
	if (Helper::isIPv4Addr($ip)) {
	    return "A";
	}
	
	if (Helper::isIPv6Addr($ip)) {
	    return "AAAA";
	}
	
	return FALSE;
    }

    
    /**
     * Returns DNS record id depending on the IP version
     *
     * @param string IP address
     * @return int record id or FALSE
     */    
    public static function getRecordId($ip)
    {
	if (Helper::isIPv4Addr($ip)) {
	    return DNS_A;
	}
	
	if (Helper::isIPv6Addr($ip)) {
	    return DNS_AAAA;
	}
	
	return FALSE;
    }    
    
    /**
     * Checks if IP has changed
     *
     * @param string hostname
     * @param string IP address
     * @param string authoritative name servers
     * @return boolean True if IP has changed
     */    
    public static function hasIPChanged($hostname, $newIp, $ns = NULL)
    {    
	$record_id = Helper::getRecordId($newIp);
	$dnsRes = dns_get_record($hostname, $record_id, $ns);

	foreach ($dnsRes as $k => $v) {
	    $currIp = "invalid";
	
	    // IPv4:
	    if (array_key_exists("ip", $v)) {
		$currIp = $v["ip"];
	    }
	    
	    // IPv6
	    if (array_key_exists("ipv6", $v)) {
		$currIp = $v["ipv6"];
	    }
	    
	    $currIpPton = inet_pton($currIp);
	    $newIpPton = inet_pton($newIp);	    
	    
	    if (strcmp($currIpPton, $newIpPton) === 0) {
		return false;
	    }
	}
	
	return true;
    }
    
    /**
     * Check valid hostname (FQDN)
     *
     * @param string Hostname
     * @return boolean
     */
    public static function checkValidHost($hostname)
    {
        return preg_match('/^[a-zA-Z0-9.-]+$/', $hostname);
    }

    /**
     * Tries to get the IPv4 of the client
     *
     * @return string ip
     */
    public static function getMyIp()
    {
        $ip = $_SERVER['REMOTE_ADDR'];
        $ip = preg_replace('/^::ffff:/', '', $ip); // Some IPv6 Servers add ::ffff:
        return $ip;
    }

    /**
     * Compares if two hostnames are the same, with regard to a wildcard
     *
     * @param string host1
     * @param string host2
     * @return boolean
     */
    public static function compareHosts($host1, $host2, $wildcard = false)
    {
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
