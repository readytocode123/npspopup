<?php
/**
 * This page either shows a human-readable version of the NPS reports, or returns a json data structure for your toolchain
 * 
 */

require_once ('npspopup.php');

if (!isset($_REQUEST['reportpassword']) || urldecode($_REQUEST['reportpassword'])!=NPS_REPORT_PASSWORD)
	die('Bad password for NPS report');

if (isset($_REQUEST['days']))
	$days = urldecode($_REQUEST['days']);
else
	$days = 0;

$records = nps_get_records($days);
$scores = nps_get_calculated_scores($records);

if ((isset($_REQUEST['format'])) && $_REQUEST['format']=='json') {

	print json_encode($scores);
	
} else {

	print "<html><head><title>NPS Report Page</title></head><body>";

	print "<table>";

	print "<tr>";
	print "<td>Day</td>";
	print "<td>NPS Score</td>";
	print "<td>Total Records</td>";
	print "<td>Promoters</td>";
	print "<td>Detractors</td>";
	print "</tr>";

	foreach ($scores as $day => $current) {
		print "<tr>";
		print "<td>".$day."</td>";
		print "<td>".$current['nps_score']."</td>";
		print "<td>".$current['record_count']."</td>";
		print "<td>".$current['promoter_count']."</td>";
		print "<td>".$current['detractor_count']."</td>";
		print "</tr>";
	}
	print "</table>";
	
	print "</body></html>";
}