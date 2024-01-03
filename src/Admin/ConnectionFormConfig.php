<?php

namespace WPProduteca\Admin;

use WC_Shipping;
use WPProduteca\Services\ProductecaService;

/**
 *
 */
class ConnectionFormConfig {
  /**
   * @var ProductecaService
   */
  protected $productService;

  /**
   *
   */
  const ID = 'producteca-admin-forms';

  /**
   *
   */
  public function __construct(
  )
  {
    $this->productService = new ProductecaService();
    $this->init();
  }

  /**
   * @return void
   */
  public function init()
  {
    add_action('admin_menu', array($this, 'add_menu_page'), 20);
  }

  /**
   * @return string
   */
  public function get_id()
  {
    return self::ID;
  }

  /**
   * @return void
   */
  public function add_menu_page()
  {
    add_menu_page(
      'Produteca',
      'Produteca',
      'manage_options',
      'producteca-admin-forms',
      array(&$this, 'listCLients'),
      'dashicons-admin-page'
    );
    add_action( 'admin_init', array(&$this, 'registerSettings') );
  }

  public function listCLients() {
    $sections = $this->getCurrentClients();
    ?>
    <div class="wrap">
      <form method="post" action="options.php">
        <?php settings_fields( 'clients_group' ); ?>
        <table cellspacing="4" cellpadding"3">

        <?php
        foreach ($sections as $section => $item) {
          $section_label = $section + 1;
          echo ('<th scope="row" colspan="2" align="left"><br><h2>'.$section_label.' Cliente'.'</h2></th>');
          foreach ($item as $key => $str)    {
            $field      = strtolower( 'produtecaclientsoption_'.$section.'_'.$key );
            $value      = get_option($field);
            $input      = "<input type='{$str['field']}' name='{$field}' id='{$field}' value='{$value}' size='80'>";
            $label_for      = '<label for='.$field.'>' . $str['title'] . '</label>';
            ?><tr><th scope="row" align="left"><?php echo $label_for; ?></th><td><?php echo $input; ?></td></tr><?php
          }
        }?>
        </table>
        <?php  submit_button(); ?>
      </form>
    </div>
    <?php
  }

  public function registerSettings () {
    $sections = $this->getCurrentClients();

    foreach ($sections as $section => $item) {
      add_settings_section($section, $section, function (){
        echo '<p>Configuración produteca</p>';
      }, 'produteca_config');
      foreach ($item as $key => $str)    {
        $field  = strtolower( 'produtecaclientsoption_'.$section.'_'.$key );
        $value  = get_option($field);
        add_option($field, $value);
        register_setting('clients_group', $field);
      }
    }
  }

  public function getCurrentClients() {
    $model = [
      'model' => [
        'clientid' => [
          'field' => 'text',
          'title' => 'Client ID'
        ],
        'accesstoken' => [
          'field' => 'text',
          'title' => 'Access Token'
        ],
        'cuit' => [
          'field' => 'text',
          'title' => 'Cuit'
        ],
        'slugflexibleshipping' => [
          'field' => 'text',
          'title' => 'Slug Flexible Shipping'
        ]
        /*'mbbxstorename' => [
          'field' => 'text',
          'title' => 'Nombre tienda Mobbex'
        ],
        'mbbxapikey' => [
          'field' => 'text',
          'title' => 'Clave de API Mobbex'
        ],
        'mbbxaccesstoken' => [
          'field' => 'text',
          'title' => 'Token de Acceso Mobbex'
        ],*/
      ],
    ];
    $sections = [];
    $all_options = wp_load_alloptions();
    foreach ($all_options as $key => $option) {
      $explode = explode('_', $key);
      if ($explode[0] == 'produtecaclientsoption') {
        if ($option) {
          $sections[$explode[1]] = $model['model'];
        }
        else {
          delete_option($key);
          unregister_setting('clients_group', $key);
        }
      }
    }
    $sections[] = $model['model'];
    return $sections;
  }
}