<?php echo pageHeader(lang('giftcard_form')); ?>

<script type="text/template" id="giftcardTemplate">
    <div style="margin-top:10px">
        <div class="input-group">
            <input type="text" name="option[giftcard_values][]" value="{{price}}" class="form-control"/>
            <div class="input-group-btn">
                <button type="button" class="btn btn-danger" onclick="remove_giftcard_value(this);"><i class="icon-times"></i></button>
            </div>
        </div>
    </div>
</script>

<script type="text/template" id="imageTemplate">
    <div class="row gc_photo" id="gc_photo_{{id}}" style="background-color:#fff; border-bottom:1px solid #ddd; padding-bottom:20px; margin-bottom:20px;">
        <div class="col-md-2">
            <input type="hidden" name="images[{{id}}][filename]" value="{{filename}}"/>
            <img class="gc_thumbnail" src="<?php echo base_url('uploads/images/thumbnails/{{filename}}');?>" style="padding:5px; border:1px solid #ddd"/>
        </div>
        <div class="col-md-10">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <input name="images[{{id}}][alt]" value="{{alt}}" class="form-control" placeholder="<?php echo lang('alt_tag');?>"/>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="checkbox">
                        <label>
                            <input type="radio" name="primary_image" value="{{id}}" {{#primary}}checked="checked"{{/primary}}/> <?php echo lang('main_image');?>
                        </label>
                    </div>
                </div>
                <div class="col-md-4">
                    <a onclick="return remove_image($(this));" rel="{{id}}" class="btn btn-danger pull-right"><i class="icon-times "></i></a>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <label><?php echo lang('caption');?></label>
                    <textarea name="images[{{id}}][caption]" class="form-control" rows="3">{{caption}}</textarea>
                </div>
            </div>
        </div>
    </div>
</script>

<script>
var giftcardTemplate = $('#giftcardTemplate').html();
var imageTemplate = $('#imageTemplate').html();
var images = <?php echo json_encode($images);?>

$(document).ready(function() {
    photos_sortable();
    //if the image already exists (phpcheck) enable the selector
   
    var giftcard_values = <?php echo $options ?>;

    $.each(giftcard_values[4].values, function(key,val) {
        add_giftcard_values(val.price);
    });
    
    $.each(images, function(key,val) {
        addProductImage(key, val.filename, val.alt, val.primary, val.caption);
    }); 
});

function remove_giftcard_value(elem)
{
    if(confirm('<?php echo lang('confirm_remove_giftcard_value');?>'))
    {
        $(elem).parent().parent().parent().remove();
    }
}

function add_giftcard_values(value)
{
    var view = {price:value};
    var output = Mustache.render(giftcardTemplate, view);

    $('#optionsContainer').append(output);
}

function photos_sortable()
{
    $('#gc_photos').sortable({
        handle : '.gc_thumbnail',
        items: '.gc_photo',
        axis: 'y',
        scroll: true
    });
}

function addProductImage(id, filename, alt, primary, caption)
{
    view = {
        id:id,
        filename:filename,
        alt:alt,
        primary:primary,
        caption:caption
    }

    var output = Mustache.render(imageTemplate, view);

    $('#gc_photos').append(output);
    $('#gc_photos').sortable('refresh');
    photos_sortable();
}

function remove_image(img)
{
    if(confirm('<?php echo lang('confirm_remove_image');?>'))
    {
        var id  = img.attr('rel');
        $('#gc_photo_'+id).remove();
    }
}

function photos_sortable()
{
    $('#gc_photos').sortable({
        handle : '.gc_thumbnail',
        items: '.gc_photo',
        axis: 'y',
        scroll: true
    });
}

function remove_option(id)
{
    if(confirm('<?php echo lang('confirm_remove_option');?>'))
    {
        $('#option-'+id).remove();
    }
}
</script>


<?php echo form_open('admin/products/gift-card-form/'.$id ); ?>
    <div class="row">
        <div class="col-md-9">
            <div class="tabbable">
                <ul class="nav nav-tabs">
                    <li class="active"><a href="#product_info" data-toggle="tab"><?php echo lang('details');?></a></li>
                    <li><a href="#product_categories" data-toggle="tab"><?php echo lang('categories');?></a></li>
                    <li><a href="#productValues" data-toggle="tab"><?php echo lang('giftcard_values');?></a></li>
                    <li><a href="#product_photos" data-toggle="tab"><?php echo lang('images');?></a></li>
                </ul>
            </div>
            <div class="tab-content">
                <div class="tab-pane active" id="product_info">

                    <div class="form-group">
                        <?php echo form_input(['placeholder'=>lang('name'), 'name'=>'name', 'value'=>assign_value('name', $name), 'class'=>'form-control']); ?>
                    </div>

                    <div class="form-group">
                        <?php echo form_textarea(['name'=>'description', 'class'=>'redactor', 'value'=>assign_value('description', $description)]); ?>
                    </div>

                    <div class="form-group">
                        <label><?php echo lang('excerpt');?></label>
                        <?php echo form_textarea(['name'=>'excerpt', 'value'=>assign_value('excerpt', $excerpt), 'class'=>'form-control', 'rows'=>5]); ?>
                    </div>

                    <fieldset>
                        <legend><?php echo lang('header_information');?></legend>
                        <div style="padding-top:10px;">
                            
                            <div class="form-group">
                                <label for="slug"><?php echo lang('slug');?> </label>
                                <?php echo form_input(['name'=>'slug', 'value'=>assign_value('slug', $slug), 'class'=>'form-control']); ?>
                            </div>

                            <div class="form-group">
                                <label for="seo_title"><?php echo lang('seo_title');?> </label>
                                <?php echo form_input(['name'=>'seo_title', 'value'=>assign_value('seo_title', $seo_title), 'class'=>'form-control']); ?>
                            </div>

                            <div class="form-group">
                                <label for="meta"><?php echo lang('meta');?></label>
                                <?php echo form_textarea(['name'=>'meta', 'value'=>assign_value('meta', html_entity_decode($meta)), 'class'=>'form-control']);?>
                                <span class="help-block"><?php echo lang('meta_example');?></span>
                            </div>
                        </div>
                    </fieldset>
                </div>

                <div class="tab-pane" id="product_categories">

                    <?php if(isset($categories[0])):?>
                        <label><strong><?php echo lang('select_a_category');?></strong></label>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th><?php echo lang('name')?></th>
                                    <th></th>
                                    <th><?php echo lang('primary'); ?></th>
                                </tr>
                            </thead>
                        <?php
                        function list_categories($parent_id, $cats, $sub='', $product_categories, $primary_category) {

                            foreach ($cats[$parent_id] as $cat):?>
                            <tr>
                                <td><?php echo  $sub.$cat->name; ?></td>
                                <td>
                                    <input type="checkbox" name="categories[]" value="<?php echo $cat->id;?>" <?php echo(in_array($cat->id, $product_categories))?'checked="checked"':'';?>/>
                                </td>
                                <td>
                                    <input type="radio" name="primary_category" value="<?php echo $cat->id;?>" <?php echo ($primary_category == $cat->id)?'checked="checked"':'';?>/>
                                </td>
                            </tr>
                            <?php
                            if (isset($cats[$cat->id]) && sizeof($cats[$cat->id]) > 0)
                            {
                                $sub2 = str_replace('&rarr;&nbsp;', '&nbsp;', $sub);
                                    $sub2 .=  '&nbsp;&nbsp;&nbsp;&rarr;&nbsp;';
                                list_categories($cat->id, $cats, $sub2, $product_categories, $primary_category);
                            }
                            endforeach;
                        }


                        list_categories(0, $categories, '', $product_categories, $primary_category);

                        ?>

                    </table>
                <?php else:?>
                    <div class="alert"><?php echo lang('no_available_categories');?></div>
                <?php endif;?>

                </div>

                <div class="tab-pane" id="productValues">
                    <div class="row">
                        <div class="pull-right" style="padding:0px 0px 10px 0px;">
                            <button type="button" class="btn btn-primary btn-default-default" name="giftcard_values btn btn-default" onclick="add_giftcard_values()">Add </button>
                        </div>

                        <div class="clearfix"></div>

                        <div id="optionsContainer"></div>
                    </div>
                </div>

                <div class="tab-pane" id="product_photos">
                    <iframe id="iframe_uploader" src="<?php echo site_url('admin/products/product_image_form');?>" style="height:75px; width:100%; border:0px;"></iframe>
                    <div id="gc_photos"></div>
                </div>

            </div>
            <button type="submit" class="btn btn-primary"><?php echo lang('save');?></button>
        </div>
        <div class="col-md-3">

            <div class="form-group">
                <?php echo form_dropdown('taxable', [0 => lang('not_taxable'), 1 => lang('taxable')], assign_value('taxable',$taxable), 'class="form-control"'); ?>
            </div>

            <div class="form-group">
                <label for="sku"><?php echo lang('sku');?></label>
                <?php echo form_input(['name'=>'sku', 'value'=>assign_value('sku', $sku), 'class'=>'form-control']);?>
            </div>

            <?php foreach($groups as $group):?>
                <fieldset>
                    <legend>
                        <?php echo $group->name;?>
                        <div class="checkbox pull-right" style="font-size:16px; margin-top:5px;">
                            <label>
                                <?php echo form_checkbox('enabled_'.$group->id, 1, ${'enabled_'.$group->id}); ?> <?php echo lang('enabled');?>
                            </label>
                        </div>
                    </legend>
                </fieldset>
            <?php endforeach;?>

        </div>
    </div>
</form>