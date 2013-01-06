<div class="wrap">
<?php echo "<h2>".__('SmsNotice Options','smsnotice')."</h2>"; ?>

<?php  
if (@$_POST['smsnotice_hidden'] != 'Y')
{
?>
    <!--手机号密码填写表单-->
    <form name="smsnotice_form" method="post" action="<?php echo str_replace('%7E','~',$_SERVER['REQUEST_URI']); ?>">
    <input type="hidden" name="smsnotice_hidden" value="Y"/>
    <?php echo "<h2>".__('Account Setting','smsnotice')."</h2>" ?>
    <p><?php _e('Phone Num:','smsnotice'); ?>
    <input type="text" name="smsnotice_phonenum" value="<?php echo $sms_phonenum ?>"/>
    </p>
    <p><?php _e('Password:','smsnotice'); ?>
    <input type="password" name="smsnotice_password" value="<?php echo $sms_password ?>"/>
    </p>
    <p><?php _e('Notice: Your phone number must belong to China Mobile, and Opened fetion service.','smsnotice'); ?>
    </p>
    <input type="submit" name="submit" value="<?php _e('save','smsnotice') ?>" />
    </form>
<?php 
}
else
{
?>
    <!--手机号验证表单-->
    <form name="smsnotice_phonenum_check_form" method="post" action="<?php echo str_replace('%7E','~',$_SERVER['REQUEST_URI']); ?>">
    <input type="hidden" name="smsnotice_phonenum_check_hidden" value="Y"/>
    <?php echo "<h2>".__('Verify Your phonenum','smsnotice')."</h2>" ?>
    <p><?php _e('CODE you received:','smsnotice'); ?>
    <input type="text" name="smsnotice_vcode" />
    </p>
    <p><?php _e("If you can't receive message, please check your password.",'smsnotice'); ?></p>
    <input type="submit" name="submit" value="<?php _e('save','smsnotice') ?>" />
    </form>
<?php 
}
?>

<?php 
if ($isPhoneVerified != 'no')
{ 
?>
<!--通知设置-->
    <form name="smsnotice_condition_form" method="post" action="<?php echo str_replace('%7E','~',$_SERVER['REQUEST_URI']); ?>">
    <input type="hidden" name="smsnotice_condition_hidden" value="Y"/>
    <?php echo "<h2>".__('Notification Setting','smsnotice')."</h2>" ?>
    <?php echo "<h3>".__('Notify me, When it happen:','smsnotice')."</h3>" ?>
    <label>
    <input name="smsnotice_notify_newpost" type="checkbox" value="Y" <?php if ($sms_nf_newpost == 'yes') echo 'checked="checked"'; ?> />
    <?php _e('new post','smsnotice'); ?>
    </label><br/>
    
    <label>
    <input name="smsnotice_notify_deletepost" type="checkbox" value="Y" <?php if ($sms_nf_deletepost == 'yes') echo 'checked="checked"'; ?> />
    <?php _e('delete post','smsnotice'); ?>
    </label><br/>
    
    <label>
    <input name="smsnotice_notify_newcomment" type="checkbox" value="Y" <?php if ($sms_nf_newcomment == 'yes') echo 'checked="checked"'; ?> />
    <?php _e('new comment','smsnotice'); ?>
    </label><br/>
    
    <label>
    <input name="smsnotice_notify_deletecomment" type="checkbox" value="Y" <?php if ($sms_nf_deletecomment == 'yes') echo 'checked="checked"'; ?> />
    <?php _e('delete comment','smsnotice'); ?>
    </label><br/>
    
    <label>
    <input name="smsnotice_notify_newuser" type="checkbox" value="Y" <?php if ($sms_nf_newuser == 'yes') echo 'checked="checked"'; ?> />
    <?php _e('new user','smsnotice'); ?>
    </label><br/>
    
    <label>
    <input name="smsnotice_notify_deleteuser" type="checkbox" value="Y" <?php if ($sms_nf_deleteuser == 'yes') echo 'checked="checked"'; ?> />
    <?php _e('delete user','smsnotice'); ?>
    </label><br/>
    
    <label>
    <input name="smsnotice_notify_adminlogin" type="checkbox" value="Y" <?php if ($sms_nf_adminlogin == 'yes') echo 'checked="checked"'; ?> />
    <?php _e('admin login','smsnotice'); ?>
    </label><br/>
    
    <label>
    <input name="smsnotice_notify_uservisit" type="checkbox" value="Y" <?php if ($sms_nf_uservisit == 'yes') echo 'checked="checked"'; ?> />
    <?php _e('Notify me when each ','smsnotice'); ?>
    </label>
    <input name="smsnotice_notify_usercount" type="text" value="<?php echo $sms_nf_usercount; ?>" />
    <?php _e('person visited.'); ?>
    <br/>
    
    <input type="submit" name="submit" value="<?php _e('save','smsnotice') ?>" />
    </form>
    
    <?php echo "<h2>".__('Verification Setting','smsnotice')."</h2>" ?>
    
<!--登陆验证设置-->
    <form name="smsnotice_verification_form" method="post" action="<?php echo str_replace('%7E','~',$_SERVER['REQUEST_URI']); ?>">
    <input type="hidden" name="smsnotice_verification_hidden" value="Y"/>
    
    <label>
    <input name="<?php echo $vk_field; ?>" type="checkbox" value="Y" <?php if ($sms_vk_login == 'yes') echo 'checked="checked"'; ?> />
    <?php _e('SMS Verification when login','smsnotice'); ?>
    </label><br/>
    
    <label><?php _e('Timeout:','smsnotice'); ?>
    <input name="<?php echo $vk_field_timeout; ?>" type="text" value="<?php echo $sms_vk_timeout; ?>" /><?php _e('min','smsnotice'); ?>
    </label><br/>
    
    <p><?php _e('Notice: System will send a "CODE" to your cell phone, when you ask for login. And system will ask for that "CODE" to verification.','smsnotice'); ?>
    </p>
    
    <input type="submit" name="submit" value="<?php _e('save','smsnotice') ?>" />
    </form>
<?php 
} 
?>
