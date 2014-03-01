<?php

// ---------------------------------------------------------
// include libraries
// ---------------------------------------------------------

require '../static/tmh/tmhOAuth.php';
require '../static/tmh/tmhUtilities.php';

// ------------------------------------------
// Initialize parameter array for Twitter API
// ------------------------------------------

$params = array();

// ---------------------------------------------------------
// Get time window and bounding box
// ---------------------------------------------------------

// start timer
$time_pre = microtime(true);
if(strlen($_POST["time_limit"]) > 0){
	$time_limit = intval($_POST["time_limit"]);
}else{
	$time_limit = 30;
}
set_time_limit($time_limit);

if(strlen($_POST["boundingBox"])>0){
	$boundingBox = $_POST["boundingBox"];
}
else {	// use default bounding box for East Lansing
	$boundingBox = "-84.514492,42.701109,-84.447201,42.800943";
}
$params['locations'] = $boundingBox;

// ------------------------------------------
// read the query string
// ------------------------------------------

if(strlen($_POST["keywords"])>0){
	$params['track'] = $_POST["keywords"];
}

// ------------------------------------------
// Get the authentication information
// ------------------------------------------

$secretFile = "../static/auth/auth.token";
$fh = fopen($secretFile, 'r');
$secretArray = array();

while (!feof($fh)) {
	$line = fgets($fh);
	$array = explode( ':', $line );
	$secretArray[trim($array[0])] = trim($array[1]) ;
}
fclose($fh);
$tmhOAuth = new tmhOAuth($secretArray);

// ------------------------------------------
// Initialize database connection
// ------------------------------------------
if($_SERVER['SERVER_NAME'] == 'www.cse.msu.edu')
{
	$link = mysqli_connect('mysql-user.cse.msu.edu','fires','A39097528','fires');
}
else
{
	$link = mysqli_connect('localhost','root','','bigdata');
}

$query = "SELECT * FROM TwitterPosting";
$result = @mysqli_query($link,$query);
if(!$result)
{
		// Twitter table does not exist, create new table
		$query = "CREATE TABLE TwitterPosting(
		twitter_id CHAR(25),
		timestamp CHAR(30),
		latitude CHAR(15),
		longitude CHAR(15),
		userID  CHAR(25),
		userName VARCHAR(30),
		message VARCHAR(150),
		PRIMARY KEY (twitter_id)
		)";
}
else
{
		//delete all records in Table Twitter
		$query = "TRUNCATE TABLE TwitterPosting";
}
$result = mysqli_query($link,$query);

// ------------------------------------------
// get tweets
// ------------------------------------------

$url = 'https://stream.twitter.com/1/statuses/filter.json';
$tmhOAuth->streaming_request('POST', $url, $params, 'my_streaming_callback');
mysqli_close($link);

// ------------------------------------------
// define callback function for streaming API
// ------------------------------------------

function my_streaming_callback($data, $length, $metrics) {

	// keep running time
	global $time_pre;
	global $link;

	$time_post = microtime(true);
	$exec_time = $time_post - $time_pre;
	global $time_limit;
	if($exec_time > $time_limit){
		return true;
	}

	// parse the data returned by Twitter
	$data = json_decode($data, true);
	$id = $data['id_str'];
	$datetime = $data['created_at'];
	$longitude = $data['coordinates']['coordinates'][0];
	$latitude = $data['coordinates']['coordinates'][1];
	$userid = $data["user"]["id"];
	$username = $data["user"]["screen_name"];

	$msg = str_replace(PHP_EOL, '', $data['text']);
	$msg = mysqli_real_escape_string($link,$msg);
	$username = mysqli_real_escape_string($link,$username);

	$query = "INSERT INTO TwitterPosting VALUES ('$id','$datetime','$latitude','$longitude','$userid','$username','$msg')";
	$result = mysqli_query($link, $query);

	echo "$id  $datetime $latitude $longitude $userid $username ";
	echo "$msg". PHP_EOL . "<br />";

	flush();
	return file_exists(dirname(__FILE__) . '/STOP');
}

?>



