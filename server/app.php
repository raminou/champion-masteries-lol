<?php
include('fonctions.php');
if(isset($_GET['pseudo']) && isset($_GET['region']))
{
	$api = new APIRiot($_GET['pseudo'], $_GET['region']);
	if($api->load())
		echo $api->getMasteries();
	else
		echo json_encode(array("error" => "Cant get the summonerId or the version of the game, check if the summoner name and her region are right or try again later."));
}
else
	echo json_encode(array("error" => "No parameters sent"));
?>