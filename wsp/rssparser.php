<?php
/**
 * Vorschau für den gewaehlten Feed
 * @author stefan@covi.de
 * @since 3.1
 * @version 6.8
 * @lastchange 2019-01-25
 */

/* start session ----------------------------- */
session_start();
/* base includes ----------------------------- */
require ("./data/include/usestat.inc.php");
require ("./data/include/globalvars.inc.php");
/* first includes ---------------------------- */
require ("./data/include/wsplang.inc.php");
require ("./data/include/dbaccess.inc.php");
require ("./data/include/funcs.inc.php");
/* checkParamVar ----------------------------- */

/* define actual system position ------------- */
$_SESSION['wspvars']['lockstat'] = 'rss';
$_SESSION['wspvars']['mgroup'] = 5;
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fposcheck'] = false;
$_SESSION['wspvars']['preventleave'] = false;
// second includes ---------------------------
require ("./data/include/checkuser.inc.php");
require ("./data/include/errorhandler.inc.php");
require ("./data/include/siteinfo.inc.php");
// define page specific vars -----------------
require ("./data/include/rssparser.inc.php");
// seitenspezifische funktionen
// head der datei
require "./data/include/header.inc.php";
require "./data/include/wspmenu.inc.php";
?>
<div id="contentholder">
	<fieldset><h1>RSS Vorschau</h1></fieldset>
	<fieldset>
		<pre style="max-width: 100%; white-space: pre-wrap;"><?php
        
        echo htmlentities(publishRSS($_SESSION['wspvars']['rssfeedid'], false, true));
        
        ?></pre>
	</fieldset>
	<fieldset class="options">
		<p><a href="rssedit.php" class="orangefield"><?php echo returnIntLang('str back'); ?></a></p>
	</fieldset>
</div>
<?php include ("./data/include/footer.inc.php"); ?>
<!-- EOF -->