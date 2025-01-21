Redmine to AbraFlexi importer
=============================

![Logo](redmine2abraflexi.svg?raw=true "Project Logo")

Generates an invoice in AbraFlexi from hours worked in Redmine.

Configuration
-------------

We need Redmine with enabled API and `.env` file with appropriately filled items:

```env
ABRAFLEXI_URL=https://demo.flexibee.eu:5434
ABRAFLEXI_LOGIN=winstrom
ABRAFLEXI_PASSWORD=winstrom
ABRAFLEXI_COMPANY=demo_de
ABRAFLEXI_SEND=True

ABRAFLEXI_CUSTOMER=SPOJE.NET
ABRAFLEXI_TYP_FAKTURY=FAKTURA
ABRAFLEXI_CENIK=WORK

REDMINE_URL=https://your.redmine.url/
REDMINE_USERNAME=username_redmine_token
REDMINE_PASSWORD=empty_for_token

REDMINE_SCOPE=last_month
REDMINE_PROJECT=project_name
REDMINE_WORKER_MAIL=vitezslav.dvorak@spojenet.cz

APP_DEBUG=True
EASE_LOGGER=console
```

**REDMINE_USERNAME**     You can log in to Redmine either with the username and password of a user who has sufficient rights to access projects and items from which the invoice is generated, or [with their API key and a random password](http://www.redmine.org/projects/redmine/wiki/Rest_api#Authentication).
**ABRAFLEXI_CENIK**       is a price list item usually representing "man-hours"
**ABRAFLEXI_TYP_FAKTURY** Type of issued invoice

Import Scopes
-------------

  * `today` 
  * `yesterday`
  * `last_week`
  * `last_month`
  * `last_two_months`
  * `previous_month` 
  * `two_months_ago`
  * `this_year` 
  * `January`  
  * `February` 
  * `March` 
  * `April` 
  * `May` 
  * `June` 
  * `July` 
  * `August` 
  * `September` 
  * `October` 
  * `November` 
  * `December` 
  * `2024-08-05>2024-08-11` - custom scope 
  * `2024-10-11` - only specific day


(The configuration can also be set only as environment variables.)

MultiFlexi
----------

Redmine2AbraFlexi is ready to run as a [MultiFlexi](https://multiflexi.eu) application.
See the full list of ready-to-run applications within the MultiFlexi platform on the [application list page](https://www.multiflexi.eu/apps.php).

[![MultiFlexi App](https://github.com/VitexSoftware/MultiFlexi/blob/main/doc/multiflexi-app.svg)](https://www.multiflexi.eu/apps.php)


Installation
------------

A repository of Debian packages is available:

```shell
sudo apt install lsb-release wget apt-transport-https bzip2

wget -qO- https://repo.vitexsoftware.com/keyring.gpg | sudo tee /etc/apt/trusted.gpg.d/vitexsoftware.gpg
echo "deb [signed-by=/etc/apt/trusted.gpg.d/vitexsoftware.gpg]  https://repo.vitexsoftware.com  $(lsb_release -sc) main" | sudo tee /etc/apt/sources.list.d/vitexsoftware.list
sudo apt update

sudo apt install redmine2abraflexi
```

Requirements
------------

https://github.com/ANovitsky/redmine_shared_api

The code is primarily written for Debian, for running on another system, e.g. Windows, it is necessary to add the required CSS and scripts.

[Project work statistics](https://wakatime.com/@5abba9ca-813e-43ac-9b5f-b1cfdf3dc1c7/projects/zgctsnwibv)

Written using the [AbraFlexi](https://github.com/Spoje-NET/php-abraflexi) library.

See also:

 * https://github.com/VitexSoftware/Toggl-to-AbraFlexi
 * https://github.com/sizek-cz/Kimai2AbraFlexi
