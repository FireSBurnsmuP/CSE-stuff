<?php
/**
 * Gets the stream from Twitter containing all posts in MI containing terms 'spartan' or 'msu'
 */

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
$time_limit = 30; // .5 min
set_time_limit($time_limit);

// setup my twitter params
// 						v- East Lansing
//$params['locations'] = "-84.514492,42.701109,-84.447201,42.800943";
// 						v- Michigan
$params['locations'] = "-87.0,41.7,-82.5,45.5";
// I have to parse the location myself if I want to search by keyword AND bbox.
// TODO skipping for now. I'll just use the OR and try to deal with it.
// 		v- Michigan (-87, 41.7, -82.5, 45.5)
// $locations = array(
// 	"long" => array(
// 		-87.0,
// 		-82.5
// 		),
// 	"lat" => array(
// 		41.7,
// 		45.5
// 		)
// 	);

// ------------------------------------------
// read the query string
// ------------------------------------------
$params['track'] = "msu,spartan";

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

$query = "SELECT * FROM TwitterHW1";
$result = @mysqli_query($link,$query);
if(!$result)
{
	// Twitter table does not exist, create new table
	$query =
	"CREATE TABLE TwitterHW1(
		twitter_id nvarchar(25),
		term nvarchar(25),
		constraint pk_idnterm PRIMARY KEY (twitter_id, term)
	)";
}
else
{
	//delete all records in Table
	$query = "TRUNCATE TABLE TwitterHW1";
}
$result = mysqli_query($link,$query);


?>
<!DOCTYPE html>
<html>
<head>
<?php include("../static/head.php"); ?>
</head>
<body>
	<h1>
		Twitter Stream Search
	</h1>
	<p>
		This page contains all the posts from the last 30 seconds on Twitter
		either containing &apos;spartan&apos; or &apos;msu&apos;,
		or just from Michigan in general.
	</p>
	<div id="divLoading" class="loading">
		Please wait while we suck on the Twitter Firehose...<br />
		<img src="../static/ajax-loading.gif" />
	</div>
	<table id="results" class="streamResults">
		<tr>
			<th scope="col">Twitter Post ID</th>
			<th scope="col">Post</th>
			<th scope="col">Keywords</th>
		</tr>
	</table>
	<p>
		<a href="hotItems.php">Click Here</a> to see the current top 25 trending
		items on Twitter either containing &apos;spartan&apos; or &apos;msu&apos;,
		or just from Michigan in general.
	</p>
	<p class="small">
		I would normally bother to force my results to contain all posts
		containing &apos;spartan&apos; or &apos;msu&apos; AND from within
		Michigan, but it's late, and there weren't enough results to justify
		filtering. I got less than 100 tweets from all of MI and fewer than 5
		tweets containing my terms in 5 minutes of streaming.
	</p>
</body>
</html>
<?php
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

	// output buffering!!
	ob_start();

	// parse the data returned by Twitter
	$data = json_decode($data, true);
	//var_dump($data);
	$id = $data['id_str'];
	$datetime = $data['created_at'];
	$longitude = $data['coordinates']['coordinates'][0];
	$latitude = $data['coordinates']['coordinates'][1];
	$userid = $data["user"]["id"];
	$username = $data["user"]["screen_name"];

	$msg = str_replace(PHP_EOL, '', $data['text']);
	$terms = $data['entities']['hashtags'];
	?>
		<tr>
			<th scope="rowgroup"><?php echo $id; ?></th>
			<td><?php echo "$datetime - $username :<br />$msg";?></td>
			<td>&nbsp;</td>
		</tr>
	<?php

	foreach ($terms as $term)
	{
		$term_text = mysqli_real_escape_string($link,strtolower($term['text']));
		$query = "INSERT INTO TwitterHW1 VALUES ('$id','$term_text')";
		$result = mysqli_query($link, $query);	
		?>
		<tr>
			<td></td>
			<td></td>
			<td><?php echo $term_text; ?></td>
		</tr>
		<?php
	}

	$html_str = ob_get_clean();
	?>
	<script>
		$(function(){
			$("#results").append('<?php echo str_replace(array("'","\n"),array("&apos;"," "),$html_str); ?>');
		});
	</script>
	<?php

	//flush();
	return file_exists(dirname(__FILE__) . '/STOP');
}
