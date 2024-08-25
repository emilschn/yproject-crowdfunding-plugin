<?php

require './googleAnalytics.php';

$start_date = 'today';
$end_date = 'today';
$path = '/';
if ( isset($_POST['stat_type']) )
{
  $stat_type = $_POST['stat_type'];
}

if ( isset($_POST['start_date']) )
{
  $start_date = $_POST['start_date'];
}

if ( isset($_POST['end_date']) )
{
  $end_date = $_POST['end_date'];
}
if ( isset($_POST['path']) )
{
  $path = $_POST['path'];
}
header('Content-Type: application/json');

$analytics = new googleAnalytics($start_date, $end_date, $path);


switch($stat_type)
{
 case 'visits':
  $analytics->get_visits();
  break;

 case 'sources':
  $analytics->get_sources();
  break;

  case 'cities':
   $analytics->get_cities();
   break;
}

