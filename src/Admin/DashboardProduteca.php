<?php

namespace WPProduteca\Admin;

use WPProduteca\Services\GenerateTableService;

class DashboardProduteca
{
  public function __construct()
  {
    $this->tableService = new GenerateTableService();
    $this->init();
  }

  public function init()
  {
    add_action('admin_menu', array($this, 'addMenuPageCallBack'), 20);
  }

  public function addMenuPageCallBack()
  {
    add_submenu_page(
      'producteca-admin-forms',
      'Produteca Dashboard',
      'Produteca Dashboard',
      'manage_options',
      'producteca-dashboard',
      array(&$this, 'buildForm'),
    );
  }

  public function buildForm()
  {
    $head = [
      'name' => 'Nombre',
      'stock' => 'Stock',
      'price' => 'Precio',
      'date' => 'Fecha',
      'supplier' => 'Proveedor',
      'id_product' => 'Produteca ID',
      'actions' => 'Acción'
    ];
    $filters = $this->getFilters();
    $data = $this->getProducts($filters);
    $build = $this->tableService->generateTable($head, $data['result']);
    $pager = $this->getPager($data);
    $name = $filters['name'] ?? FALSE;
    $proveedor = $filters['proveedor'] ?? FALSE;
    $id_produteca = $filters['id_produteca'] ?? FALSE;
    ?>
    <div class="wrap">
      <form method="post">
        <div class="tablenav">
          <label for="name">Nombre</label>
          <input type="text" id="name" name="name" value="<?php echo $name?>">
          <label for="proveedor">Proveedor</label>
          <input type="text" id="proveedor" name="proveedor" value="<?php echo $proveedor?>">
          <label for="id_produteca">Produteca ID</label>
          <input type="number" id="id_produteca" name="id_produteca" value="<?php echo $id_produteca?>">
        </div>
        <div class="tablenav">
          <input type="submit" name="submit_form" value="Filtrar">
        </div>
        <?php
        echo $build;
        echo $pager;
        ?>
      </form>
    </div>

    <?php
  }

  public function getPager($total)
  {
    $pager = $_GET['paged'] ?? 1;
    $totalPage = ceil($total['total'] / 20);
    if ($pager > $totalPage) {
      $pager = $totalPage;
    }
    $nextPage = $pager + 1;

    $previouspage = $pager - 1;

    $markupLinks = "";
    if ($pager == 1) {
      $markupLinks .= "<span class='tablenav-pages-navspan button disabled' aria-hidden='true'>«</span>";
    }
    else {
      $markupLinks .= "<a class='first-page button' href='/wp-admin/admin.php?page=producteca-dashboard'><span class='screen-reader-text'>Primera página</span><span aria-hidden='true'>«</span></a>";
    }

    if ($previouspage < 1) {
      $markupLinks .= "<span class='tablenav-pages-navspan button disabled' aria-hidden='true'>‹</span>";
    }
    else {
      $markupLinks .= "<a class='prev-page button' href='/wp-admin/admin.php?page=producteca-dashboard&paged={$previouspage}'><span class='screen-reader-text'>Página anterior</span><span aria-hidden='true'>‹</span></a>";
    }

    $markupLinks .= "
    <span class='paging-input'>
      <span class='tablenav-paging-text'>{$pager} de 
        <span class='total-pages'>{$totalPage}</span>
      </span>
    </span>
    ";
    if ($nextPage > $totalPage) {
      $markupLinks .= "<span class='tablenav-pages-navspan button disabled' aria-hidden='true'>›</span>";
    }
    else {
      $markupLinks .= "<a class='next-page button' href='/wp-admin/admin.php?page=producteca-dashboard&paged={$nextPage}''><span class='screen-reader-text'>Página siguiente</span><span aria-hidden='true'>›</span></a>";
    }
    $markupLinks .= "<a class='last-page button' href='/wp-admin/admin.php?page=producteca-dashboard&paged={$totalPage}'><span class='screen-reader-text'>Última página</span><span aria-hidden='true'>»</span></a></span>";



    $markup = "      <div class='tablenav bottom'>
          <div class='tablenav-pages'>
            <span class='displaying-num'> {$total['total']} elementos</span>
            <span class='pagination-links'>
              <span class='pagination-links'>
                {$markupLinks}
            </span>
          </div>
        </div>";
    return $markup;
  }

  public function getFilters()
  {
    $filters = [];
    if ($_POST['submit_form'] === 'Filtrar'){
      $filters['name'] = $_POST['name'] ?? FALSE;
      $filters['proveedor'] = $_POST['proveedor'] ?? FALSE;
      $filters['id_produteca'] = $_POST['id_produteca'] ?? FALSE;
    }
    return $filters;
  }

  public function getProducts($filters) {
    global $wpdb;

    $query = $this->query($filters);

    $total = $wpdb->get_var( "SELECT COUNT(1) FROM (${query}) AS combined_table" );

    $items_per_page = 20;
    $page = $_GET['paged'] ?? 1;
    $totalPage = ceil($total / $items_per_page);
    if ($page > $totalPage) {
      $page = $totalPage;
    }
    $offset = ( $page * $items_per_page ) - $items_per_page;
    $latestposts = $wpdb->get_results( $query . " LIMIT ${offset}, ${items_per_page}" );
    $result = [];

    foreach ($latestposts as $latestpost) {
      $product = wc_get_product($latestpost->id);
      $url = $product->get_slug();
      if($product->is_type('variable')) {
        $valuesVariation = $product->get_available_variations();
        $stock = "<mark>In stock</mark>(";
        foreach ($valuesVariation as $value) {
          $stock .= "<span>{$value['max_qty']}</span>,";
        }
        $stock .= ")";
      }
      else {
        $stock = $product->get_stock_quantity() ?? 0;
      }
      $result[$latestpost->id] = [
        'name' => $latestpost->title,
        'stock' => $stock,
        'price' => number_format($latestpost->price, 2),
        'date' => $product->get_date_created(),
        'supplier' => $latestpost->client_produteca,
        'id_product' => $latestpost->id_produteca,
        'actions' => "
          <div>
            <p><a href='/wp-admin/post.php?post={$latestpost->id}'>Editar</a></p>
            <p><a href='/{$url}'>Ver</a></p>
          </div>
        "
      ];
    }

    return ['result' => $result, 'total' => $total];
  }

  public function query($filters)
  {
    $query = "
      SELECT
          p.ID AS 'id',
          p.post_title AS 'title',
          m.meta_value AS 'stock',
          pm.meta_value AS 'price',
          p.post_date AS 'date',
          client.meta_value as client_produteca,
          produteca.meta_value as id_produteca
      FROM
          wp_posts p
      INNER JOIN
          wp_postmeta m ON p.ID = m.post_id
      INNER JOIN
          wp_postmeta pm ON p.ID = pm.post_id
      INNER JOIN
          wp_postmeta client ON p.ID = client.post_id
      INNER JOIN
        wp_postmeta produteca ON p.ID = produteca.post_id
      WHERE
          p.post_type = 'product'
          AND p.post_status = 'publish'
          AND m.meta_key = '_stock'
          AND pm.meta_key = '_price'
          AND client.meta_key = 'client_produteca'
          AND produteca.meta_key = 'id_produteca'
    ";

    if (!empty($filters)) {
      if ($filters['name']) {
        $query .= "AND p.post_title LIKE '%{$filters['name']}%'";
      }
      if ($filters['proveedor']) {
        $query .= "AND client.meta_value = {$filters['proveedor']}";
      }
      if ($filters['id_produteca']) {
        $query .= "AND produteca.meta_value = {$filters['id_produteca']}";
      }
    }
    $query .= "ORDER BY p.post_date DESC";

    return $query;
  }


}