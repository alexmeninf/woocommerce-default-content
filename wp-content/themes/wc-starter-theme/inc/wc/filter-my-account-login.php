<?php

add_action('woocommerce_login_form_end', 'action_function_login_form_end');
add_action('woocommerce_register_form_end', 'action_function_register_form_end');
add_action('wp_head', 'wc_style_login', 10);
add_action('wp_enqueue_scripts', 'wc_script_login', 30);


/**
 * action_function_login_form_end
 *
 * @return void
 */
function action_function_login_form_end()
{
?>
  <div class="wc-btn-forms">
    <span><?php echo __('Ainda não possui uma conta?', 'wcstartertheme') ?></span>

    <?php if (is_page(get_option('woocommerce_myaccount_page_id'))) : ?>

      <button type="button" id="create-account-form" 
      data-enable-custom-register="<?= THEME_CUSTOM_REGISTER_PAGE ? 'true' : 'false' ?>"
      data-link-register="<?php the_permalink_register() ?>"><?php echo __('Criar conta', 'wcstartertheme') ?></button>

    <?php else : ?>

      <a href="<?= the_permalink_register() ?>"><?php echo __('Criar conta', 'wcstartertheme') ?></a>

    <?php endif; ?>
  </div>
<?php
}


/**
 * action_function_register_form_end
 *
 * @return void
 */
function action_function_register_form_end()
{ ?>
  <div class="wc-btn-forms">
    <span><?php echo __('Possui uma conta?', 'wcstartertheme') ?></span>
    <button type="button" id="login-account-form"><?php echo __('Fazer login', 'wcstartertheme') ?></button>
  </div>
<?php
}


/**
 * wc_style_login
 * 
 * Estiliza algumas partes do login e registro
 *
 * @return void
 */
function wc_style_login()
{
  if (is_page(array(get_option('woocommerce_myaccount_page_id'), 'registrar')) && !is_user_logged_in()) :

    $css = "<style>
    .login-register .woocommerce-notices-wrapper {display: flex;justify-content: center;}

    .wc-btn-forms {
      display: block;
      text-align: center;
      margin-top: 35px;
      border-top: 1px solid var(--color-border-color);
      padding-top: 30px;
      position: relative;
    }

    .wc-btn-forms::before {
      content: 'ou';
      font-weight: bold;
      background-color: var(--color-fill-sidebar);
      position: absolute;
      top: -13px;
      left: 50%;
      font-size: 16px;
      padding: 0 8px;
      transform: translateX(-50%);
    }

    .wc-btn-forms a, 
    .wc-btn-forms button {
      position: relative;
    }

    .wc-btn-forms a.redirecting:after,
    .wc-btn-forms button.redirecting:after {
      content: '';
      background-image: url('" . STYLESHEET . "/assets/img/loading.svg');
      background-size: contain;
      background-position: center;
      background-repeat: no-repeat;
      position: absolute;
      padding: 10px;
      top: 8px;
      right: 0px;
    }
  </style>";

    echo $css;

  endif;

  if ((is_page(array(get_option('woocommerce_myaccount_page_id'), 'registrar')) && !is_user_logged_in()) || is_checkout()) :

    $css = '<style>
      .checkout .woocommerce-form-login, 
      .checkout .woocommerce-form-register {max-width: 500px;margin: 0 auto;}

      .wc-btn-forms button,
      .wc-btn-forms a {
        border: 0;
        padding: 5px 28px 5px 5px;
        font-weight: 700;
        color: var(--color-theme);
        background: transparent;
        text-decoration: underline;
        font-size: 15px;
      }

      .wc-btn-forms button:hover,
      .wc-btn-forms a:hover {opacity: .6;}
    </style>';

    echo $css;

  endif;
}


/**
 * wc_script_login
 * 
 * Deixa visivel somente a ação atual
 *
 * @return void
 */
function wc_script_login()
{
  if (is_page(array(get_option('woocommerce_myaccount_page_id'), 'registrar')) && !is_user_logged_in()) :
    wp_enqueue_script('script-login', get_template_directory_uri() . '/assets/js/my-account-login.js', array(), '1.0.0', true);
  endif;
}


/**
 * Redirect page on register
 */
function wc_redirect_when_registering($redirect)
{
  if (isset($_GET['redirect_to'])) {
    $redirect = $_GET['redirect_to'];
  }

  return $redirect;
}
add_filter('woocommerce_registration_redirect', 'wc_redirect_when_registering');
