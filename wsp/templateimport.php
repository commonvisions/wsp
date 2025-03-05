<?php
/**
 * edit and create templates
 * @author stefan@covi.de
 * @since 3.1
 * @version 7.0
 * @lastchange 2019-05-14
 */

/* start session ----------------------------- */
session_start();
/* base includes ----------------------------- */
require ("./data/include/usestat.inc.php");
require ("./data/include/globalvars.inc.php");
/* define actual system position ------------- */
$_SESSION['wspvars']['lockstat'] = 'design';
$_SESSION['wspvars']['mgroup'] = 4;
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fposcheck'] = true;
$_SESSION['wspvars']['addpagejs'] = array(
    'jquery/jquery.autogrowtextarea.js',
    'jquery/jquery.nestable.js',
    );
// second includes ---------------------------
require ("./data/include/checkuser.inc.php");
require ("./data/include/siteinfo.inc.php");
// define page specific vars -----------------
// header disables the problem with sending jquery-script-call in templates sourcecode 
header("X-XSS-Protection: 0");

/* define page specific functions ------------ */

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
                <h1 class="page-title"><?php echo returnIntLang('templateimport headline'); ?></h1>
                <p class="page-subtitle"><?php echo returnIntLang('templateimport info'); ?></p>
            </div>
            <ul class="breadcrumb">
                <li><i class="<?php echo $_SESSION['wspvars']['pagedesc'][0]; ?>"></i> <?php echo $_SESSION['wspvars']['pagedesc'][1]; ?></li>
                <li><?php echo $_SESSION['wspvars']['pagedesc'][2]; ?></li>
            </ul>
        </div>
        <div class="container-fluid">
            <?php showWSPMsg(1); ?>
            <ul>
                <li>ordner layout</li>
                <li>ordner media</li>
            </ul>
        </div>
    </div>
</div>

<script type="text/javascript" language="javascript">
<!--

function checkEditFields() {
    if (document.getElementById('templatename').value == '') {
        alert('<?php echo returnIntLang('templates errormsg missing tplname', false); ?>');
        document.getElementById('templatename').focus();
        return false;
        }	// if
    document.getElementById('formedittemplate').submit();
    return true;
    }	// checkEditFields()

function insertVar(selectvar) {
    if (document.getElementById(selectvar).value != '') {
        // IE
        if (document.all) {
            document.getElementById('template').focus();
            strSelection = document.selection.createRange().text
            document.selection.createRange().text = document.getElementById(selectvar).value;
        }
        // Mozilla
        else if (document.getElementById) {
            var selLength = document.getElementById('template').textLength;
            var selStart = document.getElementById('template').selectionStart;
            var selEnd = document.getElementById('template').selectionEnd;
            if ((selEnd == 1) || (selEnd == 2)) {
                selEnd = selLength;
            }	// if
            var s1 = (document.getElementById('template').value).substring(0,selStart);
            var s2 = (document.getElementById('template').value).substring(selStart, selEnd)
            var s3 = (document.getElementById('template').value).substring(selEnd, selLength);
            document.getElementById('template').value = s1 + document.getElementById(selectvar).value + s3;
        }	// if
    }	// if
    document.getElementById('template').focus();
    return;
    document.getElementById(selectvar).value = '';
    }	// insertVar()

function confirmDeleteTemplate(templatename, tid) {
    if (confirm ('<?php echo returnIntLang('templates confirm delete template1', false); ?> »' + templatename + '« <?php echo returnIntLang('templates confirm delete template2', false); ?>')) {
        document.getElementById('deleteid').value = tid;
        document.getElementById('deletetemplateform').submit();			
        }
    }
    
$(function() {
    
    $(".allowTabChar").allowTabChar();
    $('.autogrow').autoGrow();
    
});

//-->
</script>
    
<?php require ("./data/include/footer.inc.php"); ?>