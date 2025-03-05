<?php
/**
 * Userverwaltung
 * @author stefan@covi.de
 * @since 3.1
 * @version 7.0
 * @lastchange 2019-06-18
 */

/* start session ----------------------------- */
session_start();
/* base includes ----------------------------- */
require ("./data/include/usestat.inc.php");
require ("./data/include/globalvars.inc.php");
/* checkParamVar ----------------------------- */

/* define actual system position ------------- */
$_SESSION['wspvars']['lockstat'] = ''; // beschreibt als string, ob hier eine rechte-sperre vorliegt
$_SESSION['wspvars']['mgroup'] = 10;
$_SESSION['wspvars']['pagedesc'] = array('fa fa-user',returnIntLang('menu manage'),returnIntLang('menu user userlog'));
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fposcheck'] = false; 
$_SESSION['wspvars']['preventleave'] = true;
/* second includes --------------------------- */
require ("./data/include/checkuser.inc.php");
require ("./data/include/errorhandler.inc.php");
require ("./data/include/siteinfo.inc.php");
/* define page specific vars ----------------- */
/* define page specific funcs ----------------- */
if (isset($_POST['op']) && $_POST['op']=="cl") {
	$sql = "DELETE FROM `securitylog` WHERE `uid` = ".intval($_POST['userrid'])." LIMIT ".intval($_POST['countrows']);
    $res = doSQL($sql);
	if ($res['res']) {
        addWSPMsg('noticemsg', intval($_POST['countrows'])." ".returnIntLang('userlog rowsdeleted', true));
    }
}

// head der datei
include ("./data/include/header.inc.php");
include ("./data/include/navbar.inc.php");
include ("./data/include/sidebar.inc.php");
?>

<div class="main">
    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="content-heading clearfix">
            <div class="heading-left">
                <h1 class="page-title"><?php echo returnIntLang('userlog headline'); ?></h1>
                <p class="page-subtitle"><?php echo returnIntLang('userlog info'); ?></p>
            </div>
            <ul class="breadcrumb">
                <li><i class="<?php echo $_SESSION['wspvars']['pagedesc'][0]; ?>"></i> <?php echo $_SESSION['wspvars']['pagedesc'][1]; ?></li>
                <li><?php echo $_SESSION['wspvars']['pagedesc'][2]; ?></li>
            </ul>
        </div>
        <div class="container-fluid">
            <?php showWSPMsg(1); ?>
            <?php if ($_SESSION['wspvars']['usertype']==1) { ?>
                <div class="row">
                    <?php require ("./data/panels/usermanagement.history.inc.php"); ?>
                </div>
                <div class="row">
                    <?php require ("./data/panels/usermanagement.showlogs.inc.php"); ?>
                </div>
            <?php } ?>
        </div>
    </div>
</div>
<?php 

include ("data/include/footer.inc.php");
// EOF