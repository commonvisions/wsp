<?php
/**
 * trash
 * @author s.haendler@covi.de
 * @copyright (c) 2020, Common Visions Media.Agentur (COVI)
 * @since 3.1
 * @version 6.9.1
 * @lastchange 2020-07-13
 */

/* start session ----------------------------- */
session_start();
/* base includes ----------------------------- */
require ("./data/include/usestat.inc.php");
require ("./data/include/globalvars.inc.php");
/* first includes ---------------------------- */
require ("./data/include/wsplang.inc.php");
require ("./data/include/dbaccess.inc.php");
if (file_exists("./data/include/ftpaccess.inc.php")) require ("./data/include/ftpaccess.inc.php");
require ("./data/include/funcs.inc.php");
/* checkParamVar ----------------------------- */

/* define actual system position ------------- */
$_SESSION['wspvars']['lockstat'] = 'trash';
$_SESSION['wspvars']['mgroup'] = 10;
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fposcheck'] = false;
$_SESSION['wspvars']['preventleave'] = false;
require ("./data/include/checkuser.inc.php");
require ("./data/include/errorhandler.inc.php");
require ("./data/include/siteinfo.inc.php");
/* define page specific vars ----------------- */

/* define page specific functions ------------ */
if (isset($_REQUEST['op']) && trim($_REQUEST['op'])!='') {
    if (trim($_REQUEST['op'])=='cmenu') {
        doSQL("DELETE FROM `menu` WHERE `trash` = 1");
        doSQL("DELETE FROM `content` WHERE `mid` NOT IN (SELECT `mid` FROM `menu`)");
        doSQL("DELETE FROM `content_backup` WHERE `cid` NOT IN (SELECT `cid` FROM `content`)");
    }
    else if (trim($_REQUEST['op'])=='ccontent') {
        doSQL("DELETE FROM `content` WHERE `trash` = 1");
        doSQL("DELETE FROM `content` WHERE `mid` NOT IN (SELECT `mid` FROM `menu`)");
        doSQL("DELETE FROM `content_backup` WHERE `cid` NOT IN (SELECT `cid` FROM `content`)");
    }
    else if (trim($_REQUEST['op'])=='cccontent') {
        doSQL("DELETE FROM `content` WHERE `mid` NOT IN (SELECT `mid` FROM `menu`)");
        doSQL("DELETE FROM `content_backup` WHERE `cid` NOT IN (SELECT `cid` FROM `content`)");
    }
    else if (trim($_REQUEST['op'])=='ccbackup') {
        doSQL("DELETE FROM `content_backup` WHERE `cid` NOT IN (SELECT `cid` FROM `content`)");
        doSQL("DELETE FROM `content_backup` WHERE `valuefields` = ''");
    }
}

// head der datei
include ("./data/include/header.inc.php");
include ("./data/include/wspmenu.inc.php");

?>
<div id="contentholder">
	<fieldset><h1><?php echo returnIntLang('trash headline'); ?></h1></fieldset>
    <?php
  
    $menu_res = doSQL("SELECT `mid` FROM `menu` WHERE `trash` = 1");
    $content_res = doSQL("SELECT `cid` FROM `content` WHERE `trash` = 1");
    $content_menu_res = doSQL("SELECT `cid` FROM `content` WHERE `mid` NOT IN (SELECT `mid` FROM `menu`)");
    $content_backup_res = doSQL("SELECT `cid` FROM `content_backup` WHERE `cid` NOT IN (SELECT `cid` FROM `content`) OR `valuefields` = ''");
    
    ?>
    <fieldset>
        <p>Es gibt <?php echo $menu_res['num']; ?> Menüeinträge im Papierkorb.</p>
        <?php if ($menu_res['num']>0) { ?>
        <p><a href="?op=cmenu">Aufräumen</a></p>
        <?php } ?>
    </fieldset>
    <fieldset>
        <p>Es gibt <?php echo $content_res['num']; ?> Inhalte im Papierkorb.</p>
        <?php if ($menu_res['num']>0) { ?>
        <p><a href="?op=ccontent">Aufräumen</a></p>
        <?php } ?>
    </fieldset>
    <fieldset>
        <p>Es gibt <?php echo $content_menu_res['num']; ?> nicht zugeordnete Inhalte.</p>
        <?php if ($menu_res['num']>0) { ?>
        <p><a href="?op=cccontent">Aufräumen</a></p>
        <?php } ?>
    </fieldset>
    <fieldset>
        <p>Es gibt <?php echo $content_backup_res['num']; ?> nicht zugeordnete oder leere Backups.</p>
        <?php if ($menu_res['num']>0) { ?>
        <p><a href="?op=ccbackup">Aufräumen</a></p>
        <?php } ?>
    </fieldset>
    <?php
    
    /*
    echo '<pre>';
    var_export($menu_res);
    var_export($content_res);
    var_export($content_menu_res);
    var_export($content_backup_res);
    echo '</pre>';
    
    DELETE FROM `menu` WHERE `trash` = 1;
    DELETE FROM `content` WHERE `mid` NOT IN (SELECT `mid` FROM `menu`);
    DELETE FROM `content` WHERE `trash` = 1;
    DELETE FROM `content_backup` WHERE `cid` NOT IN (SELECT `cid` FROM `content`);
    */
    
    ?>
</div>
<?php include ("./data/include/footer.inc.php"); ?>
<!-- // EOF -->