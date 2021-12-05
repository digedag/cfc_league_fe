T3sports Frontend
=================

![TYPO3 compatibility](https://img.shields.io/badge/TYPO3-8.7%20%7C%209.5%20%7C%2010.4-9cf?maxAge=3600&style=flat-square&logo=typo3)
<a href="https://github.com/digedag/cfc_league_fe"><img src="ext_icon.svg" width="20"></a>
[![Latest Stable Version](https://img.shields.io/packagist/v/digedag/cfc-league-fe.svg?maxAge=3600)](https://packagist.org/packages/digedag/cfc-league-fe)
[![Total Downloads](https://img.shields.io/packagist/dt/digedag/cfc-league-fe.svg?maxAge=3600)](https://packagist.org/packages/digedag/cfc-league-fe)
[![Code Style](https://github.com/digedag/cfc_league_fe/actions/workflows/php.yaml/badge.svg)](https://github.com/digedag/cfc_league_fe/actions/workflows/php.yaml)
[![License](https://img.shields.io/packagist/l/digedag/cfc-league-fe.svg?maxAge=3600)](https://packagist.org/packages/digedag/cfc-league-fe)
<a href="https://twitter.com/intent/follow?screen_name=T3sports1">
  <img src="https://img.shields.io/twitter/follow/T3sports1.svg?label=Follow%20@T3sports1" alt="Follow @T3sports1" />
</a>
[CHANGELOG](CHANGELOG.md)

Extensive extension for Content Management System TYPO3 to manage sportclubs and competitions. This part of T3sports 
contains the frontend plugins to show score tables, match reports, player archives...

T3sports is the most extensive extension for sports management for CMS TYPO3. It is used by many well known clubs in Germany, 
Austria and Switzerland.

**Keep update to date**
It is **not** recommended to use the TER to install this extension, since there are only rare updates into TER. You should better install and update from this Github repo. There are three ways to do this:

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
3. Update with a script
```bash
#!/bin/bash
array=( rn_base cfc_league cfc_league_fe )
for i in "${array[@]}"
do
        wget -O $i.zip https://github.com/digedag/$i/archive/master.zip
        rm -rf $i
        unzip $i.zip
        mv $i-master $i
        rm $i.zip
done
```
