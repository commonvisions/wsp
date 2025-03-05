<?php
/**
 * @description dashboard page
 * @author stefan@covi.de
 * @since 7.0
 * @version 7.0
 * @lastchange 2021-09-15
 */

/* start session ----------------------------- */
session_start();
/* base includes ----------------------------- */
require("./data/include/usestat.inc.php");
require("./data/include/globalvars.inc.php");
// define actual system position -------------
$_SESSION['wspvars']['lockstat'] = ''; // beschreibt als string, ob hier eine rechte-sperre vorliegt
$_SESSION['wspvars']['pagedesc'] = array('fa fa-dashboard',returnIntLang('menu home'),returnIntLang('menu home cms'));
$_SESSION['wspvars']['menuposition'] = 'index'; // string mit der aktuellen position fuer backend-auswertung
$_SESSION['wspvars']['mgroup'] = 1; // aktive menuegruppe
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF']; // fuer den eintrag im logfile sowie die entsprechende ueberpruefung der fposcheck
$_SESSION['wspvars']['fposcheck'] = false; // bestimmt, ob ein bereich fuer andere benutzer gesperrt wird (true), wenn sich hier schon ein benutzer befindet, oder nicht (false)
$_SESSION['wspvars']['preventleave'] = false; // tells user, to save data before leaving page
$_SESSION['wspvars']['addpagecss'] = array();
$_SESSION['wspvars']['addpagejs'] = array();
/* second includes --------------------------- */
require("./data/include/checkuser.inc.php");
require("./data/include/siteinfo.inc.php");
/* define page specific vars ----------------- */

// do update checks if admin user logged in
if ($_SESSION['wspvars']['usertype']==1) {
    $_SESSION['wspvars']['updatedate'] = 0;
    $_SESSION['wspvars']['updateversion'] = 0;
    $_SESSION['wspvars']['updatesystem'] = null;
    if (isCurl()) {
        if (defined('WSP_UPDSRV') && WSP_UPDSRV=='git') {
            $defaults = array( 
                CURLOPT_URL => trim('https://api.github.com/repos/covistefan/wsp'),
                CURLOPT_HEADER => 0,
                CURLOPT_USERAGENT => 'WebSitePreview/7.0',
                CURLOPT_POST => 0,
                CURLOPT_RETURNTRANSFER => TRUE, 
                CURLOPT_TIMEOUT => 4 
            );
            $ch = curl_init();
            curl_setopt_array($ch, $defaults);    
            if( ! $getgit = curl_exec($ch)) { addWSPMsg('errormsg', 'github returned: '.trigger_error(curl_error($ch))); }
            curl_close($ch);
            $getgit = (json_decode($getgit, true));
            $_SESSION['wspvars']['updatedate'] = strtotime($getgit['pushed_at']);
        } else {
            $defaults = array( 
                CURLOPT_URL => trim('https://'.WSP_UPDSRV.'/download/version.json'),
                CURLOPT_HEADER => 0, 
                CURLOPT_RETURNTRANSFER => TRUE, 
                CURLOPT_TIMEOUT => 4 
            );
            $ch = curl_init();
            curl_setopt_array($ch, $defaults);    
            if( ! $getversion = curl_exec($ch)) { addWSPMsg('errormsg', trigger_error(curl_error($ch))); }
            curl_close($ch);
            $getversion = (json_decode($getversion, true));
            $_SESSION['wspvars']['updatedate'] = intval($getversion['pushed_at']);
            $_SESSION['wspvars']['updateversion'] = trim($getversion['version']);
        }
    }
    else {
        if (defined('WSP_UPDSRV') && WSP_UPDSRV=='git') {
            // fopen is not supported … so we can't get information
        } else {
            $fh = @fopen('https://'.WSP_UPDSRV.'/download/version.json', 'r');
            if (intval($fh)!=0) {
                $getversion = '';
                while (!feof($fh)) {
                    $getversion .= fgets($fh);
                }
                fclose($fh);
                $getversion = (json_decode($getversion, true));
                $_SESSION['wspvars']['updatedate'] = intval($getversion['pushed_at']);
                $_SESSION['wspvars']['updateversion'] = trim($getversion['version']);
            }
        }
    }
    // try to get information about stored version and last update
    if (getWSPProperties('lastupdate')!=false) {
        if (getWSPProperties('lastupdate')<$_SESSION['wspvars']['updatedate']) {
            $_SESSION['wspvars']['updatesystem'] = true;
        }
    }
    if (getWSPProperties('lastversion')!=false) {
        if (version_compare($_SESSION['wspvars']['updateversion'],getWSPProperties('lastversion'))>0) {
            $_SESSION['wspvars']['updatesystem'] = true;
        }
    }
}

/* include head ------------------------------ */
require("./data/include/header.inc.php");
require("./data/include/navbar.inc.php");
require("./data/include/sidebar.inc.php");
?>
<!-- MAIN -->
<div class="main">
    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="content-heading clearfix">
            <div class="heading-left">
                <h1 class="page-title"><?php echo returnIntLang('home welcome'); ?></h1>
                <p class="page-subtitle"><?php echo returnIntLang('home info'); ?> <strong><?php echo $_SESSION['wspvars']['workspaceurl']; ?></strong></p>
            </div>
            <ul class="breadcrumb">
                <li><i class="<?php echo $_SESSION['wspvars']['pagedesc'][0]; ?>"></i> <?php echo $_SESSION['wspvars']['pagedesc'][1]; ?></li>
                <li><?php echo $_SESSION['wspvars']['pagedesc'][2]; ?></li>
            </ul>
        </div>
        <div class="container-fluid">
            <?php showWSPMsg(1); ?>
            <div class="row clear-4" id="widgetholder">
                <?php 
                
                $widgets = doSQL("SELECT * FROM `wspproperties` WHERE `varname` LIKE 'widget%' AND `varvalue` = '1' ORDER BY `varname`");
                foreach ($widgets['set'] AS $wsk => $wsv) {
                    showWSPWidget(str_replace("widget_", "", $wsv['varname']), false);
                }
                
                if ($_SESSION['wspvars']['usertype']==1) {
                    include("./data/panels/sysinfo.inc.php"); 
                    include("./data/panels/managewidgets.inc.php");
                }
                
                ?>
            </div>
            <div class="row">
                <!-- CONTENT CHANGED -->
                <?php 
                $panel['outerclass'] = 'col-md-6';
                include("./data/panels/content.changed.inc.php");
                ?>
                <!-- END CONTENT CHANGED -->
                <!-- CONTENT PUBLISHED -->
                <?php 
                $panel['outerclass'] = 'col-md-6';
                include("./data/panels/content.published.inc.php"); 
                ?>
                <!-- END CONTENT PUBLISHED -->
            </div>
        </div>
    </div>
    <!-- END MAIN CONTENT -->
</div>
<!-- END MAIN -->

<script type="text/javascript">

legend = new Tour(
    {
    steps: [
    {
        element: '#legend-start',
        placement: 'bottom',
        title: '<?php echo returnIntLang('tour welcome to websitepreview', false); ?>',
        content: '<?php echo returnIntLang('tour welcome to websitepreview desc', false); ?>'
    },
    {
        element: '#sidebar-nav-menu',
        placement: 'right',
        title: '<?php echo returnIntLang('tour the new menu', false); ?>',
        content: '<?php echo returnIntLang('tour the new menu desc', false); ?>'
    },
    {
        element: '#tour-fullwidth',
        placement: 'bottom',
        title: '<?php echo returnIntLang('tour minimize button', false); ?>',
        content: '<?php echo returnIntLang('tour minimize button desc', false); ?>'
    },
    {
        element: '#widgetholder',
        placement: 'bottom',
        title: '<?php echo returnIntLang('tour widgets', false); ?>',
        content: '<?php echo returnIntLang('tour widgets desc', false); ?>'
    }],
    template: "<div class='popover tour'> " +
        "<div class='arrow'></div> " +
        "<h3 class='popover-title'></h3>" +
        "<div class='popover-content'></div>" +
        "<div class='popover-navigation'>" +
        "<button class='btn btn-default btn-sm' data-role='prev'>« <?php echo returnIntLang('str last', false); ?></button>" +
        "<button class='btn btn-primary btn-sm' data-role='next'><?php echo returnIntLang('str next', false); ?> »</button>" +
        "<button class='btn btn-default btn-sm' data-role='end'><?php echo returnIntLang('str finish', false); ?></button>" +
        "</div>" +
        "</div>",
        });
    legend.init();

function lsTest(){ 
    var test = 'test';
    try {
      localStorage.setItem(test, test);
      localStorage.removeItem(test);
      return true;
    } catch(e) {
      return false;
    }
}
    
$(document).ready(function() { if(lsTest() === true){ localStorage.setItem('token','<?php echo $_SESSION['wspvars']['usevar']; ?>'); }});
    
</script>

<?php require ("./data/include/footer.inc.php"); ?>