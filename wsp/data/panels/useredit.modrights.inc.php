<div class="col-md-12">
    <div class="panel">
        <div class="panel-heading">
            <h3 class="panel-title"><?php echo returnIntLang('rights modrights'); ?></h3>
        </div>
        <div class="panel-body">
            <?php 
            
            $wspmod = array();
            foreach ($modrights_res['set'] AS $mrsk => $mrsv):
                $wspmod_sql = "SELECT * FROM `wspmenu` WHERE `guid` = '".escapeSQL($mrsv['guid'])."'";
                $wspmod_res = doSQL($wspmod_sql);
                if ($wspmod_res['num']>0):
                    $wspmod['guid'][$mrsv['guid']] = $mrsv['guid'];
                    $wspmod['self_id'][$mrsv['guid']] = $wspmod_res['set'][0]['id'];
                    $wspmod['parent_id'][$mrsv['guid']] = $wspmod_res['set'][0]['parent_id'];
                    $wspmod['position'][$mrsv['guid']] = $wspmod_res['set'][0]['position'];
                    $wspmod['right'][$mrsv['guid']] = $mrsv['right'];
                    if (strlen($mrsv['labels']) > 0):
                        $wspmod['labels'][$mrsv['guid']] = unserializeBroken($mrsv['labels']);
                    else:
                        $wspmod['labels'][$mrsv['guid']] = unserializeBroken($mrsv['possibilities']);
                    endif;
                    $wspmod['possibilities'][$mrsv['guid']] = $mrsv['possibilities'];
                    $wspmod['standard'][$mrsv['guid']] = $mrsv['standard'];
                endif;    
                
                $pluginmod_sql = "SELECT * FROM `wspplugins` WHERE `guid` = '".escapeSQL($mrsv['guid'])."'";
                $pluginmod_res = doSQL($pluginmod_sql);
                if ($pluginmod_res['num']>0):
                    $wspmod['guid'][$mrsv['guid']] = $mrsv['guid'];
                    $wspmod['self_id'][$mrsv['guid']] = $pluginmod_res['set'][0]['id'];
                    $wspmod['parent_id'][$mrsv['guid']] = 0;
                    $wspmod['position'][$mrsv['guid']] = 0;
                    $wspmod['right'][$mrsv['guid']] = $mrsv['right']." [Plugin]";
                    if (strlen($mrsv['labels']) > 0):
                        $wspmod['labels'][$mrsv['guid']] = unserializeBroken($mrsv['labels']);
                    else:
                        $wspmod['labels'][$mrsv['guid']] = unserializeBroken($mrsv['possibilities']);
                    endif;
                    $wspmod['possibilities'][$mrsv['guid']] = $mrsv['possibilities'];
                    $wspmod['standard'][$mrsv['guid']] = $mrsv['standard'];
                endif;
                
            endforeach;
            
            if (is_array($wspmod['parent_id'])): asort($wspmod['parent_id']); endif;
		  
            foreach ($wspmod['parent_id'] AS $key => $value): if ($value==0):
            ?>
            <div class="row ">
                <div class="col-md-3">
                    <?php echo $wspmod['right'][$key]; ?>
                </div>
                <div class="col-md-9"><?php
				
				foreach (unserializeBroken($wspmod['possibilities'][$key]) as $poskey => $posvalue):
					if (key_exists('rights', $wspmod)):
						$restrictions = unserializeBroken($wspmod['rights'][$key]);
					else:
						$restrictions = array();
					endif;
					$checked = "";
					if (isset($modrights[$key]) && ($poskey == $modrights[$key])):
						$checked = 'checked="checked" ';
					elseif ((!isset($modrights[$key])) && ($poskey == $wspmod['standard'][$key])):
						$checked = 'checked="checked" ';
					endif;
					?>
					<input name="<?php echo $key; ?>" id="<?php echo $key.'_'.$posvalue; ?>" <?php echo $checked; ?>type="radio" value="<?php echo $posvalue; ?>" onchange="checkSub('<?php echo $wspmod['self_id'][$key]; ?>','<?php echo $posvalue; ?>')" />&nbsp;&nbsp;&nbsp;<label for="<?php echo $key.'_'.$posvalue; ?>"><?php echo $wspmod['labels'][$key][$poskey]; ?></label>&nbsp;&nbsp;&nbsp;
					<?php
				
				endforeach; 
                
                foreach ($wspmod['parent_id'] AS $subkey => $subvalue):
					if ($wspmod['self_id'][$key]==$subvalue):
						?>
                </div></div>
                <div class="row">
                    <div class="col-md-3">
                         &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <?php echo $wspmod['right'][$subkey]; ?>
                    </div>
					<div class="col-md-9"><?php
						
						foreach (unserialize($wspmod['possibilities'][$subkey]) as $poskey => $posvalue):
							if (key_exists('rights', $wspmod)):
								$restrictions = unserializeBroken($wspmod['rights'][$subkey]);
							else:
								$restrictions = array();
							endif;
							$checked = "";
							if (isset($modrights[$subkey]) && ($poskey == $modrights[$subkey])):
								$checked = 'checked="checked" ';
							elseif ((!isset($modrights[$subkey])) && ($poskey == $wspmod['standard'][$subkey])):
								$checked = 'checked="checked" ';
							endif;
							?>
							<input name="<?php echo $subkey; ?>" id="<?php echo $subkey.'_'.$posvalue; ?>" <?php echo $checked; ?>type="radio" value="<?php echo $posvalue; ?>" onchange="checkSub(<?php echo $subvalue; ?>, 2)" />&nbsp;&nbsp;&nbsp;<label for="<?php echo $subkey.'_'.$posvalue; ?>"><?php echo $wspmod['labels'][$subkey][$poskey]; ?></label>&nbsp;&nbsp;&nbsp;
							<?php
						
						endforeach; 
						
					endif;
				endforeach;
                    
                ?></div>
            </div>
            <hr class="inner-separator">
            <?php endif; endforeach; ?>
        </div>
    </div>
</div>
<script language="JavaScript" type="text/javascript">
<!--

function checkSub(id, stat) {
<?php
foreach ($wspmod['parent_id'] AS $key => $value):
    if ($value==0):
        $elements = array();
        foreach ($wspmod['parent_id'] AS $subkey => $subvalue):
            if ($wspmod['self_id'][$key]==$subvalue):
                $elements[] = $subkey;
            endif;
        endforeach;
        if (count($elements)>0):
            echo "if (id==".$wspmod['self_id'][$key].") {\n";
            echo "if (stat==0) {\n";
            foreach ($elements AS $ekey => $evalue):
                echo "document.getElementById('".$evalue."_0').checked = true;\n";
            endforeach;
            echo "}\n";
            echo "else if (stat==1) {\n";
            foreach ($elements AS $ekey => $evalue):
                echo "document.getElementById('".$evalue."_1').checked = true;\n";
            endforeach;
            echo "}\n";
            echo "else if (stat==2) {\n";
            echo "if (!(document.getElementById('".implode("_1').checked) && !(document.getElementById('", $elements)."_1').checked)) {\n";
            echo "document.getElementById('".$key."_0').checked = true;\n";
            echo "}\n";
            echo "else {\n";
            echo "document.getElementById('".$key."_1').checked = true;\n";
            echo "}\n";
            echo "}\n";
            echo "}\n";
        endif;
    endif;
endforeach;
?>
    }

// -->
</script>