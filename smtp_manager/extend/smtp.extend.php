<?php
if (!defined('_GNUBOARD_')) exit;

include_once(G5_PLUGIN_PATH . '/smtp_manager/lib/smtp.lib.php');

$smtp = smtp_manager_get_config();

if (!empty($smtp['smtp_use']) && !empty($smtp['smtp_host'])) {
    smtp_manager_apply_defines($smtp);
}

add_replace('admin_menu', 'smtp_manager_hook_admin_menu', 10, 1);
add_replace('mail_options', 'smtp_manager_hook_mail_options', 10, 10);
add_event('mail_send_result', 'smtp_manager_hook_mail_send_result', 10, 5);
