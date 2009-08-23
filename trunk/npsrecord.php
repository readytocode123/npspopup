<?php
/**
 * This page is called by the Net Promoter Score popup to record the user's opinion of the service
 * 
 */

require_once ('npspopup.php');

if (!isset($_REQUEST['user_identifier']) || 
	(!isset($_REQUEST['score'])) && (!isset($_REQUEST['comment'])))
	die('You need to pass both user_identifier and either a score or comment as arguments to npsrecord.php');
	
$user_identifier = $_REQUEST['user_identifier'];
if (isset($_REQUEST['score'])) {

	$score = urldecode($_REQUEST['score']);
	nps_record_score($user_identifier, $score);

?>
<center>
Thanks! Do you have any other comments on the service? Please include an email address or phone number if you would like us to contact you
<br/><textarea type="textarea" cols="40" rows="4" id="nps_comment"/></textarea>
<br/><input type="submit" onclick="npsRecordComment(); return false;" value="Send"/>
</center>
<?php
} else {

	$comment = urldecode($_REQUEST['comment']);
	nps_record_comment($user_identifier, $comment);
?>	
<center>
Thanks for your help improving this service!
</center>
<?php
}	
