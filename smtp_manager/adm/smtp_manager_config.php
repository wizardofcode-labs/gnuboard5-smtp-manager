<?php
$sub_menu = '100940';

require_once './_common.php';
include_once(G5_PLUGIN_PATH . '/smtp_manager/lib/smtp.lib.php');

auth_check_menu($auth, $sub_menu, 'r');

if ($is_admin != 'super') {
    alert('최고관리자만 접근 가능합니다.');
}

$message = isset($_GET['msg']) ? strip_tags($_GET['msg']) : '';
$test_success = isset($_GET['test_success']) ? (int)$_GET['test_success'] : -1;

$smtp = smtp_manager_get_config();

// 토큰은 한 번만 생성 - 두 번 호출하면 세션이 덮어씌워져 첫 번째 폼 토큰이 무효화됨
$form_token = get_admin_token();

$g5['title'] = 'SMTP 설정';
include_once(G5_ADMIN_PATH . '/admin.head.php');
?>

<div class="local_desc01 local_desc">
    <p>
        SMTP 서버 정보를 설정하면 메일 발송 시 SMTP를 우선 사용합니다.<br>
        코어 파일을 수정하지 않고 extend 방식으로 동작합니다.
    </p>
</div>

<?php if ($message) { ?>
<div class="<?php echo ($test_success === 1 || ($test_success === -1 && strpos($message, '저장') !== false)) ? 'local_desc01' : 'local_desc02'; ?> local_desc">
    <p><?php echo get_sanitize_input($message); ?></p>
</div>
<?php } ?>

<form name="fsmtpconfig" id="fsmtpconfig" method="post" action="<?php echo G5_ADMIN_URL; ?>/smtp_manager_config_update.php">
    <input type="hidden" name="token" value="<?php echo $form_token; ?>">

    <div class="tbl_frm01 tbl_wrap">
        <table>
            <caption>SMTP 설정</caption>
            <colgroup>
                <col class="grid_3">
                <col>
            </colgroup>
            <tbody>
                <tr>
                    <th scope="row">SMTP 사용</th>
                    <td>
                        <label for="smtp_use">
                            <input type="checkbox" name="smtp_use" value="1" id="smtp_use" <?php echo ((int)$smtp['smtp_use']) ? 'checked' : ''; ?>> 사용
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="smtp_host">SMTP 서버</label></th>
                    <td><input type="text" name="smtp_host" id="smtp_host" value="<?php echo get_sanitize_input($smtp['smtp_host']); ?>" class="frm_input" size="60"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="smtp_port">포트</label></th>
                    <td><input type="text" name="smtp_port" id="smtp_port" value="<?php echo (int)$smtp['smtp_port']; ?>" class="frm_input" size="10"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="smtp_secure">보안 방식</label></th>
                    <td>
                        <select name="smtp_secure" id="smtp_secure">
                            <option value="none" <?php echo $smtp['smtp_secure'] === 'none' ? 'selected' : ''; ?>>none</option>
                            <option value="ssl" <?php echo $smtp['smtp_secure'] === 'ssl' ? 'selected' : ''; ?>>ssl</option>
                            <option value="tls" <?php echo $smtp['smtp_secure'] === 'tls' ? 'selected' : ''; ?>>tls</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="smtp_user">아이디</label></th>
                    <td><input type="text" name="smtp_user" id="smtp_user" value="<?php echo get_sanitize_input($smtp['smtp_user']); ?>" class="frm_input" size="60"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="smtp_pass">비밀번호</label></th>
                    <td><input type="password" name="smtp_pass" id="smtp_pass" value="<?php echo get_sanitize_input($smtp['smtp_pass']); ?>" class="frm_input" size="60" autocomplete="new-password"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="smtp_from_name">발신자 이름</label></th>
                    <td><input type="text" name="smtp_from_name" id="smtp_from_name" value="<?php echo get_sanitize_input($smtp['smtp_from_name']); ?>" class="frm_input" size="60"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="smtp_from_email">발신자 이메일</label></th>
                    <td><input type="text" name="smtp_from_email" id="smtp_from_email" value="<?php echo get_sanitize_input($smtp['smtp_from_email']); ?>" class="frm_input email" size="60"></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="btn_fixed_top">
        <input type="submit" value="저장" class="btn btn_01">
    </div>
</form>

<form name="fsmtptest" id="fsmtptest" method="post" action="<?php echo G5_ADMIN_URL; ?>/smtp_manager_test_mail.php" style="margin-top:20px;">
    <input type="hidden" name="token" value="<?php echo $form_token; ?>">

    <section>
        <h2>테스트 메일 발송</h2>
        <div class="local_desc02 local_desc">
            <p>현재 저장된 SMTP 설정으로 테스트 메일을 발송합니다.</p>
        </div>
        <div class="tbl_frm01 tbl_wrap">
            <table>
                <caption>테스트 메일 발송</caption>
                <colgroup>
                    <col class="grid_3">
                    <col>
                </colgroup>
                <tbody>
                    <tr>
                        <th scope="row"><label for="test_recipient">테스트 수신 이메일</label></th>
                        <td><input type="text" name="test_recipient" id="test_recipient" value="<?php echo get_sanitize_input($member['mb_email']); ?>" required class="frm_input email" size="60"></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="btn_confirm01 btn_confirm">
            <input type="submit" value="테스트 메일 발송" class="btn btn_02">
        </div>
    </section>
</form>

<?php
include_once(G5_ADMIN_PATH . '/admin.tail.php');
