<?php
/**
 * Displays the Hot Items from the database table for the HW1 twitter
 * stream parsing project. Nothing special, really.
 */

// Grab my database connection
if($_SERVER['SERVER_NAME'] == 'www.cse.msu.edu')
{
	$mysqli = new mysqli('mysql-user.cse.msu.edu','fires','A39097528','fires');
}
else
{
	$mysqli = new mysqli('localhost','root','','bigdata');
}

// And make my query to grab all the information I need for this page
$sql_str = 
	"SELECT term, COUNT(*) as count
	FROM TwitterHW1
	GROUP BY term
	order by count desc
	limit 0,25";
$result = $mysqli->query($sql_str);
?>
<!DOCTYPE html>
<html>
<head>
	<?php include("../static/head.php"); ?>
</head>
<body>
	<h1>
		Top 25 Hot Topics
	</h1>
	<h3>
		related to MSU, Spartans, or from Michigan, USA in general
	</h3>
	<div id="divLoading" class="loading">
		Please wait while we figure all this out...<br />
		<img src="../static/ajax-loading.gif" />
	</div>
	<table class="streamResults">
		<tr>
			<th scope="col">Hashtag</th>
			<th scope="col">Count</th>
		</tr>
<?php
/* I'm going to progressively make the background color of the row less red the
 * less 'hot' the term is. Just a style thing.
 */
$bgnred = 105;
while($row = $result->fetch_assoc())
{
	?>
		<tr style="background-color: rgb(255,<?php
			echo $bgnred . ',' . $bgnred;?>);">
			<td><?php echo $row['term'];?></td>
			<td><?php echo $row['count'];?></td>
		</tr>
	<?php
	$bgnred = ($bgnred >= 255 ? 255 : $bgnred + 10);
}
$result->free();
$mysqli->close();
?>
	</table>
</body>
</html>