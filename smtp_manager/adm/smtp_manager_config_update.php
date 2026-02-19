<?php
/**
 * SMTP Manager - 설정 저장 처리
 * plugin/smtp_manager 플러그인의 설정 저장 요청을 처리합니다.
 * adm/ 경로에 위치하여 admin_referer_check 및 check_admin_token 을 통과합니다.
 */
$sub_menu = '100940';

require_once './_common.php';
include_once(G5_PLUGIN_PATH . '/smtp_manager/lib/smtp.lib.php');

auth_check_menu($auth, $sub_menu, 'w');

if ($is_admin != 'super') {
    alert('최고관리자만 접근 가능합니다.');
}

check_admin_token();

$smtp_use = isset($_POST['smtp_use']) ? 1 : 0;
$smtp_host = isset($_POST['smtp_host']) ? strip_tags(clean_xss_attributes(trim($_POST['smtp_host']))) : '';
$smtp_port = isset($_POST['smtp_port']) ? (int)preg_replace('/[^0-9]/', '', $_POST['smtp_port']) : 25;
$smtp_secure = isset($_POST['smtp_secure']) ? trim($_POST['smtp_secure']) : 'none';
$smtp_user = isset($_POST['smtp_user']) ? strip_tags(clean_xss_attributes(trim($_POST['smtp_user']))) : '';
$smtp_pass = isset($_POST['smtp_pass']) ? trim($_POST['smtp_pass']) : '';
$smtp_from_name = isset($_POST['smtp_from_name']) ? strip_tags(clean_xss_attributes(trim($_POST['smtp_from_name']))) : '';
$smtp_from_email = isset($_POST['smtp_from_email']) ? trim($_POST['smtp_from_email']) : '';

if (!in_array($smtp_secure, array('none', 'ssl', 'tls'))) {
    $smtp_secure = 'none';
}

if ($smtp_port <= 0) {
    $smtp_port = 25;
}

if ($smtp_from_email && !filter_var($smtp_from_email, FILTER_VALIDATE_EMAIL)) {
    alert('발신자 이메일 형식이 올바르지 않습니다.');
}

$updated = smtp_manager_update_config(array(
    'smtp_use' => $smtp_use,
    'smtp_host' => $smtp_host,
    'smtp_port' => $smtp_port,
    'smtp_secure' => $smtp_secure,
    'smtp_user' => $smtp_user,
    'smtp_pass' => $smtp_pass,
    'smtp_from_name' => $smtp_from_name,
    'smtp_from_email' => $smtp_from_email
));

if (!$updated) {
    alert('SMTP 관련 필드가 없습니다. 먼저 install.php를 실행해 주세요.');
}

set_session('ss_smtp_msg', 'SMTP 설정이 저장되었습니다.');
set_session('ss_smtp_test_success', -1);
goto_url(G5_ADMIN_URL . '/smtp_manager_config.php');
