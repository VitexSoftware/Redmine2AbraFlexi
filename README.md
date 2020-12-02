Redmine to AbraFlexi importer
============================

![Logo](https://github.com/VitexSoftware/Redmine2AbraFlexi/raw/master/project-logo.png "Project Logo")

Ze zvolených projektů v Redmine vygeneruje fakturu ve AbraFlexi.

Nastavení
---------

Potřebujeme Redmine s povoleným api a [config.json](config.json) s patřičně vyplněnými položkami:

```
{
    "EASE_APPNAME": "Redmin2AbraFlexi",
    "EASE_LOGGER": "syslog",
    "ABRAFLEXI_URL": "https://demo.abraflexi.eu:5434",
    "ABRAFLEXI_LOGIN": "demo",
    "ABRAFLEXI_PASSWORD": "demo",
    "ABRAFLEXI_COMPANY": "demo",
    "ABRAFLEXI_TYP_FAKTURY": "FAKTURA",
    "ABRAFLEXI_CENIK": "WORK",
    "REDMINE_URL": "https://vitexsoftware.cz/redmine",
    "REDMINE_USERNAME": "apikey",
    "REDMINE_PASSWORD": "",
    "debug": "false"
}
```

**REDMINE_USERNAME**     Do redmine je možné se přihlašovat buď s jménem a heslem uživatele, který má dostatečná práva aby měl dostupné projekty a položky ze kterých se sestavuje faktura, nebo [jeho API klíčem a náhodným heslem](http://www.redmine.org/projects/redmine/wiki/Rest_api#Authentication).
**ABRAFLEXI_CENIK**       je položka ceníku obvykle vyjadřující "člověkohodiny"
**ABRAFLEXI_TYP_FAKTURY** Typ faktury vydané 

Použití
-------

Na stránce [redmineprojects.php](src/redmineprojects.php) se zvolí ze kterých projektů se budou exportovat odpracované časy

![Výběr projektů](vyber-projektu.png?raw=true "Volba projektů")

Po odeslání formuláře se na další [stránce](src/redminetimeentries.php) zobrazí vygenerovaná faktura.

![Vygenerovaná faktura](hotovo.png?raw=true "Výsledná faktura")

Instalace
---------

Složka **src** je kořen webu který má být dostupný webserveru. 
ve složce projektu je třeba spustit **composer install** který doinstaluje potřebné závislosti.


Požadavky
---------

https://github.com/ANovitsky/redmine_shared_api

Kód je primárně psaný pro Debian, pro provoz na jiném systému, např windows je třeba doplnit požadované css a skripty.

[Statistiky práce na projektu](https://wakatime.com/@5abba9ca-813e-43ac-9b5f-b1cfdf3dc1c7/projects/zgctsnwibv)

Napsáno s použitím knihovny [AbraFlexi](https://github.com/Spoje-NET/php-abraflexi)
