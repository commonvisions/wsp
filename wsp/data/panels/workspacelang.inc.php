<?php

// block to define workspace language
if ((array_key_exists('workspacelang', $_SESSION['wspvars']) && $_SESSION['wspvars']['workspacelang']=="") || (!(array_key_exists('workspacelang', $_SESSION['wspvars'])))) {
    $_SESSION['wspvars']['workspacelang'] = $_SESSION['wspvars']['sitelanguages']['shortcut'][0];
}
if (isset($_REQUEST['wsl']) && trim($_REQUEST['wsl'])!="") {
    $_SESSION['wspvars']['workspacelang'] = trim($_REQUEST['wsl']);
}
if (intval(count($_SESSION['wspvars']['sitelanguages']['shortcut']))>1) {
    ?>
    <div class="right">
        <div class="dropdown">
            <a href="#" class="toggle-dropdown" data-toggle="dropdown" aria-expanded="false"><i class="fa fa-globe"></i> <?php echo strtoupper($_SESSION['wspvars']['workspacelang']); ?> </a>
            <ul class="dropdown-menu dropdown-menu-right">
                <?php

                foreach ($_SESSION['wspvars']['sitelanguages']['shortcut'] AS $key => $value) {
                    echo "<li><a href='?wsl=".$_SESSION['wspvars']['sitelanguages']['shortcut'][$key]."'>";
                    echo "<i class=\"fa ";
                    echo ($_SESSION['wspvars']['workspacelang']==$_SESSION['wspvars']['sitelanguages']['shortcut'][$key]) ? 'fa-check-circle' : 'fa-globe';
                    echo "\"></i>".$_SESSION['wspvars']['sitelanguages']['longname'][$key]."</a></li>";
                }

                ?>
            </ul>
        </div>
    </div>
<?php } 