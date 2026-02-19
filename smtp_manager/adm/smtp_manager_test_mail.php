<?php
/**
 * SMTP Manager - 테스트 메일 발송 처리
 * plugin/smtp_manager 플러그인의 테스트 메일 발송 요청을 처리합니다.
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

$test_recipient = isset($_POST['test_recipient']) ? trim($_POST['test_recipient']) : '';
$error_message = '';

if (smtp_manager_send_test_mail($test_recipient, $error_message)) {
    goto_url(G5_ADMIN_URL . '/smtp_manager_config.php?test_success=1&msg=' . urlencode('테스트 메일 발송에 성공했습니다.'));
} else {
    $msg = $error_message ? $error_message : '테스트 메일 발송에 실패했습니다.';
    goto_url(G5_ADMIN_URL . '/smtp_manager_config.php?test_success=0&msg=' . urlencode($msg));
}
