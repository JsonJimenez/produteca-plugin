<?php


namespace WPProduteca\Admin;

use WP_Query;

class ConnectionFormProduteca {
  public function __construct(
  )
  {
    $this->init();
  }

  public function init()
  {
    add_action('admin_menu', array($this, 'addMenuPageCallBack'), 20);
  }

  public function addMenuPageCallBack()
  {
    add_submenu_page(
      'producteca-admin-forms',
      'Produteca Conection',
      'Produteca Conection',
      'manage_options',
      'producteca-admin-api-form',
      array(&$this, 'buildForm'),
    );
    add_action( 'admin_init', array(&$this, 'registerSettings') );
  }

  public function buildForm() {
    $sections = $this->buildFIelds();
    ?>
    <div class="wrap">
      <form method="post" action="options.php">
        <?php settings_fields( 'conectionproduteca_group' ); ?>
        <table cellspacing="4" cellpadding"3">

        <?php
        foreach ($sections as $section => $item) {
          foreach ($item as $key => $str)    {
            $field      = strtolower( 'produtecaapioption_'.$section.'_'.$key );
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
    $sections = $this->buildFIelds();
    foreach ($sections as $section => $item) {
      add_settings_section($section, $section, function (){
        echo '<p>Configuración produteca</p>';
      }, 'conection_produteca_config');
      foreach ($item as $key => $str)    {
        $field  = strtolower( 'produtecaapioption_'.$section.'_'.$key );
        $value  = get_option($field);
        add_option($field, $value);
        register_setting('conectionproduteca_group', $field);
      }
    }
  }

  public function buildFIelds() {
    $model = [
      'model' => [
        'apiurl' => [
          'field' => 'text',
          'title' => 'Consulta del feed de productos'
        ],
        'apiurlproduct' => [
          'field' => 'text',
          'title' => 'Consulta de Producto'
        ],

        'apiurlsale' => [
          'field' => 'text',
          'title' => 'Creación de venta'
        ],
        'apiurlsaleconsult' => [
          'field' => 'text',
          'title' => 'Consulta de venta'
        ],
        'defaultcuit' => [
          'field' => 'text',
          'title' => 'Cuit'
        ],
      ],
    ];
    $sections = [];
    $all_options = wp_load_alloptions();
    foreach ($all_options as $key => $option) {
      $explode = explode('_', $key);
      if ($explode[0] == 'produtecaapioption') {
        if ($option) {
          $sections[$explode[1]] = $model['model'];
        }
        else {
          delete_option($key);
          unregister_setting('conectionproduteca_group', $key);
        }
      }
    }
    if (empty($sections)) {
      $sections[] = $model['model'];
    }
    return $sections;
  }
}