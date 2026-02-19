<?php
/**
 * SMTP Manager - 메일 로그 본문 조회 (AJAX JSON 응답)
 * adm/ 경로에 위치하여 admin_referer_check 를 통과합니다.
 */
require_once './_common.php';

// JSON 응답 헬퍼
function smtp_log_view_json($data, $http_status = 200)
{
    http_response_code($http_status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// 관리자 권한 확인
if (!$is_admin || $is_admin !== 'super') {
    smtp_log_view_json(array('error' => '접근 권한이 없습니다.'), 403);
}

// XHR 요청인지 확인
$is_xhr = isset($_SERVER['HTTP_X_REQUESTED_WITH'])
    && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
if (!$is_xhr) {
    smtp_log_view_json(array('error' => '올바른 방법으로 요청해 주세요.'), 400);
}

// ID 파라미터 검증
$log_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($log_id <= 0) {
    smtp_log_view_json(array('error' => '잘못된 요청입니다.'), 400);
}

// smtp.lib.php 에서 테이블 상수 및 헬퍼 함수 로드
include_once(G5_PLUGIN_PATH . '/smtp_manager/lib/smtp.lib.php');

$log_table = SMTP_MANAGER_MAIL_LOG_TABLE;

if (!smtp_manager_table_exists($log_table)) {
    smtp_log_view_json(array('error' => '로그 테이블이 존재하지 않습니다.'), 404);
}

$row = sql_fetch(
    " select id, recipient, subject, content, status, error_message, created_at
        from `{$log_table}`
       where id = '{$log_id}'
       limit 1 ",
    false
);

if (!$row || empty($row['id'])) {
    smtp_log_view_json(array('error' => '해당 로그를 찾을 수 없습니다.'), 404);
}

smtp_log_view_json(array(
    'id'            => (int)$row['id'],
    'recipient'     => (string)$row['recipient'],
    'subject'       => (string)$row['subject'],
    'content'       => (string)$row['content'],
    'status'        => (string)$row['status'],
    'error_message' => (string)$row['error_message'],
    'created_at'    => (string)$row['created_at'],
));
