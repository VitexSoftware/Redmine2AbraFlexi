Redmine to FlexiBee importer
============================

![Logo](https://github.com/VitexSoftware/Redmine2FlexiBee/raw/master/project-logo.png "Project Logo")

Ze zvolených projektů v Redmine vygeneruje fakturu ve FlexiBee.

Nastavení
---------

Potřebujeme Redmine s povoleným api a [config.json](config.json) s patřičně vyplněnými položkami:

```
{
    "EASE_APPNAME": "Redmin2FlexiBee",
    "EASE_LOGGER": "syslog",
    "FLEXIBEE_URL": "https://demo.flexibee.eu:5434",
    "FLEXIBEE_LOGIN": "demo",
    "FLEXIBEE_PASSWORD": "demo",
    "FLEXIBEE_COMPANY": "demo",
    "FLEXIBEE_TYP_FAKTURY": "FAKTURA",
    "FLEXIBEE_CENIK": "WORK",
    "REDMINE_URL": "https://vitexsoftware.cz/redmine",
    "REDMINE_USERNAME": "apikey",
    "REDMINE_PASSWORD": "",
    "debug": "false"
}
```

**REDMINE_USERNAME**     Do redmine je možné se přihlašovat buď s jménem a heslem uživatele, který má dostatečná práva aby měl dostupné projekty a položky ze kterých se sestavuje faktura, nebo [jeho API klíčem a náhodným heslem](http://www.redmine.org/projects/redmine/wiki/Rest_api#Authentication).
**FLEXIBEE_CENIK**       je položka ceníku obvykle vyjadřující "člověkohodiny"
**FLEXIBEE_TYP_FAKTURY** Typ faktury vydané 

Použití
-------

Na stránce [redmineprojects.php](src/redmineprojects.php) se zvolí ze kterých projektů se budou exportovat odpracované časy

![Výběr projektů](https://github.com/VitexSoftware/Redmine2FlexiBee/raw/master/vyber-projektu.png "Volba projektů")

Po odeslání formuláře se na další [stránce](src/redminetimeentries.php) zobrazí vygenerovaná faktura.

![Vygenerovaná faktura](https://github.com/VitexSoftware/Redmine2FlexiBee/raw/master/hotovo.png "Výsledná faktura")

Instalace
---------

Složka **src** je kořen webu který má být dostupný webserveru. 
ve složce projektu je třeba spustit **composer install** který doinstaluje potřebné závislosti.


Požadavky
---------

Kód je primárně psaný pro Debian, pro provoz na jiném systému, např windows je třeba doplnit požadované css a skripty.

[Statistiky práce na projektu](https://wakatime.com/@5abba9ca-813e-43ac-9b5f-b1cfdf3dc1c7/projects/zgctsnwibv)

Napsáno s použitím knihovny [FlexiPeeHP](https://github.com/Spoje-NET/FlexiPeeHP)
