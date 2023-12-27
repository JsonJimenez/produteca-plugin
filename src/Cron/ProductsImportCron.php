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
          update_post_meta($product->ID, 'mobbex_marketplace_cuit', $client['cuit']);
          update_post_meta($product->ID, 'mbbx_enable_multisite', TRUE);
          update_post_meta($product->ID, 'mbbx_store_name', $client['mbbx_store_name']);
          update_post_meta($product->ID, 'mbbx_api_key', $client['mbbx_api_key']);
          update_post_meta($product->ID, 'mbbx_access_token', $client['mbbx_access_token']);
          $this->save_store('post', $client, $product->ID);
        }
      }
    }
  }

  public function save_store($meta_type, $client, $id)
  {
    $stores = get_option('mbbx_stores') ?: [];

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
    }
  }
}