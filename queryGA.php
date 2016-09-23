<?php
require_once __DIR__.'/google-api-php-client/vendor/autoload.php';

session_start();

$servername = "localhost";
$username = "root";
$password = "root";
$database = "analyticsTest";
$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
	die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT view_id,refresh_token,access_token,created,code FROM analytics_info";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
		$accessTokenfromDB = $row["access_token"];
		$refreshToken = $row["refresh_token"];
		$created = $row["created"];
		$viewId = $row["view_id"];
    }
}

$tokenforGoogle = json_encode(array("access_token" => $accessTokenfromDB, "token_type" => "Bearer", "expires_in" => 3600, "created" => floatval($created)));

$client = new Google_Client();
$client->setAuthConfig(__DIR__ . '/client_secrets.json');
$client->addScope(Google_Service_Analytics::ANALYTICS_READONLY);
$client->setAccessType('offline');
$client->setAccessToken($tokenforGoogle);
$accessToken = $client->getAccessToken();
$analytics = new Google_Service_Analytics($client);

function getResults($analytics, $profileId) {
  return $analytics->data_ga->get( # <-------------- errors here
      'ga:' . $profileId,
      '30daysAgo',
      'today',
      'ga:pageviews',
      array('dimensions' => 'ga:sourceMedium')
    );
}

function printResults($results) {
  if (count($results->getRows()) > 0) {
    $rows = $results->getRows();
      foreach($rows as $row){
		    $row_data = explode("/", $row[0]);
		    if($row_data[0] == "google " or $row_data[0] == "bing " or $row_data[0] == "yahoo "){
			    if($row_data[1] == " referral" or $row_data[1] == " cpc" or $row_data[1] == " organic"){
					print $row[0];
    					print "\n";
	    				print "page views : " . $row[1];
	    				print "\n \n";
			}
		}
	}
  } else {
	print "<p>No results found.</p>";
  }
}

if(!$client->isAccessTokenExpired()){
	$results = getResults($analytics, $viewId);
	printResults($results);
}else{
	echo "We cant have access with an expired token bro!";
}

?>