<?php
class dimwwt_install {
    function __construct() {
        global $wpdb;
        $charset_collate = 'DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci';

        $tbl = $wpdb->prefix . __dimwwt . '_requests';
        $sql[] = "CREATE TABLE IF NOT EXISTS $tbl (
			  chat_id				VARCHAR (100)			NOT NULL,
			  current_request	   	VARCHAR (100) 		  	NULL,
			  request_time	   		VARCHAR (100) 		  	NULL,
			    PRIMARY KEY (`chat_id`)

			) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        foreach ($sql as $s) {
            dbDelta($s);
        }

    }
}