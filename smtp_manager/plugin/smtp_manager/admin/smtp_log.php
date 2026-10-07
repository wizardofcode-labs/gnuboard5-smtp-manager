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

<div id="smtp-mail-viewer" role="dialog" aria-modal="true" aria-labelledby="smtp-mail-viewer-title"
     tabindex="-1" hidden>
    <div id="smtp-mail-viewer-panel">
        <div id="smtp-mail-viewer-header">
            <h2 id="smtp-mail-viewer-title">메일 본문 보기</h2>
            <button type="button" id="smtp-mail-viewer-close" aria-label="메일 본문 보기 닫기">닫기</button>
        </div>
        <div id="smtp-mail-viewer-body" aria-live="polite"></div>
    </div>
</div>

<style>
#smtp-mail-viewer[hidden] { display: none; }
#smtp-mail-viewer {
    position: fixed;
    inset: 0;
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
    background: rgba(0, 0, 0, .5);
}
#smtp-mail-viewer-panel {
    display: flex;
    flex-direction: column;
    width: 900px;
    max-width: 100%;
    max-height: calc(100vh - 32px);
    overflow: hidden;
    background: #fff;
    color: #222;
    border-radius: 6px;
    box-shadow: 0 8px 30px rgba(0, 0, 0, .25);
}
#smtp-mail-viewer-header {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 16px 20px;
    border-bottom: 1px solid #ddd;
}
#smtp-mail-viewer-title { margin: 0; font-size: 18px; }
#smtp-mail-viewer-close {
    padding: 6px 12px;
    border: 1px solid #bbb;
    border-radius: 4px;
    background: #fff;
    color: #222;
    cursor: pointer;
}
#smtp-mail-viewer-body {
    min-height: 0;
    overflow: auto;
    padding: 20px;
    overflow-wrap: anywhere;
}
#smtp-mail-viewer-body .smtp-mail-viewer-error { color: #cc0000; padding: 20px 0; }
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
    var viewUrl = <?php echo json_encode(G5_ADMIN_URL . '/smtp_manager_log_view.php', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    var viewer = document.getElementById('smtp-mail-viewer');
    var bodyEl = document.getElementById('smtp-mail-viewer-body');
    var closeButton = document.getElementById('smtp-mail-viewer-close');
    var activeRequest = null;
    var returnFocus = null;
    var previousOverflow = '';

    function cancelRequest() {
        if (activeRequest) {
            var request = activeRequest;
            activeRequest = null;
            request.abort();
        }
    }

    function closeViewer() {
        cancelRequest();
        viewer.hidden = true;
        bodyEl.innerHTML = '';
        document.body.style.overflow = previousOverflow;
        if (returnFocus) returnFocus.focus();
    }

    function showError(message) {
        bodyEl.innerHTML = '<p class="smtp-mail-viewer-error" role="alert">' + escapeHtml(message) + '</p>';
    }

    closeButton.addEventListener('click', closeViewer);
    viewer.addEventListener('click', function (e) {
        if (e.target === viewer) closeViewer();
    });
    function handleViewerKeydown(e) {
        if (viewer.hidden) return;
        if (e.key === 'Escape') {
            e.preventDefault();
            closeViewer();
        } else if (e.key === 'Tab') {
            // 본문 iframe과 닫기 버튼 사이에서 키보드 포커스를 유지합니다.
            var iframe = document.getElementById('smtp-mail-viewer-iframe');
            if (!iframe || (e.shiftKey && document.activeElement === closeButton)) {
                e.preventDefault();
                (iframe || closeButton).focus();
            } else if (!e.shiftKey && document.activeElement === iframe) {
                e.preventDefault();
                closeButton.focus();
            } else if (!viewer.contains(document.activeElement)) {
                e.preventDefault();
                closeButton.focus();
            }
        }
    }
    document.addEventListener('keydown', handleViewerKeydown);

    document.addEventListener('click', function (e) {
        var anchor = e.target.closest('.smtp-log-subject');
        if (!anchor) return;
        e.preventDefault();

        cancelRequest();
        returnFocus = anchor;
        if (viewer.hidden) previousOverflow = document.body.style.overflow;
        viewer.hidden = false;
        document.body.style.overflow = 'hidden';
        bodyEl.innerHTML = '<div id="smtp-mail-viewer-loading">불러오는 중...</div>';
        closeButton.focus();

        var xhr = new XMLHttpRequest();
        activeRequest = xhr;
        xhr.open('GET', viewUrl + '?id=' + encodeURIComponent(anchor.dataset.id), true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.timeout = 30000;
        xhr.onload = function () {
            if (activeRequest !== xhr) return;
            activeRequest = null;
            var data;
            try { data = JSON.parse(xhr.responseText); } catch (err) {
                showError(xhr.status === 200
                    ? '응답을 파싱할 수 없습니다. 관리자 로그인 상태를 확인해 주세요.'
                    : '불러오기에 실패했습니다. (HTTP ' + xhr.status + ')');
                return;
            }
            if (data && typeof data.error === 'string' && data.error) {
                showError(data.error);
                return;
            }
            if (xhr.status !== 200) {
                showError('불러오기에 실패했습니다. (HTTP ' + xhr.status + ')');
                return;
            }
            if (!data || typeof data !== 'object' || typeof data.content !== 'string') {
                showError('올바른 메일 본문 응답이 아닙니다.');
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

            // 스크립트 실행을 허용하지 않는 iframe에 메일 본문을 격리합니다.
            bodyEl.innerHTML = metaHtml +
                '<iframe id="smtp-mail-viewer-iframe" title="메일 본문" srcdoc="" ' +
                'sandbox="allow-same-origin"></iframe>';

            var iframe = document.getElementById('smtp-mail-viewer-iframe');
            iframe.addEventListener('load', function () {
                try {
                    var body = iframe.contentDocument && iframe.contentDocument.body;
                    if (body) {
                        iframe.contentDocument.addEventListener('keydown', handleViewerKeydown);
                        iframe.style.height = Math.max(body.scrollHeight + 20, 400) + 'px';
                    }
                } catch (ex) { /* cross-origin 예외 무시 */ }
            });
            iframe.srcdoc = data.content || '<p style="color:#aaa;text-align:center;padding:30px;">본문 내용이 없습니다.</p>';
        };
        xhr.onerror = function () {
            if (activeRequest !== xhr) return;
            activeRequest = null;
            showError('네트워크 오류가 발생했습니다.');
        };
        xhr.ontimeout = function () {
            if (activeRequest !== xhr) return;
            activeRequest = null;
            showError('요청 시간이 초과되었습니다. 다시 시도해 주세요.');
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
