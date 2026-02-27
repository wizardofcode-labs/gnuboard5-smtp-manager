<?php
/**
 * SMTP Manager — 발송 로그 선택 삭제 처리
 *
 * POST del_ids[]  : 삭제할 로그 ID 배열
 * POST token      : CSRF 토큰
 */
if (!defined('_GNUBOARD_')) {
    define('G5_IS_ADMIN', true);
    include_once('../../../common.php');
}
if (!defined('_GNUBOARD_')) exit;

include_once(G5_ADMIN_PATH . '/admin.lib.php');
include_once(G5_PLUGIN_PATH . '/smtp_manager/lib/smtp.lib.php');

$log_url = G5_PLUGIN_URL . '/smtp_manager/admin/smtp_log.php';

if ($is_admin != 'super') {
    alert('최고관리자만 접근 가능합니다.', $log_url);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    goto_url($log_url);
    exit;
}

check_admin_token();

$del_ids = isset($_POST['del_ids']) && is_array($_POST['del_ids'])
    ? (array)$_POST['del_ids'] : array();

if (!empty($del_ids)) {
    $safe_ids = array();
    foreach ($del_ids as $id) {
        $id = (int)$id;
        if ($id > 0) {
            $safe_ids[] = $id;
        }
    }

    if (!empty($safe_ids) && smtp_manager_table_exists(SMTP_MANAGER_MAIL_LOG_TABLE)) {
        $in_clause = implode(',', $safe_ids);
        sql_query(
            " DELETE FROM `" . SMTP_MANAGER_MAIL_LOG_TABLE . "`
              WHERE `id` IN ({$in_clause}) ",
            false
        );
    }
}

goto_url($log_url);
exit;
