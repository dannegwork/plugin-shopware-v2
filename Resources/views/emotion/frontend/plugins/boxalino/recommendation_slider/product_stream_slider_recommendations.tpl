{if $articles}
    {if $title != ''}<div class="bx_replace">{$title}</div>{/if}
    {include file="frontend/_includes/product_slider_items.tpl"}
{/if}