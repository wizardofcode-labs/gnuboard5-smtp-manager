<?php
if (!defined('_GNUBOARD_')) exit;

if (!defined('SMTP_MANAGER_MAIL_LOG_TABLE')) {
    define('SMTP_MANAGER_MAIL_LOG_TABLE', G5_TABLE_PREFIX . 'mail_log');
}

if (!function_exists('smtp_manager_config_fields')) {
    function smtp_manager_config_fields()
    {
        return array(
            'smtp_use' => "TINYINT(1) NOT NULL DEFAULT '0'",
            'smtp_host' => "VARCHAR(255) NOT NULL DEFAULT ''",
            'smtp_port' => "INT(11) NOT NULL DEFAULT '25'",
            'smtp_secure' => "VARCHAR(10) NOT NULL DEFAULT 'none'",
            'smtp_user' => "VARCHAR(255) NOT NULL DEFAULT ''",
            'smtp_pass' => "VARCHAR(255) NOT NULL DEFAULT ''",
            'smtp_from_name' => "VARCHAR(255) NOT NULL DEFAULT ''",
            'smtp_from_email' => "VARCHAR(255) NOT NULL DEFAULT ''"
        );
    }
}

if (!function_exists('smtp_manager_column_exists')) {
    function smtp_manager_column_exists($table, $column)
    {
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        $column = preg_replace('/[^a-zA-Z0-9_]/', '', $column);

        if (!$table || !$column) {
            return false;
        }

        $row = sql_fetch(" SHOW COLUMNS FROM `{$table}` LIKE '{$column}' ", false);

        return isset($row['Field']) && $row['Field'] === $column;
    }
}

if (!function_exists('smtp_manager_table_exists')) {
    function smtp_manager_table_exists($table)
    {
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);

        if (!$table) {
            return false;
        }

        $row = sql_fetch(" SHOW TABLES LIKE '{$table}' ", false);

        if (!$row || !is_array($row)) {
            return false;
        }

        return (bool)array_values($row)[0];
    }
}

if (!function_exists('smtp_manager_install_schema')) {
    function smtp_manager_install_schema()
    {
        global $g5;

        $result = array(
            'success' => true,
            'messages' => array()
        );

        $config_table = $g5['config_table'];

        foreach (smtp_manager_config_fields() as $column => $column_sql) {
            if (!smtp_manager_column_exists($config_table, $column)) {
                $sql = " ALTER TABLE `{$config_table}` ADD `{$column}` {$column_sql} ";
                sql_query($sql, true);
                $result['messages'][] = $column . ' 필드 추가 완료';
            }
        }

        $log_table = SMTP_MANAGER_MAIL_LOG_TABLE;
        $sql = " CREATE TABLE IF NOT EXISTS `{$log_table}` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                    `recipient` VARCHAR(255) NOT NULL DEFAULT '',
                    `subject` VARCHAR(255) NOT NULL DEFAULT '',
                    `content` MEDIUMTEXT NOT NULL,
                    `status` VARCHAR(20) NOT NULL DEFAULT 'fail',
                    `error_message` TEXT NULL,
                    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `idx_created_at` (`created_at`),
                    KEY `idx_recipient` (`recipient`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ";

        sql_query($sql, true);
        $result['messages'][] = $log_table . ' 테이블 확인/생성 완료';

        return $result;
    }
}

if (!function_exists('smtp_manager_get_config')) {
    function smtp_manager_get_config()
    {
        global $g5;

        $defaults = array(
            'smtp_use' => 0,
            'smtp_host' => '',
            'smtp_port' => '25',
            'smtp_secure' => 'none',
            'smtp_user' => '',
            'smtp_pass' => '',
            'smtp_from_name' => '',
            'smtp_from_email' => ''
        );

        if (!smtp_manager_column_exists($g5['config_table'], 'smtp_use')) {
            return $defaults;
        }

        $sql = " select smtp_use, smtp_host, smtp_port, smtp_secure, smtp_user, smtp_pass, smtp_from_name, smtp_from_email
                    from {$g5['config_table']}
                    limit 1 ";
        $row = sql_fetch($sql, false);

        if (!$row || !is_array($row)) {
            return $defaults;
        }

        return array_merge($defaults, $row);
    }
}

if (!function_exists('smtp_manager_update_config')) {
    function smtp_manager_update_config($data)
    {
        global $g5;

        if (!smtp_manager_column_exists($g5['config_table'], 'smtp_use')) {
            return false;
        }

        $smtp_use = isset($data['smtp_use']) ? (int)$data['smtp_use'] : 0;
        $smtp_host = isset($data['smtp_host']) ? trim($data['smtp_host']) : '';
        $smtp_port = isset($data['smtp_port']) ? (int)$data['smtp_port'] : 25;
        $smtp_secure = isset($data['smtp_secure']) ? trim($data['smtp_secure']) : 'none';
        $smtp_user = isset($data['smtp_user']) ? trim($data['smtp_user']) : '';
        $smtp_pass = isset($data['smtp_pass']) ? trim($data['smtp_pass']) : '';
        $smtp_from_name = isset($data['smtp_from_name']) ? trim($data['smtp_from_name']) : '';
        $smtp_from_email = isset($data['smtp_from_email']) ? trim($data['smtp_from_email']) : '';

        $smtp_secure = in_array($smtp_secure, array('none', 'ssl', 'tls')) ? $smtp_secure : 'none';

        $sql = " update {$g5['config_table']}
                    set smtp_use = '{$smtp_use}',
                        smtp_host = '" . sql_escape_string($smtp_host) . "',
                        smtp_port = '{$smtp_port}',
                        smtp_secure = '" . sql_escape_string($smtp_secure) . "',
                        smtp_user = '" . sql_escape_string($smtp_user) . "',
                        smtp_pass = '" . sql_escape_string($smtp_pass) . "',
                        smtp_from_name = '" . sql_escape_string($smtp_from_name) . "',
                        smtp_from_email = '" . sql_escape_string($smtp_from_email) . "' ";

        sql_query($sql, true);

        return true;
    }
}

if (!function_exists('smtp_manager_parse_recipients')) {
    function smtp_manager_parse_recipients($value)
    {
        $recipients = array();

        if (is_array($value)) {
            foreach ($value as $item) {
                $recipients = array_merge($recipients, smtp_manager_parse_recipients($item));
            }
            return $recipients;
        }

        $value = trim((string)$value);
        if ($value === '') {
            return $recipients;
        }

        $parts = preg_split('/[,;]+/', $value);

        foreach ($parts as $part) {
            $part = trim($part);
            if ($part && filter_var($part, FILTER_VALIDATE_EMAIL)) {
                $recipients[] = $part;
            }
        }

        return $recipients;
    }
}

if (!function_exists('smtp_manager_insert_log')) {
    function smtp_manager_insert_log($recipient, $subject, $content, $status, $error_message = '')
    {
        $table = SMTP_MANAGER_MAIL_LOG_TABLE;

        if (!smtp_manager_table_exists($table)) {
            return false;
        }

        $recipient = trim((string)$recipient);
        $subject = trim((string)$subject);
        $content = (string)$content;
        $status = ($status === 'success') ? 'success' : 'fail';
        $error_message = trim((string)$error_message);

        $sql = " insert into `{$table}`
                    set recipient = '" . sql_escape_string($recipient) . "',
                        subject = '" . sql_escape_string($subject) . "',
                        content = '" . sql_escape_string($content) . "',
                        status = '" . sql_escape_string($status) . "',
                        error_message = '" . sql_escape_string($error_message) . "',
                        created_at = '" . G5_TIME_YMDHIS . "' ";

        sql_query($sql, true);

        return true;
    }
}

if (!function_exists('smtp_manager_hook_admin_menu')) {
    function smtp_manager_hook_admin_menu($menu)
    {
        if (!isset($menu['menu100']) || !is_array($menu['menu100'])) {
            return $menu;
        }

        $menu['menu100'][] = array('100940', 'SMTP 설정', G5_ADMIN_URL . '/smtp_manager_config.php', 'cf_smtp_manager');
        $menu['menu100'][] = array('100941', '메일 발송 로그', G5_PLUGIN_URL . '/smtp_manager/admin/smtp_log.php', 'cf_smtp_log');

        return $menu;
    }
}

if (!function_exists('smtp_manager_apply_defines')) {
    function smtp_manager_apply_defines($smtp)
    {
        if (!defined('G5_SMTP')) {
            define('G5_SMTP', $smtp['smtp_host']);
        }

        if (!defined('G5_SMTP_PORT')) {
            define('G5_SMTP_PORT', (int)$smtp['smtp_port']);
        }

        if (!defined('G5_SMTP_SECURE')) {
            define('G5_SMTP_SECURE', $smtp['smtp_secure']);
        }

        if (!defined('G5_SMTP_USER')) {
            define('G5_SMTP_USER', $smtp['smtp_user']);
        }

        if (!defined('G5_SMTP_PASS')) {
            define('G5_SMTP_PASS', $smtp['smtp_pass']);
        }

        if (!defined('G5_SMTP_FROM_NAME')) {
            define('G5_SMTP_FROM_NAME', $smtp['smtp_from_name']);
        }

        if (!defined('G5_SMTP_FROM_EMAIL')) {
            define('G5_SMTP_FROM_EMAIL', $smtp['smtp_from_email']);
        }
    }
}

if (!function_exists('smtp_manager_hook_mail_options')) {
    function smtp_manager_hook_mail_options($mail, $fname, $fmail, $to, $subject, $content, $type, $file, $cc, $bcc)
    {
        $smtp = smtp_manager_get_config();

        if (empty($smtp['smtp_use'])) {
            return $mail;
        }

        if (!empty($smtp['smtp_host'])) {
            $mail->Host = $smtp['smtp_host'];
        }

        if (!empty($smtp['smtp_port'])) {
            $mail->Port = (int)$smtp['smtp_port'];
        }

        if (!empty($smtp['smtp_user'])) {
            $mail->SMTPAuth = true;
            $mail->Username = $smtp['smtp_user'];
            $mail->Password = $smtp['smtp_pass'];
        } else {
            $mail->SMTPAuth = false;
        }

        if (!empty($smtp['smtp_secure']) && $smtp['smtp_secure'] !== 'none') {
            $mail->SMTPSecure = $smtp['smtp_secure'];
        }

        if (!empty($smtp['smtp_from_email']) && filter_var($smtp['smtp_from_email'], FILTER_VALIDATE_EMAIL)) {
            $mail->From = $smtp['smtp_from_email'];
        }

        if (!empty($smtp['smtp_from_name'])) {
            $mail->FromName = $smtp['smtp_from_name'];
        }

        return $mail;
    }
}

if (!function_exists('smtp_manager_hook_mail_send_result')) {
    function smtp_manager_hook_mail_send_result($mail_send_result, $mail, $to, $cc = '', $bcc = '')
    {
        $subject = '';
        $content = '';
        $error_message = '';

        if (is_object($mail)) {
            $subject = isset($mail->Subject) ? $mail->Subject : '';
            $content = isset($mail->Body) ? $mail->Body : '';
            $error_message = !empty($mail->ErrorInfo) ? $mail->ErrorInfo : '';
        }

        $status = $mail_send_result ? 'success' : 'fail';

        $recipients = array();
        $recipients = array_merge($recipients, smtp_manager_parse_recipients($to));
        $recipients = array_merge($recipients, smtp_manager_parse_recipients($cc));
        $recipients = array_merge($recipients, smtp_manager_parse_recipients($bcc));
        $recipients = array_values(array_unique($recipients));

        if (empty($recipients)) {
            $recipients[] = '';
        }

        foreach ($recipients as $recipient) {
            smtp_manager_insert_log($recipient, $subject, $content, $status, $error_message);
        }
    }
}

if (!function_exists('smtp_manager_send_test_mail')) {
    function smtp_manager_send_test_mail($recipient, &$error_message = '')
    {
        global $config;

        $recipient = trim($recipient);

        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $error_message = '올바른 테스트 수신 이메일을 입력해 주세요.';
            return false;
        }

        $smtp = smtp_manager_get_config();

        if (empty($smtp['smtp_use'])) {
            $error_message = 'SMTP 사용이 비활성화되어 있습니다. SMTP 사용을 체크 후 다시 시도해 주세요.';
            return false;
        }

        include_once(G5_LIB_PATH . '/mailer.lib.php');

        $from_name = $smtp['smtp_from_name'] ? $smtp['smtp_from_name'] : $config['cf_admin_email_name'];
        $from_email = ($smtp['smtp_from_email'] && filter_var($smtp['smtp_from_email'], FILTER_VALIDATE_EMAIL)) ? $smtp['smtp_from_email'] : $config['cf_admin_email'];

        $subject = '[SMTP 테스트] 메일 발송 확인';
        $content = '<p>SMTP Manager 테스트 메일입니다.</p><p>발송 시각 : ' . G5_TIME_YMDHIS . '</p>';

        $result = mailer($from_name, $from_email, $recipient, $subject, $content, 1);

        if (!$result) {
            $error_message = '테스트 메일 발송에 실패했습니다. 메일 발송 로그에서 오류 메시지를 확인해 주세요.';
            return false;
        }

        return true;
    }
}
