<div class="panel">
    <div class="panel-heading">
        <h3 class="panel-title"><?php echo returnIntLang('contentedit content info'); ?></h3>
        <?php panelOpener(true, array(), false, 'contentmeta'); ?>
    </div>
    <div class="panel-body">
        <div class="row">
            <div class="col-md-6 input-label"><?php echo returnIntLang('contentedit content desc'); ?></div>
            <div class="col-md-6"><input type="text" name="description" placeholder="<?php echo prepareTextField(returnIntLang('contentedit content desc')); ?>" id="meta_description" class="form-control" value="<?php echo prepareTextField($description); ?>" /></div>
        </div>
        <div class="row">
            <div class="col-md-6 input-label"><?php echo returnIntLang('contentedit content last change'); ?></div>
            <div class="col-md-6 input-label">
                <p><?php echo date(returnIntLang('format date time'), $contentinfo_res['set'][0]['lastchange']); ?></p>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 input-label"><?php echo returnIntLang('contentedit content last autosave'); ?></div>
            <div class="col-md-6 input-label" id="<?php echo $contentinfo_res['set'][0]['cid']; ?>-autosave">
                <p><?php echo date(returnIntLang('format date time'), $contentinfo_res['set'][0]['lastchange']); ?></p>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 input-label"><?php echo returnIntLang('contentedit content last publish'); ?></div>
            <div class="col-md-6 input-label"><?php echo (($contentinfo_res['set'][0]['lastpublish']>0 && $contentinfo_res['set'][0]['lastpublish']>$contentinfo_res['set'][0]['lastchange'])?date(returnIntLang('format date time'), $contentinfo_res['set'][0]['lastpublish']):returnIntLang('str never published')); ?></div>
        </div>
    </div>
</div>