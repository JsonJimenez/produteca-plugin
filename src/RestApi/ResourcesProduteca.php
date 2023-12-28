<?php


namespace WPProduteca\RestApi;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WPProduteca\Services\ProductecaService;

/**
 *
 */
class ResourcesProduteca {
  /**
   * @var ProductecaService
   */
  protected $productService;

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
  public function init() {
    add_action( 'rest_api_init', function () {
      register_rest_route(
        'api/v1',
        '/produteca/callback',
        array(
          'methods' => 'POST',
          'callback' => array( $this, 'get' ),
        ),
      );
    });
  }

  /**
   * @param WP_REST_Request $request
   * @return WP_Error|\WP_HTTP_Response|\WP_REST_Response
   * @throws \HTTP_Request2_LogicException
   */
  public function get(WP_REST_Request $request) {
    $parameters = $request->get_query_params();
    $response = [
      'status' => 'error',
      'code' => 400
    ];
    $type = $parameters['resourceType'];
    if ($type == 'salesOrder') {
      $integrationId = $parameters['resourceId'];
      $this->productService->updateOrder($integrationId);
      $response = [
        'status' => 'success',
        'code' => 200,
        'integrationId' => $integrationId
      ];
      return new WP_REST_Response( $response, 200);
    }
    else {
      $productId = $parameters['resourceId'];

      $productPostId = $this->productService->getProductByCustomField('id_produteca', $productId);
      try {
        if ($productPostId && !empty($productPostId)) {
          $prodct = wc_get_product($productPostId[0]->post_id);
          $client = get_post_meta($productPostId[0]->post_id, 'client_produteca', true );
          $update = [
            'status' => 'no',
            'client' => $client
          ];
          if ($prodct && $client) {
            $clientLoad = $this->productService->getClientByCLientId($client);
            $productRest = $this->productService->getProduct($clientLoad, $productId);
            $this->productService->updateProduct($prodct, $productRest);
            $update['status'] = 'si';
          }
          $response = [
            'status' => 'success',
            'code' => 200,
            'productId' => $productId,
            'update' => $update
          ];
        }
        else {
          $response = [
            'status' => 'success',
            'code' => 200,
            'message' => 'Porducto no existe'
          ];
          $this->productService->insertProducts();
        }
        return new WP_REST_Response($response, 200);
      }catch (\Exception $exception) {
        return new WP_REST_Response( $response, 400);
      }
    }
  }
}