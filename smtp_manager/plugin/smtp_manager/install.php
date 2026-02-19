<?php
if (!defined('_GNUBOARD_')) {
    define('G5_IS_ADMIN', true);
    include_once('../../common.php');
}
if (!defined('_GNUBOARD_')) exit;

include_once(G5_ADMIN_PATH . '/admin.lib.php');
include_once(G5_PLUGIN_PATH . '/smtp_manager/lib/smtp.lib.php');

if ($is_admin != 'super') {
    alert('최고관리자만 접근 가능합니다.');
}

$result = smtp_manager_install_schema();

$g5['title'] = 'SMTP Manager 설치';
include_once(G5_ADMIN_PATH . '/admin.head.php');
?>

<div class="local_desc01 local_desc">
    <p>SMTP Manager 설치가 완료되었습니다.</p>
</div>

<div class="tbl_frm01 tbl_wrap">
    <table>
        <caption>설치 결과</caption>
        <colgroup>
            <col class="grid_3">
            <col>
        </colgroup>
        <tbody>
            <?php foreach ($result['messages'] as $message) { ?>
            <tr>
                <th scope="row">처리</th>
                <td><?php echo get_sanitize_input($message); ?></td>
            </tr>
            <?php } ?>
            <tr>
                <th scope="row">상태</th>
                <td><?php echo $result['success'] ? 'success' : 'fail'; ?></td>
            </tr>
        </tbody>
    </table>
</div>

<div class="btn_fixed_top">
    <a href="<?php echo G5_ADMIN_URL; ?>/smtp_manager_config.php" class="btn btn_01">SMTP 설정으로 이동</a>
    <a href="<?php echo G5_PLUGIN_URL; ?>/smtp_manager/admin/smtp_log.php" class="btn btn_02">메일 발송 로그 보기</a>
</div>

<?php
include_once(G5_ADMIN_PATH . '/admin.tail.php');
