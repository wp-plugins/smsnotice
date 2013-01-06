<?php
/*
Plugin Name: SmsNotice
Plugin URI: http://walkbird.sinaapp.com/?p=240
Description: Send sms to administrator when a comment is posted.
Version: 0.8
Author: walkbird
Author URI: http://weibo.com/ccwalkbird
License: GPLv2 or later
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/


// 确保用户直接访问时不会泄露任何东西
if ( !function_exists( 'add_action' ) ) {
	_e("Hi there!  I'm just a plugin, not much I can do when called directly.",'smsnotice');
	exit;
}

//初始化
//导入飞信模块
require_once dirname( __FILE__ ) . '/PHPFetion.php';
require_once dirname( __FILE__ ) . '/config.php';

$sendSMSUrl = 'http://'. $_SERVER['HTTP_HOST'] . '/wp-content/plugins/SmsNotice/smsapi.php';

//导入字体
function smsnotice_init(){

	$plugin_dir = basename(dirname(__FILE__));
	load_plugin_textdomain( 'smsnotice', false, $plugin_dir );
}
add_action('plugins_loaded', 'smsnotice_init');

//发短信函数
function bird_send_msg($msg,$userid = 1) {
	
	$sms_password = get_option("smsnotice_password_".$userid,'');
	$sms_phonenum = get_option("smsnotice_phonenum_".$userid,'');
	
	//使用SAE任务队列优化
	if (!class_exists('SaeTaskQueue'))
	{
		//无队列，采用直接发送方式
		$fetion = new PHPFetion($sms_phonenum,$sms_password);
		$fetion->send($sms_phonenum,$msg);
	}
	else
	{
		//有队列，向队列发送请求
		$queue = new SaeTaskQueue(TaskQueue_Name);
		$array = array();
		global $sendSMSUrl;
		$array[] = array('url'=>$sendSMSUrl,'postdata'=>"phonenum=".$sms_phonenum."&password=".$sms_password."&msg=".urlencode($msg));
		$queue->addTask($array);
		$queue->push();
	}
	return;
}

function bird_send_msg_AllUser_check($msg,$checkStr)
{
	//SAE任务队列可用，创建队列
	$queue = new SaeTaskQueue(TaskQueue_Name);
	$array = array();
	global $sendSMSUrl;
	
	$users = get_users(array('role'=>'administrator','fields'=>'ID'));
	
	foreach($users as $userid)
	{
		//枚举每一个用户
		$sms_nf_check = get_option($checkStr.$userid,'no');
		$isPhoneVerified = get_option("smsnotice_phonenum_check_".$userid,'no');
		
		//判断用户是否订阅该通知及是否通过手机认证
		if ($sms_nf_check == 'no' || $isPhoneVerified == 'no') continue;
		
		$sms_password = get_option("smsnotice_password_".$userid,'');
		$sms_phonenum = get_option("smsnotice_phonenum_".$userid,'');
		
		$array[] = array('url'=>$sendSMSUrl,'postdata'=>"phonenum=".$sms_phonenum."&password=".$sms_password."&msg=".urlencode($msg));
	}
	$queue->addTask($array);
	$queue->push();
}

//文章发表时，发短信给管理员
function bird_newpost_notify($postID) {
	
	$msg = sprintf(__('[%1$s] published a post: "%2$s" [@%3$s]','smsnotice'),get_userdata(get_post($postID)->post_author)->user_login,substr(get_post($postID)->post_title,0,20),get_option('blogname'));

	if (!class_exists('SaeTaskQueue'))
	{
		//在无队列时，默认只向系统创建者发送
		$sms_nf_newpost = get_option("smsnotice_notify_newpost_1",'no');
		$isPhoneVerified = get_option("smsnotice_phonenum_check_1",'no');
		//判断用户是否订阅该通知及是否通过手机认证
		if ($sms_nf_newpost == 'no' || $isPhoneVerified == 'no') return;
		
		bird_send_msg($msg);
	}
	else
	{
		
		
		//SAE任务队列可用，向所有订阅用户发送
		bird_send_msg_AllUser_check($msg,"smsnotice_notify_newpost_");
	}
	return $postID;
}
add_action('publish_post','bird_newpost_notify');


//文章删除时，发短信给管理员
function bird_deletepost_notify($postID) {
	global $current_user;
	get_currentuserinfo();
	$msg = sprintf(__('[%1$s] trashed a post: [%2$s] "%3$s" [@%4$s]','smsnotice'),$current_user->user_login,get_userdata(get_post($postID)->post_author)->user_login,substr(get_post($postID)->post_title,0,20),get_option('blogname'));
	
	if (!class_exists('SaeTaskQueue'))
	{
		//在无队列时，默认只向系统创建者发送
		$sms_nf_deletepost = get_option("smsnotice_notify_deletepost_1",'no');
		$isPhoneVerified = get_option("smsnotice_phonenum_check_1",'no');
		//判断用户是否订阅该通知及是否通过手机认证
		if ($sms_nf_newpost == 'no' || $isPhoneVerified == 'no') return;

		bird_send_msg($msg);
	}
	else 
	{
		//SAE任务队列可用，向所有订阅用户发送
		bird_send_msg_AllUser_check($msg,"smsnotice_notify_deletepost_");
	}
	return $postID;
}

add_action('trashed_post','bird_deletepost_notify');


//评论发表时，发送短信给管理员
function bird_newcomment_notify($commentID) {
	
	$postID = get_comment($commentID)->comment_post_ID;
	$msg = sprintf(__('[%1$s] post a comment: "%2$s" ON "%3$s"[@%4$s]','smsnotice'),get_comment($commentID)->comment_author,substr(get_comment($commentID)->comment_content,0,20),get_post($postID)->post_title,get_option('blogname'));
	
	if (!class_exists('SaeTaskQueue'))
	{
		//在无队列时，默认只向系统创建者发送
		$sms_nf_newcomment = get_option("smsnotice_notify_newcomment_1",'no');
		$isPhoneVerified = get_option("smsnotice_phonenum_check_1",'no');
		//判断用户是否订阅该通知及是否通过手机认证
		if ($sms_nf_newpost == 'no' || $isPhoneVerified == 'no') return;
		
		bird_send_msg($msg);
	}
	else
	{
		//SAE任务队列可用，向所有订阅用户发送
		bird_send_msg_AllUser_check($msg,"smsnotice_notify_newcomment_");
	}
	return $commentID;
}

add_action('comment_post','bird_newcomment_notify');

//评论删除时，发短信给管理员
function bird_deletecomment_notify($commentID) {
	
	global $current_user;
	get_currentuserinfo();
	$postID = get_comment($commentID)->comment_post_ID;
	$msg = sprintf(__('[%1$s] trashed a comment: [%2$s] "%3$s" ON "%4$s"[@%5$s]','smsnotice'),$current_user->user_login,get_comment($commentID)->comment_author,substr(get_comment($commentID)->comment_content,0,20),get_post($postID)->post_title,get_option('blogname'));
	
	if (!class_exists('SaeTaskQueue'))
	{
		//在无队列时，默认只向系统创建者发送
		$sms_nf_deletecomment = get_option("smsnotice_notify_deletecomment_1",'no');
		$isPhoneVerified = get_option("smsnotice_phonenum_check_1",'no');
		//判断用户是否订阅该通知及是否通过手机认证
		if ($sms_nf_newpost == 'no' || $isPhoneVerified == 'no') return;
		
		bird_send_msg($msg);
	}
	else
	{
		//SAE任务队列可用，向所有订阅用户发送
		bird_send_msg_AllUser_check($msg,"smsnotice_notify_deletecomment_");
	}
	return $commentID;
}

add_action('trashed_comment','bird_deletecomment_notify');

//添加用户时，发短信给管理员
function bird_newuser_notify($userID)
{
	global $current_user;
	get_currentuserinfo();
	
	$msg = sprintf(__('[%1$s] add a user: %2$s [@%3$s]','smsnotice'),$current_user->user_login,get_userdata($userID)->user_login,get_option('blogname'));
	
	if (!class_exists('SaeTaskQueue'))
	{
		//在无队列时，默认只向系统创建者发送
		$sms_nf_newuser = get_option("smsnotice_notify_newuser_1",'no');
		$isPhoneVerified = get_option("smsnotice_phonenum_check_1",'no');
		//判断用户是否订阅该通知及是否通过手机认证
		if ($sms_nf_newpost == 'no' || $isPhoneVerified == 'no') return;
		
		bird_send_msg($msg);
	}
	else
	{
		//SAE任务队列可用，向所有订阅用户发送
		bird_send_msg_AllUser_check($msg,"smsnotice_notify_newuser_");
	}
	return $userID;
}
add_action('user_register','bird_newuser_notify');

//删除用户时，发短信给管理员
function bird_deleteuser_notify($userID) 
{
	global $current_user;
	get_currentuserinfo();
	$msg = sprintf(__('[%1$s] delete a user: %2$s [@%3$s]','smsnotice'),$current_user->user_login,get_userdata($userID)->user_login,get_option('blogname'));
	
	if (!class_exists('SaeTaskQueue'))
	{
		//在无队列时，默认只向系统创建者发送
		$sms_nf_deleteuser = get_option("smsnotice_notify_deleteuser_1",'no');
		$isPhoneVerified = get_option("smsnotice_phonenum_check_1",'no');
		//判断用户是否订阅该通知及是否通过手机认证
		if ($sms_nf_newpost == 'no' || $isPhoneVerified == 'no') return;
		
		bird_send_msg($msg);
	}
	else
	{
		//SAE任务队列可用，向所有订阅用户发送
		bird_send_msg_AllUser_check($msg,"smsnotice_notify_deleteuser_");
	}
	return $userID;
}

add_action('delete_user','bird_deleteuser_notify');
 

//管理员登录时，发短信给管理员
function bird_adminlogin_notify($userID) {
	//TODO
	
}

//add_action('wp_login','bird_adminlogin_notify');

//每一定人次访问量时，发短信给管理员


//管理用户登录验证
function bird_login_vk()
{	
	$vk_field = _field('smsnotice_verification');
	$sms_vk_login = get_option($vk_field,'no');
	$isPhoneVerified = get_option("smsnotice_phonenum_check_1",'no');
	if ($sms_vk_login == 'no' || $isPhoneVerified == 'no') return;
	
	$vk_field_code = $vk_field.'_code';
	$vk_field_codeTime = $vk_field.'_time';
	$vk_field_isLogin = $vk_field.'_islogin';
	$vk_field_timeout = $vk_field.'_timeout';
	
	
	$vk_lasttime = get_option($vk_field_codeTime,'0');
	$vk_code = get_option($vk_field_code,'1000');
	$vk_isLogin = get_option($vk_field_isLogin,'no');
	$vk_timeout = get_option($vk_field_timeout,'30');
	
	//判定是否为提交CODE状态
	if (@isset($_POST['smsnotice_vk_submit']))
	{
		$postCode = @$_POST['smsnotice_vk_code'];
		if ($postCode == $vk_code){
			$vk_isLogin = 'yes';
			$vk_lasttime = time();
			update_option($vk_field_isLogin,$vk_isLogin);
			update_option($vk_field_codeTime,$vk_lasttime);
		}
		else
		{
			echo "CODE ERROR<br/>";	
		}
	}
	
	//判断是否已经登录，且没有超时
	//时间单位为毫秒
	if ($vk_isLogin == 'yes' && (time() - $vk_lasttime < $vk_timeout*60)){
		//登录就刷新最后时间
		//默认30分钟未进行操作就需要重新认证
		update_option($vk_field_codeTime,time());
		return;
	}
	
	//更新登录记录为未登录
	$vk_isLogin = 'no';
	update_option($vk_field_isLogin,$vk_isLogin);
	
	//需要重新生成CODE
	
	if (time() - $vk_lasttime > 60)
	{
		//需要重新生成序列号
		$vk_code = rand(1000,9999);
		$vk_lasttime = time();
		update_option($vk_field_code,$vk_code);
		update_option($vk_field_codeTime,$vk_lasttime);
		
		//发送CODE到手机
		$smsmsg =sprintf(__('You are trying to Login to Admin Area.Your CODE is : %1$s .[@%2$s]'),$vk_code,get_option('blogname')).'[Support By SMSNotice]';
		bird_send_msg($smsmsg);
		
	}
	else
	{
		//无需重新生成CODE
	}
	
	//判断是否为请求重发状态
	if (@isset($_POST['smsnotice_vk_sendAgain']))
	{
		//发送CODE到手机
		$smsmsg =sprintf(__('You are trying to Login to Admin Area.Your CODE is : %1$s .[@%2$s]'),$vk_code,get_option('blogname')).'[Support By SMSNotice]';
		bird_send_msg($smsmsg);
		echo __("SMS has sent.Please check.").'<br/>';
	}
	
	//显示输入框
	$msg = 
		'<h2>'.__("Verification via SMS CODE").'</h2><br/>
		<form name="smsnotice_vk_form" method="post" action="'.str_replace("%7E","~",$_SERVER["REQUEST_URI"]).'">
		<label>'.__('CODE:','smsnotice').'<input name="smsnotice_vk_code" type="text" /></label><br/>
		'.__("Notice: if you can't receive the SMS in 5 minutes , please press SENDAGAIN.").'<br/>
		<input type="submit" name="smsnotice_vk_submit" value="submit"/>
		<input type="submit" name="smsnotice_vk_sendAgain" value="sendAgain"/>
		</form>
		';
	
	if (SMSNotice_Debug == 'true')
	{
		$msg .= '<p>your code is :'.$vk_code.'</p>';
	}
	//TODO：增加备选验证手段，采用邮箱验证的方式
	
	wp_die($msg);
}
add_action('admin_init','bird_login_vk');

//添加管理页面
add_action('admin_menu','bird_smsnotice_menu');
function bird_smsnotice_menu(){
	
	add_plugins_page(__('SmsNotice Setting Page','smsnotice'),
		__('SmsNotice','smsnotice'),
		'manage_options',
		__FILE__,
		'bird_smsnotice_options');
}



function bird_smsnotice_options(){
	if (!current_user_can('manage_options')){
		wp_die(__('You do not have sufficient permissions to access this page.','smsnotice'));
	}
	
	$sms_password = get_option(_field("smsnotice_password"),'');
	$sms_phonenum = get_option(_field("smsnotice_phonenum"),'');
	
	$sms_nf_newpost = get_option(_field("smsnotice_notify_newpost"),'no');
	$sms_nf_deletepost = get_option(_field("smsnotice_notify_deletepost"),'no');
	$sms_nf_newcomment = get_option(_field("smsnotice_notify_newcomment"),'no');
	$sms_nf_deletecomment = get_option(_field("smsnotice_notify_deletecomment"),'no');
	$sms_nf_newuser = get_option(_field("smsnotice_notify_newuser"),'no');
	$sms_nf_deleteuser = get_option(_field("smsnotice_notify_deleteuser"),'no');
	$sms_nf_adminlogin = get_option(_field("smsnotice_notify_adminlogin"),'no');
	$sms_nf_uservisit = get_option(_field("smsnotice_notify_uservisit"),'no');
	$sms_nf_usercount = get_option(_field("smsnotice_notify_usercount"),'100');
	
	
	$vk_field = _field('smsnotice_verification');
	$sms_vk_login = get_option($vk_field,'no');
	
	$vk_field_timeout = $vk_field.'_timeout';
	$sms_vk_timeout = get_option($vk_field_timeout,'30');
	
	if (@$_POST['smsnotice_hidden'] == 'Y')
	{
		//更新账户信息，设置验证状态为未验证
		
		$sms_password = $_POST['smsnotice_password'];
		$sms_phonenum = $_POST['smsnotice_phonenum'];
		$ck_code = rand(1000,9999);
		
		update_option(_field("smsnotice_phonenum_check_code"),$ck_code);
		update_option(_field("smsnotice_password"),$sms_password);
		update_option(_field("smsnotice_phonenum"),$sms_phonenum);
		update_option(_field("smsnotice_phonenum_check"),'no');

		global $current_user;
		get_currentuserinfo();
		
		//发送CODE到手机
		$smsmsg =sprintf(__('You are trying to BIND this phonenum to [%1$s].Your CODE is : %2$s .[@%3$s]'),$current_user->user_login,$ck_code,get_option('blogname')).'[Support By SMSNotice]';
		bird_send_msg($smsmsg,get_current_user_id());

		echo '<p>'.__('Validation SMS has been sent, please check your cellphone!','smsnotice').'</p>';
	}
	
	if (@$_POST['smsnotice_phonenum_check_hidden'] == 'Y')
	{
		$ck_code = get_option(_field("smsnotice_phonenum_check"),'1000');
		
		if ($ck_code = $_POST['smsnotice_vcode'])
		{
			update_option(_field("smsnotice_phonenum_check"),'yes');
			echo '<p>'.__('Validation Success!','smsnotice').'</p>';
		}
		else
		{
			echo '<p>'.__('Validation Fail!','smsnotice').'</p>';
		}
		
	}
	
	if (@$_POST['smsnotice_condition_hidden'] == 'Y')
	{
		//更新通知订阅
		if (@$_POST['smsnotice_notify_newpost'] == 'Y') $sms_nf_newpost = "yes"; else $sms_nf_newpost = "no";
		if (@$_POST['smsnotice_notify_deletepost'] == 'Y') $sms_nf_deletepost = "yes"; else $sms_nf_deletepost = "no";
		if (@$_POST['smsnotice_notify_newcomment'] == 'Y') $sms_nf_newcomment = "yes"; else $sms_nf_newcomment = "no";
		if (@$_POST['smsnotice_notify_deletecomment'] == 'Y') $sms_nf_deletecomment = "yes"; else $sms_nf_deletecomment = "no";
		if (@$_POST['smsnotice_notify_newuser'] == 'Y') $sms_nf_newuser = "yes"; else $sms_nf_newuser = "no";
		if (@$_POST['smsnotice_notify_deleteuser'] == 'Y') $sms_nf_deleteuser = "yes"; else $sms_nf_deleteuser = "no";
		if (@$_POST['smsnotice_notify_adminlogin'] == 'Y') $sms_nf_adminlogin = "yes"; else $sms_nf_adminlogin = "no";
		if (@$_POST['smsnotice_notify_uservisit'] == 'Y') $sms_nf_uservisit = "yes"; else $sms_nf_uservisit = "no";
		$sms_nf_usercount = $_POST['smsnotice_notify_usercount'];
		
		update_option(_field("smsnotice_notify_newpost"),$sms_nf_newpost);
		update_option(_field("smsnotice_notify_deletepost"),$sms_nf_deletepost);
		update_option(_field("smsnotice_notify_newcomment"),$sms_nf_newcomment);
		update_option(_field("smsnotice_notify_deletecomment"),$sms_nf_deletecomment);
		update_option(_field("smsnotice_notify_newuser"),$sms_nf_newuser);
		update_option(_field("smsnotice_notify_deleteuser"),$sms_nf_deleteuser);
		update_option(_field("smsnotice_notify_adminlogin"),$sms_nf_adminlogin);
		update_option(_field("smsnotice_notify_uservisit"),$sms_nf_uservisit);
		update_option(_field("smsnotice_notify_usercount"),$sms_nf_usercount);
		
		echo '<p>'.__('notify change saved!','smsnotice').'</p>';
	}
	
	if (@$_POST['smsnotice_verification_hidden'] == 'Y')
	{
		//更新验证设置
		if (@$_POST[$vk_field] == 'Y')	$sms_vk_login = 'yes'; else $sms_vk_login = 'no';
		$sms_vk_timeout = $_POST[$vk_field_timeout];
		
		update_option($vk_field, $sms_vk_login);
		update_option($vk_field_timeout, $sms_vk_timeout);
		
		echo '<p>'.__('verification change saved!','smsnotice').'</p>';
	}
	
	//手机是否通过验证的相关信息
	$isPhoneVerified = get_option(_field("smsnotice_phonenum_check"),'no');
	
	include("setting.php");
}

function _field($str)
{
	return $str.'_'.get_current_user_id();
}


?>