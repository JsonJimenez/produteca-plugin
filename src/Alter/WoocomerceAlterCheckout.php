<?php


namespace WPProduteca\Alter;

use WC_Shipping;
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
    $dataCost = $order->get_meta('cost_sale_json');
    if ($dataCost) {
      $costforitem = $dataCost;
    }
    elseif($costforitem = WC()->session->get('costforsale')) {
      update_post_meta($order_id, 'cost_sale_json', json_encode($costforitem));
      WC()->session->set('costforsale', false);
    }
    else {
      $costforProduct = FALSE;
    }
    $existeSale = $order->get_meta('produteca_sale_id');
    $status = $order->has_status('processing');
    if ($status && !$existeSale) {
      $finalItems = [];
      foreach ($items as $item) {
        $idProduct = $item->get_product_id();
        $produteca = $this->productService->getProductByCustomFieldId('id_produteca', $idProduct);
        if (!empty($produteca) && $produteca[0]->meta_value) {
          $client = get_post_meta($produteca[0]->ID, 'client_produteca', true );
          $costforProduct = 0;
          if ($costforitemdecode = json_decode($costforitem, TRUE)) {
            foreach ($costforitemdecode as $sessiondate) {
              if ($sessiondate['product_id'] == $item->get_product_id()) {
                $costforProduct += $sessiondate['total_cost'];
              }
            }
            $item->costshipping = $costforProduct;
          }

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