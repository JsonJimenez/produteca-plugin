<?php


namespace WPProduteca\Alter;

use WPProduteca\Services\ProductecaService;

class WoocomerceAlterCheckout
{
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

  public function init() {
    //add_action( 'woocommerce_order_status_processing', [$this, 'sendSaleOrder']);
    add_action( 'woocommerce_order_status_processing', [$this, 'sendSaleOrder']);
    add_action( 'woocommerce_payment_complete', [$this, 'sendSaleOrder']);
    add_action( 'woocommerce_before_thankyou', [$this, 'sendSaleOrder']);
  }

  public function sendSaleOrder($order_id) {
    $order = wc_get_order($order_id);
    $items = $order->get_items();
    $existeSale = $order->get_meta('produteca_sale_id');
    $status = $order->has_status('processing');
    if ($status && !$existeSale) {
      $finalItems = [];
      foreach ($items as $item) {
        $idProduct = $item->get_product_id();
        $produteca = $this->productService->getProductByCustomFieldId('id_produteca', $idProduct);
        if (!empty($produteca) && $produteca[0]->meta_value) {
          $client = get_post_meta($produteca[0]->ID, 'client_produteca', true );
          $finalItems[$client][] = $item;
        }
      }

      if ($finalItems) {
        foreach ($finalItems as $key => $item) {
          $client = $this->productService->getClientByCLientId($key);
          if ($client) {
            $this->productService->createSale($client, $order->get_data(), $item, $order_id);
          }
        }
      }
    }
  }
}