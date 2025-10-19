<?php
/**
 * Plugin Name: PWA 3DS Helper
 * Description: Mantém o checkout do WooCommerce estável em PWAs durante 3DS (PagBank), restaura dados, redireciona para "pedido finalizado" e registra logs.
 * Author: Ricardo. Z. Vendramini
 * Version: 3.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) { exit; }

class PWA3DS_Helper {
    const OPT_GROUP = 'pwa3ds_options';
    const OPT_TIMEOUT = 'pwa3ds_timeout_seconds';
    const OPT_DEBUG   = 'pwa3ds_debug_enabled';
    const OPT_LOGFILE = 'pwa3ds_logfile';
    const COOKIE_CID  = 'pwa3ds_cid';
    const STORE_KEY   = 'pwa3ds_store_'; // prefixo por CID
    const CRON_HOOK   = 'pwa3ds_cron_cleanup';
    const LOG_RETENTION_HOURS = 24;
    const DEFAULT_TIMEOUT = 10;

    public function __construct() {
        add_action('init',                 [$this, 'maybe_set_client_cookie']);
        add_action('init',                 [$this, 'maybe_schedule_cron']);
        add_action('admin_menu',           [$this, 'admin_menu']);
        add_action('admin_init',           [$this, 'register_settings']);
        add_action('wp_enqueue_scripts',   [$this, 'enqueue_front']);
        add_action('rest_api_init',        [$this, 'register_rest']);
        add_action(self::CRON_HOOK,        [$this, 'cron_cleanup']);
        add_action('admin_post_pwa3ds_clear_logs', [$this, 'handle_clear_logs']);
    }

    /** Util **/
    private function get_log_path() {
        $path = get_option(self::OPT_LOGFILE);
        if (!$path) {
            $upload_dir = wp_upload_dir();
            $path = trailingslashit($upload_dir['basedir']) . 'pwa3ds-logs.log';
            update_option(self::OPT_LOGFILE, $path, false);
        }
        return $path;
    }

    private function write_log($tag, $data = [], $ctx = []) {
        if (get_option(self::OPT_DEBUG, 'yes') !== 'yes') return;
        $path = $this->get_log_path();
        $dir = dirname($path);
        if (!file_exists($dir)) @wp_mkdir_p($dir);

        // sanitize sensitive fields recursively
        $sanitize = function($arr) use (&$sanitize) {
            if (!is_array($arr)) return $arr;
            $masked = [];
            foreach ($arr as $k => $v) {
                if (is_array($v)) { $masked[$k] = $sanitize($v); continue; }
                $kk = strtolower($k);
                if (preg_match('/(card|cvc|cvv|senha|password|token|number|cpf)/', $kk)) {
                    $masked[$k] = '***';
                } else {
                    $masked[$k] = $v;
                }
            }
            return $masked;
        };
        $data = $sanitize($data);
        $ctx  = $sanitize($ctx);

        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
        $line = sprintf('[%s] %s | %s | UA=%s | IP=%s',
            gmdate('Y-m-d H:i:s'),
            $tag,
            json_encode($data, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),
            $ua,
            $ip
        );
        @file_put_contents($path, $line . PHP_EOL, FILE_APPEND);
    }

    private function get_cid() {
        return isset($_COOKIE[self::COOKIE_CID]) ? sanitize_text_field($_COOKIE[self::COOKIE_CID]) : '';
    }
    private function get_store_key($cid) { return self::STORE_KEY . $cid; }

    public function maybe_set_client_cookie() {
        if (isset($_COOKIE[self::COOKIE_CID]) && $_COOKIE[self::COOKIE_CID]) return;
        $cid = wp_generate_uuid4();
        setcookie(self::COOKIE_CID, $cid, time() + MONTH_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true);
        $_COOKIE[self::COOKIE_CID] = $cid;
    }

    public function enqueue_front() {
        if (!class_exists('WooCommerce')) return;
        if (!is_cart() && !is_checkout()) return;
        $timeout = intval(get_option(self::OPT_TIMEOUT, self::DEFAULT_TIMEOUT));
        wp_enqueue_script('pwa3ds-js', plugins_url('assets/pwa3ds.js', __FILE__), ['jquery'], '3.0.0', true);
        wp_localize_script('pwa3ds-js', 'PWA3DS', [
            'timeout' => $timeout,
            'rest'    => [
                'beacon' => esc_url_raw(rest_url('pwa3ds/v1/beacon')),
                'health' => esc_url_raw(rest_url('pwa3ds/v1/health'))
            ],
            'nonce' => wp_create_nonce('wp_rest')
        ]);
        wp_enqueue_style('pwa3ds-css', plugins_url('assets/pwa3ds.css', __FILE__), [], '3.0.0');
    }

    /** Admin **/
    public function admin_menu() {
        add_menu_page('PWA 3DS Config', 'PWA 3DS Config', 'manage_options', 'pwa3ds-config', [$this, 'page_config'], 'dashicons-smartphone', 56);
        add_submenu_page('pwa3ds-config', 'Logs', 'Logs', 'manage_options', 'pwa3ds-logs', [$this, 'page_logs']);
    }

    public function register_settings() {
        register_setting(self::OPT_GROUP, self::OPT_TIMEOUT, ['type'=>'integer','default'=>self::DEFAULT_TIMEOUT]);
        register_setting(self::OPT_GROUP, self::OPT_DEBUG,   ['type'=>'string','default'=>'yes']);
        register_setting(self::OPT_GROUP, self::OPT_LOGFILE, ['type'=>'string','default'=>'']);
        add_settings_section('pwa3ds_main', 'Configurações', function(){
            echo '<p>Ajuste o tempo de espera e o modo de log.</p>';
        }, 'pwa3ds-config');
        add_settings_field(self::OPT_TIMEOUT, 'Tempo de espera (segundos)', function(){
            $v = esc_attr(get_option(self::OPT_TIMEOUT, self::DEFAULT_TIMEOUT));
            echo '<input type="number" min="5" step="1" name="'.self::OPT_TIMEOUT.'" value="'.$v.'" />';
        }, 'pwa3ds-config', 'pwa3ds_main');
        add_settings_field(self::OPT_DEBUG, 'Debug (logs) ativado', function(){
            $v = get_option(self::OPT_DEBUG, 'yes');
            echo '<label><input type="checkbox" name="'.self::OPT_DEBUG.'" value="yes" '.checked($v,'yes',false).'/> Ativar</label>';
        }, 'pwa3ds-config', 'pwa3ds_main');
    }

    public function page_config() {
        if (!current_user_can('manage_options')) return;
        echo '<div class="wrap"><h1>PWA 3DS Config</h1><form method="post" action="options.php">';
        settings_fields(self::OPT_GROUP);
        do_settings_sections('pwa3ds-config');
        submit_button();
        echo '</form></div>';
    }

    public function page_logs() {
        if (!current_user_can('manage_options')) return;
        $path = $this->get_log_path();
        echo '<div class="wrap"><h1>PWA 3DS Logs</h1>';
        echo '<p><a class="button button-secondary" href="'.esc_url(admin_url('admin-post.php?action=pwa3ds_clear_logs')).'">Limpar log</a></p>';
        echo '<textarea style="width:100%;height:60vh" readonly>';
        if (file_exists($path)) { echo esc_textarea(file_get_contents($path)); }
        echo '</textarea>';
        echo '</div>';
    }

    public function handle_clear_logs() {
        if (!current_user_can('manage_options')) wp_die('Forbidden');
        $path = $this->get_log_path();
        @file_put_contents($path, '');
        wp_redirect(admin_url('admin.php?page=pwa3ds-logs&cleared=1'));
        exit;
    }

    /** REST **/
    public function register_rest() {
        register_rest_route('pwa3ds/v1', '/beacon', [
            'methods'  => 'POST',
            'callback' => [$this, 'rest_beacon'],
            'permission_callback' => '__return_true',
        ]);
        register_rest_route('pwa3ds/v1', '/health', [
            'methods'  => 'GET',
            'callback' => [$this, 'rest_health'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function rest_beacon(\WP_REST_Request $req) {
        $cid = $this->get_cid();
        $body = $req->get_json_params();
        $phase = isset($body['phase']) ? sanitize_text_field($body['phase']) : 'CLIENT';
        $data  = isset($body['data']) ? (array)$body['data'] : $body;
        // Salvar billing_email e última URL para correlação
        $email = '';
        if (isset($data['billing_email'])) $email = sanitize_email($data['billing_email']);
        if (isset($data['data']['billing_email'])) $email = sanitize_email($data['data']['billing_email']);
        $href = isset($body['href']) ? esc_url_raw($body['href']) : '';
        $store = [
            'email' => $email,
            'href'  => $href,
            'ts'    => time()
        ];
        if ($cid) {
            set_transient($this->get_store_key($cid), $store, 30 * MINUTE_IN_SECONDS);
        }
        $this->write_log('CLIENT', $body);
        return new \WP_REST_Response(['ok'=>true]);
    }

    public function rest_health(\WP_REST_Request $req) {
        $cid = $this->get_cid();
        $has_wc_session = false;
        $wc_session_len = 0;
        if (function_exists('WC') && WC()->session) {
            $has_wc_session = WC()->session->get_customer_id() ? true : false;
            $wc_session_len = $has_wc_session ? 32 : 0;
        }
        $order_url = $this->maybe_find_recent_paid_order_url($cid);
        $should_resubmit = !$order_url && !$has_wc_session; // se já temos URL do pedido, vamos redirecionar, senão tentamos resubmeter se sessao caiu
        $resp = [
            'has_wc_session' => $has_wc_session,
            'wc_session_len' => $wc_session_len,
            'should_resubmit'=> $should_resubmit,
            'order_received_url' => $order_url,
            'ts' => time(),
        ];
        $this->write_log('HEALTH', $resp);
        return new \WP_REST_Response($resp);
    }

    private function maybe_find_recent_paid_order_url($cid) {
        if (!class_exists('WC_Order')) return null;
        $email = '';
        if ($cid) {
            $store = get_transient($this->get_store_key($cid));
            if (is_array($store) && !empty($store['email'])) $email = sanitize_email($store['email']);
        }
        // Se usuário logado, tentar pelo ID primeiro
        $args = [
            'status' => ['processing','completed','on-hold'],
            'limit'  => 1,
            'orderby'=> 'date',
            'order'  => 'DESC',
            'return' => 'objects',
            'date_created' => '>' . (new DateTime('-30 minutes', wp_timezone()))->format('Y-m-d H:i:s'),
        ];
        $user_id = get_current_user_id();
        if ($user_id) {
            $args['customer'] = $user_id;
        } elseif ($email) {
            $args['billing_email'] = $email;
        } else {
            return null;
        }
        $orders = wc_get_orders($args);
        if ($orders && isset($orders[0])) {
            /** @var WC_Order $order */
            $order = $orders[0];
            $paid = in_array($order->get_status(), ['processing','completed','on-hold'], true);
            if ($paid) {
                return $order->get_checkout_order_received_url();
            }
        }
        return null;
    }

    /** CRON **/
    public function maybe_schedule_cron() {
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time() + 600, 'ten_minutes', self::CRON_HOOK);
        }
    }

    public function cron_cleanup() {
        $path = $this->get_log_path();
        if (!file_exists($path)) return;
        $max_size = 3 * 1024 * 1024;
        $age_limit = time() - (self::LOG_RETENTION_HOURS * HOUR_IN_SECONDS);
        $lines = @file($path, FILE_IGNORE_NEW_LINES);
        if (!$lines) return;
        $kept = [];
        foreach ($lines as $ln) {
            if (preg_match('/^\[(.*?)\]/', $ln, $m)) {
                $t = strtotime($m[1] . ' UTC');
                if ($t !== false && $t >= $age_limit) $kept[] = $ln;
            }
        }
        $content = implode(PHP_EOL, $kept);
        if (strlen($content) > $max_size) {
            $content = substr($content, -$max_size);
        }
        @file_put_contents($path, $content);
    }
}

new PWA3DS_Helper();

add_filter('cron_schedules', function($s) {
    if (!isset($s['ten_minutes'])) {
        $s['ten_minutes'] = ['interval' => 600, 'display' => __('Every 10 Minutes')];
    }
    return $s;
});
