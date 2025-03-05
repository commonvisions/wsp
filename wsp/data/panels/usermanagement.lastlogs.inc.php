<div class="panel">
    <div class="panel-heading">
        <h3 class="panel-title"><?php echo returnIntLang('usermanagement manageexisting'); ?></h3>
    </div>
    <div class="panel-body">
        <p><?php echo returnIntLang('loginstat servertime'); ?> <?php echo date("Y-m-d H:i:s"); ?></p>
		<form method="post" name="setfreeform" id="setfreeform">
			<table class="table" id="logstat"></table>
			<input name="op" type="hidden" value="setfree" />
		</form>
		<p><input type="button" onclick="document.getElementById('setfreeform').submit();" value="<?php echo returnIntLang('loginstat logoff marked button', false); ?>" class="btn btn-primary" /></p>
	</div>
</div>
<script type="text/javascript">
    
function showLogstat() {
    $.post("xajax/ajax.updatelogstat.php")
        .done (function(data) {
            if (data) {
                $('#logstat').html(data);
                console.log('updated logstat');
                }
            })
    }
		
function callShowLogstat() {
    showLogstat();
    setTimeout("callShowLogstat();", 30000);
    }
		
$(window).load(function() {
    callShowLogstat();
    });
    
</script>