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
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) || filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
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
