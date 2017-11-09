<?php
/*
Plugin Name: Ludou Custom User Register
Plugin URI: http://www.ludou.org/wordpress-ludou-custom-user-register.html
Description: 修改默认的后台用户注册表单，用户可以自行输入密码，不必用Email接收密码，跳过Email验证。用户可自行选择注册的身份角色。带有验证码功能，防止恶意注册。
Version: 3.0
Author: Ludou
Author URI: http://www.ludou.org
*/

if (!isset($_SESSION)) {
 	session_start();
	session_regenerate_id(TRUE);
}

/**
 * 后台注册模块，添加注册表单,修改新用户通知。
 */
if ( !function_exists('wp_new_user_notification') ) :
/**
 * Notify the blog admin of a new user, normally via email.
 *
 * @since 2.0
 *
 * @param int $user_id User ID
 * @param string $plaintext_pass Optional. The user's plaintext password
 */
function wp_new_user_notification($user_id, $plaintext_pass = '', $flag='') {
	if(func_num_args() > 1 && $flag !== 1)
		return;

	$user = new WP_User($user_id);

	$user_login = stripslashes($user->user_login);
	$user_email = stripslashes($user->user_email);

	// The blogname option is escaped with esc_html on the way into the database in sanitize_option
	// we want to reverse this for the plain text arena of emails.
	$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

	$message  = sprintf(__('New user registration on your site %s:'), $blogname) . "\r\n\r\n";
	$message .= sprintf(__('Username: %s'), $user_login) . "\r\n\r\n";
	$message .= sprintf(__('E-mail: %s'), $user_email) . "\r\n";

	@wp_mail(get_option('admin_email'), sprintf(__('[%s] New User Registration'), $blogname), $message);
	
	if ( empty($plaintext_pass) )
		return;

	// 你可以在此修改发送给用户的注册通知Email
	$message  = sprintf(__('Username: %s'), $user_login) . "\r\n";
	$message .= sprintf(__('Password: %s'), $plaintext_pass) . "\r\n";
	$message .= 'Login URL: ' . wp_login_url() . "\r\n";

	// sprintf(__('[%s] Your username and password'), $blogname) 为邮件标题
	wp_mail($user_email, sprintf(__('[%s] Your username and password'), $blogname), $message);
}
endif;

/* 修改注册表单 */
function ludou_show_password_field() {
  // 生成token，防止跨站攻击
	$token = md5(uniqid(rand(), true));
	
	$_SESSION['ludou_register_584226_token'] = $token;
	
	define('LCR_PLUGIN_URL', plugin_dir_url( __FILE__ ));
?>
<style type="text/css">
<!--
#user_role {
  padding: 2px;
  -moz-border-radius: 4px 4px 4px 4px;
  border-style: solid;
  border-width: 1px;
  line-height: 15px;
}

#user_role option {
	padding: 2px;
}
-->
</style>
<p>
	<label for="user_nick">Real Name<br/>
		<input id="user_nick" class="input" type="text" size="25" value="<?php echo empty($_POST['user_nick']) ? '':$_POST['user_nick']; ?>" name="user_nick" />
	</label>
</p>
<p>
	<label for="user_pwd1">Password(At least 6)<br/>
		<input id="user_pwd1" class="input" type="password" size="25" value="" name="user_pass" />
	</label>
</p>
<p>
	<label for="user_pwd2">Repeat Password<br/>
		<input id="user_pwd2" class="input" type="password" size="25" value="" name="user_pass2" />
	</label>
</p>
<p style="margin:0 0 10px;">
	<label>Class Number:
        <input id="class_tag" class="input" type="text" size="25" value="<?=$_POST['class_tag']?>" name="class_tag" />
	</label>
	<br />
</p>
<p>
	<label for="CAPTCHA">Verification Code:<br />
		<input id="CAPTCHA" style="width:110px;*float:left;" class="input" type="text" size="10" value="" name="captcha_code" />
		Can't see clearly?<a href="javascript:void(0)" onclick="document.getElementById('captcha_img').src='<?php echo constant("LCR_PLUGIN_URL"); ?>/captcha/captcha.php?'+Math.random();document.getElementById('CAPTCHA').focus();return false;">Click to switch</a>
	</label>
</p>
<p>
	<label>
	<img id="captcha_img" src="<?php echo constant("LCR_PLUGIN_URL"); ?>/captcha/captcha.php" title="Can't see clearly?Click to switch" alt="Can't see clearly?Click to switch" onclick="document.getElementById('captcha_img').src='<?php echo constant("LCR_PLUGIN_URL"); ?>/captcha/captcha.php?'+Math.random();document.getElementById('CAPTCHA').focus();return false;" />
	</label>
</p>
<input type="hidden" name="spam_check" value="<?php echo $token; ?>" />
<?php
}

/* 处理表单提交的数据 */
function ludou_check_fields($login, $email, $errors) {
  if(empty($_POST['spam_check']) || $_POST['spam_check'] != $_SESSION['ludou_register_584226_token'])
		$errors->add('spam_detect', "<strong>Wrong</strong>：Please do not register maliciously");
		
	if(empty($_POST['captcha_code'])
		|| empty($_SESSION['ludou_lcr_secretword'])
		|| (trim(strtolower($_POST['captcha_code'])) != $_SESSION['ludou_lcr_secretword'])
		) {
		$errors->add('captcha_spam', "<strong>Wrong</strong>：Incorrect verification code");
	}
	unset($_SESSION['ludou_lcr_secretword']);
	
	if (!isset($_POST['user_nick']) || trim($_POST['user_nick']) == '')
	  $errors->add('user_nick', "<strong>Wrong</strong>：Real Name must be filled in");
	  
	if(strlen($_POST['user_pass']) < 6)
		$errors->add('password_length', "<strong>Wrong</strong>：The length of password must be at least 6");
	elseif($_POST['user_pass'] != $_POST['user_pass2'])
		$errors->add('password_error', "<strong>Wrong</strong>：The password must be the same for the two input");

    // check class tag
    $gbClass = new GbClass();
    $class_tag = trim($_POST['class_tag']);
    if($class_tag == "") {
        $errors->add('class_error', "<strong>Wrong</strong>：Please fill in the class number");
    }else {
        if(!$gbClass->checkTagUnique($class_tag)) {
            $errors->add('class_error', "<strong>Wrong</strong>：The class number does not exist");
        }
    }
}

/* 保存表单提交的数据 */
function ludou_register_extra_fields($user_id, $password="", $meta=array()) {
	$userdata = array();
	$userdata['ID'] = $user_id;
	$userdata['user_pass'] = $_POST['user_pass'];
	$userdata['nickname'] = str_replace(array('<','>','&','"','\'','#','^','*','_','+','$','?','!'), '', $_POST['user_nick']);

	$pattern = '/[一-龥]/u';
  if(preg_match($pattern, $_POST['user_login'])) {
    $userdata['user_nicename'] = $user_id;
  }

    // add this for class
    $gbClass = new GbClass();
    $class_tag = trim($_POST['class_tag']);
    $class_info = $gbClass->getClassByTag($class_tag);
    update_user_meta($user_id, "study_class", $class_info['id'], true);
    remove_user_from_blog($user_id,1);

    wp_new_user_notification( $user_id, $_POST['user_pass'], 1 );
    wp_update_user($userdata);

    // update class students number
    $gbClass->setClassStudentCounts($class_info['id']);
}

function remove_default_password_nag() {
	global $user_ID;
	delete_user_setting('default_password_nag', $user_ID);
	update_user_option($user_ID, 'default_password_nag', false, true);
}

function ludou_register_change_translated_text( $translated_text, $untranslated_text, $domain ) {
  if ( $untranslated_text === 'A password will be e-mailed to you.' || $untranslated_text === 'Registration confirmation will be emailed to you.' )
    return '';
  else if ($untranslated_text === 'Registration complete. Please check your e-mail.' || $untranslated_text === 'Registration complete. Please check your email.')
    return 'Registered successfully！';
  else
    return $translated_text;
}

add_filter('gettext', 'ludou_register_change_translated_text', 20, 3);
add_action('admin_init', 'remove_default_password_nag');
add_action('register_form','ludou_show_password_field');
add_action('register_post','ludou_check_fields',10,3);
add_action('user_register', 'ludou_register_extra_fields');
