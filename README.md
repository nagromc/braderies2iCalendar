# braderies2iCalendar #

`braderies2iCalendar` permet d'extraire un calendrier au format iCalendar depuis
les données de sites référençant des braderies.
Le script supporte actuellement deux sources :
[vide-greniers.org](http://vide-greniers.org) et [sabradou.com](http://www.sabradou.com).


## Pré-requis & dépendances ##

* [`iCalcreator`](http://kigkonsult.se/iCalcreator/index.php) : implémentation
en PHP des RFC 2445 et 5545. Permet la génération de fichiers au format
iCalendar.
* [`libcurl`](http://curl.haxx.se/libcurl/) : librairie permettant notamment de
transférer des fichiers via le protocole HTTP.


## Installation ##

* Activer le module `curl` du fichier `php.ini` (`extension=php_curl.dll`).
* Copier les fichiers `model.class.php`, `getcal.php`, et
`lib/iCalcreator/iCalcreator.class.php` dans le répertoire d'installation désiré.


## Utilisation ##

`/getcal.php?[options]`

Options :

* `vgOrg` : \[Optionnel\] Liste des départements dont il faut extraire les données
depuis le site vide-greniers.org. Sous la forme `vgOrg=59|62|...`
* `sbdCom` :  \[Optionnel\] Liste des villes dont il faut extraire les données
depuis le site sabradou.com. 
Sous la forme `sbdCom=59-nord/l/lille/|62-pas-de-calais/l/le-touquet-paris-plage/|...`
Attention à ne pas oublier le `/` à la fin de chaque ville.

Remarque : au moins une option est nécessaire.

Pour plus de détails sur les paramètres, consulter la partie "Obtention des 
paramètres" ci-dessous.


### Exemples d'utilisation ###

Voici quelques exemples d'utilisation :

    # Récupérera les dates des braderies des départements du Nord et du Pas-de-Calais depuis vide-greniers.org
    http://www.monserveur.org/braderies2iCalendar/getcal.php?vgOrg=59|62
    
    # Récupérera les dates des braderies des villes de Lille et du Touquet depuis sabradou.com
    http://www.monserveur.org/braderies2iCalendar/getcal.php?sbdCom=59-nord/l/lille/|62-pas-de-calais/l/le-touquet-paris-plage/
    
    # Récupérera les dates des braderies du département du Nord depuis vide-greniers.org, et de la ville de Lille depuis sabradou.com
    http://www.monserveur.org/braderies2iCalendar/getcal.php?vgOrg=59&sbdCom=59-nord/l/lille/


### Obtention des paramètres ###

#### vide-greniers.org ####

vide-greniers.org fournit dans une seule page les dates pour les départements
entiers. Pour obtenir les paramètres à insérer dans l'URL d'appel au script, il
suffit de saisir le département correspondant à la recherche.

#### sabradou.com ####

sabradou.com ne fournit pas toutes les dates d'un département dans une page unique.
Il les fournit ville par ville. Par conséquent, il est nécessaire de donner à
`braderies2iCalendar` l'URL des pages correspondant à chaque ville dont il faut
extraire les dates.

Par exemple, si on veut extraire les dates de la ville de Lille, l'URL de
sabradou.com sera :

    http://www.sabradou.com/59-nord/l/lille/index.php
    
Il suffit alors de copier/coller `59-nord/l/lille/index.php` ou `59-nord/l/lille/`
dans l'URL du script `braderies2iCalendar`.

