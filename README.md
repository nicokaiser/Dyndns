# Dyndns: a simple DynDNS server in PHP

This script takes the same parameters as the original dyndns.org server does. It can update a BIND DNS server via `nsupdate`.

As it uses the same syntax as the original DynDNS.org servers do, a dynamic DNS server equipped with this script can be used with DynDNS compatible clients without having to modify anything on the client side.


### Features

This script handles DNS updates on the url

    http://yourdomain.tld/?hostname=<domain>&myip=<ipaddr>

For security HTTP basic auth is used. You can create multiple users and assign host names for each user.


### Installation

To be able to dynamically update the BIND DNS server, a DNS key must be generated with the command:

    ddns-confgen

This command outputs instructions for your BIND installation. The generated key has to be added to the named.conf.local:

    key "ddns-key" {
        algorithm hmac-sha256;
        secret "bvZ....K5A==";
    };

and saved to a file which is referenced in config.php as "bind.keyfile". In the "zone" entry, you have to add an "update-policy":

    zone "dyndns.example.com" {
        type master;
        file "db.dyndns.example.com";
        ...
        update-polify {
            grand ddns-key zonesub ANY;
        }
    }

In this case, the zone is also called "dyndns.example.com". The (initial) db.dyndns.example.com file (located in BIND's cache directory) looks like this:

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


### PHP script configuration

The PHP script is called by the DynDNS client, it validates the input and calls "nsupdate" to 
finally update the DNS with the new data. Its configuration is rather simple, the user database is
implemented as text file "dyndns.user" with each line containing

    <user>:<password>

Where the password is crypt'ed like in Apache's htpasswd files. 
Hosts are assigned to users in using the file  "dyndns.hosts":

    <host>:<user>(,<user>,<user>,...)

(So users can update multiple hosts, and a host can be updated by multiple users).


The location of these files must be specified in  "config.php". For security reasons, don't place
them in your Document root, otherwise every Web user can read them.


### Usage

Authentication in URL:

  http://username:password@yourdomain.tld/?hostname=yourhostname&myip=ipaddress


Raw HTTP GET Request:

  GET /?hostname=yourhostname&myip=ipaddress HTTP/1.0 
  Host: yourdomain.tld 
  Authorization: Basic base-64-authorization 
  User-Agent: Company - Device - Version Number

Fragment base-64-authorization should be represented by Base 64 encoded username:password string.


### Implemented fields

- `hostname` Comma separated list of hostnames that you wish to update (up to 20 hostnames per request). This is a required field. Example: `hostname=dynhost1.yourdomain.tld,dynhost2.yourdomain.tld`
- `myip` IP address to set for the update. Defaults to the best IP address the server can determine.


### Return Codes

- `good` The update was successful, and the hostname is now updated.
- `badauth` The username and password pair do not match a real user.
- `notfqdn` The hostname specified is not a fully-qualified domain name (not in the form hostname.dyndns.org or domain.com).
- `nohost` The hostname specified does not exist in this user account (or is not in the service specified in the system parameter)
- `badagent` The user agent was not sent or HTTP method is not permitted (we recommend use of GET request method).
- `dnserr` DNS error encountered
- `911` There is a problem or scheduled maintenance on our side.


### License

MIT
