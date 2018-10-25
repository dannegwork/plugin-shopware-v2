{extends file='parent:frontend/listing/product-box/box-emotion.tpl'}

{block name="frontend_listing_box_article_picture"}
    {if $sArticle.bxTransactionDate}
        <div class="bx-transaction-date">
            <span style="font-size: .9em;">{$sArticle.bxTransactionDate}</span>
        </div>
    {/if}
    {$smarty.block.parent}
{/block}
{block name='frontend_listing_box_article_price_info'}
{if $isFinder == 'true'}
  <h2 class="bxScore" style="text-align:center;">score: {$sArticle.score}</h2>
  <button class="bxCommentButton_{$sArticle.articleID}">comment</button>
  <div class="bxComment_{$sArticle.articleID}" style="display:none">{$sArticle.comment}</div>
{/if}
    {$smarty.block.parent}
    {if $withAddToBasket == 'true'}

        <form name="sAddToBasket{$sArticle.ordernumber}" method="post" class="buybox--form" data-add-article="true" data-eventName="submit" {if $theme.offcanvasCart} data-showModal="false" data-addArticleUrl="{url controller=checkout action=ajaxAddArticleCart}"{/if}>

            <input type="hidden" name="sAdd" value="{$sArticle.ordernumber}"/>
            <div class="buybox--button-container block-group">
                <div class="buybox--quantity block">
                    {$maxQuantity=$sArticle.maxpurchase+1}
                    {if $sArticle.laststock && $sArticle.instock < $sArticle.maxpurchase}
                        {$maxQuantity=$sArticle.instock+1}
                    {/if}
                    <div class="select-field">
                        <select id="sQuantity" name="sQuantity" class="quantity--select">
                            {section name="i" start=$sArticle.minpurchase loop=$maxQuantity step=$sArticle.purchasesteps}
                                <option value="{$smarty.section.i.index}">{$smarty.section.i.index}{if $sArticle.packunit} {$sArticle.packunit}{/if}</option>
                            {/section}
                        </select>
                    </div>
                </div>
                <button class="buybox--button block btn is--primary is--icon-right is--center is--large" name="{s name="DetailBuyActionAdd"}{/s}"{if $buy_box_display} style="{$buy_box_display}"{/if}>
                    <span class="bb-btn-text">In den Warenkorb</span>
                </button>
            </div>

        </form>
    {/if}
{/block}

