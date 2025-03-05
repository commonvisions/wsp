<?php
/**
 * Verwaltung von Contentelementen
 * @author stefan@covi.de
 * @since 3.1
 * @version 7.0
 * @lastchange 2020-07-01
 */

// start session -----------------------------
session_start();
// base includes -----------------------------
require ("./data/include/usestat.inc.php");
require ("./data/include/globalvars.inc.php");
// define actual system position -------------
$_SESSION['wspvars']['lockstat'] = 'contents';
$_SESSION['wspvars']['mgroup'] = 5;
$_SESSION['wspvars']['pagedesc'] = array('fa fa-sitemap',returnIntLang('menu content'),returnIntLang('menu content contents'));
$_SESSION['wspvars']['fpos'] = $_SERVER['PHP_SELF'];
$_SESSION['wspvars']['fposcheck'] = false;
$_SESSION['wspvars']['preventleave'] = false;
$_SESSION['wspvars']['addpagecss'] = array(
    'jquery.nestable.css',
    'bootstrap-multiselect.css'
    );
$_SESSION['wspvars']['addpagejs'] = array(
    'bootstrap/bootstrap-multiselect.js',
);
// second includes ---------------------------
require ("./data/include/checkuser.inc.php");
require ("./data/include/errorhandler.inc.php");
require ("./data/include/siteinfo.inc.php");
// define page specific vars -----------------

// jump from modules ..
if (isset($_REQUEST['mjid']) && intval($_REQUEST['mjid'])>0):
	$_SESSION['wspvars']['editcontentid'] = intval($_REQUEST['mjid']);
endif;
// jump from globalcontents
if (isset($_REQUEST['sgc']) && intval($_REQUEST['sgc'])>0):
	$_SESSION['wspvars']['editcontentid'] = intval($_REQUEST['sgc']);
endif;
/* page specific includes */
require ("./data/include/clsinterpreter.inc.php");
/* page specific funcs and actions */

$_SESSION['wspvars']['contentfilterid'] = array(); // array holds ONLY mid with found contents
$_SESSION['wspvars']['contentfiltermid'] = array(); // array holds all mid related upwards to mid with found contents
$_SESSION['wspvars']['contentfiltercid'] = array(); // array holds all cid

// setup page filter for mid with content only
if (isset($_REQUEST['lc']) || isset($_SESSION['wspvars']['contentlimit']) && $_SESSION['wspvars']['contentlimit']===true) {
    $openpath = array();
    $_SESSION['wspvars']['contentlimit'] = true;
    // find all contents
    $lcsql = "SELECT DISTINCT `mid` FROM `content` WHERE `trash` = 0";
    $lcres = getResultSQL($lcsql);
    $tmpmid = array();
    foreach ($lcres AS $lcrk => $lcrv) {
        $rit = returnIDTree($lcrv);
        if (is_array($rit) && count($rit)>0) {
            $rit[] = $lcrv;
            $tmpmid = array_merge($tmpmid, $rit);
        }
    } 
    $tmpmid = array_unique($tmpmid);
    $tmpmid = array_values($tmpmid);
    if (count($tmpmid)>0) {
        $_SESSION['wspvars']['contentfiltermid'] = $tmpmid;
    }
}
// reset page filter for mid with content only
if (isset($_REQUEST['ac'])) {
    $_SESSION['wspvars']['contentlimit'] = false;
    $_SESSION['wspvars']['contentfiltercid'] = array();
    $_SESSION['wspvars']['contentfilterid'] = array();
    $_SESSION['wspvars']['contentfiltermid'] = array();
}
// setup content filter if it is given
if (isset($_POST['contentfilter'])) { 
    $_SESSION['wspvars']['contentfilter'] = false;
    if (strlen(trim($_POST['contentfilter']))>2) {
        $_SESSION['wspvars']['contentfilter'] = trim($_POST['contentfilter']);
    }
}
// set open path by last edited or given content
if (isset($_SESSION['wspvars']['editcontentid']) && intval($_SESSION['wspvars']['editcontentid'])>0) {
    $ecsql = "SELECT DISTINCT `mid` FROM `content` WHERE `cid` = ".intval($_SESSION['wspvars']['editcontentid']);
    $ecres = doResultSQL($ecsql);
    if (intval($ecres)>0) {
        // set session mid with last edited content
        $opencontent = intval($ecres);
        $openpath = returnIDTree($ecres);
    } else {
        $openpath = array();
    }
} else {
    $openpath = array();
}

// setup content filter by search
if (isset($_SESSION['wspvars']['contentfilter']) && trim($_SESSION['wspvars']['contentfilter'])!='') {
    // get all global contents that fit search statement
    $globalcontents = getResultSQL("SELECT `id` FROM `content_global` WHERE (`valuefields` LIKE '%".escapeSQL(trim($_SESSION['wspvars']['contentfilter']))."%')  AND `trash` = 0");
    // add empty statement to fill with globalcontent ids if avaiable
    $addsql = '';
    if (is_array($globalcontents) && count($globalcontents)>0): $addsql = " OR `globalcontent_id` IN ('".implode("','", $globalcontents)."') "; endif;
    // get all contents that fit search statement (and - if set - fit globalcontent ids)
    $contents = doSQL("SELECT `mid`, `cid` FROM `content` WHERE ((`valuefields` LIKE '%".escapeSQL(trim($_SESSION['wspvars']['contentfilter']))."%' OR `description` LIKE '%".escapeSQL(trim($_SESSION['wspvars']['contentfilter']))."%') ".$addsql.") AND `trash` = 0");
    // empty some vars
    unset($addsql);
    unset($globalcontents);
    if (is_array($contents['set']) && count($contents['set'])>0) {
        // reset open path ???? 
//      $openpath = array();
        foreach ($contents['set'] AS $csk => $csv) {
            $_SESSION['wspvars']['contentfiltercid'][] = $csv['cid'];
            $_SESSION['wspvars']['contentfilterid'][] = $csv['mid'];
//          $_SESSION['wspvars']['contentfiltermid'] = array_merge(returnIDTree($csv['mid']), $_SESSION['wspvars']['contentfiltermid']);
        }
        // empty some vars
        unset($contents);
        $_SESSION['wspvars']['contentfiltercid'] = array_unique($_SESSION['wspvars']['contentfiltercid']);
        sort($_SESSION['wspvars']['contentfiltercid']);
        $_SESSION['wspvars']['contentfilterid'] = array_unique($_SESSION['wspvars']['contentfilterid']);
        sort($_SESSION['wspvars']['contentfilterid']);
//      $_SESSION['wspvars']['contentfiltermid'] = array_unique($_SESSION['wspvars']['contentfiltermid']);
//      sort($_SESSION['wspvars']['contentfiltermid']);
        foreach ($_SESSION['wspvars']['contentfilterid'] AS $cfik => $cfiv) {
            $rit = returnIDTree($cfiv);
            if (is_array($rit) && count($rit)>0) {
                $rit[] = $cfiv;
                $openpath = array_merge($openpath, $rit);
            }
        }
    }
    else {
        $_SESSION['wspvars']['contentfiltercid'] = array();
        $_SESSION['wspvars']['contentfilterid'] = array();
        $_SESSION['wspvars']['contentfiltermid'] = array();
    }
}
else {
    $_SESSION['wspvars']['contentfilter'] = '';
}

if (!($_SESSION['wspvars']['rights']['contents']==2 || $_SESSION['wspvars']['rights']['contents']==4 || $_SESSION['wspvars']['rights']['contents']==5 || $_SESSION['wspvars']['rights']['contents']==7 || $_SESSION['wspvars']['rights']['contents']==15)) {
	$_SESSION['wspvars']['rights']['contents_array'] = array();
}

if ((isset($_POST['op']) && $_POST['op']=='add') && isset($_POST['sid']) && isset($_POST['gcid']) && isset($_POST['mid']) && intval($_POST['mid'])>0) {
    $ncid = insertContent(intval($_POST['mid']), 'add', trim($_POST['lang']), intval($_POST['carea']), intval($_POST['posvor']), $_POST['sid'], $_POST['gcid']);
	
    var_export($ncid);
    
    if (intval($ncid)>0) {
		$_SESSION['wspvars']['editcontentid'] = intval($ncid);
        addWSPMsg('resultmsg', returnIntLang('contentedit new content created succesfully'));
        header('location: contentedit.php');
    }
	else {
		addWSPMsg('errormsg', returnIntLang('contentedit failure creating new content'));
    }
}

// redirect to contentedit, if an id was set
if (isset($_POST['editcontentid']) && intval($_POST['editcontentid'])>0) {
	$_SESSION['wspvars']['editcontentid'] = intval($_POST['editcontentid']);
	header('location: contentedit.php');
	die();
}

// head of file - first regular output
require ("./data/include/header.inc.php");
require ("./data/include/navbar.inc.php");
require ("./data/include/sidebar.inc.php");
?>
<!-- MAIN -->
<div class="main">
    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="content-heading clearfix">
            <div class="heading-left">
                <h1 class="page-title"><?php echo returnIntLang('contentstructure headline'); ?></h1>
                <p class="page-subtitle"><?php echo returnIntLang('contentstructure info'); ?></p>
            </div>
            <ul class="breadcrumb">
                <li><i class="<?php echo $_SESSION['wspvars']['pagedesc'][0]; ?>"></i> <?php echo $_SESSION['wspvars']['pagedesc'][1]; ?></li>
                <li><?php echo $_SESSION['wspvars']['pagedesc'][2]; ?></li>
            </ul>
        </div>
        <div class="container-fluid">
            <?php showWSPMsg(1); ?>
            <div class="row">
                <?php if ((isset($_SESSION['wspvars']['disablehelp']) && intval($_SESSION['wspvars']['disablehelp'])==1)) { ?>
                <div class="col-md-12">
                <?php } else { ?>
                <div class="col-md-8">
                <?php } ?>
                    <div class="panel">
                        <div class="panel-heading">
                            <h3 class="panel-title"><?php 
                                
                                if ($_SESSION['wspvars']['rights']['contents']==1) { 
                                    if (isset($_SESSION['wspvars']['contentlimit']) && $_SESSION['wspvars']['contentlimit']===true) {
                                        echo returnIntLang('contents actualstructure filtered', true); 
                                    }
                                    else {
                                        echo returnIntLang('contents actualstructure', true); 
                                    }
                                }
                                else { 
                                    if (isset($_SESSION['wspvars']['contentlimit']) && $_SESSION['wspvars']['contentlimit']===true) {
                                        echo returnIntLang('contents restrictedstructure filtered', true); 
                                    }
                                    else {
                                        echo returnIntLang('contents restrictedstructure', true); 
                                    }
                                } 
                            
                            ?></h3>
                            <div class="right">
                                <div class="dropdown">
                                    <a href="#" class="toggle-dropdown" data-toggle="dropdown" aria-expanded="false"><i class="fa fa-bars"></i></a>
                                    <ul class="dropdown-menu dropdown-menu-right">
                                        <?php if (!(isset($_SESSION['wspvars']['contentlimit'])) || (isset($_SESSION['wspvars']['contentlimit']) && $_SESSION['wspvars']['contentlimit']!==true)) { ?>
                                        <li><a href='?lc'><i class='fas fa-list-alt'></i> <?php echo returnIntLang('contents show only when sub or content'); ?></a></li>
                                        <?php } else { ?>
                                        <li><a href='?ac'><i class='far fa-list-alt'></i> <?php echo returnIntLang('contents show all'); ?></a></li>
                                        <?php } ?>
                                    </ul>
                                </div>
                            </div>
                            <?php 
                            // block to define workspace language
                            if ((array_key_exists('workspacelang', $_SESSION['wspvars']) && $_SESSION['wspvars']['workspacelang']=="") || (!(array_key_exists('workspacelang', $_SESSION['wspvars'])))):
                                $_SESSION['wspvars']['workspacelang'] = $_SESSION['wspvars']['sitelanguages']['shortcut'][0];
                            endif;
                            if (isset($_REQUEST['wsl']) && trim($_REQUEST['wsl'])!=""):
                                $_SESSION['wspvars']['workspacelang'] = trim($_REQUEST['wsl']);
                            endif;
	
                            if (intval(count($_SESSION['wspvars']['sitelanguages']['shortcut']))>1) {
                                ?>
                                <div class="right">
                                    <div class="dropdown">
                                        <a href="#" class="toggle-dropdown" data-toggle="dropdown" aria-expanded="false"><i class="fa fa-globe"></i> <?php echo strtoupper($_SESSION['wspvars']['workspacelang']); ?></a>
                                        <ul class="dropdown-menu dropdown-menu-right">
                                            <?php
                                            
                                            foreach ($_SESSION['wspvars']['sitelanguages']['shortcut'] AS $key => $value):
                                                echo "<li><a href='?wsl=".$_SESSION['wspvars']['sitelanguages']['shortcut'][$key]."'>";
                                                echo "<i class=\"fa ";
                                                echo ($_SESSION['wspvars']['workspacelang']==$_SESSION['wspvars']['sitelanguages']['shortcut'][$key]) ? 'fa-check-circle' : 'fa-globe';
                                                echo "\"></i>".$_SESSION['wspvars']['sitelanguages']['longname'][$key]."</a></li>";
                                            endforeach;
                                            
                                            ?>
                                        </ul>
                                    </div>
                                </div>
                            <?php 
                            } 
                            
                            ?>
                        </div>
                        <div class="panel-option">
                            <form name="searchcontent-form" id="searchcontent-form" method="post">
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-search"></i><?php
                                    
                                    if (isset($_SESSION['wspvars']['contentfilterid']) && is_array($_SESSION['wspvars']['contentfilterid']) && count($_SESSION['wspvars']['contentfilterid'])>0): echo " (".count($_SESSION['wspvars']['contentfilterid']).")"; endif;
                                    
                                    ?>
                                </span>
                                <input type="text" class="form-control" placeholder="<?php echo returnIntLang('content filter input and return', false); ?>" id="contentfilter" name="contentfilter" value="<?php if(isset($_SESSION['wspvars']['contentfilter']) && trim($_SESSION['wspvars']['contentfilter'])!=''): echo trim($_SESSION['wspvars']['contentfilter']); endif; ?>" />
                            </div>
                            </form>
                        </div>
                        <div class="panel-body" id="contentstructure">
                            <div class="row">
                                <div class="col-md-12 no-margin">
                                    <?php
                                    
                                    $datatable = 'menu';
                                    $mid_res = doSQL("SELECT `mid` FROM `".$datatable."` WHERE `connected` = 0 AND !(`visibility` = 0 AND (`internlink_id` > 0 || `forwarding_id` > 0)) ORDER BY `position`");
                                    foreach ($mid_res['set'] AS $mk => $mv):
                                        echo returnContentStructureItem($datatable, $mv['mid'], true, 9999, $openpath, $_SESSION['wspvars']['contentfiltermid'], 'list', array('visible'=>1));
                                    endforeach;
                                    
                                    ?>
                            
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php if (isset($_SESSION['wspvars']['disablehelp']) && intval($_SESSION['wspvars']['disablehelp'])==1) {} else { ?>
                    <div class="col-sm-4 col-md-4 col-lg-4">
                        <?php require ("./data/panels/contents.icondesc.inc.php"); ?>
                    </div>
                <?php } ?>
            </div>
            
            <p><a class="btn btn-primary" onClick="createContent(1, 0);"><?php echo returnIntLang('contents create new content'); ?></a></p>
	
            <form id="editcontents" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
            <input type="hidden" id="editcontentid" name="editcontentid" value="" />
            </form>
            <form id="editglobal" method="post" action="globalcontentedit.php">
            <input type="hidden" name="op" value="edit" />
            <input type="hidden" id="editglobalid" name="gcid" value="" />
            </form>
        </div>
    </div>
</div>

<?php 
    
// embed holder for new content area if adding contents is allowed to user    
if($_SESSION['wspvars']['rights']['contents']!=3 && $_SESSION['wspvars']['rights']['contents']!=4) {
    echo '<div id="newcontent" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="newcontentLabel"></div>';
} 
    
?>

<script type="text/javascript" charset="utf-8">

    function checkData() {
        if ($('#sid').val()==0 && $('#gcid').val()==0) {
            alert(unescape('<?php echo setUTF8(returnIntLang('contentstructure jshint select interpreter', false)); ?>'));
            return false;
        }	// if
        else {
            $('#formnewcontent').submit();
        }	// if
    }	// checkData()

    function doShowHide(cid) {
        if (parseInt(cid)>0) {
            $.post("xajax/ajax.togglecontentview.php", { 'cid': parseInt(cid) })
                .done (function(data) {
                if (data=='show') {
                    $('#viewstat-' + parseInt(cid)).removeClass('far').removeClass('fas').addClass('fas');
                    $('#viewstat-' + parseInt(cid) + '-shown').show();
                    $('#viewstat-' + parseInt(cid) + '-hidden').hide();
                }
                if (data=='hide') {
                    $('#viewstat-' + parseInt(cid)).removeClass('far').removeClass('fas').addClass('far');
                    $('#viewstat-' + parseInt(cid) + '-shown').hide();
                    $('#viewstat-' + parseInt(cid) + '-hidden').show();
                }
            });
        }
    }

    function doClone(cid) {
        if (parseInt(cid)>0) {
            var carea = $('.dd-list li#' + cid + '.dd-item').parentsUntil('.panel-group').parent().children('.panel-body');
            var cinfo = $('.dd-list li#' + cid + '.dd-item').parentsUntil('.panel-group').parent().children('.panel-heading');
            $.post("./xajax/ajax.clonecontent.php", { 'cid': cid }).done (function(getmid) {
                // data returning is "false" or "mid"

                alert (data);

                /*
                if (getmid!==false && getmid>0) {
                    $.post("xajax/ajax.showcontent.php", { 'mid': getmid }).done (function(data) {
                        carea.html(data).show();
                        createSortable();
                    });
                };
                */
                
            });
        }
    }
    
function doDelete(cid, cname) {
    if (parseInt(cid)>0) {
        if (confirm('<?php echo returnIntLang('contentstructure jshint confirmdelete content1', false); ?>»' + cname + '«<?php echo returnIntLang('contentstructure jshint confirmdelete content2', false); ?>')) {
            $.post("xajax/ajax.contentdelete.php", { 'cid': cid })
            .done (function(data) {
                $(data).toggle('fade', {}, 300);
                $('#sidebar-trash-menu').show();
            });
        }
    }
}
    
// sets up the editcontent-form and its value and submits the form 
function doEdit(cid) {
    if (parseInt(cid)>0) {
        $('#editcontentid').val(parseInt(cid));
        $('#editcontents').submit();
    }
}

function setupPublisher(mid) {
    $.post("xajax/ajax.contentpublish.php", { 'mid': mid })
        .done (function(data) {
            if (parseInt(data)==0) {
                $('span.queue-num-badge').hide();
            } 
            else if (parseInt(data)>0) {
                $('#toggledirectpublish-' + mid).html('<i class="fas fa-spinner fa-spin"></i>');
                $('span.queue-num-badge').text(data);
                $('span.queue-num-badge').show();
            }
            console.info('sent ' + parseInt(data) + ' pages to publisher');
        });
}
    
// sets up the contents of #newcontent by ajax call and call the modal to show
function createContent(mid, carea) {
    $.post("./xajax/ajax.addcontent.php", { 'mid': mid, 'carea': carea }).done (function(data) {
        $('#newcontent').html(data);
        initMultisearch();
    });
    $('#newcontent').modal('show');
}

function updateCreateContent(mid, carea) {
    $.post("./xajax/ajax.addcontent.php", { 'mid': mid, 'carea': carea })
        .done (function(data) {
            $('#newcontent').html(data);
            initMultisearch();
        });
}
    
// creates (and recreates) the sortable contents list and listens to changes to submit to database
function createSortable(){

    $(".content-sortable").sortable({
        handle: ".dd-handle",
        connectWith: ".content-sortable",
        placeholder: "content-placeholder",
        forcePlaceholderSize: true,
        forceHelperSize: true,
        items: "li:not(.dd-disabled)",
        dropOnEmpty: true,
        appendTo: document.body,
    })
    .on('sortupdate', function(event, ui) {
        $.post("xajax/ajax.sortcontent.php", { 'copy': event.shiftKey, 'mid': $(this).attr('mid'), 'dataset': window.JSON.stringify($(this).sortable('toArray')) }).done (function(data) {
            if (data!='') {
                $('#outputDD1').text(data);
                console.info(data);
            }
        });
    });
    
    $(".content-nodrop").sortable({
        handle: ".dd-handle",
        connectWith: ".content-sortable",
        placeholder: "content-placeholder",
        forcePlaceholderSize: true,
        forceHelperSize: true,
        items: "li:not(.dd-disabled)",
        dropOnEmpty: true,
        appendTo: document.body,
    });
    
};

function initMultisearch() {
    
    $('.singleselect').multiselect();
    
    $('.searchselect').multiselect({
        maxHeight: 300,
        enableFiltering: true,
    });
    
    $('.fullselect').multiselect({
        enableFiltering: true,
        numberDisplayed: 10,
        maxHeight: 300,
        optionClass: function(element) {
            var value = $(element).attr('class');
            return value;
        }
    });
}
    
$(document).ready(function() {

    createSortable();
    initMultisearch();
    
    $(".toggle-content").on('click', function(event) {
        var carea = $(this).parentsUntil('.panel').next('.panel-body');
        if (carea.css('display')=='none') { 
            $.post("xajax/ajax.showcontent.php", { 'mid': $(this).attr('mid') }).done (function(data) {
                carea.html(data).show();
                createSortable();
                });
            } 
        else {
            carea.hide();
            carea.html('<em>this area will show contents on call</em>');
        }
    });
    
    $(".toggle-structure").on('click', function(event) {
        if ($(this).parents('.panel').next('.sub').css('display')=='none') {
            $(this).parents('.panel').next('.sub').show();
            $.post("xajax/ajax.setopenstructure.php", { 'mid': $(this).attr('mid') , 'op': 'add' });
        }
        else {
            $(this).parents('.panel').next('.sub').hide();
            $.post("xajax/ajax.setopenstructure.php", { 'mid': $(this).attr('mid') , 'op': 'remove' });
            }
        
    });
    
    <?php if (isset($opencontent) && intval($opencontent)>0) { ?>
    
    $.post("xajax/ajax.showcontent.php", { 'mid': <?php echo intval($opencontent); ?> }).done (function(data) {
        $('#carea-<?php echo intval($opencontent); ?>').html(data).show();
        createSortable();
    });
    
    <?php } ?>
    
    });
    
</script>

<?php require ("./data/include/footer.inc.php"); ?>