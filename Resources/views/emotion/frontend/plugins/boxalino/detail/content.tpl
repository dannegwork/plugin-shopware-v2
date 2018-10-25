{extends name="parent:frontend/detail/content.tpl"}

{block name='frontend_index_content_inner'}
    {$smarty.block.parent}
    {block name='detail_blog_recommendation'}
        {if $sBlogArticles}
            <div class="bx-detail-blog" style="background-color: #ffffff">
                <div class="bx-detail-blog--title">
                    <h2 style="text-align:center;">{$sBlogTitle}</h2>
                </div>
                    {include file="frontend/_includes/product_slider.tpl" productBoxLayout="emotion" fixedImage=true articles=$sBlogArticles bxBlogRecommendation=true}
            </div>
        {/if}
    {/block}
{/block}