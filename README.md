T3sports Frontend
=================

[![cfc_league_fe](ext_icon.gif)](https://github.com/digedag/cfc_league_fe)
[![Latest Stable Version](https://img.shields.io/packagist/v/digedag/cfc-league-fe.svg?maxAge=3600)](https://packagist.org/packages/digedag/cfc-league-fe)
[![Total Downloads](https://img.shields.io/packagist/dt/digedag/cfc-league-fe.svg?maxAge=3600)](https://packagist.org/packages/digedag/cfc-league-fe)
[![License](https://img.shields.io/packagist/l/digedag/cfc-league-fe.svg?maxAge=3600)](https://packagist.org/packages/digedag/cfc-league-fe)
[CHANGELOG](CHANGELOG.md)

Extensive extension for Content Management System TYPO3 to manage sportclubs and competitions. This part of T3sports 
contains the frontend plugins to show score tables, match reports, player archives...

T3sports is the most extensive extension for sports management for CMS TYPO3. It is used by many well known clubs in Germany, 
Austria and Switzerland.

**Keep update to date**
It is **not** recommended to use the TER to install this extension, since there are only rare updates into TER. You should better 
install and update from this Github repo. There are to ways to do this:

1. Use composer

Add these entries to your composer.json

```json
	"require": {
	  "digedag/rn-base": "*",
	  "digedag/cfc-league": "*@dev-master",
	  "digedag/cfc-league-fe": "*@dev-master",
```

Run composer update:
```bash
composer update --prefer-dist digedag/cfc-league-fe
```

2. Manual checkout from Github

You can clone the source from Github into directory **typo3conf/ext**.
```bash
cd typo3conf/ext
git clone https://github.com/digedag/cfc_league_fe.git
```
To pull latest changes:
```bash
cd typo3conf/ext/cfc_league_fe
git pull
```

