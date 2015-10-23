<?php
/* v.1.0.2
New popup login procedure.

Yes, it's responsive also. :)
*/

# Exit if accessed directly
if (!defined('ABSPATH'))
    exit;

if (!class_exists('Popup_Login_Custom_Window'))
{
    class Popup_Login_Custom_Window
    {
        private static $instance;
        private $active_page = 'register';
        private $is_error = false;
        private $output_message = false;
        private $is_info = false;
        private $variables = array(
            'login' => array(
                'email' => '',
            ),
            'register' => array(
                'name' => '',
                'email' => '',
            ),
        );
        private $action_successful = false;

        public static function instance() {
            if (!self::$instance)
            {
                self::$instance = new Popup_Login_Custom_Window();
                self::$instance->hooks();
            }
            return self::$instance;
        } // instance

        private function hooks() {
            add_action('init', array($this, 'register_popup_login_script'));
            add_action('wp_footer', array($this, 'print_popup_login_script'));
        } // hooks

        public function register_popup_login_script() {

            if (($_SERVER['REQUEST_METHOD'] == 'POST') && isset($_POST['is-popup-login'])) {

                $action = strtolower($_POST['action']);
                if ("login" == $action) {

                    $this->active_page = 'login';
                    $this->variables['login']['email'] = $_POST['login']['email'];

                    $creds = array(
                        'user_login' => $_POST['login']['email'],
                        'user_password' => $_POST['login']['password'],
                        'remember' => false,
                    );
                    $user = wp_signon( $creds, false );
                    if ( is_wp_error($user) ) {
                        $this->is_error = true;
                        $this->output_message = __( $user->get_error_message() );
                    } else {
                        // Crazy part: Wordpress was completing the login process but didn't set the current user. :S
                        wp_set_current_user($user->ID);
                        $this->action_successful = 'login';
                    }

                } elseif ("register" == $action) {

                    $this->active_page = 'register';
                    $this->variables['register']['name'] = $_POST['register']['name'];
                    $this->variables['register']['email'] = $_POST['register']['email'];

                    $user_id = username_exists( $_POST['register']['email'] );
                    if ( !$user_id and email_exists($_POST['register']['email']) == false ) {

                        $user_id = wp_create_user( $_POST['register']['email'], $_POST['register']['password'], $_POST['register']['email'] );
                        if ( is_wp_error($user_id) ) {
                            $this->is_error = true;
                            $this->output_message = __( $user_id->get_error_message() );
                        } else {
                            // Let's update the user's information
                            wp_update_user(array(
                                'ID' => $user_id,
                                'user_nicename' => $_POST['register']['name'],
                                'display_name' => $_POST['register']['name'],
                                ));

                            // Time to login now
                            $creds = array(
                                'user_login' => $_POST['register']['email'],
                                'user_password' => $_POST['register']['password'],
                                'remember' => false,
                            );
                            $user = wp_signon( $creds, false );
                            if ( is_wp_error($user) ) {
                                $this->is_error = true;
                                $this->output_message = __( $user->get_error_message() );
                            } else {
                                // Crazy part: Wordpress was completing the login process but didn't set the current user. :S
                                wp_set_current_user($user->ID);
                                $this->action_successful = 'register';
                            }
                        }
                    } else {
                        // Here we need to make sure that the user doesn't click the "reload", because the system will try to re-register the user.
                        $current_user_ID = get_current_user_id();
                        if ($user_id != $current_user_ID) {
                            $this->is_error = true;
                            $this->output_message = __( 'User already exists' );
                        }
                    }

                } elseif ("reset" == $action) {

                    $this->active_page = 'reset';

                    $user_id = username_exists( $_POST['reset']['email'] );
                    if ( !$user_id ) {
                        $user_id = email_exists($_POST['reset']['email']);
                    }
                    if ($user_id) {
                        $user = new WP_User( $user_id );
                        if ( true === ($this->output_message = $this->reset_password( $user )) ) {
                            // In successfull reset password procedure, we need to display the login once more.
                            $this->active_page = 'login';
                            $this->is_info = true;
                            $this->output_message = 'Password recovery email has been sent, please check your mailbox.';
                        } else {
                            $this->is_error = true;
                            $this->output_message = 'Could not send the reset email, please try again.';
                        }
                    // } else {
                    //     $this->is_error = true;
                    //     $this->output_message = 'Invalid username or e-mail.';
                    }
                } else {
                    // Unknown action, let them show once more the login popup.
                    $this->is_error = true;
                    $this->output_message = __( 'Invalid action.' );
                }

                // Let's unset the variables, we don't need them any more.
                unset($_POST['action']);
                unset($_POST['is-popup-login']);
                unset($_POST['login']['email']);
                unset($_POST['login']['password']);
                unset($_POST['register']['name']);
                unset($_POST['register']['email']);
                unset($_POST['register']['password']);
                unset($_POST['reset']['email']);
            }

            wp_register_script('popup-login', plugin_dir_url(__FILE__).'assets/js/popup-login.js', array(), '1.0.2', true);
            wp_register_style('popup-login', plugin_dir_url(__FILE__).'assets/css/popup-login.css', array(), '1.0.2');
        } // register_popup_login_script

        public function print_popup_login_script() {

            if (!is_user_logged_in() && ("post" == get_post_type())) {

                if ( $username = ($_COOKIE['popup_email'] !='' ? $_COOKIE['popup_email'] : false) ) {
                    $this->variables['login']['email'] = $_COOKIE['popup_email'];
                    $this->active_page = 'login';
                    unset($_COOKIE['popup_email']);
                    setcookie('popup_email', null, -1, '/');
                }

?>
<div id="popup-login-wrapper">
    <div id="popup-login-popup" class="popup-login-window">
        <div id="popup-login-logo">
            <img src="/wp-content/uploads/2015/05/The-Mindfulness-Summit3.png">
        </div>
        <div class="popup-login-title">
            <span class="action-login">Login</span>
            <span class="action-register">Register Access Pass</span>
            <span class="action-reset">Recover My Password</span>
        </div>
        <div class="popup-login-content">
            <span class="action-login">Login to the mindfulness summit. You will have access to everything in here!</span>
            <span class="action-register">Instantly access the mindfulness summit by finishing your 'free access pass' registration. If you created a password already click login down below.</span>
            <span class="action-reset">You forgot your password? No problem dude. Here we are to help you. Just provide us your username or email and we'll send you a new password! How cool is this?</span>
        </div>
        <form method="POST" id="popup-login-form">
            <input type="hidden" name="is-popup-login" value="1">
            <input type="hidden" name="action" value="<?php echo $this->active_page ?>">
            <div class="popup-login-form">
                <div class="action-login">
                    <div class="popup-login-section">
                        <label for="login-email">Email</label>
                        <input type="text" name="login[email]" id="login-email" class="input" value="<?php echo $this->variables['login']['email'] ?>" placeholder="Email" autocomplete="off">
                    </div>
                    <div class="popup-login-section right">
                        <label for="login-password">Password</label>
                        <input type="password" name="login[password]" id="login-password" class="input" value="" placeholder="Password" autocomplete="off">
                    </div>
                </div>
                <div class="action-register">
                    <div class="popup-login-section full">
                        <label for="register-name">Name (i.e. First &amp; Last name)</label>
                        <input type="text" name="register[name]" id="register-name" class="input" value="<?php echo $this->variables['register']['name'] ?>" placeholder="Your First &amp; Last name" autocomplete="off">
                    </div>
                    <div class="popup-login-section">
                        <label for="register-email">Email</label>
                        <input type="text" name="register[email]" id="register-email" class="input" value="<?php echo $this->variables['register']['email'] ?>" placeholder="Email" autocomplete="off">
                    </div>
                    <div class="popup-login-section right">
                        <label for="register-password">Password</label>
                        <input type="password" name="register[password]" id="register-password" class="input" value="" placeholder="Password" autocomplete="off">
                    </div>
                </div>
                <div class="action-reset">
                    <div class="popup-login-section full">
                        <label for="reset-email">Username or Email</label>
                        <input type="text" name="reset[email]" id="reset-email" class="input" value="" placeholder="Username or Email" autocomplete="off">
                    </div>
                </div>
            </div>
            <div class="popup-login-submit">
                <input type="submit" name="submit" id="submit-login" class="button-primary action-login" value="Login">
                <input type="submit" name="submit" id="submit-register" class="button-primary action-register" value="Register">
                <input type="submit" name="submit" id="submit-reset" class="button-primary action-reset" value="Submit">
            </div>
        </form>
        <div class="popup-login-options">
            <ul>
                <li class="li-login">Already have an 'Access Pass'? <a href="#" class="popup-login-cta" data-rel="login">Log in</a></li>
                <li class="li-register">Don't have an 'Access Pass'? <a href="#" class="popup-login-cta" data-rel="register">Register</a></li>
                <li class="li-reset">Lost your password? <a href="#" class="popup-login-cta" data-rel="reset">Click here</a></li>
            </ul>
        </div>
        <div class="popup-login-error">
            <span></span>
            <a href="#" class="popup-login-error-close" title="close">×</a>
        </div>
    </div>
</div>
<script>
(function ($) {
    $(document).ready(function() {
        $('.action-<?php echo $this->active_page ?>', '#popup-login-popup').show();
        $('.popup-login-options .li-<?php echo $this->active_page ?>').hide();

        setTimeout(function() {
            $('#popup-login-wrapper').fadeIn(600, function() {
                $('body').addClass('no-scroll');
            });
        }, 2000);
        <?php if ($this->is_error || $this->is_info): ?>
            setTimeout(function() {
            <?php if ($this->is_error): ?>
                console.log('showError');
                showError('<?php echo $this->output_message ?>');
            <?php else: ?>
                console.log('showInfo');
                showInfo('<?php echo $this->output_message ?>');
            <?php endif ?>
            }, 4000);
        <?php endif ?>
    });
}(jQuery));
</script>
<?php
                wp_print_styles('popup-login');
                wp_print_scripts('popup-login');
            }
        } // print_popup_login_script

        private function reset_password( $user ) {
            global $wpdb, $wp_hasher;

            // Redefining user_login ensures we return the right case in the email.
            $user_login = $user->user_login;
            $user_email = $user->user_email;

            do_action( 'retreive_password', $user_login );
            do_action( 'retrieve_password', $user_login );
            $allow = apply_filters( 'allow_password_reset', true, $user->ID );

            if ( ! $allow ) {
                return 'Password reset is not allowed for this user';
            } elseif ( is_wp_error( $allow ) ) {
                return $allow;
            }

            // Generate something random for a password reset key.
            $key = wp_generate_password( 20, false );
            do_action( 'retrieve_password_key', $user_login, $key );

            // Now insert the key, hashed, into the DB.
            if ( empty( $wp_hasher ) ) {
                require_once ABSPATH . WPINC . '/class-phpass.php';
                $wp_hasher = new PasswordHash( 8, true );
            }
            $hashed = time() . ':' . $wp_hasher->HashPassword( $key );
            $wpdb->update( $wpdb->users, array( 'user_activation_key' => $hashed ), array( 'user_login' => $user_login ) );

            $message = __('Someone requested that the password be reset for the following account:') . "\r\n\r\n";
            $message .= network_home_url( '/' ) . "\r\n\r\n";
            $message .= sprintf(__('Username: %s'), $user_login) . "\r\n\r\n";
            $message .= __('If this was a mistake, just ignore this email and nothing will happen.') . "\r\n\r\n";
            $message .= __('To reset your password, visit the following address:') . "\r\n\r\n";
            $message .= '<' . network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user_login), 'login') . ">\r\n";

            if ( is_multisite() )
                $blogname = $GLOBALS['current_site']->site_name;
            else
                $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

            $title = sprintf( __('[%s] Password Reset'), $blogname );
            $title = apply_filters( 'retrieve_password_title', $title );
            $message = apply_filters( 'retrieve_password_message', $message, $key, $user_login, $user );

            if ( $message && !wp_mail( $user_email, wp_specialchars_decode( $title ), $message ) )
                return __('The e-mail could not be sent.') . "<br />\n" . __('Possible reason: your host may have disabled the mail() function.');

            return true;
        } // reset_password

    } // class Popup_Login_Custom_Window
} // if class_exists

add_action('plugins_loaded', 'Popup_Login_Custom_Window_Load');
function Popup_Login_Custom_Window_Load() {
    Popup_Login_Custom_Window::instance();
}


// This part is based on the corresponding Gerasimov Eugene's code. Thanks man!
if (!class_exists('Popup_Login_PassResetRedir')) {
    class Popup_Login_PassResetRedir {
        private static $instance;
        private $username;
        
        public static function instance($username) {
            if (!self::$instance) {
                self::$instance = new Popup_Login_PassResetRedir();
                self::$instance->username = $username;
                self::$instance->hooks();
                if ($username) {
                    setcookie('popup_username', $username, time() + 60*60*24, '/');
                }
            }
            return self::$instance;
        }
        
        private function hooks() {
            add_action('login_head', array($this, 'redir'));
        }

        public function redir() {
            if ( $username = ($_COOKIE['popup_username'] !='' ? $_COOKIE['popup_username'] : false) ) {
                $user = get_user_by( 'login', $username );
                if ($user) {
                    setcookie('popup_email', $user->user_email, time() + 60*60*24, '/');
                }
                unset($_COOKIE['popup_username']);
                setcookie('popup_username', null, -1, '/');
            }
            ?>
<script>
function redir() {
    if (document.getElementsByClassName("reset-pass").length > 0) {
        var message = document.getElementsByClassName("reset-pass")[0].innerHTML;
        if (message.match(/Your password has been reset/)) {
            document.getElementsByClassName("reset-pass")[0].innerHTML = 'Your password has been reset. You will be redirected shortly.';
            window.setTimeout(function(){
                window.location.href = '/live';
            }, 5000);
        }
    }
}
window.onload = redir;
</script>
            <?php
        }
        
    } // class Popup_Login_PassResetRedir
} // if (!class_exists('Popup_Login_PassResetRedir'))

if (preg_match('~^/wp-login.php~', $_SERVER['REQUEST_URI'])) {
    Popup_Login_PassResetRedir::instance( isset($_GET['login']) ? $_GET['login'] : false );
}