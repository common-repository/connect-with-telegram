<?php
	class dimwwt_admin_menu {
	    function __construct() {
	        add_action('admin_menu', array($this, '_admin_menu'));
	    }

	    function _admin_menu() {
	        add_options_page(__('Connect With Telegram', __dimwwt), __('Connect With Telegram', __dimwwt), 'manage_options', 'dimwwt-menu', array($this, 'admin_menu'));
	    }

	    function admin_menu() {
	        $modes = array('telegram' => array('api' => 'https://api.telegram.org/', 'label' => __('Telegram Bot Token', __dimwwt)), 'botsaz' => array('api' => 'https://panel.botsaz.com/api/bot/', 'label' => __('Botsaz Token', __dimwwt)));
	        if (isset($_POST['dimwwt'])) {
	            /**
	             * تصحیح آدرس
	             * Address Correction
	             */
	            $_POST['dimwwt']['bot-api-url'] = 'https://' . str_replace(array('https://', 'http://'), '', rtrim($_POST['dimwwt']['bot-api-url'], '/')) . '/';

	            if (!isset($modes[$_POST['dimwwt']['bot-mode']])) {
	                $_POST['dimwwt']['bot-mode'] = 'telegram';
	            }
	            update_option('dimwwt', $_POST['dimwwt']);
	            $this->msg_success(__('Saved', 'dimwwt'));
	        }
        ?>
		<div class="dimwwt wrap">
        <h2><?php _e('Connect With Telegram', __dimwwt)?></h2>
			<form action="" method="post">
            <label>
                    <strong><?php _e('Webhook Link', __dimwwt);?>:</strong>
                    <code>
                        <?php
                        	echo get_bloginfo('url') . '/?dimwwt';
                                ?>
                    </code>
                </label>
                <hr>
                <label>
                    <strong><?php _e('Mode', 'dimwwt')?>:</strong>
                    <select id="dimwwt_bot_mode" name="dimwwt[bot-mode]">
                        <?php
                        	foreach ($modes as $mode => $url) {
                                    ?>
                                       <option data-token-label="<?php echo $url['label']; ?>" data-url="<?php echo $url['api']; ?>"<?php selected(dimwwt_admin_menu::get('bot-mode'), $mode, true);?> value="<?php echo $mode; ?>"><?php echo $mode; ?></option>

                            		<?php
                            			}
                            		        ?>
                     </select>
                </label>

				<label for="bot-welcome-msg">
					<strong><?php _e('Bot Welcome Message', __dimwwt);?></strong>
					<input type="text" name="dimwwt[bot-welcome-msg]" id="bot-welcome-msg" value="<?php echo dimwwt_admin_menu::get('bot-welcome-msg'); ?>">
				</label>
				<label for="bot-token">
					<strong id="bot-token-label"><?php _e('Bot Token', __dimwwt);?></strong>
					<input type="text" name="dimwwt[bot-token]" id="bot-token" value="<?php echo dimwwt_admin_menu::get('bot-token'); ?>">
				</label>
				<label for="bot-api-url">
					<strong><?php _e('API URL', __dimwwt);?></strong>
					<input type="text" name="dimwwt[bot-api-url]" id="bot-api-url" value="<?php echo dimwwt_admin_menu::get('bot-api-url'); ?>" placeholder="https://api.telegram.org/">
				</label>
				<label for="posts_per_page">
					<strong><?php _e('Posts per page', __dimwwt);?></strong>
					<input type="text" name="dimwwt[posts_per_page]" id="posts_per_page" value="<?php echo dimwwt_admin_menu::get('posts_per_page'); ?>" >
				</label>
				<label for="bot-allowed-cats">
					<strong><?php _e('Allowed Categories', __dimwwt);?></strong>
					<br>
					<?php
						$allowed_cats = dimwwt_admin_menu::get('bot-allowed-cats');
						        if (!$allowed_cats) {
						            $allowed_cats = array();
						        }

						        $cats = get_categories(
						            array(
						                'hide_empty' => 1,
						                'orderby' => 'name',
						            ));
						        foreach ($cats as $c) {
						            $checked = '';
						            if (in_array($c->term_id, $allowed_cats)) {
						                $checked = ' checked="checked" ';
						            }
						            echo '<p class="bot-allowed-cats"><strong>' . esc_attr($c->cat_name) . '</strong><input type="checkbox" ' . $checked . ' name="dimwwt[bot-allowed-cats][]"  value="' . $c->term_id . '"></p>';
						        }
					        ?>
				</label>
				<?php
					if (class_exists('WC_Payment_Gateway')) {
				            ?>
				<label for="shop-bot-allowed-cats">
					<strong><?php _e('Shop Allowed Categories', __dimwwt);?></strong>
					<br>
					<?php
						$allowed_cats = dimwwt_admin_menu::get('bot-shop-allowed-cats');
						            if (!$allowed_cats) {
						                $allowed_cats = array();
						            }

						            $cats = get_categories(
						                array(
						                    'hide_empty' => 1,
						                    'orderby' => 'name',
						                    'taxonomy' => 'product_cat',
						                ));
						            foreach ($cats as $c) {
						                $checked = '';
						                if (in_array($c->term_id, $allowed_cats)) {
						                    $checked = ' checked="checked" ';
						                }
						                echo '<p class="bot-allowed-cats"><strong>' . esc_attr($c->cat_name) . '</strong><input type="checkbox" ' . $checked . ' name="dimwwt[bot-shop-allowed-cats][]"  value="' . $c->term_id . '"></p>';
						            }
					            ?>
				</label>
				<?php
					} //woocommerce shop check
				        ?>
				<input class="button-primary" type="submit" value="<?php _e('Save', __dimwwt);?>" id="submit" name="submit">
			</form>
			<hr>
			<h3><?php _e('Last Request', __dimwwt);?></h3>
			<textarea class="dimwwt-last-request"><?php
			                                      echo get_option('dimwwt_last_request');
			                                              ?></textarea>
		<h3><?php _e('Last Log', __dimwwt);?></h3>
			<textarea class="dimwwt-last-request"><?php
			                                      echo get_option('dimwwt_last_error');
			                                              ?></textarea>
		</div>
		<?php
			}

			    function msg_success($msg) {
		        ?>
<div class="updated settings-error notice is-dismissible" id="setting-error-settings_updated">
<p><strong><?php echo $msg; ?></strong></p><button class="notice-dismiss" type="button"><span class="screen-reader-text"><?php _e('Close', __dimwwt);?>.</span></button></div>
<?php
	}

	    /**
	     * Get admin option value
	     * @param  string $option_name
	     * @return mix
	     */
	    static function get($option_name) {
	        $cats = array();
	        $shop_cats = array();
	        //cat
	        $_cats = get_categories(
	            array(
	                'hide_empty' => 1,
	                'orderby' => 'name',
	            ));
	        foreach ($_cats as $c) {
	            $cats[] = $c->term_id;
	        }
	        if (class_exists('WC_Payment_Gateway')) {
	            //shop cat
	            $_cats = get_categories(
	                array(
	                    'hide_empty' => 1,
	                    'orderby' => 'name',
	                    'taxonomy' => 'product_cat',
	                ));
	            foreach ($_cats as $c) {
	                $shop_cats[] = $c->term_id;
	            }
	        }
	        $dimwwt = get_option('dimwwt',
	            array(
	                'bot-api-url' => 'https://api.telegram.org/',
	                'bot-welcome-msg' => 'سلام،به ربات سایت ' . get_bloginfo('name') . ' خوش آمدید.',
	                'bot-allowed-cats' => $cats,
	                'bot-shop-allowed-cats' => $shop_cats,
	                'posts_per_page' => 5,
	                'bot-mode' => 'telegram',

	            ));
	        return $dimwwt[$option_name];
	    }
}
