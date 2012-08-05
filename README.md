braderies2iCalendar
===================

`braderies2iCalendar` permet d'extraire un calendrier au format iCalendar depuis
les données de sites référençant des braderies.
Le script supporte actuellement deux sources :
[vide-greniers.org](http://vide-greniers.org) et [sabradou.com](http://www.sabradou.com).


Pré-requis & dépendances
------------------------

* [`iCalcreator`](http://kigkonsult.se/iCalcreator/index.php) : implémentation
en PHP des RFC 2445 et 5445. Permet la génération de fichiers au format
iCalendar.
* [`libcurl`](http://curl.haxx.se/libcurl/) : librairie permettant notamment de
transférer des fichiers via le protocole HTTP.


Installation
------------

* Activer le module `curl` du fichier `php.ini` (`extension=php_curl.dll`).
* Copier les fichiers `model.class.php`, `getcal.php`, et
`lib/iCalcreator/iCalcreator.class.php` dans le répertoire d'installation désiré.


Utilisation
-----------

`/getcal.php?[options]`

Options :

* `vgOrg` : \[Optionnel\] Liste des départements dont il faut extraire les données
depuis le site vide-greniers.org. Sous la forme `vgOrg=59|62|...`
* `sbdCom` :  \[Optionnel\] Liste des villes dont il faut extraire les données
depuis le site sabradou.com. 
Sous la forme `sbdCom=59-nord/l/lille|62-pas-de-calais/l/le-touquet-paris-plage|...`

Remarque : au moins une option est nécessaire.
