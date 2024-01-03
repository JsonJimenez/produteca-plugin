<?php


namespace WPProduteca\Alter;



use WPProduteca\Services\ProductecaService;

class CoreWoocomerceAlter {

  public function __construct()
  {
    $this->init();
  }

  public function init() {
    add_filter( 'wc_order_statuses', [$this, 'changeLabels']);
  }

  public function changeLabels($order_statuses) {
    $order_statuses['wc-on-hold'] = _x( 'En camino', 'Order status', 'woocommerce' );
    return $order_statuses;
  }
}