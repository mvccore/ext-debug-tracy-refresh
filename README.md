# MvcCore - Extension - Debug - Nette Tracy - Panel Refresh

[![Latest Stable Version](https://img.shields.io/badge/Stable-v5.0.0-brightgreen.svg?style=plastic)](https://github.com/mvccore/ext-debug-tracy-refresh/releases)
[![License](https://img.shields.io/badge/License-BSD%203-brightgreen.svg?style=plastic)](https://mvccore.github.io/docs/mvccore/5.0.0/LICENSE.md)
![PHP Version](https://img.shields.io/badge/PHP->=5.4-brightgreen.svg?style=plastic)

MvcCore Debug Tracy Extension to to automatic refresh of current browser tab on selected server directory changes.

## Installation
```shell
composer require mvccore/ext-debug-tracy-refresh
```

1.
- na serveru se jen přenese jaká url se případně řeší	
	- nejprve se to zjistí ze sessionStorrage
	- pokud ano, tak se zkusí vytvořit websocket spojení, pokud dojde k chybě, že tam nic není,
	  tak se provede ajax request pro nastartování
	- po odpovědi se znovu udělá websocket spojení
	- změní se ikona a inicializje form podle session storrage

2. pokud klient klikne na start
	- pokud to neběží - provede se spec. ajax na rozběhnutí, jakmile to běží, tak:
	- připojí se to s url na server a monitoruje se to
	- při reloadu stránky se vyřeší close a pak znovu connection