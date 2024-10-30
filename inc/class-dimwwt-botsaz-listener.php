<?php
require_once __dimwwt_dir . 'inc' . DIRECTORY_SEPARATOR . 'class-dimwwt-listener.php';

/**
 * Class For Botsaz mode
 */
class dimwwt_botsaz_listener extends dimwwt_listener {
    function apiRequestWebhook($method, $parameters) {
        $url = dimwwt_admin_menu::get('bot-api-url') . 'sendMessage/';
        $content = array('api_key' => dimwwt_admin_menu::get('bot-token'));
        $parameters = array_merge($content, $parameters);
        $parameters['reply_markup'] = $this->buildKeyBoard($parameters['reply_markup']['keyboard']);
        $url = rtrim($url, '//');
        $url = $url . '/';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($parameters));
        curl_setopt($handle, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
        header("Content-Type: application/json");
        echo $result = curl_exec($ch);
        curl_close($ch);
        return true;
    }

    function apiRequestJson($method, $parameters) {
        $url = dimwwt_admin_menu::get('bot-api-url') . 'sendMessage/';
        $content = array('api_key' => dimwwt_admin_menu::get('bot-token'));
        $parameters = array_merge($content, $parameters);
        $parameters['reply_markup'] = $this->buildKeyBoard($parameters['reply_markup']['keyboard']);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($parameters));
        curl_setopt($handle, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
        header("Content-Type: application/json");
        echo $result = curl_exec($ch);
        curl_close($ch);
        return true;

    }
    function buildKeyBoard(array $options, $onetime = false, $resize = true, $selective = true) {
        $replyMarkup = array(
            'keyboard' => $options,
            'one_time_keyboard' => $onetime,
            'resize_keyboard' => $resize,
            'selective' => $selective,
        );
        $encodedMarkup = json_encode($replyMarkup, true);
        return $encodedMarkup;
    }
}