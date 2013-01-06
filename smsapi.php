<?php 
	include_once dirname( __FILE__ ) . '/PHPFetion.php';
	
	if (@isset($_REQUEST['password']) &&
		@isset($_REQUEST['phonenum']) &&
		@isset($_REQUEST['msg']))
	{
		$sms_password = $_REQUEST['password'];
		$sms_phonenum = $_REQUEST['phonenum'];
		$msg = $_REQUEST['msg'];
		
		$fetion = new PHPFetion($sms_phonenum,$sms_password);
		$ret = $fetion->send($sms_phonenum,$msg);
		
		if (strpos($ret,'短信发送成功') === false)
		{
			die($ret);
		}
		else
		{
			echo 'ok';
		}
	}
	else
	{
		die("post error");	
	}
?>