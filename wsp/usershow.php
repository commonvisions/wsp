<?php
/**
 * @author stefan@covi.de
 * @since 3.1
 * @version GIT
 * 
 * 2025-02-11
 * fixed bug with undefined $wspvars['usevar']
 * 
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
$_SESSION['wspvars']['menuposition'] = "usershow";
$_SESSION['wspvars']['lockstat'] = ''; // beschreibt als string, ob hier eine rechte-sperre vorliegt
$_SESSION['wspvars']['mgroup'] = 2; // aktive menuegruppe
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF']; // fuer den eintrag im logfile sowie die entsprechende ueberpruefung der fposcheck
$_SESSION['wspvars']['fposcheck'] = false;
$_SESSION['wspvars']['preventleave'] = false;
/* second includes --------------------------- */
require ("./data/include/checkuser.inc.php");
require ("./data/include/errorhandler.inc.php");
require ("./data/include/siteinfo.inc.php");
/* define page specific vars ----------------- */

/* define page specific functions ------------ */
if (isset($_POST['op']) && $_POST['op']=="setfree"):
	if (isset($_POST['setfree'])):
		for ($f=0;$f<count($_POST['setfree']);$f++):
			doSQL("DELETE FROM security WHERE `sid` = ".intval($_POST['setfree'][$f]));
		endfor;
	endif;
endif;

// head der datei

include ("./data/include/header.inc.php");
include ("./data/include/wspmenu.inc.php");
?>

<div id="contentholder">
	<fieldset class="text"><h1><?php echo returnIntLang('loginstat headline'); ?></h1></fieldset>
	<fieldset>
		<legend><strong><?php echo returnIntLang('loginstat servertime'); ?> <?php echo date("Y-m-d H:i:s"); ?></strong></legend>
	<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" name="setfreeform" id="setfreeform">
	<ul class="tablelist" id="logstat">
		<li class="tablecell head two"><?php echo returnIntLang('loginstat user'); ?></li>
		<li class="tablecell head two"><?php echo returnIntLang('loginstat last'); ?></li>
		<li class="tablecell head four"><?php echo returnIntLang('loginstat pos'); ?></li>
	</ul>
	<input name="usevar" type="hidden" value="<?php echo ($wspvars['usevar'] ?? ''); ?>" />
	<input name="op" type="hidden" value="setfree" />
	</form>
	</fieldset>
	<fieldset class="options">
		<p><a href="#" onClick="document.getElementById('setfreeform').submit();" class="greenfield"><?php echo returnIntLang('loginstat logoff marked button'); ?></a></p>
	</fieldset>
</div>

<script language="JavaScript" type="text/javascript">

function showLogstat() {
	$.post("xajax/ajax.updatelogstat.php")
		.done (function(data) {
			if (data) {
				$('#logstat').html(data);
				passLiTable('#logstat', 'li.tablecell', 8, new Array('one','two','three','four','five','six','seven','eight'), 'tblc');
				console.log('updated logstat');
				}
			})
	}

function callShowLogstat() {
	showLogstat();
	setTimeout("callShowLogstat();", 30000);
	}

$(window).load(function() {
	callShowLogstat();
	});
	
</script>

<?php 

@include ("./data/include/footer.inc.php"); 
