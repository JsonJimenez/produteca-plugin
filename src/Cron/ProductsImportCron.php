<?php

namespace WPProduteca\Cron;

use WPProduteca\Services\ProductecaService;

/**
 *
 */
class ProductsImportCron {
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
    add_action('produteca_products', [$this, 'pr_import_productos']);
    add_action('produteca_products_all', [$this, 'pr_import_productos_all']);
    add_action('produteca_update_cuit', [$this, 'updateCoutMobbex']);
  }

  /**
   * @return void
   * @throws \HTTP_Request2_LogicException
   * @throws \WC_Data_Exception
   */
  public function pr_import_productos() {
   $this->productService->insertProducts();
  }

  /**
   * @return void
   * @throws \HTTP_Request2_LogicException
   * @throws \WC_Data_Exception
   */
  public function pr_import_productos_all() {
    $this->productService->insertProducts(TRUE);
  }

  public function updateCoutMobbex() {
    $products = $this->productService->getAllProductByCustomField('client_produteca');
    $clients = $this->productService->getClientsConfig();
    if ($products) {
      foreach ($products as $product) {
        $client = $this->productService->searchIncolumn($clients, $product->meta_value,  'clientid');
        if ($client) {
          $product = wc_get_product( $product->ID );
          $data_store = $product->get_data_store();
          $shipping_class_id = $data_store->get_shipping_class_id_by_slug( wc_clean( $client['slugflexibleshipping'] ) );
          $product->set_shipping_class_id( $shipping_class_id ); // Set the shipping class ID
          $product->update_meta_data('mobbex_marketplace_cuit', $client['cuit']);
          $product->save();
          update_post_meta($product->ID, 'mbbx_enable_multisite', FALSE);
        }
      }
    }
  }

  public function save_store($meta_type, $client, $id)
  {
    /*$stores = get_option('mbbx_stores') ?: [];

    $store = md5($client['mbbxapikey'] . '|' . $client['mbbxaccesstoken']);

    if (array_key_exists($store, $stores)) {
      update_metadata($meta_type, $id, 'mbbx_store', $store);
    }
    else {
      if ($client['mbbxapikey'] && $client['mbbxaccesstoken']) {
        $new_store          = $store;
        $stores[$new_store] = [
          'name' => $client['mbbxstorename'],
          'mbbx_api_key' => $client['mbbxapikey'],
          'mbbx_access_token' => $client['mbbxaccesstoken']
        ];
        update_option('mbbx_stores', $stores) && update_metadata($meta_type, $id, 'mbbx_store', $new_store);
      }
    }*/
  }
}