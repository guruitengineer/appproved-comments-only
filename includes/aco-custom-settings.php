<?php if( isset($_GET['settings-updated']) ) { ?>
<div id="message" class="updated">
  <p><strong><?php _e('Settings Saved.') ?></strong></p>
</div>
<?php } ?>

<h1>Approved Comments Only Settings</h1>
<form method="post" action="../wp-admin/options.php">
  <?php settings_fields( 'aco_options' ); ?>
  <?php do_settings_sections( 'aco_options' ); ?>
  <table class="form-table">
    <tr valign="top">
      <th scope="row">Select User Level:</th>
      <td><!-- <input type="text" name="aco_user_level" value=""/> -->
        <?php $aco_user_level=get_option( 'aco_user_level' ); ?>
        <select name="aco_user_level[]" multiple>
          <option value="subscriber" <?php if(in_array("subscriber", (array)$aco_user_level)) echo 'selected'?> >Subscriber</option>
          <option value="contributor" <?php if(in_array("contributor", (array)$aco_user_level)) echo 'selected'?> >Contributor</option>
          <option value="author" <?php if(in_array("author", (array)$aco_user_level)) echo 'selected'?> >Author</option>
          <option value="editor" <?php if(in_array("editor", (array)$aco_user_level)) echo 'selected'?> >Editor</option>
          <option value="administrator" <?php if(in_array("administrator", (array)$aco_user_level)) echo 'selected'?> >Administrator</option>
        </select>
      </td>
      <?php print('Please use CTRL to select multiple values');?>
    </tr>
    <tr>
      <th scope="row">Show user's own comments only in Dashboard.</th>
      <td>
        <?php $aco_user_own_comments=get_option( 'aco_user_own_comments'); ?>
        <input type="checkbox" name="aco_user_own_comments" value="1"
        <?php
          if($aco_user_own_comments=='1') echo 'checked'
        ?>
        >
      </td>
    </tr>
  </table>
  <?php 
  submit_button(); ?>
</form>