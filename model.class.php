<?php

error_reporting(E_ALL);
// permet l'utilisation de dates au format français
setlocale(LC_TIME, "fr_FR.UTF8");



/**
 * Décrit un évènement.
 */
class Evenement {
	
	const FORMAT_DATE_TO_STRING = "%d/%m/%Y %T (%s)";
	
	/**
	 * La description de l'évènement.
	 * @var string
	 */
	private $description;
	
	/**
	 * La date de début de l'évènement.
	 * @var timestamp Unix
	 */
	private $dateDebut;
	
	/**
	 * La date de fin de l'évènement.
	 * @var timestamp Unix
	 */
	private $dateFin;
	
	/**
	 * L'endroit où aura lieu l'évènement.
	 * @var string
	 */
	private $lieu;
	
	/**
	 * Lien vers la page de l'évènement.
	 * @var string
	 */
	private $lien;
	
	
	public function __get($property) {
		if (property_exists($this, $property)) {
			return $this->$property;
		}
	}
	
	public function __set($property, $value) {
		if (property_exists($this, $property)) {
			$this->$property = $value;
		}
		
		return $this;
	}
	
	public function __toString() {
		$str = "Evenement {\n";
		$str .= "\tlieu : ".$this->lieu."\n";
		$str .= "\tdateDebut : ".strftime(self::FORMAT_DATE_TO_STRING, $this->dateDebut)."\n";
		$str .= "\tdateFin : ".strftime(self::FORMAT_DATE_TO_STRING, $this->dateFin)."\n";
		$str .= "\tdescription : ".$this->description."\n";
		$str .= "\tlien : ".$this->lien."\n";
		$str .= "}\n";
		return $str;
	}
	
}



/**
 * <p>
 * Classe abstraite dont doivent hériter les parsers de chacun des sites à
 * analyser.
 * 
 * <p>
 * Permet une implémentation différente de {@link AbstractHTMLParser::parse()}
 * pour chaque site.
 */
abstract class AbstractHTMLParser {
	
	/** L'URL de la page contenant les informations à extraire. */
	protected $url = null;
	
	/** L'expression XPath permettant d'extraire les informations dont on a besoin. */
 	protected $xpath = null;
 	
 	/**
 	 * Format de la date de l'évènement.
 	 * 
 	 * @see strftime
 	 */
 	protected $dateEvenementPattern = null;
	
	public function __construct() {
		if (is_null($this->url) || is_null($this->xpath) || is_null($this->dateEvenementPattern)) {
			throw new Exception("Les attributs 'url', 'xpath' et 'dateEvenementPattern' doivent être positionnés.");
		}
	}
	
	/**
	 * Retourne le {@link DOMDocument} représentant la page HTML désignée dans 
	 * l'attribut url.
	 * 
	 * @return DOMDocument un objet DOMDocument.
	 */
	protected function getDOMDocument() {
		// récupération de la page HTML
		$ch = curl_init($this->url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$data = curl_exec($ch);
		if ($data == null || curl_getinfo($ch, CURLINFO_HTTP_CODE) != 200) {
			throw new Exception("La page [".$this->url."] n'est pas accessible.");
		}
		curl_close($ch);
		
		// charge en mémoire la représentation DOM de la page HTML récupérée
		$domDoc = new DOMDocument();
		// permet d'ignorer les erreurs si le document HTML analysé est mal formé
		libxml_use_internal_errors(true);
		if (!$domDoc->loadHTML($data)) {
			throw new DOMException("Impossible de charger le DOM en mémoire.");
		}
		// revient à la configuration initiale
		libxml_use_internal_errors(false);
		
		return $domDoc;
	}
	
	/**
	 * Récupère depuis un {@link DOMDocument} la liste des noeuds pointés par le
	 * chemin XPath. Le {@link DOMNode} permet de spécifier un contexte à partir
	 * duquel on appliquera l'expression XPath.
	 * 
	 * @param DOMXPath $domXpath Le document DOM dans lequel on effectue la 
	 * recherche.
	 * @param string $xpath L'expression XPath permettant de trouver les noeuds.
	 * @param DOMNode $contextNode (optionnel) Permet de spécifier le noeud à
	 * partir duquel on souhaite faire la recherche.
	 * 
	 * @return DOMNodeList|bool La liste des noeuds pointés par le chemin XPath.
	 * <tt>false</tt>, si aucun noeud n'a été trouvé.
	 */
	protected function getDOMNodeList(DOMXPath $domXpath, $xpath, DOMNode $contextNode = null) {
		// applique l'expression XPath sur le DOM
		$nodeList = $domXpath->query($xpath, $contextNode);
		
		if ($nodeList == false) {
			return null;
		}
		
		return $nodeList;
	}
	
	/**
	 * Analyse un {@link DOMDocument} à partir d'une URL et d'une expression
	 * XPath.
	 * 
	 * @return array|null La liste des {@link Evenement} détectés. <tt>null</tt>
	 * si aucun évènement n'a été détecté sur la page.
	 */
	public abstract function parse();
	
}



/**
 * Classe concrète permettant d'implémenter la méthode {@link AbstractHTMLParser::parse()}
 * pour le site vide-greniers.org.
 * 
 * @see http://vide-greniers.org
 */
final class VideTiretGreniersPointOrgParser extends AbstractHTMLParser {

	/** Expression XPath récupérant la date de l'évènement */
	const DATE_XPATH = "//div[@class='initGauche'][3]/table[2]/tr/td/table[2]/tr/td[@valign='top']/table[@width='850']/tr/td[@valign='top']/b";
	/** Expression XPath récupérant les évènements ayant lieu ce jour */
	const EVENEMENT_XPATH = "//div[@class='initGauche'][3]/table[2]/tr/td/table[2]/tr/td[@valign='top']/table/tr/td[@valign='top']/table[@summary='manifestations' and @class='cadreMnf']/tr[2]/td[2]/table[@class='cadre']/tr/td[@valign='top'][1]/table[@class='cadre']";
	/** Expression XPath relative récupérant un évènement à partir de la liste des évènements */
	const EVENEMENT_RELATIF_XPATH = "tr/td[1][not(descendant::hr)]";
	/** Expression XPath relative récupérant la description de l'évènement */
	const DESCRIPTION_RELATIF_XPATH = "b/i | font/b/i";
	/** Expression XPath relative récupérant le lieu de l'évènement */
	const LIEU_RELATIF_XPATH = "a | font/a";
	/** Expression XPath relative récupérant le lien vers le détail de l'évènement */
	const LIEN_RELATIF_XPATH = "a/@href | font/a/@href";
	/** Début de l'URL permettant de construire le lien menant vers le détail de l'évènement */
	const URL_BASE_DETAIL_EVENEMENT = "http://vide-greniers.org";
	/** Expression régulière permettant d'extraire la description des parenthèses entourantes */
	const DESCRIPTION_PATTERN = "#^\((.*)\)$#";
	
	function __construct($noDpt) {
		$this->url = "http://vide-greniers.org/agendaDepartement.php?departement=".$noDpt;
		$this->xpath = self::DATE_XPATH." | ".self::EVENEMENT_XPATH;
		$this->dateEvenementPattern = "%A %d %B %Y";
		
		parent::__construct();
	}
	
	function parse() {
		$domDoc = parent::getDOMDocument();
		
		// applique l'expression XPath sur le DOM
		$domXpath = new DOMXPath($domDoc);
		$nodeList = parent::getDOMNodeList($domXpath, $this->xpath);
		
		$i = 0;
		$dateTmp;
		$evmtTmp;
		$evmtList = array();
		// itération sur les résultats obtenus
		foreach ($nodeList as $node) {
			switch ($i++ % 2) {
				// si le noeud est paire, c'est la date des évènements
				case 0:
					$dateText = $node->childNodes->item(0)->nodeValue;
					$dateTmp = strptime($dateText, $this->dateEvenementPattern);
					break;
					
				// si le noeud est impaire, c'est le lieu de l'évènement
				case 1:
					$nodeEvenements = parent::getDOMNodeList($domXpath, self::EVENEMENT_RELATIF_XPATH, $node);
					foreach ($nodeEvenements as $nodeEvenement) {
						// récupération de la description de l'évènement
						$nodeDescription = parent::getDOMNodeList($domXpath, self::DESCRIPTION_RELATIF_XPATH, $nodeEvenement);
						$description = $nodeDescription->item(0)->nodeValue;
						// extrait la description des parenthèses entourantes
						$description = preg_replace(self::DESCRIPTION_PATTERN, "$1", $description);
						
						// récupération du lieu de l'évènement
						$nodeLieu = parent::getDOMNodeList($domXpath, self::LIEU_RELATIF_XPATH, $nodeEvenement);
						$lieu = $nodeLieu->item(0)->nodeValue;
						
						// récupération du lien de l'évènement
						$nodeLien = parent::getDOMNodeList($domXpath, self::LIEN_RELATIF_XPATH, $nodeEvenement);
						$lien = $nodeLien->item(0)->nodeValue;
						
						// construction de l'objet Evenement
						$evmtTmp = new Evenement();
						$evmtTmp->description = $description;
						$evmtTmp->dateDebut = DateUtil::timestampFromStrptime($dateTmp);
						$evmtTmp->lieu = $lieu;
						$evmtTmp->lien = self::URL_BASE_DETAIL_EVENEMENT.$lien;
						
						// ajoute l'évènement à la liste retournée
						array_push($evmtList, $evmtTmp);
						
						// réinitialisation des paramètres
						unset($description, $lieu, $lien);
					}
					
					// réinitialisation de la date
					unset($dateTmp);
					
					break;
			}
		}
		
		return $evmtList;
	}
	
}



/**
 * Classe concrète permettant d'implémenter la méthode {@link AbstractHTMLParser::parse()}
 * pour le site sabradou.com.
 *
 * @see http://www.sabradou.com
 */
final class SabradouPointComParser extends AbstractHTMLParser {
	
	/** Expression XPath récupérant la lieu de l'évènement */
	const LIEU_XPATH = "/html/body/div[@id='page']/div[@id='conteneur']/ul[@class='ville-colonne'][1]/li[4]/strong/a/text()";
	/** Expression XPath relative récupérant la date de l'évènement */
	const DATE_RELATIF_XPATH = "a/text()";
	/** Expression XPath relative récupérant les détails de l'évènement */
	const DETAILS_RELATIF_XPATH = "a/attribute::title";
	/** Expression XPath relative récupérant le lien vers le détail de l'évènement */
	const LIEN_RELATIF_XPATH = "a/@href";
	/** Expression régulière permettant d'extraire l'année des évènements */
	const ANNEE_PATTERN = "#^---(\d{4})---$#";
	/** Expression régulière permettant d'extraire le détail des évènements */
	const DETAILS_EVENEMENT_PATTERN = "#^(?:(.*?)\s)?(?:(\d{1,2}):(\d{2})-(\d{1,2}):(\d{2}))?(?:\s(.*?))?$#";
	/** Début de l'URL permettant de construire le lien menant vers le détail de l'évènement */
	const URL_BASE_DETAIL_EVENEMENT = "http://www.sabradou.com/";

	function __construct($finUrl) {
		$this->url = self::URL_BASE_DETAIL_EVENEMENT.$finUrl;
		$this->xpath = "/html/body/div[@id='page']/div[@id='conteneur']/ul[@class='ville-colonne'][2]/li[@class='ville-colonne-centre' or not(@class)]";
		$this->dateEvenementPattern = "%A %e %B";

		parent::__construct();
	}

	function parse() {
		$domDoc = parent::getDOMDocument();
		
		// applique l'expression XPath sur le DOM
		$domXpath = new DOMXPath($domDoc);
		$nodeList = parent::getDOMNodeList($domXpath, $this->xpath);
		
		// récupération du lieu de l'évènement
		$nodeLieu = parent::getDOMNodeList($domXpath, self::LIEU_XPATH);
		$lieu = $nodeLieu->item(0)->nodeValue;
		
		// l'année en cours de parsing. Sert sur plusieurs itérations.
		$anneeTmp;
		$evmtTmp;
		$evmtList = array();
		foreach ($nodeList as $node) {
			// s'agit-il d'une nouvelle catégorie ?
			if ($node->attributes->getNamedItem("class") != null
					&& $node->attributes->getNamedItem("class")->value == "ville-colonne-centre") {
				// s'agit-il d'une année ?
				if (preg_match(self::ANNEE_PATTERN, $node->nodeValue)) {
					$anneeTmp = preg_replace(self::ANNEE_PATTERN, "$1", $node->nodeValue);
				}
			}
			// s'agit-il d'un évènement ?
			elseif ($node->attributes->length == 0) {
				// récupération des détails de l'évènement
				$nodeDetails = parent::getDOMNodeList($domXpath, self::DETAILS_RELATIF_XPATH, $node);
				if ($nodeDetails->length == 0) {
					continue;
				}
				$detailsText = $nodeDetails->item(0)->nodeValue;
				// si on arrive à parser la description, l'horaire et éventuellement le détail
				if (preg_match(self::DETAILS_EVENEMENT_PATTERN, $detailsText, $detailsArray)) {
					$description = $detailsArray[1];
					$heureDebut = intval($detailsArray[2]);
					$minuteDebut = intval($detailsArray[3]);
					$heureFin = intval($detailsArray[4]);
					$minuteFin = intval($detailsArray[5]);
					if (count($detailsArray) > 6) {
						$details = $detailsArray[6];
					}
				// sinon, on insère tout dans la description
				} else {
					$description = $detailsText;
				}
				
				
				// récupération de la date de l'évènement
				$nodeDate = parent::getDOMNodeList($domXpath, self::DATE_RELATIF_XPATH, $node);
				// si on ne trouve pas le noeud, alors on passe au suivant
				if ($nodeDate->length == 0) {
					continue;
				}
				$dateText = $nodeDate->item(0)->nodeValue;
				$date = strptime($dateText, $this->dateEvenementPattern);
				/* si on ne parvient pas parser la date, on passe au noeud suivant
				car les évènements sans date ne nous intéressent pas */
				if (!$date) {
					continue;
				}
				// on positionne l'année récupérée plus tôt
				DateUtil::setYearToStrptime($date, $anneeTmp);
				// on positionne l'heure de début si elle existe
				$dateDebut = $date;
				// FIXME: quand l'heure n'a pas été trouvée pour l'évènement en cours, c'est l'heure de l'évènement précédent qui est positionnée
				if (isset($heureDebut) && isset($minuteDebut)) {
					$dateDebut["tm_hour"] = $heureDebut;
					$dateDebut["tm_min"] = $minuteDebut;
				}
				// on positionne l'heure de fin si elle existe
				$dateFin = $date;
				if (isset($heureFin) && isset($minuteFin)) {
					$dateFin["tm_hour"] = $heureFin;
					$dateFin["tm_min"] = $minuteFin;
				}

				
				// récupération du lien de l'évènement
				$nodeLien = parent::getDOMNodeList($domXpath, self::LIEN_RELATIF_XPATH, $node);
				$lien = $nodeLien->item(0)->nodeValue;
				
				
				// construction de l'objet Evenement
				$evmtTmp = new Evenement();
				$evmtTmp->description = $description;
				$evmtTmp->dateDebut = DateUtil::timestampFromStrptime($dateDebut);
				$evmtTmp->dateFin = DateUtil::timestampFromStrptime($dateFin);
				$evmtTmp->lieu = $lieu;
				$evmtTmp->lien = $this->url."/../".$lien;
				
				// ajoute l'évènement à la liste retournée
				array_push($evmtList, $evmtTmp);
				
				// réinitialisation des paramètres
				unset($description, $dateDebut, $dateFin, $lien, $heureDebut, 
						$minuteDebut, $heureFin, $minuteFin);
			}
		}
		
		return $evmtList;
	}

}



/**
 * Classe utilitaire gérant des dates.
 */
class DateUtil {
	
	/**
	 * Convertit un tableau retourné par {@link strptime} en un timestamp Unix.
	 * 
	 * @param array $strptime Un tableau retourné par {@link strptime}.
	 * @return int Le timestamp correspondant au tableau retourné par {@link strptime}.
	 * 
	 * @see strptime
	 */
	public static function timestampFromStrptime(array $strptime) {
		return mktime($strptime["tm_hour"], $strptime["tm_min"], $strptime["tm_sec"], $strptime["tm_mon"]+1, $strptime["tm_mday"], $strptime["tm_year"]+1900);
	}
	
	/**
	 * Positionne l'année à un tableau retourné par {@link strptime}.
	 * 
	 * @param array $strptime Le tableau associatif retourné par {@link strptime}
	 * dont il faut positionner l'année.
	 * @param int $annee L'année à positionner.
	 */
	public static function setYearToStrptime(array &$strptime, $annee) {
		if (! is_numeric($annee)) {
			throw new Excpetion("L'année n'est pas un chiffre.");
		}
		$strptime["tm_year"] = $annee - 1900;
	}
	
}

?>