<?php
/**
 * Fonction pour afficher les horaires des trains pour une station donnée.
 *
 * Cette fonction récupère les horaires en temps réel à partir de l'API de transport en commun
 * de la région Île-de-France. Elle utilise le nom de la station pour obtenir le MonitoringRef
 * correspondant à partir d'un fichier CSV, puis interroge l'API pour obtenir les horaires
 * actuels des trains en partance de cette station.
 *
 * @return void
 */
function printhoraire(){

    //clé API
$apiKey = 'Gkc6eC6BXvXwSu9XxSGLYHkRXviQRHEB';
if (isset($_GET['station']) && !empty($_GET['station'])) {
    // Lecture du fichier CSV pour obtenir le MonitoringRef correspondant au nom de la station
    //$stationName = $_GET['station'];
    $stationName = strtolower($_GET['station']); // Convertir en minuscules
    setLastVisitedStationCookie($stationName);
    $monitoringRef = '';

    $csvFile = "listes_gares.csv"; // Chemin vers le fichier CSV
    
    if (($handle = fopen($csvFile, "r")) !== FALSE) {
        while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
            //if ($data[0] === $stationName) {
            if (strtolower($data[0]) === $stationName) { // Convertir en minuscules pour la comparaison
                $monitoringRef = $data[1];
                break;
            }
        }
        fclose($handle);
    }

    if (!empty($monitoringRef)) {
        // Enregistrement de la visite
        enregistrerVisite($monitoringRef);

        $stationName=strtoupper($stationName);
        // Affichage du nom de la gare comme titre
        echo "<h3>Nom Gre selectionné : $stationName</h3>";

        // URL de l'API avec le MonitoringRef dynamique
        $url = "https://prim.iledefrance-mobilites.fr/marketplace/stop-monitoring?MonitoringRef=$monitoringRef";

        // Configuration de la requête HTTP
        $options = [
            'http' => [
                'header' => "Content-type: application/json\r\n" . "apikey: $apiKey\r\n",
                'method' => 'GET',
            ],
        ];

        // Création du contexte de la requête
        $context = stream_context_create($options);

        // Exécution de la requête et récupération de la réponse
        $response = file_get_contents($url, false, $context);

        // Conversion de la réponse JSON en tableau associatif PHP
        $data = json_decode($response, true);

        // Vérification si la réponse est valide
        if (isset($data['Siri']['ServiceDelivery']['StopMonitoringDelivery'][0]['MonitoredStopVisit'])) {
            $monitoredStopVisits = $data['Siri']['ServiceDelivery']['StopMonitoringDelivery'][0]['MonitoredStopVisit'];

            // Heure actuelle
            $currentTimestamp = time();

            // Début du tableau HTML avec tbody
            echo "<table>";
            echo "<thead><tr><th>LA LIGNE</th><th>HEURES</th><th>DESTINATIONS</th></tr></thead>"; // ouverture de thead
            echo "<tbody>";

                // Parcours des visites de la station surveillée
                foreach ($monitoredStopVisits as $monitoredStopVisit) {
                    $departureTime = isset($monitoredStopVisit['MonitoredVehicleJourney']['MonitoredCall']['ExpectedDepartureTime']) ? strtotime($monitoredStopVisit['MonitoredVehicleJourney']['MonitoredCall']['ExpectedDepartureTime']) : null;

                    // Vérification si l'heure de départ est actuelle ou future
                    if ($departureTime !== null && $departureTime >= $currentTimestamp) {
                        $lineRef = $monitoredStopVisit['MonitoredVehicleJourney']['LineRef']['value'];
                        $lineName = getLineName($lineRef); // Fonction pour récupérer le nom de la ligne
                        $departureTimeFormatted = date('H:i', $departureTime);
                        $destination = isset($monitoredStopVisit['MonitoredVehicleJourney']['DestinationName'][0]['value']) ? $monitoredStopVisit['MonitoredVehicleJourney']['DestinationName'][0]['value'] : 'Non disponible';
                        $voie = isset($monitoredStopVisit['MonitoredVehicleJourney']['MonitoredCall']['ArrivalPlatformName'][0]['value']) ? $monitoredStopVisit['MonitoredVehicleJourney']['MonitoredCall']['ArrivalPlatformName'][0]['value'] : 'Non disponible';

                         // Vérification si une destination est sélectionnée
                         if (isset($_GET['destination']) && !empty($_GET['destination'])) {
                            // Filtrer les résultats par destination sélectionnée
                            if ($destination === $_GET['destination']) {
                                // Affichage des données dans le tableau HTML
                                echo "<tr><td>$lineName</td><td>$departureTimeFormatted</td><td>$destination</td></tr>";
                                // Incrémenter le compteur après l'affichage des données
                            }
                        } else {
                            echo "<tr><td>$lineName</td><td>$departureTimeFormatted</td><td>$destination</td></tr>";
                        }                        
                    }
                }

                // Fin du tbody et début du tfoot pour la pagination
                echo "</tbody>";
                echo "</table>";
        } else {
            echo "données indisponibles";
    }

    } else {
        echo "<p>Gare $stationName non trouvé.";
    } 
}

// Récupérer la dernière gare consultée et sa date/heure
        $lastVisitedStation = getLastVisitedStationFromCookie();

        if($lastVisitedStation !== null) {
            $lastVisitedStationName = $lastVisitedStation[0];
            $lastVisitedDateTime = $lastVisitedStation[1];
            echo "Gare consultée le $lastVisitedDateTime est <a href='?station=$lastVisitedStationName'>$lastVisitedStationName</a>";
        } else {
            echo "";
        }
   
}
function getLineName($lineRef) {
    $lineName = $lineRef; // Par défaut, si le nom de la ligne n'est pas trouvé, on retourne la référence de ligne

    // Lecture du fichier CSV contenant les correspondances entre LineRef et les noms de ligne
    $csvFileLines = "listes_lines.csv"; // Chemin vers le fichier CSV des lignes

    if (($handleLines = fopen($csvFileLines, "r")) !== FALSE) {
        while (($dataLines = fgetcsv($handleLines, 1000, ";")) !== FALSE) {
            if ($dataLines[0] === $lineRef) {
                $lineName = $dataLines[1]; // Nom de la ligne correspondant à la référence de ligne
                break;
            }
        }
        fclose($handleLines);
    }

    return $lineName;
}
/**
 * Fonction pour récupérer les informations de trafic pour une gare donnée.
 *
 * Cette fonction interroge l'API de transport en commun de la région Île-de-France
 * pour obtenir les messages d'information de trafic relatifs à une gare spécifique.
 * Elle utilise le MonitoringRef correspondant au nom de la station fourni via un formulaire,
 * puis interroge l'API pour récupérer les informations de trafic pertinentes.
 *
 * @return string Les informations de trafic pour la gare spécifiée.
 */
function fetchTrafficInfo() {
    // Votre clé API
    $apiKey = 'Gkc6eC6BXvXwSu9XxSGLYHkRXviQRHEB';

    // Récupération du MonitoringRef correspondant à la gare sélectionnée depuis le formulaire
    if (isset($_GET['station']) && !empty($_GET['station'])) {
        // Lecture du fichier CSV pour obtenir le MonitoringRef correspondant au nom de la station
        $stationName = strtolower($_GET['station']); // Convertir en minuscules
        $monitoringRef = '';

        $csvFile = "listes_gares.csv"; // Chemin vers le fichier CSV

        if (($handle = fopen($csvFile, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
                if (strtolower($data[0]) === $stationName) { // Convertir en minuscules pour la comparaison
                    $monitoringRef = $data[1];
                    break;
                }
            }
        }

        if (!empty($monitoringRef)) {
            $stopPointRef = $monitoringRef;

            // Point d'entrée de l'API
            $apiUrl = 'https://prim.iledefrance-mobilites.fr/marketplace/general-message';

            // Paramètres de la requête
            $params = [
                'LineRef' => '', // Identifiant CodifLigne de la ligne
                'StopPointRef' => $stopPointRef, // Référence de l'arrêt par défaut
                'InfoChannelRef' => 'Information', // Type d'information (Information, Perturbation, Commercial)
            ];

            // Construction de l'URL avec les paramètres de la requête
            $url = $apiUrl . '?' . http_build_query($params);

            // Configuration de la requête HTTP
            $options = [
                'http' => [
                    'header' => "Content-type: application/json\r\n" . "apiKey: $apiKey\r\n",
                    'method' => 'GET',
                ],
            ];

            // Création du contexte de la requête
            $context = stream_context_create($options);

            // Exécution de la requête et récupération de la réponse
            $response = file_get_contents($url, false, $context);

            // Vérifier si la requête a réussi
            if ($response === FALSE) {
                // La requête a échoué
                return 'Erreur ';
            }

            // Conversion de la réponse JSON en tableau associatif PHP
            $data = json_decode($response, true);

            // Vérifier si les données JSON sont correctement formatées
            if ($data === NULL) {
                // Les données JSON sont mal formatées
                return 'Les données sont invalides.';
            }

            // Vérifier si des messages d'information sont disponibles
            // Vérifier si des messages d'information sont disponibles
if (isset($data['Siri']['ServiceDelivery']['GeneralMessageDelivery'])) {
    $messages = $data['Siri']['ServiceDelivery']['GeneralMessageDelivery'];

    foreach ($messages as $message) {
        if (isset($message['InfoMessage'])) {
            foreach ($message['InfoMessage'] as $info) {
                $stopPoints = $info['Content']['StopPointRef'];
                $messageText = $info['Content']['Message'][0]['MessageText']['value'];

                // Vérifier si la station correspond à celle spécifiée
                if (in_array($params['StopPointRef'], array_column($stopPoints, 'value'))) {
                    // Stocker le message
                    $message = $messageText;
                    return $message;
                }
            }
        }
    }
}
 else {
                // Aucun message d'information disponible
                return 'Aucune information';
            }
        } else {
            // MonitoringRef non trouvé
            return 'Aucune information';
        }
    } else {
        // Paramètre de station manquant
        return 'Rechecher une gare pour obtenir les informations';
    }
}

$trafficInfo = fetchTrafficInfo();
/**
 * Fonction pour enregistrer la visite d'une station dans un fichier CSV.
 *
 * Cette fonction enregistre le MonitoringRef de la station visitée dans un fichier CSV
 * afin de suivre les visites des utilisateurs.
 *
 * @param string $monitoringRef Le MonitoringRef de la station visitée.
 * @return bool Retourne true si l'enregistrement a réussi, sinon false.
 */
function enregistrerVisite($monitoringRef) {
    // Vérifier si MonitoringRef est fourni
    if (empty($monitoringRef)) {
        // Retourner false si MonitoringRef est vide
        return false;
    }
    $visite = array(
        'MonitoringRef' => $monitoringRef,
        //'DateHeureVisite' => date('Y-m-d H:i:s') // Date/heure actuelle
    );

    // Chemin vers le fichier CSV
    $fichierCSV = 'statistique.csv';

    // Ouvrir le fichier CSV en mode ajout
    $handle = fopen($fichierCSV, 'a');

    // Vérifier si l'ouverture du fichier a réussi
    if ($handle !== false) {
        // Écrire la visite dans le fichier CSV
        fputcsv($handle, $visite);
        
        // Fermer le fichier
        fclose($handle);
        
        // Retourner true si l'enregistrement a réussi
        return true;
    } else {
        // Retourner false en cas d'échec
        return false;
    }
}

/**
 * Fonction pour charger les données de visite à partir d'un fichier CSV.
 *
 * Cette fonction lit les données de visite à partir d'un fichier CSV et les retourne sous forme de tableau.
 *
 * @return array Un tableau contenant les données de visite chargées à partir du fichier CSV.
 */
function chargerDonneesVisites() {
    $donnees = array();

    // Chemin vers le fichier CSV
    $fichierCSV = 'statistique.csv';

    // Ouvrir le fichier CSV en mode lecture
    if (($handle = fopen($fichierCSV, 'r')) !== false) {
        // Lire chaque ligne du fichier CSV
        while (($data = fgetcsv($handle, 1000, ',')) !== false) {
            $donnees[] = $data; // Ajouter les données à un tableau
        }
        fclose($handle); // Fermer le fichier
    }

    return $donnees;
}

function calculerStatistiques() {
    $donnees = chargerDonneesVisites();

    $statistiques = array();
    foreach ($donnees as $visite) {
        $station = $visite[0]; // Récupérer le MonitoringRef de la station visitée
        if (!isset($statistiques[$station])) {
            $statistiques[$station] = 1; // Initialiser le compteur à 1 pour chaque nouvelle station
        } else {
            $statistiques[$station]++; // Incrémenter le compteur pour les visites supplémentaires à la même station
        }
    }

    return $statistiques;
}

/**
 * Fonction pour afficher les statistiques sous forme d'histogramme.
 *
 * Cette fonction récupère les statistiques sur les visites des gares, les transforme en données
 * compatibles avec un histogramme, puis utilise Chart.js pour afficher l'histogramme dans une
 * balise canvas HTML.
 */
function afficherStatistiques() {
    // Calculer les statistiques
    $statistiques = calculerStatistiques();

    // Extraire les données pour l'histogramme
    $nomsGares = [];
    $nombreVisites = [];
    $pourcentages = [];

    // Ignorer la première ligne
    $premiereLigne = true;

    foreach ($statistiques as $station => $nombreVisite) {
        if ($premiereLigne) {
            $premiereLigne = false;
            continue;
        }

        // Récupérer le nom de la gare
        $nomGare = getLineNamegare($station);

        // Ajouter les données aux tableaux
        $nomsGares[] = $nomGare;
        $nombreVisites[] = $nombreVisite;
        $pourcentages[] = (($nombreVisite) / array_sum($statistiques)) * 100;
    }

  // Fonction pour générer une couleur aléatoire
function randomColor() {
    return sprintf('#%06X', mt_rand(0, 0xFFFFFF));
}

// Tableau pour stocker les couleurs générées
$couleurs = [];

// Générer une couleur unique pour chaque gare
foreach ($nomsGares as $gare) {
    $couleurs[] = randomColor();
}

// Affichage de l'histogramme avec Chart.js
echo "<canvas id='histogramme'></canvas>";

// Script JavaScript pour initialiser l'histogramme
echo "<script>";
echo "var ctx = document.getElementById('histogramme').getContext('2d');";
echo "var myChart = new Chart(ctx, {";
echo "    type: 'bar',";
echo "    data: {";
echo "        labels: " . json_encode($nomsGares) . ",";
echo "        datasets: [{";
echo "            label: 'Nombre de visites',";
echo "            data: " . json_encode($nombreVisites) . ",";
echo "            backgroundColor: " . json_encode($couleurs) . ","; // Utilisation du tableau de couleurs
echo "            borderWidth: 0"; // Pas de bordures
echo "        }]";
echo "    },";
echo "    options: {";
echo "        scales: {";
echo "            yAxes: [{";
echo "                ticks: {";
echo "                    beginAtZero: true,";
echo "                    fontColor: 'rgba(0, 0, 0, 0.7)'"; // Couleur de la police de l'axe Y
echo "                },";
echo "                gridLines: {";
echo "                    color: 'rgba(0, 0, 0, 0.1)'"; // Couleur des lignes de la grille de l'axe Y
echo "                }";
echo "            }],";
echo "            xAxes: [{";
echo "                ticks: {";
echo "                    fontColor: 'rgba(0, 0, 0, 0.7)'"; // Couleur de la police de l'axe X
echo "                },";
echo "                gridLines: {";
echo "                    color: 'rgba(0, 0, 0, 0.1)',"; // Couleur des lignes de la grille de l'axe X
echo "                    drawBorder: false"; // Pas de bordure autour de l'axe X
echo "                }";
echo "            }]";
echo "        },";
echo "        legend: {";
echo "            display: false"; // Masquer la légende
echo "        },";
echo "        barPercentage: 1,";
echo "        categoryPercentage: 1,"; // Pour supprimer l'espace entre les colonnes
echo "        barThickness: 'flex'"; // Enlever les petites carrés
echo "    }";
echo "});";
echo "</script>";
}


function getLineNamegare($lineRef1) {
    $gareName = $lineRef1; // Par défaut, si le nom de la ligne n'est pas trouvé, on retourne la référence de ligne

    // Lecture du fichier CSV contenant les correspondances entre LineRef et les noms de ligne
    $csvFileLines = "listes_gares.csv"; // Chemin vers le fichier CSV des lignes

    if (($handleLines = fopen($csvFileLines, "r")) !== FALSE) {
        while (($dataLines = fgetcsv($handleLines, 1000, ";")) !== FALSE) {
            if ($dataLines[1] === $lineRef1) {
                $gareName = $dataLines[0]; // Nom de la ligne correspondant à la référence de ligne
                break;
            }
        }
        fclose($handleLines);
    }

    return $gareName;
}

/**
 * Fonction pour afficher une image aléatoire à partir d'un dossier spécifié.
 *
 * Cette fonction parcourt le dossier spécifié, récupère tous les fichiers d'images
 * avec les extensions autorisées, sélectionne au hasard une image parmi elles,
 * puis affiche cette image sur la page d'accueil.
 *
 * @param string $cheminDossier Le chemin vers le dossier contenant les images.
 */
function afficherImageAleatoire($cheminDossier) {
    // Liste des extensions d'images autorisées
    $extensions = ["jpg", "jpeg", "png", "gif"];

    // Tableau pour stocker les noms des fichiers d'images
    $images = [];

    // Ouvrir le dossier
    if ($handle = opendir($cheminDossier)) {
        // Lire chaque fichier dans le dossier
        while (false !== ($fichier = readdir($handle))) {
            // Vérifier si le fichier est une image et si son extension est autorisée
            if (in_array(strtolower(pathinfo($fichier, PATHINFO_EXTENSION)), $extensions)) {
                // Ajouter le nom du fichier à notre tableau d'images
                $images[] = $fichier;
            }
        }
        // Fermer le dossier
        closedir($handle);
    }

    // Sélectionner une image aléatoire parmi celles trouvées
    $randomImage = $images[array_rand($images)];
    $encodedFileName = str_replace(' ', '%20', $randomImage);

    // Afficher l'image sur la page d'accueil
    echo "<figure>";
    echo "<img src='$cheminDossier$encodedFileName' alt='Image aléatoire'/>";
    echo "<figcaption>
    image dynamique du serveur
    </figcaption>";
    echo "</figure>";
}

/**
 * Fonction pour définir le cookie de la dernière gare visitée.
 *
 * Cette fonction prend le nom de la gare en paramètre, le concatène avec la date
 * et l'heure actuelle au format Y-m-d H:i:s, puis définit un cookie nommé 'last_visited_station'
 * avec cette valeur. Le cookie est valide pendant 30 jours.
 *
 * @param string $stationName Le nom de la dernière gare visitée.
 */
function setLastVisitedStationCookie($stationName) {
    $cookieName = 'last_visited_station';
    $cookieValue = $stationName . '|' . date('Y-m-d H:i:s'); // Concaténer la gare avec la date/heure au format Y-m-d H:i:s
    $expirationTime = time() + (86400 * 30); // Cookie valide pendant 30 jours

    setcookie($cookieName, $cookieValue, $expirationTime, "/");
}
function getLastVisitedStationFromCookie() {
    $cookieName = 'last_visited_station';

    if(isset($_COOKIE[$cookieName])) {
        return explode('|', $_COOKIE[$cookieName]); // Séparer la valeur du cookie en gare et date/heure
    } else {
        return null;
    }
}

/**
 * Fonction pour définir le mode (jour ou nuit) en fonction du cookie de mode.
 *
 * Cette fonction vérifie d'abord si un cookie de mode existe. Si oui, elle vérifie
 * si la valeur du cookie est valide ('nuit' ou 'jour'). Si la valeur est 'nuit', elle
 * inclut la feuille de style pour le mode nuit. Si la valeur est 'jour' ou invalide,
 * elle inclut la feuille de style pour le mode jour. Si aucun cookie de mode n'existe,
 * le mode par défaut est défini comme jour et la feuille de style pour le mode jour est incluse.
 */
function setMode() {
    // Vérifier si un cookie de mode existe
    if (isset($_COOKIE['mode'])) {
        // Vérifier si la valeur du cookie est valide (jour ou nuit)
        $mode = $_COOKIE['mode'];
        if ($mode === 'nuit') {
            echo '<link rel="stylesheet" href="styles/nuit.css"/>';
        } elseif ($mode === 'jour') {
            echo '<link rel="stylesheet" href="styles/jour.css"/>';
        } else {
            // Si la valeur du cookie est incorrecte, le supprimer
            setcookie('mode', '', time() - 3600, "/~login/"); // Chemin spécifique à votre espace
        }
    } else {
        // Si aucun cookie de mode n'existe, définir le mode par défaut (jour)
        echo '<link rel="stylesheet" href="styles/jour.css"/>';
    }
}

function switchMode() {
    if (isset($_COOKIE['mode']) && $_COOKIE['mode'] === 'nuit') {
        // Si le mode est nuit, changer le cookie pour jour
        setcookie('mode', 'jour', time() + (86400 * 30), "/"); // 86400 = 1 jour
    } else {
        // Si le mode est jour ou n'existe pas, changer le cookie pour nuit
        setcookie('mode', 'nuit', time() + (86400 * 30), "/"); // 86400 = 1 jour
    }
}

// Appeler la fonction pour définir le mode
setMode();

// Vérifier si l'utilisateur a cliqué sur le lien de changement de mode
if (isset($_GET['switch'])) {
    switchMode();
    // Rediriger l'utilisateur vers la page actuelle pour rafraîchir le mode
    header("Location: {$_SERVER['PHP_SELF']}");
    exit();
}

setMode();


function getGeolocationInfo() {
    // Récupérer les informations de localisation basées sur l'adresse IP du visiteur
    $ip = $_SERVER['REMOTE_ADDR'];
    $geopluginUrl = "http://www.geoplugin.net/xml.gp?ip=$ip";
    $geopluginData = simplexml_load_file($geopluginUrl);
    if ($geopluginData !== false) {
        $latitude = $geopluginData->geoplugin_latitude;
        $longitude = $geopluginData->geoplugin_longitude;
        $city = $geopluginData->geoplugin_city;
        $region = $geopluginData->geoplugin_region;
        $regionName = $geopluginData->geoplugin_regionName;
        $countryName = $geopluginData->geoplugin_countryName;
        $continentName = $geopluginData->geoplugin_continentName;

        $locationInfo = array(
            'latitude' => $latitude,
            'longitude' => $longitude,
            'ville' => $city,
            'region' => $region,
            'commune' => $regionName,
            'pays' => $countryName,
            'continent' => $continentName
        );

        return $locationInfo;
    } else {
        return 'Erreur lors de la récupération des données de localisation.';
    }
}

function getWeatherData($latitude, $longitude) {
    $api_key = 'kQg1hINdCGsXKNmoOeTxepH498DSCHxe'; // Nouvelle clé API
    $url = 'https://api.tomorrow.io/v4/timelines?location=' . $latitude . ',' . $longitude . '&fields=temperature&timesteps=1h&units=metric&apikey=' . $api_key;
    $json_data = @file_get_contents($url);
    if ($json_data === FALSE) {
        // Retourner une valeur par défaut ou gérer l'erreur de manière appropriée
        return false;
    } else {
        $weather_data = json_decode($json_data, true);
        if ($weather_data && isset($weather_data['data']['timelines'][0]['intervals'][0]['values']['temperature'])) {
            return $weather_data['data']['timelines'][0]['intervals'][0]['values']['temperature'];
        } else {
            return false;
        }
    }
}
?>