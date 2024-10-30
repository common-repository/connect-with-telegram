<?php
/**
 * Plugin Name:Connect With Telegram
 * Description: Connect Your Site To Telegram,user can search and view your last posts / products from telegram
 * Author:Omid Shamloo
 * Plugin URI:http://wp-master.ir
 * Author URI:http://wp-master.ir
 * Version:1.1
 * Text Domain: dimwwt
 */

class dimwwt {
    public $version = 1.0;
    public $menu_slug;
    protected static $_instance = null;
    public $bank_lists = array();

    public function __construct() {
        $this->define_constants();
        $this->includes();
        $this->init_hooks();

        do_action('dimwwt_loaded');
    }

    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'install'));
        register_deactivation_hook(__FILE__, array($this, 'uninstall'));
        add_action('init', array($this, 'init'));
        add_action('plugins_loaded', array($this, 'plugins_loaded'));
        add_action('wp_enqueue_scripts', array($this, 'wp_enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'wp_enqueue_scripts'));

    }

    private function define_constants() {

        $this->define('__dimwwt', 'dimwwt');
        $this->define('__dimwwt_dir', realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR);
        $this->define('__dimwwt_url', plugin_dir_url(__FILE__));
        $this->define('__dimwwt_ver', $this->version);
    }

    /**
     * init wp actions goes here
     */
    public function init() {
        new dimwwt_admin_menu();

        /**
         * wich mode classes must be loaded
         */
        $mode = dimwwt_admin_menu::get('bot-mode');
        if (trim($mode) == '') {
            $mode = 'telegram';
        }

        $class_name = "dimwwt_{$mode}_listener";
        new $class_name();

    }
    /**
     * define constracts
     * @param  string $name
     * @param  string|int $value
     * @return null
     */
    private function define($name, $value) {
        if (!defined($name)) {
            define($name, $value);
        }
    }
    /**
     * Include required core files used in admin and on the frontend.
     */
    public function includes() {
        include_once __dimwwt_dir . 'inc' . DIRECTORY_SEPARATOR . 'class-auto-loader.php';
        require_once __dimwwt_dir . 'inc' . DIRECTORY_SEPARATOR . 'functions.php';
    }
    /**
     * Load Language files
     */
    public function plugins_loaded() {
        load_plugin_textdomain(__dimwwt, false, dirname(plugin_basename(__FILE__)) . DIRECTORY_SEPARATOR);

        __('Connect With Telegram', __dimwwt);
        __('Omid Shamloo', __dimwwt);
        __('Connect Your Site To Telegram,user can search and view your last posts / products from telegram', __dimwwt);
    }
    /**
     * add style . scripts to admin oage
     * @return null
     */
    public function wp_enqueue_scripts() {
        // wp_register_style('dimwwt-style', __dimwwt_url . 'assets/css/style.css');
        // wp_enqueue_style('dimwwt-style');

        // wp_register_script('dimwwt-js', __dimwwt_url . 'assets/js/script.js', array('jquery'));

        if (is_admin()) {
            wp_register_style('dimwwt-styleadmin', __dimwwt_url . 'assets/css/admin.css');
            wp_enqueue_style('dimwwt-styleadmin');
            wp_register_script('dimwwt-js', __dimwwt_url . 'assets/js/admin.js', array('jquery'));

        }
        /**
         * Localizes Scripts
         */
        $translation_array = array(
            'ajaxurl' => admin_url('admin-ajax.php'),
        );
        wp_localize_script('dimwwt-js', 'dimwwt', $translation_array);
        wp_enqueue_script('dimwwt-js');
    }
    function install() {
        new dimwwt_install();
    }
    function uninstall() {}

}
new dimwwt();
