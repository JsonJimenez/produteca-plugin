<?php

namespace WPProduteca\Services;

use WC_Countries;
use WC_Order_Item_Product;
use WC_Product;
use WC_Product_Simple;
use WC_Product_Variable;
use WC_Product_Variation;

class ManagerProduteca {
  protected $configStore;

  public function __construct()
  {}

  public function createProduct($item, $client) {
    $exist_sku = wc_get_product_id_by_sku($item->variations[0]->sku);
    if ($exist_sku) {
      return wc_get_product();
    }
    $product = new WC_Product_Simple();
    $product->set_name($item->name);
    $product->set_slug($this->createSlug($item->name));
    $product->set_sku($item->variations[0]->sku);
    $product->set_regular_price($item->price); // in current shop currency
    $product->set_description( $item->notes);
    $product->set_manage_stock(TRUE);
    $product->set_stock_quantity($item->variations[0]->stock);
    $product->update_meta_data('id_produteca', $item->id);
    $product->update_meta_data('variation_produteca_only', $item->variations[0]->id);
    $product->update_meta_data('client_produteca', $client['clientid']);
    $product->update_meta_data('mobbex_marketplace_cuit', $client['cuit']);
    $product->update_meta_data('mbbx_enable_multisite', TRUE);

    $idFile = $this->uploadImage($item->thumbnail);
    if ($idFile) {
      $product->set_image_id($idFile);
    }
    $idFIles = [];

    if (!empty($item->variations[0]->pictures)) {
      foreach ($item->variations[0]->pictures as $picture) {
        $idFIles[] = $this->uploadImage($picture->url);
      }
    }
    $product->set_width($item->dimensions->width);
    $product->set_height($item->dimensions->height);
    $product->set_length($item->dimensions->length);
    $product->set_weight($item->dimensions->weight);
    $product->set_gallery_image_ids($idFIles);
    $product->save();
    $this->save_store('post', $client, $product->get_id());
    return $product;
  }

  public function createProductVariation($item, $client) {
    // Create a variable product with a color attribute.
    $product = new WC_Product_Variable();
    $product->set_name($item->name);
    $product->set_slug($this->createSlug($item->name));
    $product->set_price($item->price); // in current shop currency
    $product->set_regular_price($item->activeDeal->regularPrice); // in current shop currency
    $product->set_description( $item->notes);
    $product->set_stock_quantity($item->variations[0]->stock);
    $product->update_meta_data('id_produteca', $item->id);
    $product->update_meta_data('variation_produteca_only', $item->variations[0]->id);
    $product->update_meta_data('client_produteca', $client['clientid']);
    $product->update_meta_data('mobbex_marketplace_cuit', $client['cuit']);
    $product->update_meta_data('mbbx_enable_multisite', TRUE);
    $idFile = $this->uploadImage($item->thumbnail);
    if ($idFile) {
      $product->set_image_id($idFile);
    }
    $idFIles = [];

    if (!empty($item->variations[0]->pictures)) {
      foreach ($item->variations[0]->pictures as $picture) {
        $idFIles[] = $this->uploadImage($picture->url);
      }
    }
    $product->set_gallery_image_ids($idFIles);
    $product->set_width($item->dimensions->width);
    $product->set_height($item->dimensions->height);
    $product->set_length($item->dimensions->length);
    $product->set_weight($item->dimensions->weight);
    $product->save();

    $attributes = [];
    $product_id = $product->get_id();
    foreach ($item->variations as $variationToAttr) {
      foreach ($variationToAttr->attributes as $attr) {
        $attributes[$attr->key][$attr->value] = $attr->value;
      }
    }
    $product_attributes = [];
    foreach ($attributes as $key => $terms) {
      $taxonomy = wc_attribute_taxonomy_name($key);
      $attr_label = ucfirst($key);
      $attr_name = ( wc_sanitize_taxonomy_name($key));
      if ($taxonomy) {
        $product_attributes[$taxonomy] = array (
          'name'         => $taxonomy,
          'value'        => '',
          'position'     => '',
          'is_visible'   => 1,
          'is_variation' => 1,
          'is_taxonomy'  => 1
        );
      }
      foreach( $terms as $value ){
        $term_name = ucfirst($value);
        $term_slug = sanitize_title($value);

        // Check if the Term name exist and if not we create it.
        if( ! term_exists( $value, $taxonomy ) )
          wp_insert_term( $term_name, $taxonomy, array('slug' => $term_slug ) ); // Create the term

        // Set attribute values
        wp_set_post_terms( $product_id, $term_name, $taxonomy, true );
      }
    }
    update_post_meta( $product_id, '_product_attributes', $product_attributes );
    $product->save();
    $this->save_store('post', $client, $product->get_id());
    foreach ($item->variations as $variationByItems) {
      $variation = new WC_Product_Variation();
      $variation->set_parent_id($product_id);
      $variation->set_sku($variationByItems->sku);
      $variation->set_price($product->get_price());
      $variation->set_regular_price($product->get_price());
      $idFIlesVariation = [];
      if (!empty($variationByItems->pictures)) {
        foreach ($variationByItems->pictures as $picture) {
          $idFIlesVariation[] = $this->uploadImage($picture->url);
        }
      }
      $setAttr = [];
      foreach ($variationByItems->attributes as $attr) {
        $taxonomy = wc_attribute_taxonomy_name($attr->key);
        if ($taxonomy) {
          $setAttr[$taxonomy] = sanitize_title($attr->value);
        }
      }
      $variation->set_attributes($setAttr);
      $variation->update_meta_data('produteca_variation', $variationByItems->id);
      $variation->set_gallery_image_ids($idFIlesVariation);
      $variation->set_manage_stock(TRUE);
      $variation->set_stock_quantity($variationByItems->stock);
      $variation->set_status('publish');
      $variation->save();
    }
  }

  public function updateProduct(WC_Product $product, $item) {
    $product->set_name($item->name);
    $product->set_regular_price($item->price); // in current shop currency
    $product->set_description( $item->notes);
    $product->set_stock_quantity($item->variations[0]->stock);
    $idFile = $this->uploadImage($item->thumbnail);
    if ($idFile) {
      $product->set_image_id($idFile);
    }
    $idFIles = [];

    if (!empty($item->variations[0]->pictures)) {
      foreach ($item->variations[0]->pictures as $picture) {
        $idFIles[] = $this->uploadImage($picture->url);
      }
    }
    $product->set_width($item->dimensions->width);
    $product->set_height($item->dimensions->height);
    $product->set_length($item->dimensions->length);
    $product->set_weight($item->dimensions->weight);
    $product->set_gallery_image_ids($idFIles);
    $product->save();
    if ($item->hasVariations) {
      $variations = $product->get_available_variations();
      $currentVariosn = [];
      foreach ($variations as $variation) {
        $currentVariosn[$variation['sku']] = $variation['variation_id'];
      }
      foreach ($item->variations as $variationItem) {
        if (in_array($variationItem->sku, $currentVariosn)) {
          $variation = new WC_Product_Variation($currentVariosn[$variation['sku']]);
          $variation->set_price($product->get_price());
          $variation->set_regular_price($product->get_price());
          $variation->set_manage_stock(TRUE);
          $variation->set_stock_quantity($variationItem->stock);
          $variation->save();
        }
      }
    }
  }

  public function getMobbexTransaction($order_id) {
    global $wpdb;
    $mobbex_transaction = $wpdb->get_results("
        SELECT * FROM wp_mobbex_transaction WHERE order_id".$order_id);
    return $mobbex_transaction;
  }

  public function createModelSale($data, $finalItems, $order_id) {
    $lines = [];
    $customData = $this->loadMetaValuesChekout($order_id);
    foreach ($finalItems as $item) {
      /* @var WC_Order_Item_Product $item */
      $product = wc_get_product($item->get_product_id());
      if($item->get_variation_id()) {
        $variationId = get_post_meta($item->get_variation_id(), 'produteca_variation', true );
      }
      else {
        $variationId = get_post_meta($item->get_product_id(), 'variation_produteca_only', true );
      }
      $lines[] = [
        'price' => $item->get_total() / $item->get_quantity(),
        'quantity' => $item->get_quantity(),
        'variation' => $product->get_sku(),
      ];
    }
    $fullName = "{$data['billing']['first_name']} {$data['billing']['last_name']}";
    $state = $this->getState($data['billing']['country'], $data['billing']['state']);
    $shipping_total = $data['shipping_total'] ?? 0;

    $pay_method = str_replace(' ', '', $data['payment_method_title']);

    if (strpos($pay_method, 'Mobbex') !== false) {
      $pay_method == "CreditCard";
    }

    //$pay_method == "CreditCard";

    $paymentNetwork = 'debvisa';
    $firstSixDigits = 000000;
    $lastFourDigits = 0000;
    $identification = '00000000';

    $mobbex_transaction = $this->getMobbexTransaction($order_id);

    if ($mobbex_transaction) {
        $result = reset($mobbex_transaction);
        
        $childs_json = json_decode($result->childs);
        
        $identification = $childs_json[0]->entity->payment->source->cardholder->identification;
        $number = $childs_json[0]->entity->payment->source->number;
        $paymentNetwork = $childs_json[0]->entity->payment->source->reference;

        $number_parts = explode('*', $number);
        $firstSixDigits = $number_parts[0];
        $lastFourDigits = $number_parts[1];
    }


    
    $sale = [
      'integrations' => [
        [
          'integrationId' => "{$data['id']}CRMKPLC"
        ]
      ],
      'contact' => [
        'contactPerson' => $fullName,
        'mail' => $data['billing']['email'],
        'phoneNumber' => $data['billing']['phone'],
        'taxId' => $customData['shipping_document'] ?: $customData['billing_document'],
        'location' => [
          'streetName' => $data['billing']['address_1'],
          'streetNumber' => $customData['shipping_street_number'] ?: $customData['billing_street_number'],
          'addressNotes' => $data['customer_note'],
          'state' => $state,
          'city' => $data['billing']['city'],
          'zipCode' => $data['billing']['postcode']
        ],
        'billingInfo' => [
          'docType' => $customData['billing_type_document'],
          'docNumber' => $customData['billing_document'],
          'streetName' => $data['billing']['address_1'],
          'streetNumber' => $customData['shipping_street_number'] ?: $customData['billing_street_number'],
          'comment' => $data['customer_note'],
          'zipCode' => $data['billing']['postcode'],
          'city' => $data['billing']['city'],
          'state' => $state,
          'businessName' => $data['billing']['company'],
          'stateRegistration' => '',
          'taxPayerType' => 'Consumidor Final',
          'firstName' => $data['billing']['first_name'],
          'lastName' => $data['billing']['last_name'],
        ]
      ],
      'shipments' => [],
      'lines' => $lines,
      'payments' => [
        [
          'amount' => (float)$data['total'] + $shipping_total,
          'status' => 'Approved',
          'method' => "CreditCard",
          'integrations' => [],
          'installments' => 1,
          'card' => [
            'paymentNetwork' => $paymentNetwork,
            'firstSixDigits' => $firstSixDigits,
            'lastFourDigits' => $lastFourDigits,
            'cardholderIdentificationNumber' => $identification,
            'cardholderIdentificationType' => 'CI',
            'cardholderName' => $fullName,
          ],
        ]
      ],
      'warehouse' => '',
      'warehouseId' => 0,
      'shippingCost' => $shipping_total,
      'isCanceled' => FALSE,
    ];
    return json_encode($sale);
  }

  protected function getState($country_code, $state_code) {
    $countries = new WC_Countries(); // Get an instance of the WC_Countries Object
    $country_states = $countries->get_states( $country_code );
    return $country_states[$state_code];
  }

  protected function uploadImage($file, $desc = 'Wp') {
    $file_array  = [ 'name' => wp_basename( $file ), 'tmp_name' => download_url( $file ) ];

    // If error storing temporarily, return the error.
    if ( is_wp_error( $file_array['tmp_name'] ) ) {
      return $file_array['tmp_name'];
    }

    // Do the validation and storage stuff.
    $id = media_handle_sideload( $file_array, 0, $desc );

    // If error storing permanently, unlink.
    if ( is_wp_error( $id ) ) {
      @unlink( $file_array['tmp_name'] );
      return $id;
    }

    return $id;
  }

  public function createSlug($str, $delimiter = '-'){

    $slug = strtolower(trim(preg_replace('/[\s-]+/', $delimiter, preg_replace('/[^A-Za-z0-9-]+/', $delimiter, preg_replace('/[&]/', 'and', preg_replace('/[\']/', '', iconv('UTF-8', 'ASCII//TRANSLIT', $str))))), $delimiter));
    return $slug;

  }

  public function loadMetaValuesChekout($orderId) {
    $values = [];
    $model = [
      'billing_type_document',
      'billing_document',
      'billing_street_number',
      'shipping_type_document',
      'shipping_document',
      'shipping_street_number',
    ];
    foreach ($model as $item) {
      $values[$item] = get_post_meta($orderId, $item, true);
    }
    return $values;
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