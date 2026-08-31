# PHP OpenVPN Admin
A website written in PHP, to manage a self-hosted OpenVPN server. It is intended to be used on full VM, and this VM will be only to serve as a VPN server. Cheap rented VM suits the best. This tool is written in a way to use minimum memory as possible, thats why it was writen in PHP without FPM, and uses SQLite. This solution doesnt use any Docker.  

> **This project is completely vibe-coded** — written by an AI coding agent
> from natural-language instructions, with human review and testing but no
> hand-written code. Read it before you trust it with a production host.

## Contains
This Git repository contains:
- installation script to install and configure all required packages to make your own OpenVPN server
- website to manage acess to OpenVPN server  

## Requirements
- full VM with at least 512MB of RAM and 1vCPU
- Debian ( might work on Ubuntu too )
- root acces

## What does it use
- OpenVPN server - VPN server
- Apache2 - HTTP server for the website
- Unbound - DNS resolver
- PHP - to render the website
- SQLite - for database
- IPTables - as a firewall ( it doesnt use NFTables )

## How to install
Switch to root OS account:
> su - root

Move to some folder where you will keep the application permanently e.g.:  
> mkdir -p /opt/git/
> cd /opt/git/

Clone the Git repo:  
> git clone https://github.com/dodoslavn/phpopenvpnadmin  
> cd phpopenvpnadmin

Run installation script (can be repeated):  
> ./install/install.sh

## Your own HTTPS certificate
Once installation script finished, you can update the SSL certificate and the private key to signed one by CA, in folder:  
> /etc/vpnadmin/ssl/

## OpenVPN client
Once you generate your OpenVPN profile file ( .ovpn ), you have to download it and import it to your OpenVPN client application on your phone/PC.  
The OpenVPN server is configured in a way, users are authenticated via client certificate, but also with username and password ( it is the same username and password as login to the website ). It is not possible to require username and password only for some profiles because of OpenVPN server configuration limitation.

## Recommendation
It is recommended to use automatic OS updates for the VPN server, preferably via unattended-upgrades. All installed packages are from standard Debian repository.
