<?php
class dimwwt_listener {
    public function __construct() {
        if (!isset($_GET['dimwwt'])) {
            return;
        }

        $this->parse();
        die();
    }

    /**
     * parse telegram request
     */
    public function parse() {
        $content = file_get_contents("php://input");

        /**
         * log last request
         */
        update_option('dimwwt_last_request', $content);

        $this->error_log('-');
        $update = json_decode($content, true);

        if (!$update) {
            $this->error_log("receive wrong update, must not happen");
            exit;
        }

        if (isset($update["message"])) {
            $this->processMessage($update["message"]);
        }

    }

    function get_chat($chat_id) {
        global $wpdb;
        $tbl = $wpdb->prefix . __dimwwt . '_requests';
        $res = $wpdb->get_row($wpdb->prepare("select * from $tbl where chat_id=%d", $chat_id));
        if ($res) {
            return $res;
        }

        /**
         * add to DB
         */
        $res = array(
            'chat_id' => $chat_id,
            'current_request' => '-',
            'request_time' => time(),
        );
        $ins = $wpdb->insert($tbl, $res, array('%s', '%s', '%s'));
        if ($ins) {
            return (object) $res;
        }

        return false;

    }
    /**
     * Update chat_request table
     */
    function update_chat_request($chat_id, $request) {
        global $wpdb;
        $tbl = $wpdb->prefix . __dimwwt . '_requests';
        $wpdb->update($tbl,
            array('current_request' => $request, 'request_time' => time()),
            array('chat_id' => $chat_id),
            array('%s', '%s'),
            array('%s')
        );
    }

    function exec_curl_request($handle) {
        $response = curl_exec($handle);

        if ($response === false) {
            $errno = curl_errno($handle);
            $error = curl_error($handle);
            $this->error_log("Curl returned error $errno: $error\n");
            curl_close($handle);
            return false;
        }

        $http_code = intval(curl_getinfo($handle, CURLINFO_HTTP_CODE));
        curl_close($handle);

        if ($http_code >= 500) {
            // do not wat to DDOS server if something goes wrong
            sleep(10);
            return false;
        } else if ($http_code != 200) {
            $response = json_decode($response, true);
            $this->error_log("Request has failed with error {$response['error_code']}: {$response['description']}\n");
            if ($http_code == 401) {
                throw new Exception('Invalid access token provided');
            }
            return false;
        } else {
            $response = json_decode($response, true);
            if (isset($response['description'])) {
                $this->error_log("Request was successfull: {$response['description']}\n");
            }
            $response = $response['result'];
        }

        return $response;
    }

    function apiRequest($method, $parameters) {
        if (!is_string($method)) {
            $this->error_log("Method name must be a string\n");
            return false;
        }

        if (!$parameters) {
            $parameters = array();
        } else if (!is_array($parameters)) {
            $this->error_log("Parameters must be an array\n");
            return false;
        }

        foreach ($parameters as $key => &$val) {
            // encoding to JSON array parameters, for example reply_markup
            if (!is_numeric($val) && !is_string($val)) {
                $val = json_encode($val);
            }
        }
        $url = dimwwt_admin_menu::get('bot-api-url') . 'bot' . dimwwt_admin_menu::get('bot-token') . '/' . $method . '?' . http_build_query($parameters);

        $handle = curl_init($url);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($handle, CURLOPT_TIMEOUT, 60);

        return $this->exec_curl_request($handle);
    }

    function processMessage($message) {
        // process incoming message
        $message_id = $message['message_id'];
        $chat_id = $message['chat']['id'];

        //keyboard
        $reply_markup = array(
            'keyboard' => array(array(__('Menu', __dimwwt), __('Last Posts', __dimwwt), __('Search', __dimwwt), 'R')),
            'one_time_keyboard' => false,
            'resize_keyboard' => true,
        );

        //woocommerce
        if (class_exists('WC_Payment_Gateway') || function_exists('edd_insert_payment')) {
            $reply_markup['keyboard'][1][] = __('Last Products', __dimwwt);
            $reply_markup['keyboard'][1][] = __('Search Products', __dimwwt);
        }

        if (isset($message['text'])) {
            // incoming text message
            $text = trim($message['text']);
            $chat = $this->get_chat($chat_id);

            /*
            جستجوی کلمه
             */
            if ($chat->current_request == __('Search', __dimwwt)) {
                //get string and return search results
                $return = '';
                wp_reset_postdata();
                $last_posts_query = new WP_Query(array('s' => $text, 'posts_per_page' => dimwwt_admin_menu::get('posts_per_page')));
                if ($last_posts_query->have_posts()) {
                    $return = 'نتیجه جستجو برای "' . $text . '"' . "\n";
                    while ($last_posts_query->have_posts()) {
                        $last_posts_query->the_post();
                        $return .= get_the_title() . "\n" . site_url() . '/?p=' . get_the_ID() . "\n\n\n";
                    }
                    wp_reset_postdata();
                }

                if (trim($return) == '') {
                    $return = $text . ":" . __('No Results :( ', __dimwwt);
                }
                $this->apiRequestWebhook("sendMessage", array('chat_id' => $chat_id, "text" => $return, 'reply_markup' => $reply_markup));
                $this->update_chat_request($chat_id, '-');
                die();

            }
            /*
            جستجو در محصولات
             */
            if ($chat->current_request == __('Search Products', __dimwwt)) {
                //get string and return search results
                $return = '';
                wp_reset_postdata();
                $last_posts_query = new WP_Query(array('post_type' => 'product', 's' => $text, 'posts_per_page' => dimwwt_admin_menu::get('posts_per_page')));
                if ($last_posts_query->have_posts()) {
                    $return = __('Search Resuls for ', __dimwwt) . '"' . $text . '"' . "\n";
                    while ($last_posts_query->have_posts()) {
                        $last_posts_query->the_post();
                        $return .= get_the_title() . "\n" . site_url() . '/?p=' . get_the_ID() . "\n\n\n";
                    }
                    wp_reset_postdata();
                }

                if (trim($return) == '') {
                    $return = $text . ":" . __('No Results :( ', __dimwwt);
                }
                $this->apiRequestWebhook("sendMessage", array('chat_id' => $chat_id, "text" => $return, 'reply_markup' => $reply_markup));
                $this->update_chat_request($chat_id, '-');
                die();

            }

            /*
            آخرین مطالب د سته
             */

            if ($chat->current_request == 'جستجوی دسته ها') {
                //get string and return search results
                $return = '';
                $cat_id = get_cat_ID($text);
                $args = array('cat' => array($cat_id), 'posts_per_page' => dimwwt_admin_menu::get('posts_per_page'));
                if ($text == __('All Categories', __dimwwt)) {
                    unset($args['cat']);
                }
                $last_posts_query = new WP_Query($args);
                if ($last_posts_query->have_posts()) {
                    $return = __('Lasts From ', __dimwwt) . '"' . $text . '"' . "\n";
                    while ($last_posts_query->have_posts()) {
                        $last_posts_query->the_post();
                        $return .= get_the_title() . "\n" . site_url() . '/?p=' . get_the_ID() . "\n\n\n";
                    }
                    wp_reset_postdata();
                }
                if (trim($return) == '') {
                    $return = __('No Results :( ', __dimwwt);
                }
                $this->apiRequestWebhook("sendMessage", array('chat_id' => $chat_id, "text" => $return, 'reply_markup' => $reply_markup));
                $this->update_chat_request($chat_id, '-');
                die();

            }

            /*
            جستجوی محصولات
             */
            if ($chat->current_request == 'جستجوی محصولات') {
                if (!(class_exists('WC_Payment_Gateway') || function_exists('edd_insert_payment'))) {
                    return;
                }

                //get string and return search results
                $return = '';
                $args = array(
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'product_cat',
                            'field' => 'name',
                            'terms' => $text,
                        ),
                    ),
                    'post_type' => 'product',
                    'posts_per_page' => dimwwt_admin_menu::get('posts_per_page'),
                );
                //edd
                if (function_exists('edd_insert_payment')) {
                    $args['post_type'] = 'download';
                    $args['tax_query']['taxonomy'] = 'download_category';
                }
                if ($text == __('All Categories', __dimwwt)) {
                    unset($args['tax_query']);
                    $args['taxonomy'] = 'product_cat';
                    //edd
                    if (function_exists('edd_insert_payment')) {
                        $args['taxonomy'] = 'download_category';
                    }
                }
                $last_posts_query = new WP_Query($args);
                if ($last_posts_query->have_posts()) {
                    $return = __('Lasts Products ', __dimwwt) . ' "' . $text . '"' . "\n";
                    while ($last_posts_query->have_posts()) {
                        $last_posts_query->the_post();
                        $return .= get_the_title() . "\n" . site_url() . '/?p=' . get_the_ID() . "\n\n\n";
                    }
                    wp_reset_postdata();
                }
                if (trim($return) == '') {
                    $return = __('No Results :( ', __dimwwt);
                }
                $this->apiRequestWebhook("sendMessage", array('chat_id' => $chat_id, "text" => $return, 'reply_markup' => $reply_markup));
                $this->update_chat_request($chat_id, '-');
                die();

            }

            switch ($text) {
            case __('Last Posts', __dimwwt): {
                    $return = '';
                    $return = __('Please select a category', __dimwwt);
                    $allowed_cats = dimwwt_admin_menu::get('bot-allowed-cats');
                    $cats = array();
                    foreach ($allowed_cats as $ac) {
                        $cats[] = get_cat_name($ac);
                    }
                    $cats = array_chunk($cats, 2);
                    array_unshift($cats, array(__('All Categories', __dimwwt)));
                    $reply_markup = array(
                        'keyboard' => ($cats),
                        'one_time_keyboard' => false,
                        'resize_keyboard' => true);
                    $this->apiRequestWebhook("sendMessage", array('chat_id' => $chat_id, "text" => $return, 'reply_markup' => $reply_markup));
                    $this->update_chat_request($chat_id, 'جستجوی دسته ها');
                }break;
            case __('Last Products', __dimwwt): {
                    if (!(class_exists('WC_Payment_Gateway') || function_exists('edd_insert_payment'))) {
                        return;
                    }

                    $return = '';
                    $return = __('Please select a category', __dimwwt);
                    $allowed_cats = dimwwt_admin_menu::get('bot-shop-allowed-cats');
                    $cats = array();
                    foreach ($allowed_cats as $ac) {
                        $cats[] = get_cat_name($ac);
                    }
                    $cats = array_chunk($cats, 2);
                    array_unshift($cats, array(__('All Categories', __dimwwt)));
                    $reply_markup = array(
                        'keyboard' => ($cats),
                        'one_time_keyboard' => false,
                        'resize_keyboard' => true);
                    $this->apiRequestWebhook("sendMessage", array('chat_id' => $chat_id, "text" => $return, 'reply_markup' => $reply_markup));
                    $this->update_chat_request($chat_id, 'جستجوی محصولات');
                }break;
            case __('Search', __dimwwt): {

                    $this->apiRequestWebhook("sendMessage", array('chat_id' => $chat_id, "text" => __('Enter Keyword', __dimwwt), 'reply_markup' => $reply_markup));
                    $this->update_chat_request($chat_id, __('Search', __dimwwt));
                }break;
            case __('Search Products', __dimwwt): {

                    $this->apiRequestWebhook("sendMessage", array('chat_id' => $chat_id, "text" => __('Enter Keyword', __dimwwt), 'reply_markup' => $reply_markup));
                    $this->update_chat_request($chat_id, __('Search Products', __dimwwt));
                }break;
            case 'R':{
                    //randomp post
                    $return = '';
                    $args = array('orderby' => 'rand', 'posts_per_page' => 1);
                    $last_posts_query = new WP_Query($args);
                    if ($last_posts_query->have_posts()) {
                        while ($last_posts_query->have_posts()) {
                            $last_posts_query->the_post();
                            $return .= get_the_title() . "\n" . get_the_content() . "\n\n\n";
                        }
                        wp_reset_postdata();
                    }
                    $this->apiRequestWebhook("sendMessage", array('chat_id' => $chat_id, "text" => $return, 'reply_markup' => $reply_markup));
                    $this->update_chat_request($chat_id, '-');
                }break;

            default:
                {
                    $this->apiRequestJson("sendMessage",
                        array('chat_id' => $chat_id, "text" => dimwwt_admin_menu::get('bot-welcome-msg'),
                            'reply_markup' => $reply_markup,
                        )
                    );
                }
                break;
            }

        }
    }

    function error_log($msg) {
        update_option('dimwwt_last_error', time() . " : " . $msg);
    }
}
