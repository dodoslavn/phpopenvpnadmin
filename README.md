# PHP OpenVPN Admin
A website written in PHP, to manage a self-hosted OpenVPN server. It is intended to run this on VM, and this VM will be only to server as a VPN server. Cheap rented VM suits the best.  

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
