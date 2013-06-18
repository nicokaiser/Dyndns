Introduction
============

This script takes the same parameters as the original members.dyndns.org 
server does. It can update a BIND DNS server via "nsupdate".

As it uses the same syntax as the original DynDNS.org servers do, a dynamic DNS server equipped with
this script can be used with DynDNS compatible clients without having to modify anything on the
client side.


Installation
============

To mimic the original DynDNS.org behavior, the Script's URL must be

	http://members.dyndns.org/nic/update

You may have to adjust your own DNS configuration to make "members.dyndns.org" point to your own 
Server and you Web Servers configuration to make "/nic/update" call the PHP script provided in this 
package.


Furthermore, to be able to dynamically update the BIND DNS server, DNS key must be generated with
the command:

	dnskeygen -n dyndns.example.com -H 512 -h

(Where "dyndns.example.com" is the key name)
The resulting key (look at the "Key:" line in the resulting Kdyndns.example.com.+157+00000.private)
must be copied to both, the  config.php  file (along with the key name, see there for details), and 
the BIND configuration (see below).


The key has to be added to the BIND configuration (named.conf), as well as a DNS zone:


key dyndns.example.com. {
	algorithm HMAC-MD5;
	secret "bvZ....K5A==";
};

zone "dyndns.example.com" {
	type master;
	file "dyndns.example.com.zone";
	allow-update {
		key dyndns.example.com.;
	};
};

In this case, the zone is also called "dyndns.example.com". The (initial) dyndns.example.com.zone 
file (located in BIND's cache directory) looks like this:

$TTL 1h 
@ IN SOA dyndns.example.com. root.example.com. (
        2007111501      ; serial
        1h              ; refresh
        15m             ; retry
        7d              ; expiration
        1h              ; minimum
        )  
        NS <your dns server>

Remember to change access rights so BIND is able to write to this file.


PHP script configuration
------------------------

The PHP script is called by the DynDNS client, it validates the input and calls "nsupdate" to 
finally update the DNS with the new data. Its configuration is rather simple, the user database is
implemented as text file "dyndns.user" with each line containing

	<user>:<password>

Where <password> is crypt'ed like in Apache's htpasswd files. 
Hosts are assigned to users in using the file  "dyndns.hosts":

	<host>:<user>(,<user>,<user>,...)

(So users can update multiple hosts, and a host can be updated by multiple users).


The location of these files must be specified in  "config.php". For security reasons, don't place
them in your Document root, otherwise every Web user can read them.



Implementation
==============

Here you can find details on which capabilities of the DynDNS specification are implemented.

Hostname: members.dyndns.org
HTTP ports: 80, 8245
HTTPS port: 443 (not supported!)


Usage
-----

Authentication in URL:

http://username:password@members.dyndns.org/nic/update?hostname=yourhostname&myip=ipaddress&wildcard=NOCHG&mx=NOCHG&backmx=NOCHG


Raw HTTP GET Request:

GET /nic/update?hostname=yourhostname&myip=ipaddress&wildcard=NOCHG&mx=NOCHG&backmx=NOCHG HTTP/1.0 
Host: members.dyndns.org 
Authorization: Basic base-64-authorization 
User-Agent: Company - Device - Version Number

Fragment base-64-authorization should be represented by Base 64 encoded username:password string.


Implemented fields
------------------

hostname
  Comma separated list of hostnames that you wish to update (up to 20 hostnames per request). 
  This is a required field.
  Example: hostname=test.dyndns.org,customtest.dyndns.org

myip
  IP address to set for the update.
  (If this parameter is not specified, the best IP address the server can determine will be used)

(See http://www.dyndns.com/developers/specs/syntax.html for more details)


Return Codes
------------

good
  The update was successful, and the hostname is now updated.

badauth
  The username and password pair do not match a real user.

notfqdn
  The hostname specified is not a fully-qualified domain name (not in the form hostname.dyndns.org 
  or domain.com).

nohost
  The hostname specified does not exist in this user account (or is not in the service specified in 
  the system parameter)

badagent
  The user agent was not sent or HTTP method is not permitted (we recommend use of GET request method).

dnserr
  DNS error encountered

911
  There is a problem or scheduled maintenance on our side.

(See http://www.dyndns.com/developers/specs/return.html for more details)



Nico Kaiser
nico@siriux.net
