<div class="panel">
    <div class="panel-heading">
        <h3 class="panel-title"><?php echo returnIntLang('globalcontent createnew for lang 1', true); ?> "<?php echo (isset($_SESSION['wspvars']['sitelanguages']['longname'][(@array_keys($_SESSION['wspvars']['sitelanguages']['shortcut'], $_SESSION['wspvars']['workspacelang'])[0])]))?($_SESSION['wspvars']['sitelanguages']['longname'][(@array_keys($_SESSION['wspvars']['sitelanguages']['shortcut'], $_SESSION['wspvars']['workspacelang'])[0])]):$_SESSION['wspvars']['workspacelang']; ?>" <?php echo returnIntLang('globalcontent createnew for lang 2', true); ?></h3>
    </div>
    <div class="panel-body">
        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data" id="formnewglobalcontent">
            <div class="row">
                <div class="col-md-12">
                    <input name="op" id="op" type="hidden" value="create" />
                    <input name="lang" type="hidden" value="<?php echo $_SESSION['wspvars']['workspacelang']; ?>" />
                    <select name="sid" id="sid" size="1" class="form-control searchselect width-50">
                        <option value="genericwysiwyg"><?php echo returnIntLang('hint generic wysiwyg', false); ?></option>
                        <?php
                        $interpreter_sql = "SELECT `guid`, `name`, `classname` FROM `interpreter` ORDER BY `classname`, `name`";
                        $interpreter_res = doSQL($interpreter_sql);
                        if ($interpreter_res['num'] > 0) {
                            $classname = "";
                            foreach ($interpreter_res['set'] AS $iprsk => $iprsv) {
                                if (trim($iprsv['classname']) != $classname):
                                    if ($irs > 0):
                                        echo "</optgroup>";
                                    endif;
                                    echo "<optgroup label=\"".trim($iprsv['classname'])."\">";
                                    $classname = trim($iprsv['classname']);
                                endif;
                                echo "<option value=\"" .trim($iprsv['guid']). "\">" .trim($iprsv['name']). "</option>\n";
                            }
                        }
                        ?>
                    </select> <a href="#" onclick="if (document.getElementById('sid').value == 0) { alert(unescape('<?php echo returnIntLang('hint choose interpreter', false); ?>')); } else { document.getElementById('formnewglobalcontent').submit(); } return false;" class="btn btn-primary"><?php echo returnIntLang('str create', true); ?></a>
                </div>
            </div>
        </form>
    </div>
</div>
<script>

$(document).ready(function() { 

    $('.searchselect').multiselect({
        maxHeight: 300,
        enableFiltering: true,
    });

});

</script>