<?php

//-- API DE GESTION DES STATS DE GOOGLE ANALYTICS --//

// id de la vue GA
require( dirname( __FILE__ ) . '/../../../../wp-config.php' );
$analytics_id = ANALYTICS_ID;

if ( isset($_POST['stat_type']) )
{
  $stat_type = $_POST['stat_type']; // type de statistique à récupérer (visits, sources, cities)
}

if ( isset($_POST['start_date']) )
{
  $start_date = $_POST['start_date']; // date de début de période
}

if ( isset($_POST['end_date']) )
{
  $end_date = $_POST['end_date']; // date de fin de période
}

// Test si la date de début est après la date de fin
// Si c'est le cas, on définit la date de début avec la valeur de la date de fin
$datetime_start_test = new DateTime( $start_date );
$datetime_end_test = new DateTime( $end_date );
if ( $datetime_start_test > $datetime_end_test ) {
	$start_date = $end_date;
}

if ( isset($_POST['path']) )
{
  $path = $_POST['path']; // path de la page concernée par les stats
}

header('Content-Type: application/json'); // API JSON

// Chargement de la librairie Google API
require( dirname( __FILE__ ) . '/vendor/autoload.php' );
$analytics = initializeAnalytics();

// Création et return de l'objet Analytics
function initializeAnalytics()
{
  $client = new Google_Client();

  // Fichier json fourni par Google API
  $KEY_FILE_LOCATION = dirname( __FILE__ ) . '/../../../../analytics-account-credentials.json';

  $client->setAuthConfig( $KEY_FILE_LOCATION );
  $client->setScopes( ['https://www.googleapis.com/auth/analytics.readonly'] );
  $analytics = new Google_Service_Analytics( $client );

  return $analytics;
}

class WDG_Google_Analytics
{

  public function __construct() {
        global $analytics;
        global $analytics_id;
        $this->analytics = $analytics;
        $this->analytics_id = $analytics_id;
  }

  public function get_visits($start_date,$end_date,$path) // foncrion de récupération des visites
  {
    $metrics = 'ga:sessions';

    $optParams = array(
          'dimensions' => 'ga:date',
          'filters' => 'ga:pagePath==' . $path
    );

    $result = $this->analytics->data_ga->get( $this->analytics_id,
              $start_date,
              $end_date, $metrics, $optParams);

    if( $result->getRows() ) {
      $result_rows = $result->getRows();
    }

    $i = 0;

    echo '[';
    foreach ( $result_rows as $row ) {
      if ( $i == 0 ){
        echo '{"date":'.$row[0].',"visits":'.$row[1].'}';
      }
      else {
        echo ',{"date":'.$row[0].',"visits":'.$row[1].'}';
      }
      $i++;
    }
    echo ']';
  }

  public function get_sources($start_date,$end_date,$path) // fonction de récupération des sources (canaux)
  {
    $metrics = 'ga:sessions';

    $optParams = array(
          'dimensions' => 'ga:channelGrouping',
          'filters' => 'ga:pagePath==' . $path,
          'max-results' => 5,
          'sort' => '-ga:sessions'
    );

    $result = $this->analytics->data_ga->get( $this->analytics_id,
              $start_date,
              $end_date, $metrics, $optParams);

    if( $result->getRows() ) {
      $result_rows = $result->getRows();
    }

    $i = 0;

	echo '[';
	if ( !empty( $result_rows ) ) {
		foreach ( $result_rows as $row ) {
		  $sourceFr = str_replace("Organic Search", "Référencement naturel", $row[0]); // traduction des termes EN de GA
		  $sourceFr = str_replace("Referral", "Lien hypertexte", $sourceFr);
		  $sourceFr = str_replace("(Other)", "Autre", $sourceFr);
		  if ( $i == 0 ){
			echo '{"source":"'.$sourceFr.'","visits":'.$row[1].'}';
		  }
		  else {
			echo ',{"source":"'.$sourceFr.'","visits":'.$row[1].'}';
		  }
		  $i++;
		}
	}
    echo ']';

  }

  public function get_cities($start_date,$end_date,$path) // foncrion de récupération des provenances (villes)
  {
    $metrics = 'ga:sessions';

    $optParams = array(
          'dimensions' => 'ga:city',
          'max-results' => 10,
          'filters' => 'ga:pagePath==' . $path,
          'sort' => '-ga:sessions'
    );

    $result = $this->analytics->data_ga->get( $this->analytics_id,
              $start_date,
              $end_date, $metrics, $optParams);

    if( $result->getRows() ) {
      $result_rows = $result->getRows();
    }

    $i = 0;

	echo '[';
	if ( !empty( $result_rows ) ) {
		foreach ( $result_rows as $row ) {
			$citiesFr = str_replace("(not set)", "Inconnue", $row[0]);
			if ( $i == 0 ){
				echo '{"cities":"'.$citiesFr.'","visits":'.$row[1].'}';
			} else {
				echo ',{"cities":"'.$citiesFr.'","visits":'.$row[1].'}';
			}
			$i++;
		}
	}
    echo ']';

  }

}

$wdg_analytics = new WDG_Google_Analytics;

// on appel la bonne fonction selon le type de stat souhaité
switch($stat_type)
{
 case 'visits':
  $wdg_analytics->get_visits($start_date,$end_date,$path);
  break;

 case 'sources':
  $wdg_analytics->get_sources($start_date,$end_date,$path);
  break;

  case 'cities':
   $wdg_analytics->get_cities($start_date,$end_date,$path);
   break;
}
