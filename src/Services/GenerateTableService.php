<?php

namespace WPProduteca\Services;

class GenerateTableService
{
  public function generateTable($header = [], $rows = [])
  {

    $head = "<thead><tr>";
    foreach ($header as $item) {
      $head .= "<th class='manage-column'>{$item}</th>";
    }
    $head .= "</tr></thead>";

    $body = "<tbody class='the-list'>";
    if (!empty($rows)) {
      foreach ($rows as $item) {
        $tr = "<tr>";
        foreach ($item as $value) {
          $tr .= "<th class='column'>{$value}</th>";
        }
        $tr .= "</tr>";
        $body .= $tr;
      }
    }
    $body .= "</tbody>";

    $build = "<table class='wp-list-table widefat fixed striped table-view-list'>";
    $build .= $head;
    $build .= $body;
    $build .= "</table>";
    return $build;
  }
}