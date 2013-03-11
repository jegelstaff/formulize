
<div class="wrap">
	
    <?php screen_icon(); ?>
    
	<form action="options.php" method="post" id="<?php echo $plugin_id; ?>_options_form" name="<?php echo $plugin_id; ?>_options_form">
    
	<?php settings_fields($plugin_id.'_options'); ?>
	<?php do_settings_sections($plugin_id.'_options'); ?>
    
    <h2>Formulize Plugin Options &raquo; Settings</h2>
    <table class="widefat">
		<thead>
		   <tr>
			 <th><input type="submit" name="submit" value="Save Settings" class="button-primary" style="padding:8px;" /></th>
		   </tr>
		</thead>
		<tfoot>
		   <tr>
			 <th><input type="submit" name="submit" value="Save Settings" class="button-primary" style="padding:8px;" /></th>
		   </tr>
		</tfoot>
		<tbody>
		   <tr>
			 <td style="padding:25px;font-family:Verdana, Geneva, sans-serif;color:#666;">
                 <label for="formulize_path">
    <p>Path to Formulize root directory: <input type="text" name="formulize_path" value="<?php echo get_option('formulize_path'); ?>" /></p>
<p>ex: /var/www/formulize</p>
                 </label>
                 <label for="synchronize_users_button">
    <p>Synchronize Users:
	<input name="synchronize_users_button" type="checkbox" value="1" <?php checked('1', get_option('synchronize_users_button')); ?>/>
	</p>
                 </label>
             </td>
		   </tr>
		</tbody>
	</table>
    
	</form>
    
</div>