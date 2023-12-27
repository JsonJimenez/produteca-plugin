<?php

namespace WPProduteca;

use WPProduteca\Admin\ConnectionFormConfig;
use WPProduteca\Admin\ConnectionFormProduteca;
use WPProduteca\Alter\CoreWoocomerceAlter;
use WPProduteca\Alter\FieldsWoocomerceAlter;
use WPProduteca\Alter\WoocomerceAlterCheckout;
use WPProduteca\Cron\ProductsImportCron;
use WPProduteca\RestApi\ResourcesProduteca;

/**
 *
 */
class Core {

  /**
   *
   */
  public function __construct()
  {
    $this->hooks();
  }

  /**
   * @return void
   */
  public function hooks()
  {
    $this->forms();
    $this->crons();
    $this->alters();
    $this->restapi();
  }

  /**
   * @return void
   */
  public function crons() {
    new ProductsImportCron();
  }

  /**
   * @return void
   */
  public function forms() {
    new ConnectionFormConfig();
    new ConnectionFormProduteca();
  }

  /**
   * @return void
   */
  public function alters() {
    new FieldsWoocomerceAlter();
    new WoocomerceAlterCheckout();
    new CoreWoocomerceAlter();
  }

  /**
   * @return void
   */
  public function restapi() {
    new ResourcesProduteca();
  }
}