Redmine to AbraFlexi importer
=============================

![Logo](project-logo.png?raw=true "Project Logo")

Z odpracovaných hodin v Redmine vygeneruje fakturu ve AbraFlexi.

Nastavení
---------

Potřebujeme Redmine s povoleným api a `.env` s patřičně vyplněnými položkami:

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
REDMINE_WORKER_MAIL=vitezslav.dvorak@spojenet.cz

APP_DEBUG=True
EASE_LOGGER=console
```

**REDMINE_USERNAME**     Do redmine je možné se přihlašovat buď s jménem a heslem uživatele, který má dostatečná práva aby měl dostupné projekty a položky ze kterých se sestavuje faktura, nebo [jeho API klíčem a náhodným heslem](http://www.redmine.org/projects/redmine/wiki/Rest_api#Authentication).
**ABRAFLEXI_CENIK**       je položka ceníku obvykle vyjadřující "člověkohodiny"
**ABRAFLEXI_TYP_FAKTURY** Typ faktury vydané 

(Konfiguraci je možné taktéž pouze nastavit jako proměnné prostředí.)

Instalace
---------



Požadavky
---------

https://github.com/ANovitsky/redmine_shared_api

Kód je primárně psaný pro Debian, pro provoz na jiném systému, např windows je třeba doplnit požadované css a skripty.

[Statistiky práce na projektu](https://wakatime.com/@5abba9ca-813e-43ac-9b5f-b1cfdf3dc1c7/projects/zgctsnwibv)

Napsáno s použitím knihovny [AbraFlexi](https://github.com/Spoje-NET/php-abraflexi)


See also:

 * https://github.com/VitexSoftware/Toggl-to-AbraFlexi
 * https://github.com/sizek-cz/Kimai2AbraFlexi
