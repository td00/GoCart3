<?php if(!empty($category)):?>
    <div class="page-header">
        <h1><?php echo $category->name; ?></h1>
    </div>

    <?php if(!empty($category->description)):?>
    <div class="categoryDescription">
        <?php echo (new content_filter($category->description))->display();?>
    </div>
    <?php endif;?>

    <div class="productsFilter">
        <label class="control-label" for="input-limit"><?php echo lang('sort'); ?></label>
        <select id="sort">
            <option value='{"orderBy":"name", "sortOrder":"ASC"}'><?php echo lang('sort_by_name_asc');?></option>
            <option value='{"orderBy":"name", "sortOrder":"DESC"}'><?php echo lang('sort_by_name_desc');?></option>
            <option value='{"orderBy":"price", "sortOrder":"ASC"}'><?php echo lang('sort_by_price_asc');?></option>
            <option value='{"orderBy":"price", "sortOrder":"DESC"}'><?php echo lang('sort_by_price_desc');?></option>
        </select>
    </div> 

    <div id="products">
    </div>

    <div id="productPages" class="pagination"></div>
    
<?php endif;
    
    /*
        $this->show('products', ['products'=>$products]);?>

    <div class="text-center pagination">
        <?php echo CI::pagination()->create_links();?>
    </div>
   */?>
</div>


<script id="productTemplate" type="text/x-handlebars-template">
    <div class="product-{{ id }} animated fadeInDown">
        {{#if tagNote }}
            <div class="categoryItemNote {{ tagNoteType }}">{{ tagNote }}</div>
        {{/if }}
        <div class="col-nest">
            <div class="col" data-cols="1/5" data-medium-cols="1/4" data-small-cols="1/4">
                <div class="previewImg"><img src="{{ productImage }}"></div>
            </div>

            <div class="col" data-cols="4/5" data-medium-cols="3/4" data-small-cols="3/4">
                <div class="categoryItemDetails"> {{ name }} </div>

                {{#if showPrice }}
                <div class="categoryItemHover">
                    <div class="look">
                        {{#if tiers}}
                            <small><?php echo lang('starting_at');?></small><br>
                        {{/if}}
                        {{ formattedPrice }}
                    </div>
                </div>
                {{/if}}
            </div>
        </div>

    </div>
</script>

<script id="paginationTemplate" type="text/x-handlebars-template">
    <a href="<?php echo base_url('category/'.$category->slug.'?page={{ paginagtion }}');?>">{{ paginagtion }}</a>
</script>

<script type="text/template" id="noProducts">
    <div class="col" data-cols="1"><div class="alert animated fadeIn"><?php echo lang('no_products');?></div></div>
</script>


<script type="text/javascript">

var params = {
    "page":<?php echo (isset($_GET['page'])?(intval($_GET['page'])):1);?>,
    "orderBy":"name",
    "sortOrder":"ASC",
    "rows":24,
    "categoryId":<?php echo $category->id;?>
}

var startingPage = params.page;

var productTemplate = Handlebars.compile($('#productTemplate').html());
var paginationTemplate = Handlebars.compile($('#paginationTemplate').html());
var products = $('#products');
var totalProducts = 0;
var ajaxProducts = [];
var processing = false;
var instanceCount = 0;
var pathInfo = '<?php echo $_SERVER['PATH_INFO'];?>';

$(document).ready(function(){

    $("#sort").change(function () {

        var sorts = JSON.parse($(this).val());

        for(var attrib in sorts) { 
            params[attrib] = sorts[attrib]; 
        }

        //reset the page
        params.page = 1;
        productCount = 0;
        processing = false;
        instanceCount = 0;

        $('.productPage').html('');

        getPage();
    });

    //get the total product count
    $.post('<?php echo site_url('category/products/count');?>', {categoryId:params.categoryId}, function(data) {

        totalProducts = parseInt(data);
        if(totalProducts > 0)
        {
            //getProducts();

            var i = 0;
            var pagination = 1;
            do {

                $('#products').append('<div class="productPage" id="page-'+pagination+'" data-page="'+pagination+'"></div>');
                i += params.rows;
                pagination++;
            } while (i < totalProducts);

            //default header pagination
            createHeaderPagination();

            getPage();
        }
        else
        {
            products.html($('#noProducts').html());
        }
        
    });
});

var lastPage = params.page;
function createHeaderPagination()
{
    if(params.page != lastPage || $('#canonicalLink').length == 0)
    {
        lastPage = params.page;
        //if they exist, get rid of them
        $('#canonicalLink').remove();
        $('[rel="next"]').remove();
        $('[rel="prev"]').remove();

        var next = parseInt(params.page)+1;
        var prev = parseInt(params.page)-1;
        var queryString = '';

        if(params.page != 1)
        {
            queryString = '?page='+params.page;
        }
        
        console.log(next);
        if($('[data-page='+next+']').length > 0)
        {
            $('head').append('<link rel="next" href="'+pathInfo+'?page='+next+'"/>');
        }

        if($('[data-page='+prev+']').length > 0)
        {
            $('head').append('<link rel="prev" href="'+pathInfo+'?page='+prev+'"/>');
        }
        
        $('head').append('<link id="canonicalLink" rel="canonical" href="'+pathInfo+queryString+'" />');
    }
}


function getPage()
{
    if(processing == false)
    {
        if($('#page-'+params.page).children().length == 0)
        {
            instanceCount = 0;
            processing = true;

            $('#page-'+params.page).spin();

            $.post('<?php echo site_url('category/products');?>', params, function(data) {

                ajaxProducts = data;
                triggerProductProcess();

                $('#page-'+params.page).spin(false);

            }, 'json');
        }
    }

}

function triggerProductProcess()
{
    if(ajaxProducts[instanceCount] != undefined)
    {
        setTimeout(function(){
            processProduct(ajaxProducts[instanceCount]);
            instanceCount++;
            triggerProductProcess();
        }, 150);
    }
    else
    {
        processing = false;
    }
}

function processProduct(product)
{
    

    product.url = '<?php echo site_url('/product/');?>'+'/'+product.slug;

    if(product.sale_price > 0)
    {
        product.tagNote = <?php echo json_encode(lang('on_sale'));?>;
        product.tagNoteType = 'red';
    }
    if(product.track_stock && product.quantity < 1 && <?php echo ((bool)config_item('inventory_enabled')) ? 'true' : 'false' ;?>)
    {
        product.tagNote = <?php echo json_encode(lang('out_of_stock'));?>;
        product.tagNoteType = 'yellow';
    }

    if(parseInt(product.is_giftcard) > 0)
    {
        product.showPrice = false;
    }
    else
    {
        product.showPrice = true;
    }

    product.tiers = ((product.tiers > 1) ? true : false);
    
    product.productImage = '<?php echo theme_img('no_picture.png');?>';

    if(product.images[0] != undefined)
    {
        product.productImage = product.images[0].filename;

        for(i=0; i<product.images.length; i++)
        {
            if(product.images[i].primary != undefined)
            {
                product.productImage = '<?php echo base_url('uploads/images/medium');?>/'+product.images[i].filename;
            }
        }
    }

    var html = productTemplate(product);

    $('#page-'+params.page).append(html);
}

function loadPagesBasedOnPosition(){
    $('.productPage').each(function(key,element){

        element = $(element);

        var elementTopToPageTop = element.offset().top;
        var windowTopToPageTop = $(window).scrollTop();
        var windowInnerHeight = window.innerHeight;
        var elementTopToWindowTop = elementTopToPageTop - windowTopToPageTop;
        var elementTopToWindowBottom = windowInnerHeight - elementTopToWindowTop;
        
        if(elementTopToWindowTop < 200 && elementTopToWindowTop > 0)
        {
            
            //var thisPage = parseInt(element.attr('data-page'));
            //var pageString = '?page='+thisPage;

            //history.replaceState(null, null, '<?php echo base_url('category/'.$category->slug);?>'+pageString);
            
            //update header pagination
            createHeaderPagination();

            if(processing == false)
            {
                //load if 200px from the top
                params.page = element.attr('data-page');

                getPage();
            }
        }
        else if(elementTopToWindowBottom < 200 && elementTopToWindowBottom > 0)
        {
            //load if 200px from the bottom
            if(params.page != parseInt(element.attr('data-page')))
            {
                if(processing == false)
                {
                    params.page = element.attr('data-page');
                    getPage();
                }
            }
        }
    });
}

$(window).scroll(function(){
    loadPagesBasedOnPosition();
});
</script>