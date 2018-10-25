{extends name="parent:frontend/_includes/product_slider_item.tpl"}

{block name="frontend_common_product_slider_item"}
   {if $bxBlogRecommendation}
       <div class="product-slider--item bxBlogRecommendationSlider">
           {include file="frontend/plugins/boxalino/blog/recommendation.tpl" sArticle=$article}
       </div>
   {else}
       {$smarty.block.parent}
   {/if}
{/block}