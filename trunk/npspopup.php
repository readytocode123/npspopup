<?php
/**
 * Module for gathering Net Promoter Score (NPS) statistics for your site.
 *
 * To use it, call nps_include_popup() from the pages you'd like to measure. For a certain
 * number of users per day (10 by default), a popup will appear and they'll be asked to rate
 * their experiences. This data is then available through an admin HTML page or as JSON data
 * for data gathering.
 *
 * For more information on the process, see:
 * http://startuplessonslearned.blogspot.com/2008/11/net-promoter-score-operational-tool-to.html
 *
 */

/**
 * IMPORTANT! Change this to a unique password yourself to restrict access to the reports
 * generated from the NPS reports
 */
define ('NPS_REPORT_PASSWORD', 'reportpassword');

/**
 * You'll need to change these two settings to your own database user name
 * and password
 */
define ('NPS_DATABASE_USERNAME', '');
define ('NPS_DATABASE_PASSWORD', '');

/**
 * Then, to create the database and table, run these commands from the mysql console:
CREATE DATABASE npsdata;
USE npsdata;
CREATE TABLE records (user_identifier VARCHAR(100) PRIMARY KEY, time_added DATETIME, score INT, comment TEXT);
 */

define ('NPS_DATABASE_HOST', 'localhost');
define ('NPS_DATABASE_NAME', 'npsdata');

define ('NPS_USERS_PER_DAY', 10); // The service will keep requesting NPS feedback until it has this many
define ('NPS_USER_PROBABILITY', 100); // The percentage chance that any given page view will show a popup
define ('NPS_POPUP_DELAY', 2); // How many seconds to wait before bringing up the popup 

/**
 * Includes the javascript and logic to bring up a popup requesting NPS feedback from users
 *
 * @param string $user_identifier To prevent users from being repeatedly bombarded with the survey,
 * you must supply some kind of unique identifier for them, like a user name or id number. If none
 * is supplied, it will default to the client IP address
 */
function nps_include_popup($user_identifier=null) {

	if (!isset($user_identifier))
		$user_identifier = nps_get_client_ip_address();
		
	$want_popup = nps_want_popup($user_identifier);
	if (!$want_popup)
		return;

?>		
<script type="text/javascript" src="tinybox.js"></script>
<script type="text/javascript">

var npsContent = "<center>How likely would you be to recommend this service to a friend or colleague?<br/>"

npsContent += "<span style='font-size:75%;'>Least Likely</span> ";
for (var index=0; index<11; index+=1) {
	npsContent += "<a href='javascript:npsRecordScore("+index+")'>";
	npsContent += index+"</a> ";
}
npsContent += "<span style='font-size:75%;'>Most Likely</span>";

npsContent += "</center>";

window.setTimeout(npsOpenPopup, <?=NPS_POPUP_DELAY*1000?>, npsContent);

function npsOpenPopup(npsContent) {
	TINY.box.show(npsContent);
}

function npsRecordScore(score) {
	var recordUrl = "npsrecord.php";
	recordUrl += "?user_identifier=<?=urlencode($user_identifier)?>";
	recordUrl += "&score="+score;

	TINY.box.show(recordUrl, 1, 0, 0, 1);
}

function npsRecordComment() {
	var commentElement = document.getElementById('nps_comment');
	var comment = commentElement.value;
	
	var recordUrl = "npsrecord.php";
	recordUrl += "?user_identifier=<?=urlencode($user_identifier)?>";
	recordUrl += "&comment="+encodeURIComponent(comment);

	TINY.box.show(recordUrl, 1, 0, 0, 1);
}

</script>
<?php
}

/**
 * Checks to see if we should display a popup for this user
 *
 * @param string $user_identifier This identifier (either a user name or id number) is checked
 * against the database to see if the user's already seen the popup today.
 */
function nps_want_popup($user_identifier) {

	// First check the overall probability to see if we should even try to show the popup
	$current_percent = rand(0, 100);
	if ($current_percent>NPS_USER_PROBABILITY)
		return false;

	$connection = nps_open_database();
	
	// Don't show the popup if there's already been enough records gathered today
	$query = "SELECT COUNT(*) as records_today FROM records WHERE DATE_SUB(CURDATE(),INTERVAL 1 DAY) <= time_added;";
	$query_result = mysql_query($query, $connection) or die("query failed on nps database - '$query'");
	$current_row = mysql_fetch_array($query_result);
	$records_today = $current_row['records_today'];
	if ($records_today>=NPS_USERS_PER_DAY)
		return false;
		
	// Don't show the popup to a user if she's seen it in the last 30 days	
	$query = "SELECT * FROM records WHERE user_identifier='".mysql_real_escape_string($user_identifier, $connection)."'";
	$query .= " AND DATE_SUB(CURDATE(),INTERVAL 30 DAY) <= time_added;";
	$query_result = mysql_query($query, $connection) or die("query failed on nps database - '$query'");
	$current_row = mysql_fetch_array($query_result);
	if ($current_row)
		return false;
	
	return true;
}

/**
 * Records the score given by the user, expressing how likely she or he is to recommend the
 * service to a friend
 *
 * @param string $user_identifier The user's identifier (either a user name or id number)
 * @param string $score The rating given, 0 to 10
 */
function nps_record_score($user_identifier, $score) {

	$connection = nps_open_database();
	$query = "INSERT INTO records (user_identifier, time_added, score, comment) VALUES('";
	$query .= mysql_real_escape_string($user_identifier, $connection)."', NOW(), '";
	$query .= mysql_real_escape_string($score, $connection)."', '');";
	mysql_query($query, $connection) or die("query failed on nps database - '$query'");
}

/**
 * Records any miscellaneous comments from the user in response to the survey
 *
 * @param string $user_identifier The user's identifier (either a user name or id number)
 * @param string $comment A text string containing the user's response
 */
function nps_record_comment($user_identifier, $comment) {

	$connection = nps_open_database();
	$query = "UPDATE records SET comment='";
	$query .= mysql_real_escape_string($comment, $connection)."'";
	$query .= " WHERE user_identifier='".mysql_real_escape_string($user_identifier)."'";
	$query .= " AND DATE_SUB(CURDATE(),INTERVAL 1 DAY) <= time_added;";
	mysql_query($query, $connection) or die("query failed on nps database - '$query'");
}

/**
 * Returns the raw list of scores and comments for the given time period
 *
 * @param integer $days How many days backward to look - if 0, then look at all time
 * @result Returns an array of records, each with a user_identifier, time_added, day_added, score and comment field
 */
function nps_get_records($days) {

	$connection = nps_open_database();
	$query = "SELECT user_identifier, time_added, TO_DAYS(time_added) as day_added, score, comment FROM records";
	if ($days>0) {
		$query .= "WHERE DATE_SUB(CURDATE(),INTERVAL ";
		$query .= mysql_real_escape_string($days, $connection)." DAY) <= time_added";
	}
	$query .= ";";
	$query_result = mysql_query($query, $connection) or die("query failed on nps database - '$query'");

	$result = array();
	while ($current_row = mysql_fetch_array($query_result, MYSQL_ASSOC)) {
		$result [] = $current_row;
	}
	
	error_log("Found ".count($result));
	
	return $result;
}

/**
 * Calculates the Net Promoter Scores for the time period
 *
 * @param array $records The raw records for the time period you're interested in
 * @result Returns an array of records, one for each day, with an NPS score for each
 */
function nps_get_calculated_scores($records) {

	$result = array();
	foreach ($records as $current) {
		$day = $current['day_added'];
		$current_score = $current['score'];
		
		if (!isset($result[$day])) {
			$result[$day] = array(
				'record_count' => 0,
				'promoter_count' => 0, 
				'detractor_count' => 0, 
				'nps_score' => 0);
		}
		
		$stats = $result[$day];
		$stats['record_count'] += 1;
		
		if ($current_score>8) {
			$stats['promoter_count'] +=1;
		} else if ($current_score<7) {
			$stats['detractor_count'] += 1;
		}
		
		$promoter_percent = ($stats['promoter_count']*100)/$stats['record_count'];
		$detractor_percent = ($stats['detractor_count']*100)/$stats['record_count'];
		$stats['nps_score'] = $promoter_percent-$detractor_percent;
		
		$result[$day] = $stats;
	}
 
	return $result;
}

/**
 * A utility function to connect to the results database
 *
 * @return The connection to the mysql data, or null
 */
function nps_open_database() {
	
	$connection = mysql_connect(
		NPS_DATABASE_HOST,
		NPS_DATABASE_USERNAME, 
		NPS_DATABASE_PASSWORD) 
		or die("Couldn't open NPS database");
	
	mysql_select_db(NPS_DATABASE_NAME) or die("Couldn't select NPS database");

	return $connection;
}

/**
 * Works out the IP address of the machine requesting the current page, taking into account
 * any proxy server settings they may have. For more details, see
 * http://roshanbh.com.np/2007/12/getting-real-ip-address-in-php.html
 *
 * @return string The address of the machine requesting the page
 */
function nps_get_client_ip_address() {
	if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
		$ip = $_SERVER['HTTP_CLIENT_IP'];
	} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	} else {
		$ip = $_SERVER['REMOTE_ADDR'];
	}
	return $ip;
}