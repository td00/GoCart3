<?php
/**
 * Products Class
 *
 * @package     GoCart
 * @subpackage  Models
 * @category    Products
 * @author      Clear Sky Designs
 * @link        http://gocartdv.com
 */

Class Products extends CI_Model
{
    public function __construct()
    {
        $this->customer = \CI::Login()->customer();
    }

    public function getProduct($id)
    {
        //do this again right here since it can be used for combining the cart. We want to make sure it's fresh.
        $this->customer = \GC::getCustomer();

        //find the product
        $product = CI::db()->where('id', $id)->where('enabled_'.$this->customer->group_id, '1')->get('products')->row();
        if($product)
        {
            $product->pricing = $this->pricing($id, $this->customer->group_id);
            $product = $this->processImageDecoding($product);
        }
        
        return $product;
    }

    public function product_autocomplete($name, $limit)
    {
        return  CI::db()->like('name', $name)->get('products', $limit)->result();
    }

    public function pricing($product_id, $group_id=false)
    {
        if($group_id)
        {
            CI::db()->where('group_id', $group_id);
        }

        $pricing = CI::db()->where('product_id', $product_id)->order_by('group_id', 'ASC')->order_by('from_quantity', 'ASC')->get('product_pricing')->result();

        if($pricing)
        {
            return $pricing;
        }
        else
        {
            return [];
        }
    }

    public function savePricing($product_id, $pricing)
    {
        //remove pricing first
        CI::db()->where('product_id', $product_id)->delete('product_pricing');

        if($pricing)
        {
            //add the new prices in.
            foreach($pricing as $price)
            {
                $price['product_id'] = $product_id;
                CI::db()->insert('product_pricing', $price);
            }    
        }
    }

    public function touchInventory($id, $quantity)
    {
        $product = $this->getProduct($id);
        if(!$product)
        {
            return false;
        }

        CI::db()->where('id', $id)->update('products', ['quantity' => ($product->quantity - $quantity)]);
    }

    public function products($data=[], $return_count=false)
    {
        if(empty($data))
        {
            //if nothing is provided return the whole shabang
            CI::db()->order_by('name', 'ASC');
            $result = CI::db()->get('products');

            return $result->result();
        }
        else
        {
            $customer = \CI::Login()->customer();

            //grab the limit
            if(!empty($data['rows']))
            {
                CI::db()->limit($data['rows']);
            }

            //grab the page
            if(!empty($data['page']))
            {
                CI::db()->offset($data['page']);
            }

            //do we order by something other than category_id?
            if(!empty($data['order_by']))
            {
                //if we have an order_by then we must have a direction otherwise KABOOM
                CI::db()->order_by($data['order_by'], $data['sort_order']);
            }

            if(!empty($data['enabled_'.$customer->group_id]))
            {
                //if we have an order_by then we must have a direction otherwise KABOOM
                CI::db()->where($data['enabled_'.$customer->group_id], 1);
            }

            //do we have a search submitted?
            if(!empty($data['term']))
            {
                $search = json_decode($data['term']);
                //if we are searching dig through some basic fields
                if(!empty($search->term))
                {
                    CI::db()->like('name', $search->term);
                    CI::db()->or_like('description', $search->term);
                    CI::db()->or_like('excerpt', $search->term);
                    CI::db()->or_like('sku', $search->term);
                }
            }

            if(!empty($data['category_id']))
            {
                //lets do some joins to get the proper category products
                CI::db()->join('category_products', 'category_products.product_id=products.id', 'right');
                CI::db()->where('category_products.category_id', $data['category_id']);
            }

            if($return_count)
            {
                return CI::db()->count_all_results('products');
            }
            else
            {
                CI::db()->select('*, count('.CI::db()->dbprefix('product_pricing').'.group_id) as tiers, LEAST(IFNULL(NULLIF(sale_price, 0), price), price) as sort_price', false);
                CI::db()->join('product_pricing', 'product_pricing.product_id=products.id', 'left')->group_by('product_pricing.product_id');

                $return =  CI::db()->get('products')->result();
                $products = [];
                foreach($return as $product)
                {
                    $product->formattedPrice = ($product->sale_price > 0)?format_currency($product->sale_price):format_currency($product->price);
                    $products[] = $this->processImageDecoding($product);
                }
                
                return $products;
            }
        }
    }

    public function productsAdmin($data=[], $return_count=false)
    {
        if(empty($data))
        {
            //if nothing is provided return the whole shabang
            CI::db()->order_by('name', 'ASC');
            $result = CI::db()->get('products');

            return $result->result();
        }
        else
        {
            //grab the limit
            if(!empty($data['rows']))
            {
                CI::db()->limit($data['rows']);
            }

            //grab the page
            if(!empty($data['page']))
            {
                CI::db()->offset($data['page']);
            }

            //do we order by something other than category_id?
            if(!empty($data['order_by']))
            {
                //if we have an order_by then we must have a direction otherwise KABOOM
                CI::db()->order_by($data['order_by'], $data['sort_order']);
            }

            //do we have a search submitted?
            if(!empty($data['term']))
            {
                $search = json_decode($data['term']);
                //if we are searching dig through some basic fields
                if(!empty($search->term))
                {
                    CI::db()->like('name', $search->term);
                    CI::db()->or_like('description', $search->term);
                    CI::db()->or_like('excerpt', $search->term);
                    CI::db()->or_like('sku', $search->term);
                }
            }

            if(!empty($data['category_id']))
            {
                //lets do some joins to get the proper category products
                CI::db()->join('category_products', 'category_products.product_id=products.id', 'right');
                CI::db()->where('category_products.category_id', $data['category_id']);
            }

            if($return_count)
            {
                return CI::db()->count_all_results('products');
            }
            else
            {
                return CI::db()->get('products')->result();
            }
        }
    }

    public function getProducts($category_id = false, $limit = false, $offset = false, $by=false, $sort=false)
    {
        //if we are provided a category_id, then get products according to category
        if ($category_id)
        {
            CI::db()->select('category_products.*, products.*, product_pricing.*, count('.CI::db()->dbprefix('product_pricing').'.group_id) as tiers, LEAST(IFNULL(NULLIF(sale_price, 0), price), price) as sort_price', false)->from('category_products')->join('products', 'category_products.product_id=products.id')->join('product_pricing', 'product_pricing.product_id=products.id')->group_by('product_pricing.product_id')->where(array('category_id'=>$category_id, 'enabled_'.$this->customer->group_id=>1, 'product_pricing.group_id'=>$this->customer->group_id));

            CI::db()->order_by($by, $sort);

            $result = CI::db()->limit($limit)->offset($offset)->get()->result();

            $products = [];

            foreach($result as $product)
            {
                $products[] = $this->processImageDecoding($product);
            }
            return $products;
        }
        else
        {
            //sort by alphabetically by default
            return CI::db()->order_by('name', 'ASC')->get('products')->result();
        }
    }

    public function count_products($id)
    {
        return CI::db()->select('product_id')->from('category_products')->join('products', 'category_products.product_id=products.id')->where(array('category_id'=>$id, 'enabled_'.$this->customer->group_id=>1))->count_all_results();
    }

    public function slug($slug, $related=true)
    {
        $result = CI::db()->get_where('products', array('slug'=>$slug, 'enabled_'.$this->customer->group_id=>1))->row();

        if(!$result)
        {
            return false;
        }

        $related = json_decode($result->related_products);

        if(!empty($related))
        {
            //build the where
            $where = [];
            foreach($related as $r)
            {
                $where[] = '`id` = '.$r;
            }
            CI::db()->where(array('enabled_'.$this->customer->group_id=>1));
            CI::db()->where('('.implode(' OR ', $where).')', null);
            CI::db()->where('enabled_'.$this->customer->group_id, 1);

            $result->related_products   = CI::db()->get('products')->result();
        }
        else
        {
            $result->related_products   = [];
        }

        $result->categories = $this->getProductCategories($result->id);
        $pricing = $this->pricing($result->id, $this->customer->group_id);

        $result->pricing = [];
        foreach($pricing as $price)
        {
            //for comparing against
            $price->price_val = $price->price;
            $price->sale_price_val = $price->sale_price;
            
            //formatted
            $price->sale_price = format_currency($price->sale_price);
            $price->price = format_currency($price->price);

            $result->pricing[] = $price;
        }

        return $result;
    }

    public function find($id, $related=true)
    {
        $result = CI::db()->get_where('products', array('id'=>$id))->row();
        if(!$result)
        {
            return false;
        }

        if($related)
        {
            $relatedItems = json_decode($result->related_products);
            if(!empty($relatedItems))
            {
                //build the where
                $where = [];
                foreach($relatedItems as $r)
                {
                    $where[] = '`id` = '.$r;
                }

                CI::db()->where('('.implode(' OR ', $where).')', null);
                CI::db()->where('enabled_'.$this->customer->group_id, 1);

                $result->related_products   = CI::db()->get('products')->result();
            }
            else
            {
                $result->related_products   = [];
            }
        }

        $result->categories = $this->getProductCategories($id);
        $result->pricing = $this->pricing($id);

        return $result;
    }

    public function getProductCategories($id)
    {
        return CI::db()->where('product_id', $id)->join('categories', 'category_id = categories.id')->get('category_products')->result();
    }

    public function save($product, $categories=false)
    {
        if ($product['id'])
        {
            CI::db()->where('id', $product['id']);
            CI::db()->update('products', $product);

            $id = $product['id'];
        }
        else
        {
            CI::db()->insert('products', $product);
            $id = CI::db()->insert_id();
        }

        if($categories !== false)
        {
            if($product['id'])
            {
                //get all the categories that the product is in
                $cats   = $this->getProductCategories($id);

                //generate cat_id array
                $ids    = [];
                foreach($cats as $c)
                {
                    $ids[]  = $c->id;
                }

                //eliminate categories that products are no longer in
                foreach($ids as $c)
                {
                    if(!in_array($c, $categories))
                    {
                        CI::db()->delete('category_products', array('product_id'=>$id,'category_id'=>$c));
                    }
                }

                //add products to new categories
                foreach($categories as $c)
                {
                    if(!in_array($c, $ids))
                    {
                        CI::db()->insert('category_products', array('product_id'=>$id,'category_id'=>$c));
                    }
                }
            }
            else
            {
                //new product add them all
                foreach($categories as $c)
                {
                    CI::db()->insert('category_products', array('product_id'=>$id,'category_id'=>$c));
                }
            }
        }

        //return the product id
        return $id;
    }

    public function delete_product($id)
    {
        // delete product
        CI::db()->where('id', $id);
        CI::db()->delete('products');

        //delete references in the product to category table
        CI::db()->where('product_id', $id);
        CI::db()->delete('category_products');

        // delete coupon reference
        CI::db()->where('product_id', $id);
        CI::db()->delete('coupons_products');
    }

    public function search_products($term, $limit=false, $offset=false, $by=false, $sort=false)
    {
        $results = [];

        CI::db()->select('products.*, product_pricing.*', false)->join('product_pricing', 'product_pricing.product_id=products.id')->group_by('product_pricing.product_id')->where(array('enabled_'.$this->customer->group_id=>1, 'product_pricing.group_id'=>$this->customer->group_id));
        //this one counts the total number for our pagination
        CI::db()->where('enabled_'.$this->customer->group_id, 1);
        CI::db()->where('(name LIKE "%'.CI::db()->escape_like_str($term).'%" OR description LIKE "%'.CI::db()->escape_like_str($term).'%" OR excerpt LIKE "%'.CI::db()->escape_like_str($term).'%" OR sku LIKE "%'.CI::db()->escape_like_str($term).'%")');
        $results['count'] = CI::db()->count_all_results('products');

        CI::db()->select('products.*, product_pricing.*, count('.CI::db()->dbprefix('product_pricing').'.group_id) as tiers, LEAST(IFNULL(NULLIF(sale_price, 0), price), price) as sort_price', false)->join('product_pricing', 'product_pricing.product_id=products.id')->group_by('product_pricing.product_id')->where(array('enabled_'.$this->customer->group_id=>1, 'product_pricing.group_id'=>$this->customer->group_id));
        //this one gets just the ones we need.
        CI::db()->where('enabled_'.$this->customer->group_id, 1);
        CI::db()->where('(name LIKE "%'.CI::db()->escape_like_str($term).'%" OR description LIKE "%'.CI::db()->escape_like_str($term).'%" OR excerpt LIKE "%'.CI::db()->escape_like_str($term).'%" OR sku LIKE "%'.CI::db()->escape_like_str($term).'%")');

        if($by && $sort)
        {
            CI::db()->order_by($by, $sort);
        }
        $products = CI::db()->get('products', $limit, $offset)->result();
        $results['products'] = [];
        foreach($products as $product)
        {
            $results['products'][] = $this->processImageDecoding($product);
        }

        return $results;
    }

    public function processImageDecoding($product)
    {
        if($product)
        {
            $product->images = json_decode($product->images, true);
            if($product->images)
            {
                $product->images = array_values($product->images);
            }
            else
            {
                $product->images = [];
            }
            return $product;
        }
        else
        {
            return $product;
        }
        
    }

    public function validate_slug($slug, $id=false, $counter=false)
    {
        CI::db()->select('slug');
        CI::db()->from('products');
        CI::db()->where('slug', $slug.$counter);
        if ($id)
        {
            CI::db()->where('id !=', $id);
        }
        $count = CI::db()->count_all_results();

        if ($count > 0)
        {
            if(!$counter)
            {
                $counter = 1;
            }
            else
            {
                $counter++;
            }
            return $this->validate_slug($slug, $id, $counter);
        }
        else
        {
             return $slug.$counter;
        }
    }
}
