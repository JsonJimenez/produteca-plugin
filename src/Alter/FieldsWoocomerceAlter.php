<?php


namespace WPProduteca\Alter;

use WPProduteca\Services\ProductecaService;

/**
 *
 */
class FieldsWoocomerceAlter {
  /**
   * @var ProductecaService
   */
  protected $productService;
  /**
   *
   */
  public function __construct()
  {
    $this->productService = new ProductecaService();
    $this->init();
  }

  /**
   * @return void
   */
  public function init()
  {
    //Add fields product general
    add_action( 'woocommerce_product_options_general_product_data', [$this, 'addFields']);
    add_action( 'woocommerce_process_product_meta', [$this, 'saveData']);
    //Add fields variations
    add_action( 'woocommerce_variation_options_pricing', [$this, 'addFieldsVariation'], 10, 3);
    add_action( 'woocommerce_save_product_variation', [$this, 'saveDataVariation']);
    //Add hidden facture
    add_action( 'woocommerce_admin_order_data_after_order_details', [$this, 'addFieldsOrder']);
    //add_action( 'woocommerce_thankyou', [$this, 'sendSaleOrder']);
  }

  /**
   * @return void
   */
  public function addFields(){
    global $woocommerce, $post;
    echo '<div class=" product_custom_field ">';
    woocommerce_wp_text_input(
      array(
        'id'          => 'id_produteca',
        'label'       => __( 'Produteca ID' ),
        'placeholder' => 'ID de sincronización',
        'desc_tip'    => 'true'
      )
    );
    woocommerce_wp_text_input(
      array(
        'id'            => 'variation_produteca_only',
        'label'         => 'Variation Produteca',
        'desc_tip'      => true,
      )
    );
    woocommerce_wp_text_input(
      array(
        'id'            => 'client_produteca',
        'label'         => 'Proveedor',
        'desc_tip'      => true,
      )
    );
    echo '</div>';

  }

  /**
   * @param $post_id
   * @return void
   */
  public function saveData($post_id) {
    // Custom Product Text Field
    $id_produteca = $_POST['id_produteca'];
    if (!empty($id_produteca)) {
      update_post_meta($post_id, 'id_produteca', esc_attr($id_produteca));
    }

    $id_variation = $_POST['variation_produteca_only'];
    if (!empty($id_produteca)) {
      update_post_meta($post_id, 'variation_produteca_only', esc_attr($id_variation));
    }

    $client_produteca = $_POST['client_produteca'];
    if (!empty($id_produteca)) {
      update_post_meta($post_id, 'client_produteca', esc_attr($client_produteca));
    }
  }

  /**
   * @return void
   */
  public function addFieldsVariation($loop, $variation_data, $variation){
    echo '<div class=" variation_produteca ">';
    woocommerce_wp_text_input(
      array(
        'id'            => 'produteca_variation[' . $loop . ']',
        'label'         => 'Variation Produteca',
        'wrapper_class' => 'form-row',
        'desc_tip'      => true,
        'value'         => get_post_meta( $variation->ID, 'produteca_variation', true )
      )
    );
    echo '</div>';

  }

  /**
   * @param $post_id
   * @return void
   */
  public function saveDataVariation($variation_id, $loop) {
    $text_field = ! empty( $_POST[ 'produteca_variation' ][ $loop ] ) ? $_POST[ 'produteca_variation' ][ $loop ] : '';
    update_post_meta( $variation_id, 'produteca_variation', sanitize_text_field( $text_field ) );
  }

  public function addFieldsOrder($order)
  {
    $shippingdate = $order->get_meta('produteca_sale_id');
    $cost_sale_json = $order->get_meta('cost_sale_json');

    echo '<div>';
    woocommerce_wp_textarea_input(array(
      'id' => 'produteca_sale_id',
      'label' => 'Venta ID Produteca',
      'value' => $shippingdate,
      'wrapper_class' => 'form-field-wide'
    ));
    woocommerce_wp_textarea_input(array(
      'id' => 'cost_sale_json',
      'label' => 'Costo de envio',
      'value' => $cost_sale_json,
      'wrapper_class' => 'form-field-wide'
    ));
    echo '</div>';

  }
}