# MvcCore - Extension - Debug - Nette Tracy - Panel Refresh

[![Latest Stable Version](https://img.shields.io/badge/Stable-v5.0.5-brightgreen.svg?style=plastic)](https://github.com/mvccore/ext-debug-tracy-refresh/releases)
[![License](https://img.shields.io/badge/License-BSD%203-brightgreen.svg?style=plastic)](https://mvccore.github.io/docs/mvccore/5.0.0/LICENSE.md)
![PHP Version](https://img.shields.io/badge/PHP->=5.4-brightgreen.svg?style=plastic)

MvcCore Debug Tracy Extension to to automatic refresh of current browser tab on selected directory changes.

**THIS PACKAGE IS HIGHLY RECOMMENDED TO USE ONLY IN DEVELPMENT ENVIRONMENT!**


## Installation
```shell
composer require mvccore/ext-debug-tracy-refresh
```

#### Linux

User used to execute composer.phar and php scripts over web server has to have:
 - Execute privileges in Node.JS directory.  
   If there is necessary to add those privileges into `/etc/sudoers`, you can use:
   `www ALL = NOPASSWD: /root/.nvm/versions/node/vXX.XX.XX/bin`
 - Node.JS bin dir in `$PATH` environment variable or  
   the dir could be in `system.ini` config in section `[debug]`:  
   `refresh.nodePath = "/root/.nvm/versions/node/vXX.XX.XX/bin"`

#### Windows

User used to execute composer.phar and php scripts over web server has to have:
 - Execute privileges in Node.JS directory, but Windows mostly doesn't care about this.
 - Node.JS bin dir in `%PATH%` environment variable or  
   the dir could be in `system.ini` config in section `[debug]`:  
   `refresh.nodePath = "/root/.nvm/versions/node/vXX.XX.XX/bin"`

## How It Works

##### Features

- When there is detected file change, page is automaticly refreshed.
- You can select only directories (with all subdirectories and files) you need 
  to monitor file changes.
- You can configure exclude patterns to exclude huge directories like  
  `.git`, `.hg`, `.svn`, `vendor`, `~/Var` or any other directories or files  
  by path or JS regular expression definition.
- You can configure extensions to include only to detect file changes.
- More specific include/exclude filters causes faster monitoring start!
- Server side application with WebSocket connection ends itself automatically,  
  if there are no WebSocket connection after one minute, so you don't care  
  about to stop it somewhere.

##### Instalation

The extension executes command `npm install` durring instalation with `composer.phar`.  
You can do it manually if it fails in extension root directory, where is `package.json`.  
Npm instalation creates `./node_modules/` directory in extension root dorectory  
and downloads a few packages (approx. 20). There is downloaded npm package  
`@mvccore/ext-debug-tracy-refresh-js`, originaly written in TypeScript with prebuilded  
Javascript files, used for Tracy debug panel and server side app in Node.JS.  
When there is started file system monitoring from Tracy debug panel,  
there is started via AJAX request a Node.JS application on server with WebSocket  
connection on configurable adress (`$_SERVER['SERVER_NAME']` by default)  
and port (9006 by default). You need to care about privileges for user  
used to execute PHP scripts over web server to have execute privileges to run Node.JS. 

## Configuration

Configuration is possible in `system.ini` config with those properties:
```ini
...
[debug]
; there is always used server name by application request
refresh.address  = 127.0.0.1
; you need to open this port in server firewall
refresh.port     = 9006
; you need to have exeute privileges for user used 
; to execute composer.phar and PHP scripts over web server:
refresh.nodePath = "/root/.nvm/versions/node/vXX.XX.XX/bin"
...
```