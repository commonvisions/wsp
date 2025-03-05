<?php
/**
 * website publisher
 * @author stefan@covi.de
 * @since 3.1
 * @version 7.0
 * @lastchange 2019-03-08
 */

// start session -----------------------------
session_start();
// base includes -----------------------------
require ("./data/include/usestat.inc.php");
require ("./data/include/globalvars.inc.php");
// define actual system position -------------
$_SESSION['wspvars']['lockstat'] = 'publishlog';
$_SESSION['wspvars']['mgroup'] = 7;
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fposcheck'] = false;
$_SESSION['wspvars']['preventleave'] = false;
$_SESSION['wspvars']['pagedesc'] = array('fa fa-globe',returnIntLang('menu changed publisher'),returnIntLang('menu changed'));
// second includes ---------------------------
require ("./data/include/checkuser.inc.php");
require ("./data/include/errorhandler.inc.php");
require ("./data/include/siteinfo.inc.php");
// page specific includes --------------------

$_SESSION['wspvars']['publisherdata'] = getWSPProperties();

// head der datei
include ("./data/include/header.inc.php");
include ("./data/include/navbar.inc.php");
require ("./data/include/sidebar.inc.php");

?>
<div class="main">
    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="content-heading clearfix">
            <div class="heading-left">
                <h1 class="page-title"><?php echo returnIntLang('publishlog headline'); ?></h1>
                <p class="page-subtitle"><?php echo returnIntLang('publishlog info'); ?></p>
            </div>
            <ul class="breadcrumb">
                <li><i class="<?php echo $_SESSION['wspvars']['pagedesc'][0]; ?>"></i> <?php echo $_SESSION['wspvars']['pagedesc'][1]; ?></li>
                <li><?php echo $_SESSION['wspvars']['pagedesc'][2]; ?></li>
            </ul>
        </div>
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <pre id="publishoutput" style="width: 100%; min-height: 60vh;"></pre>
                </div>
            </div>
        </div>
    </div>
</div>
<script>

    pLog = true;
    cT = 5000;
    
</script>
<?php include ("./data/include/footer.inc.php"); ?>