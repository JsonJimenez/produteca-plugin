<?php


namespace WPProduteca\RestApi;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WPProduteca\Services\ManagerProduteca;
use WPProduteca\Services\ProductecaService;

/**
 *
 */
class ResourcesProduteca {
  /**
   * @var ProductecaService
   */
  protected $productService;
  protected $managerProduteca;

  /**
   *
   */
  public function __construct(
  )
  {
    $this->productService = new ProductecaService();
    $this->managerProduteca = new ManagerProduteca();
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
    $companyId = $parameters['companyId'];
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
          $update = [
            'status' => 'no',
            'client' => $companyId
          ];
          if ($prodct && $companyId) {
            $clientLoad = $this->productService->getClientByCLientId($companyId);
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
          $clientLoad = $this->productService->getClientByCLientId($companyId);
          $productRest = $this->productService->getProduct($clientLoad, $productId);
          if ($productRest->hasVariations) {
            $this->managerProduteca->createProductVariation($productRest, $clientLoad);
          }
          else {
            $this->managerProduteca->createProduct($productRest, $clientLoad);
          }
          $response = [
            'status' => 'success',
            'code' => 200,
            'message' => 'Producto creado con exito'
          ];

        }
        return new WP_REST_Response($response, 200);
      }catch (\Exception $exception) {
        return new WP_REST_Response( $response, 400);
      }
    }
  }
}