# PHP OpenVPN Admin
A web interface written in PHP for managing a self-hosted OpenVPN server. It is intended to run on a dedicated VM whose sole purpose is to serve as a VPN server — a cheap rented VM works perfectly. The tool is designed to use as little memory as possible, which is why it is written in plain PHP without FPM and uses SQLite instead of a full database server. No Docker required.

> **This project is completely vibe-coded** — written by an AI coding agent
> from natural-language instructions, with human review and testing but no
> hand-written code. Read it before you trust it with a production host.

## Contents
This Git repository contains:
- an installation script that installs and configures all required packages to set up your own OpenVPN server
- a web interface for managing access to the OpenVPN server

## Requirements
- a full VM with at least 512 MB of RAM and 1 vCPU
- Debian (may also work on Ubuntu)
- root access

## What it uses
- OpenVPN — VPN server
- Apache2 — HTTP server for the web interface
- Unbound — DNS resolver
- PHP — server-side rendering
- SQLite — database
- iptables — firewall (does not use nftables)
- OpenSSL — certificate management

## How to install
Switch to the root account:
> su - root

Move to a folder where you will keep the application permanently, e.g.:
> mkdir -p /opt/git/
> cd /opt/git/

Clone the repository:
> git clone https://github.com/dodoslavn/phpopenvpnadmin  
> cd phpopenvpnadmin

Run the installation script (can be safely re-run):
> ./install/install.sh

## Using your own HTTPS certificate
Once the installation script has finished, you can replace the self-signed SSL certificate and private key with a CA-signed one by replacing your files in:
> /etc/vpnadmin/ssl/

## OpenVPN client
Once you generate an OpenVPN profile (.ovpn file), download it and import it into your OpenVPN client application on your phone or PC.

The server is configured to authenticate users with both a client certificate and a username/password — the same credentials used to log in to the web interface. It is not possible to require username and password only for specific profiles due to an OpenVPN server configuration limitation.

## Recommendation
It is recommended to enable automatic OS updates on the VPN server, preferably via unattended-upgrades. All installed packages come from the standard Debian repository.
