<?php

require_once "./lib/iCalcreator/iCalcreator.class.php";
require_once "./model.class.php";



// définition des sources utilisables
$SOURCES = array(
		"vgOrg" =>
			array(
				"nomSite" => "vide-grenier.org",
				"pattern" => "#^\d{2}$#"
			)
		,
		"sbdCom" =>
			array(
				"nomSite" => "sabradou.com",
				"pattern" => "#^\d{2}-.+?/[a-z]/.+?$#"
			)
);

const SEPARATEUR_SOURCE = "|";



function usage() {
	$usage = "Utilisation: ".$_SERVER['SCRIPT_NAME']."?[options]\n";
	$usage .= "\n";
	$usage .= "    Options :\n";
	$usage .= "        vgOrg :   [Optionnel] Liste des départements dont il faut extraire les données depuis le site vide-greniers.org. Sous la forme vgOrg=59".SEPARATEUR_SOURCE."62".SEPARATEUR_SOURCE."...\n";
	$usage .= "        sbdCom :  [Optionnel] Liste des villes dont il faut extraire les données depuis le site sabradou.com. Sous la forme sbdCom=59-nord/l/lille".SEPARATEUR_SOURCE."62-pas-de-calais/l/le-touquet-paris-plage".SEPARATEUR_SOURCE."...\n";
	$usage .= "\n";
	$usage .= "    Remarque : au moins une option est nécessaire.\n";
	return $usage;
}

/**
 * Met au format HTML le résultat retourné par {@link usage}.
 */
function printUsage() {
	echo "<pre>";
	echo usage();
	echo "</pre>";
}

/**
 * Récupère la liste de tous les sites à analyser.
 * 
 * @param array $listeSourcesSupportees La liste des sites déclarés comme analysables.
 * @return array Un tableau contenant tous les sites à analyser sous la forme :
 * <pre>
 * [
 *   'vgOrg' => 
 *     [
 *       0 => '59'
 *       1 => '62'
 *     ]
 *   'sbdCom' => 
 *     [
 *       0 => '59-nord/l/lille'
 *       1 => '62-pas-de-calais/l/le-touquet-paris-plage'
 *     ]
 * ]
 * </pre>
 */
function getSources($listeSourcesSupportees) {
	$listeSources = array();
	// récupération des sources et des paramètres associés
	foreach ($_GET as $codeSrc => $paramsSrc) {
		// si le paramètre est présent dans la liste des sites supportés
		if(in_array($codeSrc, array_keys($listeSourcesSupportees))) {
			// alors on l'ajoute à la liste des sources à analyser
			$listeSources[$codeSrc] = $paramsSrc;
		}
	}
	// vérification de la présence d'au moins une source
	if (empty($listeSources)) {
		echo "Aucune source n'a été paramétrée. Les sources supportées sont [".implode(', ', array_keys($listeSourcesSupportees))."].";
		printUsage();
		exit -1;
	}
	
	$listeRetour = array();
	// validation des paramètres des sites à analyser
	foreach ($listeSources as $codeSrc => $paramsSrcText) {
		$paramsSrcArray = explode("|", $paramsSrcText);
		foreach ($paramsSrcArray as $param) {
			if (! preg_match($listeSourcesSupportees[$codeSrc]["pattern"], $param)) {
				echo "Le paramètre [".$param."] n'est pas au format attendu.";
				printUsage();
				exit -1;
			} else {
				// ajout de la source à la liste des pages à analyser
				$listeRetour[$codeSrc] = $paramsSrcArray;
			}
		}
	}
	
	return $listeRetour;
}





if (isset($_GET["debug"])) {
	header("Content-type: text/html; charset=utf-8");
} else {
	// permet le téléchargement du fichier
	header("Content-type: text/calendar; charset=utf-8");
}

$listeEvt = array();
// récupération des sources et des paramètres associés
$listeSourcesAAnalyser = getSources($SOURCES);
// récupération des paramètres en fonction des sources trouvées
foreach ($listeSourcesAAnalyser as $source => $paramSource) {
	foreach ($paramSource as $param) {
		switch ($source) {
			case "vgOrg":
				$parser = new VideTiretGreniersPointOrgParser($param);
				break;
			
			case "sbdCom":
				$parser = new SabradouPointComParser($param);
				break;
		}
		
		$evmtsTmp = $parser->parse();
		$listeEvt = array_merge($listeEvt, $evmtsTmp);
	}
}

if (isset($_GET["debug"])) {
	echo "<pre>";
	foreach ($listeEvt as $evt) {
		echo $evt;
	}
	echo "</pre>";
} else {
	// sortie au format iCalendar
	$sourcesEnteteICal = array();
	foreach (array_keys($listeSourcesAAnalyser) as $src) {
		array_push($sourcesEnteteICal, $SOURCES[$src]["nomSite"]);
	}
	$vcal = new vcalendar( array("unique_id" => "[".implode(',', $sourcesEnteteICal)."]") );
	foreach ($listeEvt as $evmt) {
		$event = &$vcal->newComponent("vevent");
		// si la date de fin n'est pas positionnée
		if ($evmt->dateFin == 0) {
			// on déclare l'évènement comme un évènement à la journée
			$event->setProperty("dtstart", array("timestamp" => $evmt->dateDebut, "tz" => "Europe/Paris"), array("VALUE" => "DATE"));
		} else {
			// on déclare l'évènement entre deux dates
			$event->setProperty("dtstart", array("timestamp" => $evmt->dateDebut, "tz" => "Europe/Paris"), array("VALUE" => "DATE-TIME"));
			$event->setProperty("dtend", array("timestamp" => $evmt->dateFin, "tz" => "Europe/Paris"), array("VALUE" => "DATE-TIME"));
		}
		$event->setProperty("summary", '['.$evmt->lieu.'] '.$evmt->description);
		$event->setProperty("description", $evmt->description.'\n'.$evmt->lien);
		$event->setProperty("location", $evmt->lieu);
	}
	
	echo $vcal->createCalendar();
}

?>