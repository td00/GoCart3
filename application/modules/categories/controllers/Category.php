<?php namespace GoCart\Controller;
/**
 * Category Class
 *
 * @package     GoCart
 * @subpackage  Controllers
 * @category    Category
 * @author      Clear Sky Designs
 * @link        http://gocartdv.com
 */

class Category extends Front {

    public function index($slug) {

        \CI::lang()->load('categories');

        $category = \CI::Categories()->slug($slug);

        //no category? show 404
        if(!$category)
        {
            throw_404();
            return;
        }

        $this->view('categories/'.$category->template, ['category'=>$category]);
    }

    public function products($count = false)
    {
        $customer = \CI::Login()->customer();

        $params = [
            'category_id'=>\CI::input()->post('categoryId'),
            'order_by'=>\CI::input()->post('orderBy'),
            'sort_order'=>\CI::input()->post('sortOrder'),
            'rows'=>\CI::input()->post('rows'),
            'page'=>(intval(\CI::input()->post('page'))-1) * intval(\CI::input()->post('rows')),
            'enabled_'.$customer->group_id=>1,
            'product_pricing.group_id'=>$customer->group_id
            ];

        $products = \CI::Products()->products($params, $count);

        echo json_encode($products);
    }

    public function countProducts()
    {
        $this->products(true);
    }

    public function shortcode($slug = false, $perPage = false)
    {
        if(!$perPage)
        {
            $perPage = config_item('products_per_page');
        }

        $products = \CI::Categories()->get($slug, 'id', 'ASC', 0, $perPage);

        return $this->partial('categories/products', $products);
    }

}
