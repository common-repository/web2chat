<?php
/*
Plugin Name: Web2Chat
Plugin URI: https://web2chat.ai/integrations/wordpress
Description: Official Web2Chat support for WordPress.
Author: Web2Chat
Author URI: https://web2chat.ai
Version: 1.0.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define('WEB2CHAT_PLUGIN_VERSION', '1.0.2');


class Web2ChatIdentityVerificationCalculator
{
  private $raw_data = array();
  private $secret_key = "";

  public function __construct($data, $secret_key)
  {
    $this->raw_data = $data;
    $this->secret_key = $secret_key;
  }

  public function identityVerificationComponent()
  {
    $secret_key = $this->getSecretKey();
    if (empty($secret_key))
    {
      return $this->emptyIdentityVerificationHashComponent();
    }
    if (array_key_exists("user_id", $this->getRawData()))
    {
      return $this->identityVerificationHashComponent("user_id");
    }
    if (array_key_exists("email", $this->getRawData()))
    {
      return $this->identityVerificationHashComponent("email");
    }
    return $this->emptyIdentityVerificationHashComponent();
  }

  private function emptyIdentityVerificationHashComponent()
  {
    return array();
  }

  private function identityVerificationHashComponent($key)
  {
    $raw_data = $this->getRawData();
    return array("user_hash" => hash_hmac("sha256", $raw_data[$key], $this->getSecretKey()));
  }

  private function getSecretKey()
  {
    return $this->secret_key;
  }
  private function getRawData()
  {
    return $this->raw_data;
  }
}

class Web2ChatSettingsPage
{
  private $settings = array();
  private $styles = array();

  public function __construct($settings)
  {
    $this->settings = $settings;
    $this->styles = $this->setStyles($settings);
  }

  public function dismissibleMessage($text)
  {
    return '<div id="message" class="updated notice is-dismissible">' .
       '<p>' . esc_html($text) . '</p>' .
       '<button type="button" class="notice-dismiss"><span class="screen-reader-text">' . esc_html__('Dismiss this notice.', 'web2chat') . '</span></button>' .
       '</div>';
  }

  public function getAuthUrl() {
    return "https://wp-auth.web2chat.ai/confirm?state=".get_site_url()."::".wp_create_nonce('web2chat-oauth');
  }

  public function htmlUnclosed()
{
    $settings = $this->getSettings();
    $styles = $this->getStyles();
    $app_id = sanitize_text_field(wp_unslash(($settings['app_id'])));
    $secret = sanitize_text_field(wp_unslash(($settings['secret'])));
    $auth_url = $this->getAuthUrl();
    $dismissable_message = '';

    if (isset($_GET['appId'])) {
        // Copying app_id from setup guide
        $app_id = sanitize_text_field(wp_unslash($_GET['appId']));
        $dismissable_message = $this->dismissibleMessage("We've copied your new Web2Chat app id below. Click to save changes and then close this window to finish signing up for Web2Chat.");
    }

    if (isset($_GET['saved'])) {
        $dismissable_message = $this->dismissibleMessage("Your app id has been successfully saved. You can now close this window to finish signing up for Web2Chat.");
    }

    if (isset($_GET['authenticated'])) {
        $dismissable_message = $this->dismissibleMessage("You successfully authenticated with Web2Chat.");
    }


    return '<div class="wrap">' .
           $dismissable_message .

           '<section id="main_content" style="padding-top: 70px;">' .
           '<div class="container">' .
           '<div class="cta">' .

           '<div class="sp__2--lg sp__2--xlg"></div>' .
           '<div id="oauth_content" style="' . esc_attr($styles['app_id_link_style']) . '">' .
           '<div class="t__h1 c__blue">Get started with Web2Chat</div>' .

           '<div class="cta__desc">' .
           'Chat with visitors to your website in real-time, capture them as leads, and convert them to customers. Install Web2Chat on your WordPress site in a couple of clicks.' .
           '</div>' .

           '<div style="position:relative;margin-top:30px;">' .
           '<a class="bn49" href="' . esc_url($auth_url) . '">Connect with Web2Chat</a>' .
           '</div>' .
           '</div>' .

           '<div class="t__h1 c__blue" style="' . esc_attr($styles['app_id_copy_title']) . '">Web2Chat setup</div>' .
           '<div class="t__h1 c__blue" style="' . esc_attr($styles['app_id_saved_title']) . '">Web2Chat app ID saved</div>' .
           '<div id="app_id_and_secret_content" style="' . esc_attr($styles['app_id_row_style']) . '">' .
           '<div class="t__h1 c__blue" style="' . esc_attr($styles['app_id_copy_hidden']) . '">Web2Chat has been installed</div>' .

           '<div class="cta__desc">' .
           '<div style="' . esc_attr($styles['app_id_copy_hidden']) . '">' .
           'Web2Chat is now set up and ready to go. You can now chat with your existing and potential new customers, send them targeted messages, and get feedback.<br/><br/>' .
           '<a class="c__blue" href="https://app.web2chat.ai/_/inbox" target="_blank">Click here to access your Web2Chat Team Inbox.</a><br/><br/>' .
           'Need help? <a class="c__blue" href="https://help.web2chat.ai/" target="_blank">Visit our documentation</a> for best practices, tips, and much more.<br/><br/>' .
           '</div>' .

           '<div>' .
           '<form method="post" action="" name="update_settings">' .
           '<table class="form-table" align="center" style="width: inherit;">' .
           '<tbody>' .
           '<tr>' .
           '<th scope="row" style="text-align: center; vertical-align: middle;"><label for="web2chat_app_id">App ID</label></th>' .
           '<td>' .
           '<input id="web2chat_app_id" ' . esc_attr($styles['app_id_state']) . ' name="app_id" type="text" value="' . esc_attr($app_id) . '" class="' . esc_attr($styles['app_id_class']) . '">' .
           '<button type="submit" class="btn btn__primary cta__submit" style="' . esc_attr($styles['button_submit_style']) . '">Save</button>' .
           '</td>' .
           '</tr>' .
           '</tbody>' .
           '</table>';
}


  public function htmlClosed()
{
    $settings = $this->getSettings();
    $styles = $this->getStyles();
    $auth_url = $this->getAuthUrl();
    $secret = sanitize_text_field(wp_unslash(($settings['secret'])));
    $app_id = sanitize_text_field(wp_unslash(($settings['app_id'])));
    $auth_url_identity_verification = "";
    
    if (empty($secret) && !empty($app_id)) {
        $auth_url_identity_verification = esc_url($auth_url) . '&enable_identity_verification=1';
    }

    return '</form>' .
           '<div style="' . esc_attr($styles['app_id_copy_hidden']) . '">' .
           '<div style="' . esc_attr($styles['app_secret_link_style']) . '">' .
           '<a class="c__blue" href="' . esc_url($auth_url_identity_verification) . '">Authenticate with your Web2Chat application to enable Identity Verification</a>' .
           '</div>' .
           '<p style="font-size:0.86em">Identity Verification ensures that conversations between you and your users are kept private.<br/></p>' .
           '<br/>' .
           '<div style="font-size:0.8em">If the Web2Chat application associated with your WordPress is incorrect, please <a class="c__blue" href="' . esc_url($auth_url) . '">click here</a> to reconnect with Web2Chat, to choose a new application.</div>' .
           '</div>' .
           '</div>' .
           '</div>' .
           '</div>' .
           '</div>' .
           '</div>' .
           '</section>' .
           '</div>';
}


  public function html()
  {
    return $this->htmlUnclosed() . $this->htmlClosed();
  }

  public function setStyles($settings) {
    $styles = array();
    $app_id = sanitize_text_field(wp_unslash(($settings['app_id'])));
    $secret = sanitize_text_field(wp_unslash(($settings['secret'])));
    $identity_verification = isset($settings['identity_verification']) 
    ? sanitize_text_field(wp_unslash(($settings['identity_verification']))) 
    : '';

    // Use Case : Identity Verification enabled : checkbox checked and disabled
    if($identity_verification) {
      $styles['identity_verification_state'] = 'checked disabled';
    } else {
      $styles['identity_verification_state'] = '';
    }

    // Use Case : app_id here but Identity Verification disabled
    if (empty($secret) && !empty($app_id)) {
      $styles['app_secret_row_style'] = 'display: none;';
      $styles['app_secret_link_style'] = '';
    } else {
      $styles['app_secret_row_style'] = '';
      $styles['app_secret_link_style'] = 'display: none;';
    }

    // Copying appId from Web2Chat Setup Guide for validation
    if (isset($_GET['appId'])) {
        $app_id = sanitize_text_field(wp_unslash($_GET['appId']));
        $styles['app_id_state'] = 'readonly';
        $styles['app_id_class'] = "cta__email";
        $styles['button_submit_style'] = '';
        $styles['app_id_copy_hidden'] = 'display: none;';
        $styles['app_id_copy_title'] = '';
        $styles['identity_verification_state'] = 'disabled'; # Prevent from sending POST data about identity_verification when using app_id form
    } else {
      $styles['app_id_class'] = "";
      $styles['button_submit_style'] = 'display: none;';
      $styles['app_id_copy_title'] = 'display: none;';
      $styles['app_id_state'] = 'disabled'; # Prevent from sending POST data about app_id when using identity_verification form
      $styles['app_id_copy_hidden'] = '';
    }

    //Use Case App_id successfully copied
    if (isset($_GET['saved'])) {
      $styles['app_id_copy_hidden'] = 'display: none;';
      $styles['app_id_saved_title'] = '';
    } else {
      $styles['app_id_saved_title'] = 'display: none;';
    }

    // Display 'connect with Web2Chat' button if no app_id provided (copied from setup guide or from Oauth)
    if (empty($app_id)) {
      $styles['app_id_row_style'] = 'display: none;';
      $styles['app_id_link_style'] = '';
    } else {
      $styles['app_id_row_style'] = '';
      $styles['app_id_link_style'] = 'display: none;';
    }
    return $styles;
  }

  private function getSettings()
  {
    return $this->settings;
  }

  private function getStyles()
  {
    return $this->styles;
  }
}

class Web2ChatSnippet
{
  private $snippet_settings = "";

  public function __construct($snippet_settings)
  {
    $this->snippet_settings = $snippet_settings;
  }
  public function html()
  {
    return $this->shutdown_on_logout() . $this->source();
  }

  private function shutdown_on_logout()
{
    return '<script data-cfasync="false">
    document.onreadystatechange = function () {
      if (document.readyState == "complete") {
        var logout_link = document.querySelectorAll(\'a[href*="wp-login.php?action=logout"]\');
        if (logout_link) {
          for(var i=0; i < logout_link.length; i++) {
            logout_link[i].addEventListener( "click", function() {
              Web2Chat("shutdown");
            });
          }
        }
      }
    };
    </script>';
}

private function source()
{
    $snippet_json = $this->snippet_settings->json();
    $app_id = esc_js($this->snippet_settings->appId());

    $script = '<script data-cfasync="false">!function(){var t=window;if("function"==typeof t.Chat);else{var e=document,a=function(){a.c(arguments)};';
    $script .= 'a.q=[],a.c=function(t){a.q.push(t)},t.Chat=a;var n=function(){var t=e.createElement("script");t.type="text/javascript",t.async=!0,t.src="https://widget.web2chat.ai/widget/' . $app_id . '";';
    $script .= 'var a=e.getElementsByTagName("script")[0];a.parentNode.insertBefore(t,a)};"complete"===document.readyState?n():t.attachEvent?t.attachEvent("onload",n):t.addEventListener("load",n,!1)}}();</script>';

    $script .= '<script data-cfasync="false">';
    $script .= 'window.Chat("boot", ' . $snippet_json . ');';
    $script .= '</script>';

    return $script;
}
}

class Web2ChatSnippetSettings
{
  private $raw_data = array();
  private $secret = NULL;
  private $wordpress_user = NULL;

  public function __construct($raw_data, $secret = NULL, $wordpress_user = NULL, $constants = array('ICL_LANGUAGE_CODE' => 'language_override'))
  {
    $this->raw_data = $this->validateRawData($raw_data);
    $this->secret = $secret;
    $this->wordpress_user = $wordpress_user;
    $this->constants = $constants;
  }

  public function json()
  {
    return wp_json_encode($this->getRawData());
  }

  public function appId()
  {
    $raw_data = $this->getRawData();
    return $raw_data["app_id"];
  }

  private function getRawData()
  {
    $user = new Web2ChatUser($this->wordpress_user, $this->raw_data);
    $settings = apply_filters("web2chat_settings", $user->buildSettings());
    $identityVerificationCalculator = new Web2ChatIdentityVerificationCalculator($settings, $this->secret);
    $result = array_merge($settings, $identityVerificationCalculator->identityVerificationComponent());
    $result = $this->mergeConstants($result);
    $result['installation_type'] = 'wordpress';
    return $result;
  }

  private function mergeConstants($settings) {
    foreach($this->constants as $key => $value) {
      if (defined($key)) {
        $const_val = Web2ChatWordPressEscaper::escJS(constant($key));
        $settings = array_merge($settings, array($value => $const_val));
      }
    }
    return $settings;
  }

  private function validateRawData($raw_data)
  {
    if (!array_key_exists("app_id", $raw_data)) {
      throw new Exception("app_id is required");
    }
    return $raw_data;
  }
}

class Web2ChatWordPressEscaper
{
  public static function escAttr($value)
  {
    if (function_exists('esc_attr')) {
      return esc_attr($value);
    } else {
      return $value;
    }
  }

  public static function escJS($value)
  {
    if (function_exists('esc_js')) {
      return esc_js($value);
    } else {
      return $value;
    }
  }
}

class Web2ChatUser
{
  private $wordpress_user = NULL;
  private $settings = array();

  public function __construct($wordpress_user, $settings)
  {
    $this->wordpress_user = $wordpress_user;
    $this->settings = $settings;
  }

  public function buildSettings()
  {
    if (empty($this->wordpress_user))
    {
      return $this->settings;
    }
    if (!empty($this->wordpress_user->user_email))
    {
      $this->settings["email"] = Web2ChatWordPressEscaper::escJS($this->wordpress_user->user_email);
    }
    if (!empty($this->wordpress_user->display_name))
    {
      $this->settings["name"] = Web2ChatWordPressEscaper::escJS($this->wordpress_user->display_name);
    }
    return $this->settings;
  }
}

class Web2ChatValidator
{
  private $inputs = array();
  private $validation;

  public function __construct($inputs, $validation)
  {
    $this->input = $inputs;
    $this->validation = $validation;
  }

  public function validAppId()
  {
    return $this->validate($this->input["app_id"]);
  }

  public function validSecret()
  {
    return $this->validate($this->input["secret"]);
  }

  private function validate($x)
  {
    return call_user_func($this->validation, $x);
  }
}

function web2chat_add_snippet()
{
  $options = get_option('web2chat');
  $snippet_settings = new Web2ChatSnippetSettings(
    array("app_id" => esc_js($options['app_id'])),
    esc_js($options['secret']),
    wp_get_current_user()
  );
  $snippet = new Web2ChatSnippet($snippet_settings);
  echo $snippet->html();
}

function web2chat_add_settings_page()
{
  add_options_page(
    'Web2Chat Settings',
    'Web2Chat',
    'manage_options',
    'web2chat',
    'web2chat_render_options_page'
  );
}

function web2chat_render_options_page()
{
  if (!current_user_can('manage_options'))
  {
    wp_die('You do not have sufficient permissions to access Web2Chat settings');
  }
  $options = get_option('web2chat');
  
  if ($options === false || !is_array($options)) {
    // Handle the case where the options do not exist
    $options = array(
      'app_id' => '', // Default value for app_id
      'secret' => ''  // Default value for secret
    );
  }

  $settings_page = new Web2ChatSettingsPage(array("app_id" => $options['app_id'], "secret" => $options['secret']));
  echo $settings_page->htmlUnclosed();
  wp_nonce_field('web2chat-update');
  echo $settings_page->htmlClosed();
}

function web2chat_sanitize_settings($input) {
  // Initialize an array to store sanitized values
  $sanitized_input = array();

  // Check if the 'app_id' is set, sanitize it, and store it
  if (isset($input['app_id'])) {
      $sanitized_input['app_id'] = sanitize_text_field($input['app_id']);
  }

  // Check if the 'secret' is set, sanitize it, and store it
  if (isset($input['secret'])) {
      $sanitized_input['secret'] = sanitize_text_field($input['secret']);
  }

  // Add any other settings that need to be sanitized here...

  return $sanitized_input;
}

function web2chat_settings() {
  register_setting('web2chat', 'web2chat', 'web2chat_sanitize_settings');
  if (isset($_GET['state']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['state'])), 'web2chat-oauth') && current_user_can('manage_options') && isset($_GET['app_id']) && isset($_GET['secret']) ) {
    update_option("web2chat", array("app_id" => sanitize_text_field(wp_unslash($_GET['app_id'])), "secret" => sanitize_text_field(wp_unslash($_GET['secret']))));
    wp_safe_redirect(admin_url('options-general.php?page=web2chat&authenticated=1'));
  }
}

function web2chat_enqueue_admin_assets($hook_suffix) {
  if ($hook_suffix !== 'settings_page_web2chat') {
    return; // Do nothing if not on the Web2Chat settings page.
  }

  // Enqueue the custom CSS file
  wp_enqueue_style('web2chat-custom-style', plugin_dir_url(__FILE__) . 'assets/css/style.css', array(), WEB2CHAT_PLUGIN_VERSION);

  wp_enqueue_script('jquery');
}

add_action('admin_enqueue_scripts', 'web2chat_enqueue_admin_assets');
add_action('wp_footer', 'web2chat_add_snippet');
add_action('admin_menu', 'web2chat_add_settings_page');
add_action('network_admin_menu', 'web2chat_add_settings_page');
add_action('admin_init', 'web2chat_settings');

