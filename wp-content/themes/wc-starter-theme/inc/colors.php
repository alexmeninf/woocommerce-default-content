<?php

// add_action('customize_register', 'theme_customize_admin_register');
// add_action('wp_enqueue_scripts', 'theme_enqueue_styles');


/**
 * theme_customize_admin_register
 * Campos de configuração das cores no tema.
 *
 * @param  mixed $wp_customize
 * @return mixed
 */
function theme_customize_admin_register($wp_customize)
{

  // Cor destaque
  $wp_customize->add_setting('default_color', array(
    'default'   => '#0066cc',
    'transport' => 'refresh',
    'sanitize_callback' => 'sanitize_hex_color',
  ));

  $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'default_color', array(
    'section' => 'colors',
    'label'   => esc_html__('Cor destaque', 'wcstartertheme'),
  )));

  // Cor do fundo
  $wp_customize->add_setting('bg_theme_color', array(
    'default'   => '#ffffff',
    'transport' => 'refresh',
    'sanitize_callback' => 'sanitize_hex_color',
  ));

  $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'bg_theme_color', array(
    'section' => 'colors',
    'label'   => esc_html__('Cor do fundo', 'wcstartertheme'),
  )));

  // Cor do fundo secundário
  $wp_customize->add_setting('bg_theme_color_secondary', array(
    'default'   => '#f3f3f3',
    'transport' => 'refresh',
    'sanitize_callback' => 'sanitize_hex_color',
  ));

  $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'bg_theme_color_secondary', array(
    'section' => 'colors',
    'label'   => esc_html__('Cor do fundo secundário', 'wcstartertheme'),
  )));

  // Cor das bordas
  $wp_customize->add_setting('border_color_theme', array(
    'default'   => '#dcdcdc',
    'transport' => 'refresh',
    'sanitize_callback' => 'sanitize_hex_color',
  ));

  $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'border_color_theme', array(
    'section' => 'colors',
    'label'   => esc_html__('Cor das bordas', 'wcstartertheme'),
  )));

  // Cor do texto
  $wp_customize->add_setting('text_theme_color', array(
    'default'   => '#202020',
    'transport' => 'refresh',
    'sanitize_callback' => 'sanitize_hex_color',
  ));

  $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'text_theme_color', array(
    'section' => 'colors',
    'label'   => esc_html__('Cor do texto', 'wcstartertheme'),
  )));

  // Cor secundaria do texto
  $wp_customize->add_setting('text_secondary_theme_color', array(
    'default'   => '#717274',
    'transport' => 'refresh',
    'sanitize_callback' => 'sanitize_hex_color',
  ));

  $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'text_secondary_theme_color', array(
    'section' => 'colors',
    'label'   => esc_html__('Cor secundaria do texto', 'wcstartertheme'),
  )));

  // Button color
  $wp_customize->add_setting('button_color', array(
    'default'   => '',
    'transport' => 'refresh',
    'sanitize_callback' => 'sanitize_hex_color',
  ));

  $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'button_color', array(
    'section' => 'colors',
    'label'   => esc_html__('Cor do botão', 'wcstartertheme'),
  )));

  // Button text color
  $wp_customize->add_setting('button_text_color', array(
    'default'   => '#ffffff',
    'transport' => 'refresh',
    'sanitize_callback' => 'sanitize_hex_color',
  ));

  $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'button_text_color', array(
    'section' => 'colors',
    'label'   => esc_html__('Cor do texto do botão', 'wcstartertheme'),
  )));

  // Hover button color
  $wp_customize->add_setting('button_hover_color', array(
    'default'   => '',
    'transport' => 'refresh',
    'sanitize_callback' => 'sanitize_hex_color',
  ));

  $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'button_hover_color', array(
    'section' => 'colors',
    'label'   => esc_html__('Cor destaque do botão', 'wcstartertheme'),
  )));

  // Hover text button color
  $wp_customize->add_setting('button_text_hover_color', array(
    'default'   => '#ffffff',
    'transport' => 'refresh',
    'sanitize_callback' => 'sanitize_hex_color',
  ));

  $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'button_text_hover_color', array(
    'section' => 'colors',
    'label'   => esc_html__('Cor destaque do texto do botão', 'wcstartertheme'),
  )));
}


/**
 * theme_get_customizer_admin_css
 * Aplicar variáveis de cores no estilo do tema
 *
 * @return mixed
 */
function theme_get_customizer_admin_css()
{
  ob_start();

  // Abertura body css
  echo 'body {';

  // Cor destaque
  $default_color = get_theme_mod('default_color', '#0066cc');
  if (!empty($default_color)) {
?>
    --color-theme: <?php echo sanitize_hex_color($default_color); ?>;
  <?php
  }

  // Cor do fundo  
  $bg_theme_color = get_theme_mod('bg_theme_color', '#ffffff');
  if (!empty($bg_theme_color)) {
  ?>
    --color-fill: <?php echo sanitize_hex_color($bg_theme_color); ?>;
  <?php
  }

  // Cor do fundo secundário
  $bg_theme_color_secondary = get_theme_mod('bg_theme_color_secondary', '#e4e4e4');
  if (!empty($bg_theme_color_secondary)) {
  ?>
    --color-fill-secondary: <?php echo sanitize_hex_color($bg_theme_color_secondary); ?>;
  <?php
  }

  // Cor das bordas
  $border_color_theme = get_theme_mod('border_color_theme', '#dcdcdc');
  if (!empty($border_color_theme)) {
  ?>
    --color-border-color: <?php echo sanitize_hex_color($border_color_theme); ?>;
  <?php
  }

  // Cor do texto
  $text_theme_color = get_theme_mod('text_theme_color', '#1f1c23');
  if (!empty($text_theme_color)) {
  ?>
    --color-text: <?php echo sanitize_hex_color($text_theme_color); ?>;
  <?php
  }

  // Cor secundaria do texto
  $text_secondary_theme_color = get_theme_mod('text_secondary_theme_color', '#929094');
  if (!empty($text_secondary_theme_color)) {
  ?>
    --color-text-secondary-color: <?php echo sanitize_hex_color($text_secondary_theme_color); ?>;
  <?php
  }

  // Button color
  $button_color = get_theme_mod('button_color', '');
  if (!empty($button_color)) {
  ?>
    --color-button-background: <?php echo sanitize_hex_color($button_color); ?>;
  <?php
  } else { ?>
    --color-button-background: <?php echo sanitize_hex_color($default_color); ?>;
  <?php
  }

  // Button text color
  $button_text_color = get_theme_mod('button_text_color', '#ffffff');
  if (!empty($button_text_color)) {
  ?>
    --color-button-text: <?php echo sanitize_hex_color($button_text_color); ?>;
  <?php
  }

  // Hover button color
  $button_hover_color = get_theme_mod('button_hover_color', '');
  if (!empty($button_hover_color)) {
  ?>
    --color-button-background-hover: <?php echo sanitize_hex_color($button_hover_color); ?>;
  <?php
  } else { ?>
    --color-button-background-hover: <?php echo sanitize_hex_color(wc_hex_darker($default_color)); ?>;
  <?php
  }

  // Hover text button color
  $button_text_hover_color = get_theme_mod('button_text_hover_color', '#ffffff');
  if (!empty($button_text_hover_color)) {
  ?>
    --color-button-text-hover: <?php echo sanitize_hex_color($button_text_hover_color); ?>;
<?php
  }

  // Fim do :root Css
  echo '}';

  $css = ob_get_clean();
  return $css;
}


/**
 * Modify our styles registration like so:
 */
function theme_enqueue_styles()
{
  wp_enqueue_style('theme-colors', get_stylesheet_uri()); // This is where you enqueue your theme's main stylesheet
  $custom_css = theme_get_customizer_admin_css();
  wp_add_inline_style('theme-colors', $custom_css);
}
