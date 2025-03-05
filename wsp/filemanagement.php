<?php
/**
 * @description (media) file management
 * @author stefan@covi.de
 * @since 3.1
 * @version 7.0
 * @lastchange 2021-09-15
 */

$_SESSION['wspvars']['addpagecss'] = array(
    'jstree.css',
    'sweetalert2.css',
    'datatables/dataTables.bootstrap.min.css',
    'datatables/jquery.dataTables.min.css',
    'formstone/upload.css',
    );
$_SESSION['wspvars']['addpagejs'] = array(
    'jstree.js',
    'sweetalert2.js',
    'jquery/jquery.dataTables.min.js',
    'formstone/core.js',
    'formstone/upload.js',
    );

$sitedata = getWSPProperties();
$xtnsicons = array(
    'txt' => 'fas fa-file-alt',
    'pdf' => 'fas fa-file-pdf',
    'doc' => 'fas fa-file-word',
    'docx' => 'far fa-file-word',
    'ppt' => 'fas fa-file-powerpoint',
    'pptx' => 'far fa-file-powerpoint',
    'xls' => 'fas fa-file-excel',
    'xlsx' => 'far fa-file-excel',
    'woff' => 'fa fa-fonticons',
    'woff2' => 'fa fa-fonticons',
    'eot' => 'fa fa-fonticons',
    'ttf' => 'fa fa-fonticons',
    'svg' => 'fas fa-file-code',
    'json' => 'fas fa-file-alt',
    'csv' => 'fas fa-file-csv',
    'eps' => 'fas fa-file-pdf',
    'ai' => 'fas fa-file-pdf',
    'zip' => 'fas fa-file-archive',
    'tgz' => 'fas fa-file-archive',
    'rar' => 'fas fa-file-archive',
    );

// setting up basetarget, if defined for user
if (isset($_SESSION['wspvars']['rights'][$mediafolder."folder"]) && trim($_SESSION['wspvars']['rights'][$mediafolder."folder"])!='' && strlen(trim($_SESSION['wspvars']['rights'][$mediafolder."folder"]))>3) {
    $_SESSION['wspvars']['upload']['basetarget'] = cleanPath(trim("/".$_SESSION['wspvars']['rights'][$mediafolder."folder"]."/"));
}

// if basetarget doesn't exist
if(!(is_dir(cleanPath(DOCUMENT_ROOT."/".$_SESSION['wspvars']['upload']['basetarget'])))) {
    $return = createFolder($_SESSION['wspvars']['upload']['basetarget']);
    if ($return===false) {
        addWSPMsg('errormsg', returnIntLang('media could not create media base directory'));
    }
}

if (isset($_POST['newfldr']) && trim($_POST['newfldr'])!='') {
    $newfolder = urltext($_POST['newfldr']);
    $fldr = cleanPath(base64_decode(trim($_REQUEST['fldr'])).DIRECTORY_SEPARATOR.$newfolder);
    $return = createFolder($fldr);
    if ($return) {
        addWSPMsg('resultmsg', returnIntLang('media subfolder was created 1')." ".$fldr." ".returnIntLang('media subfolder was created 2'));
        cleanupDirList($_SESSION['wspvars']['upload']['basetarget']);
        $_REQUEST['fldr'] = base64_encode(cleanPath(DIRECTORY_SEPARATOR.$fldr.DIRECTORY_SEPARATOR));
    }
    else {
        addWSPMsg('errormsg', returnIntLang('media subfolder could not be created 1')." ".$fldr." ".returnIntLang('media subfolder could not be created 2'));
    }
}

if (isset($_POST['renamefldr']) && trim($_POST['renamefldr'])!='') {
    $fldr = cleanPath(base64_decode(trim($_REQUEST['fldr'])));
    $renamefldr = urltext($_POST['renamefldr']);
    $renamefldrfrom = urltext($_POST['renamefldrfrom']);
    $path = explode(DIRECTORY_SEPARATOR, $fldr);
    foreach ($path AS $pk => $pv) {
        if (trim($pv)==''): unset($path[$pk]); endif;
    }
    $path = array_values($path);
    if ($path[(count($path)-1)]==$renamefldrfrom): $path[(count($path)-1)] = $renamefldr; endif;
    $path = cleanPath(DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, $path).DIRECTORY_SEPARATOR);
    $return = renameFolder($fldr, $path, true);
    if ($return) {
        addWSPMsg('resultmsg', returnIntLang('media subfolder was renamed 1')." ".$path." ".returnIntLang('media subfolder was renamed 2'));
        cleanupDirList($_SESSION['wspvars']['upload']['basetarget']);
        $_REQUEST['fldr'] = base64_encode(cleanPath(DIRECTORY_SEPARATOR.$path.DIRECTORY_SEPARATOR));
    }
    else {
        addWSPMsg('errormsg', returnIntLang('media subfolder could not be renamed 1')." ".$fldr." ".returnIntLang('media subfolder could not be renamed 2'));
    }
}

if (isset($_POST['emptyfldr']) && trim($_POST['emptyfldr'])!='') {
    $fldr = cleanPath(base64_decode(trim($_POST['emptyfldr'])));
    $return = emptyFolder($fldr);
    if ($return) {
        addWSPMsg('resultmsg', returnIntLang('media subfolder was emptied 1')." <strong>".$fldr."</strong> ".returnIntLang('media subfolder was emptied 2'));
        cleanupDirList($_SESSION['wspvars']['upload']['basetarget']);
        $_REQUEST['fldr'] = trim($_POST['emptyfldr']);
        $_POST['action'] = 'reloadlist';
    }
    else {
        addWSPMsg('errormsg', returnIntLang('media subfolder could not be emptied 1')." <strong>".$fldr."</strong> ".returnIntLang('media subfolder could not be emptied 2'));
        $_POST['action'] = 'reloadlist';
    }
}

if (isset($_POST['deletefldr']) && trim($_POST['deletefldr'])!='') {
    $fldr = cleanPath(base64_decode(trim($_POST['deletefldr'])));
    $return = deleteFolder(cleanPath(base64_decode(trim($_POST['deletefldr']))), true);
    if ($return) {
        addWSPMsg('resultmsg', returnIntLang('media subfolder was deleted 1')." ".$fldr." ".returnIntLang('media subfolder was deleted 2'));
        $path = explode(DIRECTORY_SEPARATOR, $fldr);
        foreach ($path AS $pk => $pv) {
            if (trim($pv)=='') { 
                unset($path[$pk]); 
            }
        }
        $path = array_values($path);
        array_pop($path);
        cleanupDirList($mediafolder);
        $_REQUEST['fldr'] = base64_encode(cleanPath(DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, $path).DIRECTORY_SEPARATOR));
    }
    else {
        addWSPMsg('errormsg', returnIntLang('media subfolder could not be deleted 1')." ".$fldr." ".returnIntLang('media subfolder could not be deleted 2'));
    }
}

if (isset($_POST['deletefiles']) && is_array($_POST['deletefiles']) && count($_POST['deletefiles'])>0) {
    // remove file from filesystem
    foreach ($_POST['deletefiles'] AS $df => $dv) {
        $fdata = unserializeBroken(base64_decode($dv));
        if (isset($fdata[1]) && trim($fdata[1])!='' && strpos(trim($fdata[1]), trim($_SESSION['wspvars']['activemedia'][$mediafolder]))===0) {
            $res = deleteFile($fdata[1]);
            if ($res) {
                // remove information about file from database
                doSQL("DELETE FROM `wspmedia` WHERE `filepath` = '".escapeSQL(trim($fdata[1]))."'");
            }
        }
    }
    $_POST['action'] = 'reloadlist';
}

if (isset($_REQUEST['fldr']) && trim($_REQUEST['fldr'])!='') {
	$_SESSION['wspvars']['activemedia'][$mediafolder] = cleanPath(base64_decode(trim($_REQUEST['fldr'])));
}
else if (!(isset($_SESSION['wspvars']['activemedia'][$mediafolder]))) {
	if (isset($_SESSION['wspvars']['rights'][$mediafolder."folder"]) && trim($_SESSION['wspvars']['rights'][$mediafolder."folder"])!='' && strlen(trim($_SESSION['wspvars']['rights'][$mediafolder."folder"]))>3) {
        $_SESSION['wspvars']['activemedia'][$mediafolder] = cleanPath(trim($_SESSION['wspvars']['rights'][$mediafolder."folder"]));
    }
    else {
        $_SESSION['wspvars']['activemedia'][$mediafolder] = cleanPath('/media/'.$mediafolder.'/');
    }
}

// remove file with dirlist so it will be created while building treeview
if (isset($_POST['action']) && trim($_POST['action'])=='reloadlist') {
    cleanupDirList($mediafolder);
}

// check if activemedia is in basetarget
$cactivemedia = explode("/", $_SESSION['wspvars']['activemedia'][$mediafolder]);
$cbasetarget = explode("/", $_SESSION['wspvars']['upload']['basetarget']);
foreach ($cbasetarget AS $cbk => $cbv) { if (trim($cbv)!='' && $cbasetarget[$cbk]!=$cactivemedia[$cbk]) { $_SESSION['wspvars']['activemedia'][$mediafolder] = $_SESSION['wspvars']['upload']['basetarget']; }}
// recheck all activemedia directories for clean directory path 
foreach($_SESSION['wspvars']['activemedia'] AS $amk => $amv) {
    $_SESSION['wspvars']['activemedia'][$amk] = cleanPath($amv);
}
// setup if user is in (his) root directory
$rootdir = ($_SESSION['wspvars']['activemedia'][$mediafolder]==cleanPath('/'.$_SESSION['wspvars']['upload']['basetarget'].'/')?true:false);
// setting up sorting
if (isset($_REQUEST['srt']) && intval($_REQUEST['srt'])==1) {
    $_SESSION['wspvars']['mediasort'][$mediafolder] = 'filesize';
} else if (isset($_REQUEST['srt']) && intval($_REQUEST['srt'])==2) {
    $_SESSION['wspvars']['mediasort'][$mediafolder] = 'filedate';
} else if (!(isset($_SESSION['wspvars']['mediasort'][$mediafolder])) || (isset($_REQUEST['srt']) && intval($_REQUEST['srt'])==0)) {
    $_SESSION['wspvars']['mediasort'][$mediafolder] = 'filename';
}
// setting up sort direction
if (isset($_REQUEST['dir']) && intval($_REQUEST['dir'])==1) {
    $_SESSION['wspvars']['mediasortorder'][$mediafolder] = 'DESC';
} else if (!(isset($_SESSION['wspvars']['mediasortorder'][$mediafolder])) || (isset($_REQUEST['dir']) && intval($_REQUEST['dir'])==0)) {
    $_SESSION['wspvars']['mediasortorder'][$mediafolder] = 'ASC';
}
// setting up display mode
if (isset($_REQUEST['dpl']) && intval($_REQUEST['dpl'])==1) {
    $_SESSION['wspvars']['displaymedia'] = 'tinybox';
}
else if (isset($_REQUEST['dpl']) && intval($_REQUEST['dpl'])==2) {
    $_SESSION['wspvars']['displaymedia'] = 'list';
}
else if (isset($_REQUEST['dpl']) && intval($_REQUEST['dpl'])==0) {
    $_SESSION['wspvars']['displaymedia'] = 'box';
}
// grep single folder name for displaying
$fldrs = explode("/", $_SESSION['wspvars']['activemedia'][$mediafolder]);
foreach ($fldrs AS $fk => $fv): if (trim($fv)!=''): $actfoldername = trim($fv); endif; endforeach;
// create final filedir-path to read
if(is_dir(cleanPath(DOCUMENT_ROOT."/".$_SESSION['wspvars']['activemedia'][$mediafolder]))) {
    $filedir = cleanPath($_SESSION['wspvars']['activemedia'][$mediafolder]);
    $filelist = scanfiles($filedir);
}
else {
    
    $filelist = array();
    addWSPMsg('errormsg', returnIntLang('media requested folder wasnt found'));
}

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
                <h1 class="page-title"><?php echo returnIntLang('media '.$mediafolder.' headline'); ?></h1>
                <p class="page-subtitle"><?php echo returnIntLang('media '.$mediafolder.' info'); ?></p>
            </div>
            <ul class="breadcrumb">
                <li><i class="<?php echo $_SESSION['wspvars']['pagedesc'][0]; ?>"></i> <?php echo $_SESSION['wspvars']['pagedesc'][1]; ?></li>
                <li><?php echo $_SESSION['wspvars']['pagedesc'][2]; ?></li>
            </ul>
        </div>
        <div class="container-fluid">
            <?php
            
//            echo "<pre>POST: ";
//            var_export($_POST);
//            echo "<hr />mediafolder: ";
//            var_export($mediafolder);
//            echo "<hr />isroot: ";
//            var_export($rootdir);
//            echo "<hr />activemedia ".$mediafolder.": ";
//            var_export($_SESSION['wspvars']['activemedia'][$mediafolder]);
//            echo "<hr />basetarget: ";
//            var_export($_SESSION['wspvars']['upload']['basetarget']);
//            echo "<hr/>";
//            echo "</pre>";
            
            ?>
            <?php showWSPMsg(1); ?>
            <div class="row">
                <div class="col-md-6 col-lg-4">
                    <div class="panel">
                        <div class="panel-heading">
                            <h3 class="panel-title"><?php echo returnIntLang('media str folder'); ?> &nbsp; <i class="fas fa-sync-alt" onclick="$('#reloadlist').submit();"></i></h3>
                            <div class="right">
                                <div class="dropdown">
                                    <a href="#" class="toggle-dropdown" data-toggle="dropdown" aria-expanded="false"><i class="fa fa-bars"></i> </a>
                                    <ul class="dropdown-menu dropdown-menu-right">
                                        <li><a id="btn-expand" onclick="$('#treeview').jstree('open_all');"><i class="fa fa-plus-square"></i><?php echo returnIntLang('structure expand structure', true); ?></a></li>
                                        <li><a id="btn-collapse" onclick="$('#treeview').jstree('close_all');"><i class="fa fa-minus-square"></i><?php echo returnIntLang('structure collapse structure', true); ?></a></li>
                                    </ul>
                                </div>
                            </div>
                            <form id="reloadlist" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                                <input type="hidden" name="fldr" value="<?php echo base64_encode($_SESSION['wspvars']['activemedia'][$mediafolder]); ?>" />
                                <input type="hidden" name="action" value="reloadlist" />
                            </form>
                        </div>
                        <div class="panel-body">
                            <div id="treeview"></div>
                        </div>
                        <form name="showfiles_form" id="showfiles_form" method="post">
                            <input type="hidden" name="fldr" id="showfiles_fldr" value="" />
                        </form>
                    </div>
                    <?php 
                    // showing up folder actions if folder exists
                    if (is_dir(DOCUMENT_ROOT."/".$_SESSION['wspvars']['activemedia'][$mediafolder])) { ?>
                    <div class="panel">
                        <div class="panel-heading">
                            <h3 class="panel-title"><?php echo returnIntLang('media str folder actions'); ?></h3>
                        </div>
                        <form name="changefolder_form" id="changefolder_form" method="post">
                            <div class="panel-body">
                                <input type="hidden" name="fldr" value="<?php echo base64_encode($_SESSION['wspvars']['activemedia'][$mediafolder]); ?>" class="form-control" />
                                <p><?php echo returnIntLang('media create subfolder 1')." <strong>"; echo $_SESSION['wspvars']['activemedia'][$mediafolder]; echo "</strong> ".returnIntLang('media create subfolder 2'); ?></p>
                                <div class="form-group">
                                    <input type="text" name="newfldr" value="" class="form-control" />
                                </div>
                                <?php if ($rootdir===false && count(scandir(DOCUMENT_ROOT.$_SESSION['wspvars']['activemedia'][$mediafolder]))<=2): ?>
                                <p><?php echo returnIntLang('media rename folder 1')." <strong>"; echo "”".$actfoldername."”"; echo "</strong> ".returnIntLang('media rename folder 2'); ?></p>
                                <div class="form-group">
                                    <input type="text" name="renamefldr" class="form-control" placeholder="<?php echo prepareTextField($actfoldername); ?>" />
                                    <input type="hidden" name="renamefldrfrom" value="<?php echo prepareTextField($actfoldername); ?>" />
                                </div>
                                <?php endif; ?>
                                <div class="btn-group">
                                    <button class="btn btn-info"><?php echo returnIntLang('media folder doaction', false); ?></button>
                                    <?php if (count(scanfiles($_SESSION['wspvars']['activemedia'][$mediafolder]))>0 || count(scandirs($_SESSION['wspvars']['activemedia'][$mediafolder]))>0): ?>
                                        <input type="hidden" name="emptyfldr" id="emptyfldr_field" value="" />
                                        <a id="emptyfldr" class="btn btn-danger"><?php echo returnIntLang('media empty folder', false); ?></a>
                                        <script>

                                            $('#emptyfldr').on('click', function(e) {
                                                e.preventDefault();
                                                swal(
                                                {
                                                    title: '<?php echo returnIntLang('media really empty all files in folder?', false); ?>',
                                                    text: "<?php echo returnIntLang('media this action cannot be undone', false); ?>",
                                                    type: 'warning',
                                                    showCancelButton: true,
                                                    confirmButtonColor: '#F9354C',
                                                    cancelButtonColor: '#41B314',
                                                    confirmButtonText: '<?php echo returnIntLang('str submit', false); ?>',
                                                    cancelButtonText: '<?php echo returnIntLang('str cancel', false); ?>'
                                                }).then(function()
                                                {
                                                    $('#emptyfldr_field').val('<?php echo base64_encode($_SESSION['wspvars']['activemedia'][$mediafolder]); ?>');
                                                    $('#changefolder_form').submit();
                                                }).catch(swal.noop);
                                            });

                                        </script>
                                    <?php elseif ($rootdir===false): ?>
                                        <input type="hidden" name="deletefldr" id="deletefldr_field" value="" />
                                        <a id="deletefldr" value="<?php echo base64_encode($_SESSION['wspvars']['activemedia'][$mediafolder]); ?>" class="btn btn-danger"><?php echo returnIntLang('media delete folder', false); ?></a>
                                        <script>

                                            $('#deletefldr').on('click', function(e) {
                                                e.preventDefault();
                                                swal(
                                                {
                                                    title: '<?php echo returnIntLang('media really delete folder?', false); ?>',
                                                    text: "<?php echo returnIntLang('media this action cannot be undone', false); ?>",
                                                    type: 'warning',
                                                    showCancelButton: true,
                                                    confirmButtonColor: '#F9354C',
                                                    cancelButtonColor: '#41B314',
                                                    confirmButtonText: '<?php echo returnIntLang('str submit', false); ?>',
                                                    cancelButtonText: '<?php echo returnIntLang('str cancel', false); ?>'
                                                }).then(function()
                                                {
                                                    $('#deletefldr_field').val('<?php echo base64_encode($_SESSION['wspvars']['activemedia'][$mediafolder]); ?>');
                                                    $('#changefolder_form').submit();
                                                }).catch(swal.noop);
                                            });

                                        </script>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </form>
                    </div>
                    <?php } ?>
                </div>
                <div class="col-md-6 col-lg-8">
                    <form action="#" method="POST">
                    <div class="panel">
                        <div class="panel-heading">
                            <h3 class="panel-title"><?php echo returnIntLang('media str files'); ?></h3>
                            <div class="right">
                                <div class="dropdown">
                                    <a href="#" class="toggle-dropdown" data-toggle="dropdown" aria-expanded="false"><i class="fa fa-bars"></i> </a>
                                    <ul class="dropdown-menu dropdown-menu-right">
                                        <li><a href='?srt=0'><i class="fa fa-font"></i><?php echo returnIntLang('media sort mediafiles by name'); ?> <?php if(isset($_SESSION['wspvars']['mediasort'][$mediafolder]) && trim($_SESSION['wspvars']['mediasort'][$mediafolder])=='filename'): echo "<i class='fa fa-check'></i>"; endif; ?></a></li>
					                    <li><a href='?srt=1'><i class="fa fa-hdd-o"></i><?php echo returnIntLang('media sort mediafiles by filesize'); ?> <?php if(isset($_SESSION['wspvars']['mediasort'][$mediafolder]) && trim($_SESSION['wspvars']['mediasort'][$mediafolder])=='filesize'): echo "<i class='fa fa-check'></i>"; endif; ?></a></li>
                                        <li><a href='?srt=2'><i class="fa fa-clock-o"></i><?php echo returnIntLang('media sort mediafiles by date'); ?> <?php if(isset($_SESSION['wspvars']['mediasort'][$mediafolder]) && trim($_SESSION['wspvars']['mediasort'][$mediafolder])=='filedate'): echo "<i class='fa fa-check'></i>"; endif; ?></a></li>
                                        <li class="divider"></li>
                                        <li><a href="?dir=0"><i class="fa fa-sort-alpha-asc"></i><?php echo returnIntLang('media sort mediafiles asc', true); ?> <?php if(isset($_SESSION['wspvars']['mediasortorder'][$mediafolder]) && trim($_SESSION['wspvars']['mediasortorder'][$mediafolder])=='ASC'): echo "<i class='fa fa-check'></i>"; endif; ?></a></li>
                                        <li><a href="?dir=1"><i class="fa fa-sort-alpha-desc"></i><?php echo returnIntLang('media sort mediafiles desc', true); ?> <?php if(isset($_SESSION['wspvars']['mediasortorder'][$mediafolder]) && trim($_SESSION['wspvars']['mediasortorder'][$mediafolder])=='DESC'): echo "<i class='fa fa-check'></i>"; endif; ?></a></li>
                                        <li class="divider"></li>
                                        <li><a href='?dpl=1'><i class="fa fa-th"></i><?php echo returnIntLang('media show mediafiles tinybox'); ?> <?php if(isset($_SESSION['wspvars']['displaymedia']) && trim($_SESSION['wspvars']['displaymedia'])=='tinybox'): echo "<i class='fa fa-check'></i>"; endif; ?></a></li>
                                        <li><a href='?dpl=0'><i class="fa fa-th-large"></i><?php echo returnIntLang('media show mediafiles box'); ?> <?php if(isset($_SESSION['wspvars']['displaymedia']) && trim($_SESSION['wspvars']['displaymedia'])=='box'): echo "<i class='fa fa-check'></i>"; endif; ?></a></li>
					                    <li><a href='?dpl=2'><i class="fa fa-th-list"></i><?php echo returnIntLang('media show mediafiles list'); ?> <?php if(isset($_SESSION['wspvars']['displaymedia']) && trim($_SESSION['wspvars']['displaymedia'])=='list'): echo "<i class='fa fa-check'></i>"; endif; ?></a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="panel-body upload">
                            <div class="inlinemedia">
                            <?php if (count($filelist)==0) { ?>
                                <p><?php echo returnIntLang('media no files in folder'); ?></p>
                            <?php } else if ($_SESSION['wspvars']['displaymedia']=='list') {
                                
                                // list
                                
                                ?>
                                <form method="post" id="deletefiles">
                                <table id="filelist-datatable" class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th><?php echo returnIntLang('str filename'); ?> <?php if(isset($_SESSION['wspvars']['mediasort'][$mediafolder]) && trim($_SESSION['wspvars']['mediasort'][$mediafolder])=='filename'): echo "<i class='fa fa-sort-alpha-".strtolower(trim($_SESSION['wspvars']['mediasortorder'][$mediafolder]))."'></i>"; endif; ?></th>
                                            <th><?php echo returnIntLang('str filesize'); ?> <?php if(isset($_SESSION['wspvars']['mediasort'][$mediafolder]) && trim($_SESSION['wspvars']['mediasort'][$mediafolder])=='filesize'): echo "<i class='fa fa-sort-numeric-".strtolower(trim($_SESSION['wspvars']['mediasortorder'][$mediafolder]))."'></i>"; endif; ?></th>
                                            <th><?php echo returnIntLang('str uploaddate'); ?> <?php if(isset($_SESSION['wspvars']['mediasort'][$mediafolder]) && trim($_SESSION['wspvars']['mediasort'][$mediafolder])=='filedate'): echo "<i class='fa fa-sort-numeric-".strtolower(trim($_SESSION['wspvars']['mediasortorder'][$mediafolder]))."'></i>"; endif; ?></th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php

                                        $showlist = showMediaFiles($_SESSION['wspvars']['activemedia'][$mediafolder], $filelist, $_SESSION['wspvars']['mediasort'][$mediafolder], $_SESSION['wspvars']['mediasortorder'][$mediafolder]);
                                        
                                        foreach ($showlist AS $slk => $slv):
                                            echo "<tr>";
                                            echo "<td class='col-md-7'><span style='display: inline-block; max-width: 100%; word-break: break-word; overflow: hidden;'><a href='./mediadetails.php?fl=".$slv['filehash']."&ml=".base64_encode($mediafolder)."'>".(($slv['filedesc']!='')?"<em title='".$slv['filename']."'>".$slv['filedesc']."</em> [".$slv['filename']."]":$slv['filename'])."</span></td>";
                                            echo "<td class='col-md-2'>";
                                            echo showHumanSize($slv['filesize']);
                                            if (is_array($slv['filedata'])): echo ' - '.implode("x", $slv['filedata']).'px'; endif;
                                            echo "</td>";
                                            echo "<td class='col-md-2'>".date(returnIntLang('format date time', false), $slv['filedate'])."</td>";
                                            echo "<td class='col-md-1 text-right'>";

                                            if (isset($slv['fileusage']) && $slv['fileusage']===true) {
                                                echo " <i class='fa fa-check'></i>";
                                            }
                                            else {
                                                // maybe later it should be possible to drag/drop files to another folder 
                                                // echo " <i class='fa fa-arrows'></i>";
                                                echo " <input type='checkbox' name='deletefiles[]' value='".$slv['filehash']."' />";
                                            }
                                            if (isset($slv['filepreview']) && $slv['filepreview']) {
                                                echo " <i class='fa fa-file-image-o'></i>";
                                            }
                                            echo "</td>";
                                            echo "</tr>";
                                        endforeach;

                                        ?>
                                    </tbody>
                                </table>
                                </form>
                            <?php } else { 
                                
                                // box / minibox
                                
                                $showlist = showMediaFiles($_SESSION['wspvars']['activemedia'][$mediafolder], $filelist, $_SESSION['wspvars']['mediasort'][$mediafolder], $_SESSION['wspvars']['mediasortorder'][$mediafolder]);
                                
                                echo "<div class='row file-box'>";
                                foreach ($showlist AS $slk => $slv) {
                                    echo "<div class='col-img-".(($_SESSION['wspvars']['displaymedia']=='box')?'box':'minibox')." col-md-".(($_SESSION['wspvars']['displaymedia']=='box')?'4':'3')." col-sm-".(($_SESSION['wspvars']['displaymedia']=='box')?'6':'4')."'>";
                                        echo "<div class='panel' style='margin-bottom: 10px;'><div class='panel-body' style='padding: 10px;'>";
                                            if (is_array($slv['filedata'])) {
                                                // if filedata exists => it's an image
                                                echo "<a href='./mediadetails.php?fl=".$slv['filehash']."&ml=".base64_encode($_SESSION['wspvars']['upload']['basetarget'])."'><div class='filepreview' style='background-image: url(".((isset($slv['filethmb']))?$slv['filethmb']:$slv['filepath']).");'></div></a>";
                                            }
                                            else {
                                                echo "<a href='./mediadetails.php?fl=".$slv['filehash']."&ml=".base64_encode($_SESSION['wspvars']['upload']['basetarget'])."'><div class='filepreview file-preview ".$slv['filextns']."'><i class='".((array_key_exists($slv['filextns'], $xtnsicons))?$xtnsicons[$slv['filextns']]:'fas fa-file')."'></i></div></a>";
                                            }
                                            echo "<div class='filename' style='display: inline-block; width: 100%; word-break: break-word; overflow: hidden;'><a href='./mediadetails.php?fl=".$slv['filehash']."&ml=".base64_encode($mediafolder)."'>".$slv['filename']."</a></div>";
                                            if (($_SESSION['wspvars']['displaymedia']=='box')) {
                                                echo "<div class='filesize' style='display: inline-block; width: 100%; word-break: break-word; overflow: hidden;'>";
                                                echo showHumanSize($slv['filesize']);
                                                if (is_array($slv['filedata'])): echo '<br />'.implode("x", $slv['filedata']).'px'; endif;
                                                echo "</div>";
                                                echo "<div class='filedate' style='display: inline-block; width: 100%; word-break: break-word; overflow: hidden;'>".date(returnIntLang('format date time', false), $slv['filedate'])."</div>";
                                            }
                                            /*
                                            echo "<div class='fileaction' style='display: inline-block; width: 100%; word-break: break-word; overflow: hidden;'>";
                                            if (isset($slv['fileusage']) && $slv['fileusage']) {
                                              echo " <i class='fa fa-check'></i>";
                                            }
                                            else {
                                                echo " <i class='fa fa-trash'></i>";
                                            }
                                            if (isset($slv['filepreview']) && $slv['filepreview']) {
                                                echo " <i class='fa fa-file-image-o'></i>";
                                            }
                                            echo "</div>";
                                            */
                                    
                                            // remove file option
                                            echo "<div class='file-remove'>";
                                            if (!(isset($slv['fileusage'])) || $slv['fileusage']===false) {
                                                echo " <input type='checkbox' name='deletefiles[]' value='".$slv['filehash']."' />";
                                            } else {
                                                echo "<i class='fa fa-check'></i>";
                                            }
                                            echo "</div>";
                                        echo "</div></div>";
                                    echo "</div>";
                                }
                                echo "</div>";
                            } ?>
                                <div class="filelists">
                                    <ol class="filelist complete"></ol>
                                    <ol class="filelist queue"></ol>
                                </div>
                            </div>
                        </div>
                    </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
 
    .fs-upload.fs-upload-dropping { outline: 3px dashed grey; }
    .fs-upload.fs-upload-dropping .inlinemedia { opacity: 0.3; }
    .fs-upload-input { border: 4px solid grey; }
    .fs-upload-target { 
        display: inline-block;
        -webkit-border-radius: 3px;
        -moz-border-radius: 3px;
        border-radius: 3px;
        background-color: #5bc0de;
        border-color: #4ebbdb;
        border-width: 1px;
        border-style: solid;
        color: #fff;
        padding: 6px 22px;
        font-size: 14px;
        font-weight: normal;
        line-height: 1.42857143;
        }
    

    .filelists {
/*        margin: 20px 0; */
        margin: 0px;
    }

    .filelists h5 {
        margin: 10px 0 0;
    }

    .filelists .cancel_all {
        color: red;
        cursor: pointer;
        clear: both;
        font-size: 10px;
        margin: 0;
        text-transform: uppercase;
    }

    .filelist {
        margin: 0;
        padding: 10px 0;
    }
    
    .filelist li {
        background: #fff;
        border-bottom: 1px solid #ECEFF1;
        font-size: 14px;
        list-style: none;
        padding: 5px;
        position: relative;
    }
    
    .filelist li:before {
        display: none !important;
    }
    
    .filelist li .bar {
        background: #eceff1;
        content: '';
        height: 100%;
        left: 0;
        position: absolute;
        top: 0;
        width: 0;
        z-index: 0;
        -webkit-transition: width 0.1s linear;
        transition: width 0.1s linear;
    }

    .filelist li .content {
        display: block;
        overflow: hidden;
        position: relative;
        z-index: 1;
    }

    .filelist li .file {
        color: #455A64;
        float: left;
        display: block;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 50%;
        white-space: nowrap;
    }

    .filelist li .progress {
        color: #B0BEC5;
        display: block;
        float: right;
        font-size: 10px;
        text-transform: uppercase;
    }

    .filelist li .cancel {
        color: red;
        cursor: pointer;
        display: block;
        float: right;
        font-size: 10px;
        margin: 0 0 0 10px;
        text-transform: uppercase;
    }

    .filelist li.error .file {
        color: red;
    }

    .filelist li.error .progress {
        color: red;
    }

    .filelist li.error .cancel {
        display: none;
    }

    .filelist.complete li {
        background: #dff0d8;
    }
    
</style>
<script>
    
    function onCancel(e) {
        console.log("Cancel");
        var index = $(this).parents("li").data("index");
        $(this).parents("form").find(".upload").upload("abort", parseInt(index, 10));
    }

    function onCancelAll(e) {
        console.log("Cancel All");
        $(this).parents("form").find(".upload").upload("abort");
    }
    
    function onBeforeSend(formData, file) {
        console.log("Before Send");
        formData.append("fldr", "<?php echo base64_encode($_SESSION['wspvars']['activemedia'][$mediafolder]); ?>");
        // return (file.name.indexOf(".jpg") < -1) ? false : formData; // cancel all jpgs
        return formData;
    }

    function onQueued(e, files) {
        console.log("Queued");
        var html = '';
        for (var i = 0; i < files.length; i++) {
            html += '<li data-index="' + files[i].index + '"><span class="content"><span class="file">' + files[i].name + '</span><span class="cancel">Cancel</span><span class="progress">Queued</span></span><span class="bar"></span></li>';
        }
        $(this).parents("form").find(".filelist.queue").append(html);
    }

    function onStart(e, files) {
        $(this).parents("form").find(".filelist").css('margin','10px 0px');
        $(this).parents("form").find(".filelist.queue").show().find("li").find(".progress").text("Waiting");
    }

    function onComplete(e) {
        // All done!
        // alert("<?php echo returnIntLang('media upload queue done', false); ?>");
    }

    function onFileStart(e, file) {
        console.log("File Start");
        $(this).parents("form").find(".filelist.queue")
            .find("li[data-index=" + file.index + "]")
            .find(".progress").text("0%");
    }

    function onFileProgress(e, file, percent) {
        console.log("File Progress");
        var $file = $(this).parents("form").find(".filelist.queue").find("li[data-index=" + file.index + "]");
        
        $file.find(".progress").text(percent + "%")
        $file.find(".bar").css("width", percent + "%");
    }

    function onFileComplete(e, file, response) {
        console.log("File Complete");
        if (response.trim() === "" || response.toLowerCase().indexOf("error") > -1) {
            $(this).parents("form").find(".filelist.queue")
                .find("li[data-index=" + file.index + "]").addClass("error")
                .find(".progress").text(response.trim());
        } else {
            var $target = $(this).parents("form").find(".filelist.queue").find("li[data-index=" + file.index + "]");
            $target.find(".file").text(file.name);
            $target.find(".progress").remove();
            $target.find(".cancel").remove();
            $target.appendTo($(this).parents("form").find(".filelist.complete"));
        }
    }

    function onFileError(e, file, error) {
        console.log("File Error");
        $(this).parents("form").find(".filelist.queue")
            .find("li[data-index=" + file.index + "]").addClass("error")
            .find(".progress").text("Error: " + error);
    }
    
    function onChunkStart(e, file) {
        console.log("Chunk Start");
    }
    
    function onChunkProgress(e, file, percent) {
        console.log("Chunk Progress");
    }
    
    function onChunkComplete(e, file, response) {
        console.log("Chunk Complete");
    }

    function onChunkError(e, file, error) {
        console.log("Chunk Error");
    }
        
    function showFiles(folderHash){
        $('#showfiles_fldr').val(folderHash);
        $('#showfiles_form').submit();
    }  

</script>
<script>
    
    $(function() {
    
        $(".upload").upload({
            action: "./xajax/ajax.mediaupload.php",
            beforeSend: onBeforeSend,
            label: '<?php echo returnIntLang('media btn drop files on area or click here to select', false); ?>',
        })
            .on("start.upload", onStart)
            .on("complete.upload", onComplete)
            .on("filestart.upload", onFileStart)
            .on("fileprogress.upload", onFileProgress)
            .on("filecomplete.upload", onFileComplete)
            .on("fileerror.upload", onFileError)
            .on("chunkstart.upload", onChunkStart)
            .on("chunkprogress.upload", onChunkProgress)
            .on("chunkcomplete.upload", onChunkComplete)
            .on("chunkerror.upload", onChunkError)
            .on("queued.upload", onQueued);
    
        $(".filelist.queue").on("click", ".cancel", onCancel);
        $(".cancel_all").on("click", onCancelAll);
        
        <?php 
        
        // show delete-files-button if some files exist
        if (isset($showlist) && count($showlist)>0) { ?>
        $(".upload").append(' &nbsp; <input type="submit" style="margin-top: -3px;" class="btn btn-danger" value="<?php echo returnIntLang('media btn remove selected files', false); ?>" />');
        <?php } ?>
    });

</script>
<script>

    $(document).ready(function(){
        
        showDTpaging = <?php echo ((isset($showlist) && count($showlist)>10)?'true':'false'); ?>;
        
        // datatable with paging options and live search
        $('#filelist-datatable').dataTable({
            ordering:  false,
            sDom: "<'row'<'col-sm-6'l><'col-sm-6'f>r>t<'row'<'col-sm-6'i><'col-sm-6'p>>",
            language: {
                "decimal":        "<?php echo returnIntLang('datatable decimal', false); ?>",
                "emptyTable":     "<?php echo returnIntLang('datatable emptyTable', false); ?>",
                "info":           "<?php echo returnIntLang('datatable info', false); ?>",
                "infoEmpty":      "<?php echo returnIntLang('datatable infoEmpty', false); ?>",
                "infoFiltered":   "<?php echo returnIntLang('datatable infoFiltered', false); ?>",
                "infoPostFix":    "<?php echo returnIntLang('datatable infoPostFix', false); ?>",
                "thousands":      "<?php echo returnIntLang('datatable thousands', false); ?>",
                "lengthMenu":     "<?php echo returnIntLang('datatable lengthMenu', false); ?>",
                "loadingRecords": "<?php echo returnIntLang('datatable loadingRecords', false); ?>",
                "processing":     "<?php echo returnIntLang('datatable processing', false); ?>",
                "search":         "<?php echo returnIntLang('datatable search', false); ?>",
                "zeroRecords":    "<?php echo returnIntLang('datatable zeroRecords', false); ?>",
                "paginate": {
                    "first":      "<?php echo returnIntLang('datatable paginate first', false); ?>",
                    "last":       "<?php echo returnIntLang('datatable paginate last', false); ?>",
                    "next":       "<?php echo returnIntLang('datatable paginate next', false); ?>",
                    "previous":   "<?php echo returnIntLang('datatable paginate previous', false); ?>"
                },
                "aria": {
                    "sortAscending":  "<?php echo returnIntLang('datatable aria sortAscending', false); ?>",
                    "sortDescending": "<?php echo returnIntLang('datatable aria sortAscending', false); ?>"
                }
            },
            "paging": showDTpaging,
            "searching": false
        });
        
        $('#treeview').jstree({
            'core': {
                "themes" : { "stripes" : true },
                'data': { 
                    'url': './xajax/ajax.returndirlist.php?path=<?php echo $_SESSION['wspvars']['upload']['basetarget']; ?>',
//                    'data' : function (node) {
//                        return { 'id' : node.id };
//                    }
                },
                'check_callback': true,
            },
            'plugins': ['types', 'wholerow'],
            'types': {
                'root': { 'icon': 'fa fa-desktop text-primary' },
                'default': { 'icon': 'fa fa-folder' }
            }
        }).on('loaded.jstree', function (e, data) {
            <?php 

            if (isset($_POST['fldr'])) {
                if ($rootdir) {
                    echo "var fldr = 'root'; // post\n";
                } else {
                    echo "var fldr = '".substr(urltext(cleanPath(base64_decode($_POST['fldr']))),1,-1)."';\n";
                }
            } elseif (isset($_SESSION['wspvars']['activemedia'][($mediafolder)])) {
                if ($rootdir) {
                    echo "var fldr = 'root'; // session\n";
                } else {
                    echo "var fldr = '".substr(urltext(cleanPath($_SESSION['wspvars']['activemedia'][$mediafolder])),1,-1)."';\n";
                }
            } else {
                echo "var fldr = 'root';\n // base";
            }

            ?>
            $('#treeview').jstree('select_node', fldr);
        });
        
    });
    
    
</script>        
<?php include ("./data/include/footer.inc.php"); ?>