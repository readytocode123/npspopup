<html>
<head>
<title>NPS Popup Test Page</title>
<?php 
require_once('npspopup.php'); 

// Your service should put in an identifier that's meaningful for your systems if possible,
// like a user name or id. If nothing is specified, the module will default to the client IP
if (isset($_REQUEST['user_identifier']))
	$user_identifier = $_REQUEST['user_identifier'];
else
	$user_identifier = null;

nps_include_popup($user_identifier); 

?>
<link rel="stylesheet" href="style.css" />
</head>
<body>
<div id="testdiv">
	<h1>NPS Popup Test Page</h1>
	<h2><a href="http://leanstartup.pbwiki.com">leanstartup.pbwiki.com</a>.</h2>
	<p>This module demonstrates a simple implementation of the Net Promoter Score (NPS) concept, using a random Javascript popup and a MySql/PHP back end</p>
	<p>When you first visit the page, you should see a popup appear after a couple of seconds, that will give you the chance to rate your experience on a scale of 0 to 10, and add any comments about the service</p>
	<p>The back end both ensures that users aren't asked to fill out this survey more than once, and collects them into a report, accessible through HTML at this address:</p>
	<p><a href="npsreport.php?reportpassword=<?=urlencode(NPS_REPORT_PASSWORD)?>">npsreport.php?reportpassword=<?=urlencode(NPS_REPORT_PASSWORD)?></a></p>
	<p>or as a JSON datasource to combine with other data collection tools at:</p>
	<p><a href="npsreport.php?reportpassword=<?=urlencode(NPS_REPORT_PASSWORD)?>&format=json">npsreport.php?reportpassword=<?=urlencode(NPS_REPORT_PASSWORD)?>&format=json</a></p>
	<p>Written by <a href="http://petewarden.typepad.com">Pete Warden</a>, using popup code by <a href="http://www.leigeber.com/2009/05/javascript-popup-box/">Michael Leigeber</a> and based on <a href="http://startuplessonslearned.blogspot.com/2008/11/net-promoter-score-operational-tool-to.html">Eric Ries' blog posts on NPS</a></p>
</div>

</body>
</html>