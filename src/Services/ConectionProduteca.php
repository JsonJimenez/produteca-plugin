<?php

namespace WPProduteca\Services;

use HTTP_Request2;
use HTTP_Request2_Exception;

class ConectionProduteca {
  /**
   * @var HTTP_Request2
   */
  protected $request2;

  /**
   *
   */
  public function __construct()
  {
    $this->request2 = new HTTP_Request2();
  }

  public function getProducts($client, $pager = FALSE) {
    $conectionConfig = $this->getConectionConfig();
    $accessToken = $client['accesstoken'];
    $apiUrl = $conectionConfig['apiurl'];
    $pagerproduteca = 0;
    if ($pager) {
      $url = "{$apiUrl}?access_token={$accessToken}&page=$pagerproduteca";
    }
    else {
      $url = "{$apiUrl}?access_token={$accessToken}";
    }
    $this->request2->setUrl($url);
    $this->request2->setMethod(HTTP_Request2::METHOD_GET);
    try {
      $response = $this->request2->send();
      if ($response->getStatus() == 200) {
        return json_decode($response->getBody());
      }
      return [];
    }
    catch(HTTP_Request2_Exception $e) {
      return [];
    }
  }

  public function getProduct($client, $productId) {
    $conectionConfig = $this->getConectionConfig();
    $accessToken = $client['accesstoken'];
    $apiUrl = $conectionConfig['apiurlproduct'];
    $url = "{$apiUrl}/{$productId}";
    $this->request2->setUrl($url);
    $this->request2->setMethod(HTTP_Request2::METHOD_GET);
    $this->request2->setHeader('Authorization', "Bearer {$accessToken}");
    $this->request2->setHeader('Accept', '*/*');
    try {
      $response = $this->request2->send();
      if ($response->getStatus() == 200) {
        return json_decode($response->getBody());
      }
      return $response->getReasonPhrase();
    }
    catch(HTTP_Request2_Exception $e) {
      return $e->getCause();
    }
  }

  public function createSale($client, $body) {
    $conectionConfig = $this->getConectionConfig();
    $accessToken = $client['accesstoken'];
    $apiUrl = $conectionConfig['apiurlsale'];
    $this->request2->setUrl($apiUrl);
    $this->request2->setMethod(HTTP_Request2::METHOD_POST);
    $this->request2->setHeader('Authorization', "Bearer {$accessToken}");
    $this->request2->setHeader('Accept', '*/*');
    $this->request2->setHeader('Content-Type', 'application/json');
    $this->request2->setBody($body);
    try {
      $response = $this->request2->send();
      if ($response->getStatus() == 200) {
        return json_decode($response->getBody());
      }
      return $response->getStatus();
    }
    catch(HTTP_Request2_Exception $e) {
      return $e->getCode();
    }
  }

  public function getSale($client, $integrationId) {
    $conectionConfig = $this->getConectionConfig();
    $accessToken = $client['accesstoken'];
    $apiUrl = $conectionConfig['apiurlsaleconsult'];
    $url = "{$apiUrl}?integrationId={$integrationId}";
    $this->request2->setUrl($url);
    $this->request2->setMethod(HTTP_Request2::METHOD_GET);
    $this->request2->setHeader('Authorization', "Bearer {$accessToken}");
    $this->request2->setHeader('Accept', '*/*');
    $this->request2->setHeader('Content-Type', 'application/json');
    try {
      $response = $this->request2->send();
      if ($response->getStatus() == 200) {
        return json_decode($response->getBody());
      }
      return $response->getStatus();
    }
    catch(HTTP_Request2_Exception $e) {
      return $e->getCode();
    }
  }

  public function getConectionConfig() {
    $data = [
      'apiurl' => get_option('produtecaapioption_0_apiurl'),
      'apiurlproduct' => get_option('produtecaapioption_0_apiurlproduct'),
      'apiurlsale' => get_option('produtecaapioption_0_apiurlsale'),
      'apiurlsaleconsult' => get_option('produtecaapioption_0_apiurlsaleconsult'),
    ];
    return $data;
  }
}