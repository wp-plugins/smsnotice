===SmsNotice===
Contributors: walkbird
Tags: SMS, comments, Fetion, 飞信, 登陆, login, notice
Requires at least: 3.0.1
Tested up to: 3.4
Stable tag: 0.8
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

短信通知(SMSNotice)是一款为中国移动飞信用户设计的wordpress监控插件。

== Description ==

短信通知(SMSNotice)是一款为中国移动飞信用户设计的wordpress监控插件。当用户设定的事件发生时会第一时间发送短信给用户

。

注意：由于短信是通过飞信发送的，故需要用户提供手机号和密码，手机号和密码明文存储在wordpress数据库的wp_options表中（

至于为什么是明文，这涉及到飞信发送的原理）。当仅仅是拷贝插件代码时，不会发生密码泄露。
 
插件提供对以下事件的通知：

* 发表\删除评论
* 发表\删除文章
* 添加\删除用户

验证功能：

* 登陆时短信验证码验证

== Installation ==

上传SMSNotice文件夹到plugin目录下，激活插件，在插件的配置页下输入手机号和密码。

== Changelog ==

= 0.8 =
* 支持多用户
* 支持SAE任务队列。自动识别SaeTaskQueue是否可用。

= 0.5 =
Release