<?php

namespace WPProduteca\Services;

/**
 *
 */
class ProductecaService {
  protected $conectionProduteca;
  protected $managerProduteca;
  public function __construct()
  {
    $this->conectionProduteca = new ConectionProduteca();
    $this->managerProduteca = new ManagerProduteca();
  }


  /**
   * @return void
   * @throws \HTTP_Request2_LogicException
   * @throws \WC_Data_Exception
   */
  public function insertProducts($all = FALSE) {
    $clients = $this->getClientsConfig();
    foreach ($clients as $client) {
      $reponse = $this->conectionProduteca->getProducts($client, $all);
      foreach ($reponse as $item) {
        $post_id = $this->getProductByCustomField('id_produteca', $item->id);
        if (empty($post_id)) {
          if ($item->hasVariations) {
            $this->managerProduteca->createProductVariation($item, $client);
          }
          else {
            $this->managerProduteca->createProduct($item, $client);
          }
        }

      }
    }
  }

  public function updateProduct($prodct, $item) {
    $this->managerProduteca->updateProduct($prodct, $item);
  }

  public function getProduct($client, $productId) {
    return $this->conectionProduteca->getProduct($client, $productId);
  }

  public function createSale($client, $data, $finalItems, $order_id) {
    $dataSale = $this->managerProduteca->createModelSale($data, $finalItems, $order_id);
    $response = $this->conectionProduteca->createSale($client, $dataSale);
    if (is_array($response)) {
      $saleResponse = end($response);
      if (is_object($saleResponse[0])) {
        update_post_meta($order_id, 'produteca_sale_id', $saleResponse[0]->id);
      }
    }
  }

  public function updateOrder($integrationId) {
    $id = str_replace('CRMKPLC', '', $integrationId);
    $order = wc_get_order($id);
    $existeSale = $order->get_meta('produteca_sale_id');
    $itemsSuccess = [];
    if ($order && $existeSale) {
      $clients = [];
      $items = $order->get_items();
      foreach ($items as $item) {
        $idProduct = $item->get_product_id();
        $produteca = $this->getProductByCustomFieldId('id_produteca', $idProduct);
        if (!empty($produteca) && $produteca[0]->meta_value) {
          $client = get_post_meta($produteca[0]->ID, 'client_produteca', true );
          if ($client) {
            $clients[] = $this->getClientByCLientId($client);
          }
        }
      }
      foreach ($clients as $value) {
        $response = $this->conectionProduteca->getSale($value, $integrationId);
        if (is_object($response)) {
          if (!$response->isCanceled) {
            $itemsSuccess[] = TRUE;
          }
        }
      }
      $countProducts = count($clients);
      $totalItemsSuccess = count($itemsSuccess);
      if ($totalItemsSuccess == 0) {
        $order->set_status('wc-cancelled');
      }
      elseif ($itemsSuccess && $countProducts == $totalItemsSuccess) {
        $order->set_status('wc-on-hold');
      }
      else {
        $order->set_status('wc-failed');
      }
      $order->save();
    }
  }

  public function getProductByCustomField($customField, $value) {
    global $wpdb;
    $post_id = $wpdb->get_results("
        SELECT postmeta.post_id FROM $wpdb->postmeta AS postmeta
        INNER JOIN $wpdb->posts AS posts ON posts.ID = postmeta.post_id
        WHERE posts.post_status != 'trash' AND postmeta.meta_key = '". $customField ."' AND postmeta.meta_value = '". $value ."' LIMIT 1");
    return $post_id;
  }

  public function getProductByCustomFieldId($customField, $value) {
    global $wpdb;
    $post_id = $wpdb->get_results("
        SELECT posts.ID, postmeta.meta_value FROM $wpdb->postmeta AS postmeta
        INNER JOIN $wpdb->posts AS posts ON posts.ID = postmeta.post_id
        WHERE posts.post_status != 'trash' AND postmeta.meta_key = '". $customField ."' AND posts.ID = '". $value ."' LIMIT 1");
    return $post_id;
  }

  public function getAllProductByCustomField($customField) {
    global $wpdb;
    $post_id = $wpdb->get_results("
        SELECT posts.ID, postmeta.meta_value FROM $wpdb->postmeta AS postmeta
        INNER JOIN $wpdb->posts AS posts ON posts.ID = postmeta.post_id
        WHERE posts.post_status != 'trash' AND postmeta.meta_key = '{$customField}'");
    return $post_id;
  }

  public function getClientsConfig() {
    $clients = [];
    $all_options = wp_load_alloptions();
    foreach ($all_options as $key => $option) {
      $explode = explode('_', $key);
      if ($explode[0] === 'produtecaclientsoption') {
        if ($option) {
          $clients[$explode[1]][$explode[2]] = $option;
        }
      }
    }
    return $clients;
  }

  public function getClientByCLientId($clientId) {
    $clients = $this->getClientsConfig();
    return $this->searchIncolumn($clients, $clientId,  'clientid');
  }

  public function searchIncolumn($data, $id, $column) {
    $value = array_search($id, array_column($data, $column));
    return $value === false ? [] : $data[$value];
  }
}