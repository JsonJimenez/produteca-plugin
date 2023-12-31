<?php
/**
 * Plugin Name: Produteca Plugin
 * Plugin URI: https://www.nivelics.com/es
 * Description: Importación de productos produteca.
 * Version: 0.1
 * Author: Jeison Jimenez
 * Author URI: https://www.your-site.com/
 **/


if (!defined('ABSPATH')) {
  exit; // Exit if accessed directly.
}

/**
 * Autoloader. We need it being separate and not using Composer autoloader because of the Gmail libs,
 * which are huge and not needed for most users.
 * Inspired by PSR-4 examples: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader-examples.md
 *
 * @since 1.0.0
 *
 * @param string $class The fully-qualified class name.
 */
spl_autoload_register( function ( $class ) {

  list( $plugin_space ) = explode( '\\', $class );
  if ( $plugin_space !== 'WPProduteca' ) {
    return;
  }

  /*
   * This folder can be both "wp-mail-smtp" and "wp-mail-smtp-pro".
   */
  $plugin_dir = basename( __DIR__ );

  // Default directory for all code is plugin's /src/.
  $base_dir = plugin_dir_path( __DIR__ ) . '/' . $plugin_dir . '/src/';

  // Get the relative class name.
  $relative_class = substr( $class, strlen( $plugin_space ) + 1 );

  // Prepare a path to a file.
  $file = wp_normalize_path( $base_dir . $relative_class . '.php' );

  // If the file exists, require it.
  if ( is_readable( $file ) ) {
    /** @noinspection PhpIncludeInspection */
    require_once $file;
  }
} );

require_once __DIR__ . '/vendor/autoload.php';

/**
 * Global function-holder. Works similar to a singleton's instance().
 *
 * @since 1.0.0
 *
 * @return WPProduteca\Core
 */
function wp_produteca() {

  /**
   * @var \WPProduteca\Core
   */
  static $core;

  if ( ! isset( $core ) ) {
    $core = new \WPProduteca\Core();
  }

  return $core;
}

wp_produteca();