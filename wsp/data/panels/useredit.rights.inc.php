<div class="panel">
    <div class="panel-heading">
        <h3 class="panel-title"><?php echo returnIntLang('str rights'); ?></h3>
        <p class="panel-subtitle"><?php echo returnIntLang('rights description'); ?></p>
    </div>
    <div class="panel-body">
        <div class="row">
            <div class="col-md-3">
                <?php echo returnIntLang('usermanagement rights siteprops'); ?>
            </div>
            <div class="col-md-9">
                <div class="form-group">
                    <select name="changerights[siteprops]" class="form-control singleselect fullwidth" >
                        <option value="0"><?php echo returnIntLang('usermanagement rights no access'); ?></option>
                        <option value="1" <?php echo (isset($rights['siteprops']) && intval($rights['siteprops'])==1)?' selected="selected" ':'' ?>><?php echo returnIntLang('usermanagement rights full access'); ?></option>
                    </select>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-3">
                <?php echo returnIntLang('usermanagement rights design'); ?>
            </div>
            <div class="col-md-9">
                <div class="form-group">
                    <select name="changerights[design]" class="form-control singleselect fullwidth" >
                        <option value="0"><?php echo returnIntLang('usermanagement rights no access'); ?></option>
                        <option value="1" <?php echo (isset($rights['design']) && intval($rights['design'])==1)?' selected="selected" ':'' ?>><?php echo returnIntLang('usermanagement rights full access'); ?></option>
                    </select>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-3">
                <?php echo returnIntLang('usermanagement rights sitestructure'); ?>
            </div>
            <div class="col-md-9">
                <div class="form-group">
                    <select name="changerights[sitestructure]" id="changerights_sitestructure" class="form-control singleselect fullwidth" onchange="checkSelect('sitestructure', this.value)">
                        <option value="0"><?php echo returnIntLang('usermanagement rights no access'); ?></option>
                        <option value="1" <?php echo (isset($rights['sitestructure']) && intval($rights['sitestructure'])==1)?' selected="selected" ':'' ?>><?php echo returnIntLang('usermanagement rights full access'); ?></option>
                        <option value="7" <?php echo (isset($rights['sitestructure']) && intval($rights['sitestructure'])==7)?' selected="selected" ':'' ?>><?php echo returnIntLang('usermanagement rights selected access'); ?></option>
                        <option value="3" <?php echo (isset($rights['sitestructure']) && intval($rights['sitestructure'])==3)?' selected="selected" ':'' ?>><?php echo returnIntLang('usermanagement rights full edit access'); ?></option>
                        <option value="4" <?php echo (isset($rights['sitestructure']) && intval($rights['sitestructure'])==4)?' selected="selected" ':'' ?>><?php echo returnIntLang('usermanagement rights selected edit access'); ?></option>
                    </select>
                </div>
            </div>
        </div>

        <div class="row" id="selectsitestructure" <?php echo (!(isset($rights['sitestructure'])) || (isset($rights['sitestructure']) && (intval($rights['sitestructure'])<4 || intval($rights['sitestructure'])>10)))?' style="display: none;" ':'' ?>>
            <div class="col-md-3">
                <?php echo returnIntLang('usermanagement rights sitestructure selection'); ?>
            </div>
            <div class="col-md-9">
                <div class="form-group">
                    <select name="changeidrights[sitestructure][]" id="changeidrights_sitestructure" multiple="multiple" class="form-control fullselect fullwidth" >
                        <?php echo returnStructureItem('menu', 0, true, 9999, (isset($saved_idrights['sitestructure'])?$saved_idrights['sitestructure']:array()), 'option'); ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-3">
                <?php echo returnIntLang('usermanagement rights contents'); ?>
            </div>
            <div class="col-md-9">
                <div class="form-group">
                    <select name="changerights[contents]" id="changerights_contents" class="form-control singleselect  fullwidth" onchange="checkSelect('contents', this.value)" >
                        <option value="0"><?php echo returnIntLang('usermanagement rights no access'); ?></option>
                        <option value="1" <?php echo (isset($rights['contents']) && intval($rights['contents'])==1)?' selected="selected" ':'' ?>><?php echo returnIntLang('usermanagement rights full access'); ?></option>
                        <option value="7" <?php echo (isset($rights['contents']) && intval($rights['contents'])==7)?' selected="selected" ':'' ?>><?php echo returnIntLang('usermanagement rights selected access'); ?></option>
                        <option value="3" <?php echo (isset($rights['contents']) && intval($rights['contents'])==3)?' selected="selected" ':'' ?>><?php echo returnIntLang('usermanagement rights full edit access'); ?></option>
                        <option value="4" <?php echo (isset($rights['contents']) && intval($rights['contents'])==4)?' selected="selected" ':'' ?>><?php echo returnIntLang('usermanagement rights selected edit access'); ?></option>
                        <option value="15" <?php echo (isset($rights['contents']) && intval($rights['contents'])==15)?' selected="selected" ':'' ?>><?php echo returnIntLang('usermanagement rights use sitestructure prefs'); ?></option>
                    </select>
                </div>
            </div>
        </div>

        <div class="row" id="selectcontents" <?php echo (!(isset($rights['contents'])) || (isset($rights['contents']) && (intval($rights['contents'])<4 || intval($rights['contents'])>10)))?' style="display: none;" ':'' ?>>
            <div class="col-md-3">
                <?php echo returnIntLang('usermanagement rights contents selection'); ?>
            </div>
            <div class="col-md-9">
                <div class="form-group">
                    <select name="changeidrights[contents][]" id="changeidrights_contents" multiple="multiple" class="form-control fullselect fullwidth" >
                        <?php echo returnStructureItem('menu', 0, true, 9999, (isset($saved_idrights['contents'])?$saved_idrights['contents']:array()), 'option'); ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-3">
                <?php echo returnIntLang('usermanagement rights publisher'); ?>
            </div>
            <div class="col-md-9">
                <div class="form-group">
                    <select name="changerights[publisher]" id="changerights_publisher" onchange="checkSelect('publisher', this.value)" class="form-control singleselect fullwidth" >
                        <option value="0"><?php echo returnIntLang('usermanagement rights no access'); ?></option>
                        <option value="1" <?php echo (isset($rights['publisher']) && intval($rights['publisher'])==1)?' selected="selected" ':'' ?>><?php echo returnIntLang('usermanagement rights full access'); ?></option>
                        <option value="4" <?php echo (isset($rights['publisher']) && intval($rights['publisher'])==4)?' selected="selected" ':'' ?>><?php echo returnIntLang('usermanagement rights selected access'); ?></option>
                        <option value="15" <?php echo (isset($rights['publisher']) && intval($rights['publisher'])==15)?' selected="selected" ':'' ?>><?php echo returnIntLang('usermanagement rights use sitestructure prefs'); ?></option>
                    </select>
                </div>
            </div>
        </div>

        <div class="row" id="selectpublisher" <?php echo (!(isset($rights['publisher'])) || (isset($rights['publisher']) && (intval($rights['publisher'])<4 || intval($rights['publisher'])>10)))?' style="display: none;" ':'' ?>>
            <div class="col-md-3">
                <?php echo returnIntLang('usermanagement rights publisher selection'); ?>
            </div>
            <div class="col-md-9">
                <div class="form-group">
                    <select name="changeidrights[publisher][]" id="changeidrights_publisher" multiple="multiple" class="form-control fullselect fullwidth" >
                        <?php echo returnStructureItem('menu', 0, true, 9999, (isset($saved_idrights['publisher'])?$saved_idrights['publisher']:array()), 'option'); ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12"><p><?php echo returnIntLang('rights selectrightsdescription'); ?></p></div>
        </div>

        <div class="row">
            <div class="col-md-3">
                <?php echo returnIntLang('usermanagement rights imagesfolder'); ?>
            </div>
            <div class="col-md-9">
                <div class="form-group">
                   <select name="changerights[imagesfolder]" id="changerights_imagesfolder" class="form-control searchselect fullwidth">
                        <option value=""><?php echo returnIntLang('usermanagement rights no access'); ?></option>
                        <option value"/media/images/" <?php echo ((isset($rights['imagesfolder']) && $rights['imagesfolder']=='/media/images/')?' selected="selected" ':''); ?>>/media/images/</option>
                        <?php

                        $dirlist = dirList("/media/images/","/media/images/",true,false);
                        if ($dirlist!==false):
                            foreach ($dirlist AS $dk => $dv):
                                echo "<option value='/".$dv."/' ".((isset($rights['imagesfolder']) && $rights['imagesfolder']=='/'.$dv.'/')?' selected="selected" ':'').">/".$dv."/</option>\n";
                            endforeach;
                        endif;    

                        ?>
                    </select>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-3">
                <?php echo returnIntLang('usermanagement rights downloadfolder'); ?>
            </div>
            <div class="col-md-9">
                <div class="form-group">
                    <select name="changerights[downloadfolder]" id="changerights_downloadfolder" class="form-control searchselect fullwidth">
                        <option value=""><?php echo returnIntLang('usermanagement rights no access'); ?></option>
                        <option value"/media/download/" <?php echo ((isset($rights['downloadfolder']) && $rights['downloadfolder']=='/media/download/')?' selected="selected" ':''); ?>>/media/download/</option>
                        <?php

                        $dirlist = dirList("/media/download/","/media/download/",true,false);
                        if ($dirlist!==false):
                            foreach ($dirlist AS $dk => $dv):
                                echo "<option value='/".$dv."/' ".((isset($rights['downloadfolder']) && $rights['downloadfolder']=='/'.$dv.'/')?' selected="selected" ':'').">/".$dv."/</option>\n";
                            endforeach;
                        endif;    

                        ?>
                    </select>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-3">
                <?php echo returnIntLang('usermanagement rights mediafolder'); ?>
            </div>
            <div class="col-md-9">
                <div class="form-group">
                    <select name="changerights[mediafolder]" id="changerights_mediafolder" class="form-control searchselect fullwidth">
                        <option value=""><?php echo returnIntLang('usermanagement rights no access'); ?></option>
                        <option value"/media/video/" <?php echo ((isset($rights['mediafolder']) && $rights['mediafolder']=='/media/video/')?' selected="selected" ':''); ?>>/media/video/</option>
                        <?php

                        $dirlist = dirList("/media/video/","/media/video/",true,false);
                        if ($dirlist!==false):
                            foreach ($dirlist AS $dk => $dv):
                                echo "<option value='/".$dv."/' ".((isset($rights['mediafolder']) && $rights['mediafolder']=='/'.$dv.'/')?' selected="selected" ':'').">/".$dv."/</option>\n";
                            endforeach;
                        endif;    

                        ?>
                    </select>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12"><p><?php echo returnIntLang('rights filesystemdesc'); ?></p></div>
        </div>
    </div>
</div>
<script>

function checkSelect(selectType, selectVal) {
    if (selectVal>3 && selectVal<10) {
        $('#select' + selectType).show();
    } else {
        $('#select' + selectType).hide();
    }
}
    
$(document).ready(function() { 
    
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
    
});
    
</script>