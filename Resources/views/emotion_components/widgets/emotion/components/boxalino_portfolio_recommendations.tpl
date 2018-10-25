<div class="bx-portfolio-wrapper">
    {block name="anchor_links"}
        <div class="bx-pf-anchor">
            <ol class="bx-pf-anchor-list">
                {foreach $Data.portfolio as $portfolio}
                    <li class="bx-pf-anchorlink-{$portfolio.context_parameter['category_id']}" id="bx-pf-anchor-{$portfolio.context_parameter['category_id']}">
                        <a title="{$portfolio.title[$Data.lang]}" href="#bx-pf-title-a-{$portfolio.context_parameter['category_id']}" >{$portfolio.title[$Data.lang]}</a>
                    </li>
                {/foreach}
            </ol>
        </div>
    {/block}
    {block name="content"}
        <div class="bx-pf-wrapper">
            {foreach $Data.portfolio as $portfolio}
                <div id="bx-pf-{$portfolio.context_parameter['category_id']}" class="bx-pf-content-wrapper">
                    <span id="bx-pf-title-a-{$portfolio.context_parameter['category_id']}" style="display:block; height:85px; margin-top: -85px; visibility: hidden"></span>
                    <div id="bx-pf-{$portfolio.context_parameter['category_id']}-title" class="ph-listing-content-top">
                        <h1 class="bx-pf-title panel--title is--underline">
                            <div>{$portfolio.title.{$Data.lang}}</div>
                        </h1>
                    </div>
                    <div class="bx-portfolio-content">
                        <div class="top">
                            <span class="image--media">
                                <img srcset="{$portfolio.picture.{$Data.lang}}">
                            </span>
                            <div class="bx-slider-content">
                                <div class="bx-rebuy-title">
                                    <span>{$portfolio.rebuy.title.{$Data.lang}}</span>
                                </div>
                                <div class="bx-re-buy emotion--product-slider panel" >
                                    {include file="frontend/_includes/product_slider.tpl"
                                    sliderAjaxCtrlUrl={url controller=RecommendationSlider action=portfoliorecommendation bxChoiceId=rebuy bxCount=10 category_id=$portfolio.context_parameter['category_id'] account_id=$portfolio.account_id}
                                    sliderAjaxCategoryID={'rebuy-'|cat:$portfolio.context_parameter['category_id']}
                                    productSliderCls="product-slider--content"
                                    sliderMode={'ajax'}
                                    sliderArrowControls={''}
                                    sliderAnimationSpeed=500
                                    sliderAutoSlideSpeed={''}
                                    sliderAutoSlide={''}
                                    productBoxLayout="emotion"
                                    fixedImageSize="true"
                                    withAddToBasket="true"}
                                </div>
                            </div>
                            <div class="bx-slider-content bx-pf-new-buy-rec" style="display:none;">
                                <div class="bx-newbuy-title">
                                    <span>{$portfolio.rebuy["alternative title"].{$Data.lang}}</span>
                                </div>
                                <div class="bx-new-buy emotion--product-slider panel" >
                                    {include file="frontend/_includes/product_slider.tpl"
                                    sliderAjaxCtrlUrl={url controller=RecommendationSlider action=portfoliorecommendation bxChoiceId=newbuy bxCount=10 category_id=$portfolio.context_parameter['category_id']}
                                    sliderAjaxCategoryID={'newbuy-'|cat:$portfolio.context_parameter['category_id']}
                                    productSliderCls="product-slider--content"
                                    sliderMode={'ajax'}
                                    sliderArrowControls={''}
                                    sliderAnimationSpeed=500
                                    sliderAutoSlideSpeed={''}
                                    sliderAutoSlide={''}
                                    productBoxLayout="emotion"
                                    fixedImageSize="true"
                                    withAddToBasket="true"}
                                </div>
                            </div>
                            <div style="clear: both;"></div>
                        </div>
                        <div style="clear: both;"></div>
                        <div class="bottom">
                            <div class="bx-reorient-title">
                                <span>{$portfolio.reorient.title.{$Data.lang}}</span>
                            </div>
                            <div class="bx-re-orient emotion--product-slider panel" >
                                {include file="frontend/_includes/product_slider.tpl"
                                sliderAjaxCtrlUrl={url controller=RecommendationSlider action=portfoliorecommendation bxChoiceId=reorient bxCount=10 category_id=$portfolio.context_parameter['category_id']}
                                productSliderCls="product-slider--content"
                                articles=$articles
                                sliderAjaxCategoryID={'reorient-'|cat:$portfolio.context_parameter['category_id']}
                                sliderMode={'ajax'}
                                sliderArrowControls={''}
                                sliderAnimationSpeed=500
                                sliderAutoSlideSpeed={''}
                                sliderAutoSlide={''}
                                productBoxLayout="emotion"
                                fixedImageSize="true"
                                withAddToBasket="true"}
                            </div>
                        </div>
                        <div style="clear: both;"></div>
                        <div class="bx-blog-rec">
                            <div class="bx-reorient-title">
                                <span>Passende Blog-Beiträge</span>
                            </div>
                            <div class="bx-blog emotion--product-slider panel" >
                                {include file="frontend/_includes/product_slider.tpl"
                                sliderAjaxCtrlUrl={url controller=RecommendationSlider action=blogrecommendation category_id=$portfolio.context_parameter['category_id'] category_label=$portfolio.name}
                                productSliderCls="product-slider--content"
                                sliderAjaxCategoryID={'blog-'|cat:$portfolio.title[$Data.lang]}
                                sliderMode={'ajax'}
                                sliderArrowControls={''}
                                sliderAnimationSpeed=500
                                sliderAutoSlideSpeed={''}
                                sliderAutoSlide={''}
                                productBoxLayout="emotion"
                                fixedImageSize="true"}
                            </div>
                        </div>
                    </div>
                    <br>
                    <br>
                </div>
            {/foreach}
        </div>

        <script>

            document.asyncReady(function() {
                StateManager.updatePlugin('*[data-add-article="true"]','swAddArticle');
                var groupSize = parseInt('{$Data.portfolio|count}');
                var loadedRec = [];
                $(document).ready(function() {

                    $.subscribe('plugin/swProductSlider/onLoadItemsSuccess', function (m,r,i) {
                        var el = r.$el;
                        var v = r.opts.ajaxCategoryID.split("-");
                        var type = v[0];
                        if(type === 'rebuy'){
                            var count = r.itemsCount;
                            if(count === 0) {
                                r.destroy();
                                $(el).parent().parent().next().show();
                                $(el).parent().parent().remove();

                            }
                            //remove newbuy rec
                            $(el).parent().parent().next().remove();

                            var identifier = v[1];
                            {literal}
                            loadedRec.push({id:identifier, count:count, elementID: '#bx-pf-' + identifier});
                            {/literal}
                            loadedRec.sort(function(a,b) {
                                return parseInt(b.count) - parseInt(a.count);
                            });

                            if(loadedRec.length === groupSize) {
                                {literal}
                                var temp = {};
                                {/literal}
                                loadedRec.forEach(function(e) {
                                    temp[e.elementID] = e.count;
                                });
                                window.iku = loadedRec;
                                $('.bx-pf-wrapper').children().sort(function(a,b) {
                                    var a_id = "#" + a.id,
                                        b_id = "#" + b.id;
                                    return parseInt(temp[b_id]) - parseInt(temp[a_id]);
                                }).detach().appendTo('.bx-pf-wrapper');
                                $('.bx-pf-anchor-list').children().sort(function(a,b) {
                                    var a_id = "#bx-pf-" + a.id.substring(a.id.lastIndexOf('-')+1, a.id.length),
                                        b_id = "#bx-pf-" + b.id.substring(b.id.lastIndexOf('-')+1, b.id.length);
                                    return parseInt(temp[b_id]) - parseInt(temp[a_id]);
                                }).detach().appendTo('.bx-pf-anchor-list');
                            }
                        } else if(type === 'blog') {
                            var count = r.itemsCount;
                            if(count === 0) {
                                r.destroy();
                                $(el).parent().parent().remove();
                            }
                        }

                    });
                });
            });
        </script>
    {/block}
</div>
