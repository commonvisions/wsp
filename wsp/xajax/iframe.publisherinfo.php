<?php
/**
 * website publisherinfo
 * @author stefan@covi.de
 * @since 7.0
 * @version 7.0
 * @lastchange 2019-09-03
 */

/* start session ----------------------------- */
session_start();
/* base includes ----------------------------- */
require ("../data/include/usestat.inc.php");
require ("../data/include/globalvars.inc.php");
// define actual system position - not required
// second includes ---------------------------
require ("../data/include/checkuser.inc.php");
require ("../data/include/errorhandler.inc.php");
require ("../data/include/siteinfo.inc.php");
/* page specific includes */

/* define page specific vars ----------------- */

/* define page specific functions ------------ */
	
$queue_sql = "SELECT `id` FROM `wspqueue` WHERE `done` = 0 GROUP BY CONCAT(`param`,`lang`)";
$queue_res = doSQL($queue_sql);
$queue_num = $queue_res['num'];
echo $queue_num;

?>