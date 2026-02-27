<?php
if (!defined('_GNUBOARD_')) {
    define('G5_IS_ADMIN', true);
    include_once('../../../common.php');
}
if (!defined('_GNUBOARD_')) exit;

include_once(G5_ADMIN_PATH . '/admin.lib.php');
include_once(G5_PLUGIN_PATH . '/smtp_manager/lib/smtp.lib.php');

$sub_menu = '100941';
auth_check_menu($auth, $sub_menu, 'r');

if ($is_admin != 'super') {
    alert('최고관리자만 접근 가능합니다.');
}

$log_table = SMTP_MANAGER_MAIL_LOG_TABLE;

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) {
    $page = 1;
}

$rows = 30;
$from_record = ($page - 1) * $rows;
$total_count = 0;
$result = false;
$total_page = 1;

if (smtp_manager_table_exists($log_table)) {
    $row = sql_fetch(" select count(*) as cnt from `{$log_table}` ", false);
    $total_count = isset($row['cnt']) ? (int)$row['cnt'] : 0;
    $total_page = $total_count > 0 ? ceil($total_count / $rows) : 1;

    $sql = " select * from `{$log_table}` order by id desc limit {$from_record}, {$rows} ";
    $result = sql_query($sql, false);
}

$g5['title'] = '메일 발송 로그';
include_once(G5_ADMIN_PATH . '/admin.head.php');
?>

<div class="local_desc01 local_desc">
    <p>
        메일 발송 성공/실패 이력을 확인할 수 있습니다.<br>
        로그 테이블: <?php echo get_sanitize_input($log_table); ?>
    </p>
</div>

<?php $log_form_token = get_admin_token(); ?>
<form name="flogdelete" id="flogdelete" action="./smtp_log_delete.php" method="post">
<input type="hidden" name="token" value="<?php echo $log_form_token; ?>">

<div style="margin-bottom:8px;display:flex;align-items:center;gap:8px;">
    <button type="button" onclick="delete_selected_logs()"
            style="padding:5px 14px;background:#cc0000;color:#fff;border:none;border-radius:3px;cursor:pointer;font-size:13px;">
        선택 삭제
    </button>
    <span id="log_selected_count" style="font-size:13px;color:#666;">0개 선택됨</span>
</div>

<div class="tbl_head01 tbl_wrap">
    <table>
        <caption>메일 발송 로그 목록</caption>
        <thead>
            <tr>
                <th scope="col" style="width:36px;">
                    <input type="checkbox" id="chk_log_all" onclick="toggle_log_all(this)"
                           title="전체 선택/해제">
                </th>
                <th scope="col">발송 시간</th>
                <th scope="col">수신자</th>
                <th scope="col">제목</th>
                <th scope="col">상태</th>
                <th scope="col">오류 메시지</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($result) {
                for ($i = 0; $row = sql_fetch_array($result); $i++) {
                    $status = $row['status'] === 'success' ? 'success' : 'fail';
                    $log_id = (int)$row['id'];
                    ?>
                    <tr>
                        <td class="td_num">
                            <input type="checkbox" name="del_ids[]"
                                   value="<?php echo $log_id; ?>"
                                   class="log_chk"
                                   onchange="update_log_selected_count()">
                        </td>
                        <td class="td_datetime"><?php echo get_sanitize_input($row['created_at']); ?></td>
                        <td class="td_left"><?php echo get_sanitize_input($row['recipient']); ?></td>
                        <td class="td_left">
                            <a href="#" class="smtp-log-subject"
                               data-id="<?php echo $log_id; ?>"
                               data-subject="<?php echo htmlspecialchars($row['subject'], ENT_QUOTES, 'UTF-8'); ?>"
                               title="클릭하면 메일 본문을 확인할 수 있습니다"
                               style="text-decoration:underline; cursor:pointer;">
                                <?php echo get_sanitize_input($row['subject']); ?>
                            </a>
                        </td>
                        <td class="td_num"><?php echo $status === 'success' ? '<span style="color:#0066cc;">success</span>' : '<span style="color:#cc0000;">fail</span>'; ?></td>
                        <td class="td_left"><?php echo get_sanitize_input($row['error_message']); ?></td>
                    </tr>
                    <?php
                }

                if (!$i) {
                    echo '<tr><td colspan="6" class="empty_table">로그가 없습니다.</td></tr>';
                }
            } else {
                echo '<tr><td colspan="6" class="empty_table">로그 테이블이 없습니다. install.php를 먼저 실행해 주세요.</td></tr>';
            }
            ?>
        </tbody>
    </table>
</div>
</form>

<?php if ($total_count > $rows) { ?>
<div class="pg_wrap">
    <?php echo get_paging($config['cf_write_pages'], $page, $total_page, G5_PLUGIN_URL . '/smtp_manager/admin/smtp_log.php?page='); ?>
</div>
<?php } ?>

<style>
#smtp-mail-viewer-iframe {
    width: 100%;
    min-height: 400px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background: #fff;
}
#smtp-mail-viewer-meta {
    margin-bottom: 12px;
    padding: 10px 14px;
    background: #f7f7f7;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    font-size: 13px;
    line-height: 1.8;
}
#smtp-mail-viewer-meta strong {
    display: inline-block;
    width: 60px;
    color: #555;
}
#smtp-mail-viewer-loading {
    text-align: center;
    padding: 40px 0;
    color: #888;
    font-size: 14px;
}
</style>

<script>
function toggle_log_all(obj) {
    var chks = document.querySelectorAll('.log_chk');
    for (var i = 0; i < chks.length; i++) {
        chks[i].checked = obj.checked;
    }
    update_log_selected_count();
}

function update_log_selected_count() {
    var checked = document.querySelectorAll('.log_chk:checked').length;
    document.getElementById('log_selected_count').textContent = checked + '개 선택됨';
    // 전체선택 체크박스 상태 동기화
    var all = document.querySelectorAll('.log_chk').length;
    var chkAll = document.getElementById('chk_log_all');
    if (chkAll) { chkAll.checked = (all > 0 && checked === all); }
}

function delete_selected_logs() {
    var chks = document.querySelectorAll('.log_chk:checked');
    if (chks.length === 0) {
        alert('삭제할 항목을 선택해 주세요.');
        return;
    }
    if (!confirm(chks.length + '개의 로그를 삭제하시겠습니까?\n이 작업은 취소할 수 없습니다.')) {
        return;
    }
    document.getElementById('flogdelete').submit();
}
</script>

<script>
(function () {
    var viewUrl = '<?php echo G5_ADMIN_URL; ?>/smtp_manager_log_view.php';

    document.addEventListener('click', function (e) {
        var anchor = e.target.closest('.smtp-log-subject');
        if (!anchor) return;
        e.preventDefault();

        var logId  = anchor.dataset.id;
        var subject = anchor.dataset.subject;

        // 팝업 열고 로딩 표시
        PopupManager.render(
            '메일 본문 보기',
            '<div id="smtp-mail-viewer-loading">불러오는 중...</div>',
            ''
        );

        // AJAX 요청
        var xhr = new XMLHttpRequest();
        xhr.open('GET', viewUrl + '?id=' + encodeURIComponent(logId), true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.onload = function () {
            if (xhr.status !== 200) {
                document.getElementById('popupBody').innerHTML =
                    '<p style="color:red;padding:20px;">불러오기에 실패했습니다. (HTTP ' + xhr.status + ')</p>';
                return;
            }
            var data;
            try { data = JSON.parse(xhr.responseText); } catch (err) {
                document.getElementById('popupBody').innerHTML =
                    '<p style="color:red;padding:20px;">응답을 파싱할 수 없습니다.</p>';
                return;
            }
            if (data.error) {
                document.getElementById('popupBody').innerHTML =
                    '<p style="color:red;padding:20px;">' + data.error + '</p>';
                return;
            }

            var metaHtml =
                '<div id="smtp-mail-viewer-meta">' +
                    '<div><strong>수신자</strong> ' + escapeHtml(data.recipient) + '</div>' +
                    '<div><strong>제목</strong> '   + escapeHtml(data.subject)   + '</div>' +
                    '<div><strong>발송</strong> '   + escapeHtml(data.created_at) + '</div>' +
                    '<div><strong>상태</strong> '   +
                        (data.status === 'success'
                            ? '<span style="color:#0066cc;">success</span>'
                            : '<span style="color:#cc0000;">fail</span>') +
                        (data.error_message ? ' &nbsp;— ' + escapeHtml(data.error_message) : '') +
                    '</div>' +
                '</div>';

            // 메일 본문은 iframe srcdoc으로 격리하여 렌더링
            var iframeHtml =
                '<iframe id="smtp-mail-viewer-iframe" srcdoc="" frameborder="0" ' +
                'sandbox="allow-same-origin" scrolling="auto"></iframe>';

            var bodyEl = document.getElementById('popupBody');
            bodyEl.innerHTML = metaHtml + iframeHtml;

            // srcdoc 직접 할당 (XSS 격리)
            var iframe = document.getElementById('smtp-mail-viewer-iframe');
            iframe.srcdoc = data.content || '<p style="color:#aaa;text-align:center;padding:30px;">본문 내용이 없습니다.</p>';

            // iframe 높이 자동 조절
            iframe.addEventListener('load', function () {
                try {
                    var body = iframe.contentDocument && iframe.contentDocument.body;
                    if (body) {
                        iframe.style.height = Math.max(body.scrollHeight + 20, 200) + 'px';
                    }
                } catch (ex) { /* cross-origin 예외 무시 */ }
            });
        };
        xhr.onerror = function () {
            document.getElementById('popupBody').innerHTML =
                '<p style="color:red;padding:20px;">네트워크 오류가 발생했습니다.</p>';
        };
        xhr.send();
    });

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
}());
</script>

<?php
include_once(G5_ADMIN_PATH . '/admin.tail.php');
