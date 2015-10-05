<?php

$routes[] = ['GET', '/admin/categories', 'GoCart\Controller\AdminCategories#index'];
$routes[] = ['GET|POST', '/admin/categories/form/[i:id]?', 'GoCart\Controller\AdminCategories#form'];
$routes[] = ['GET|POST', '/admin/categories/delete/[i:id]', 'GoCart\Controller\AdminCategories#delete'];
$routes[] = ['GET|POST', '/category/products', 'GoCart\Controller\Category#products'];
$routes[] = ['GET|POST', '/category/products/count', 'GoCart\Controller\Category#countProducts'];
$routes[] = ['GET|POST', '/category/[:slug]', 'GoCart\Controller\Category#index'];


$themeShortcodes[] = ['shortcode'=>'category', 'method'=>['GoCart\Controller\Category', 'shortcode']];