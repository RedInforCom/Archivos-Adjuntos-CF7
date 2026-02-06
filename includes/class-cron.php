<?php
if (!defined('ABSPATH')) exit;
class AACF7_Cron {
    public function __construct() {
        add_action('aacf7_cleanup_files', array($this, 'cleanup_old_files'));
    }
    public function cleanup_old_files() {
        // Implementación de limpieza
    }
}
