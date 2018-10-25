{if $Data.widget_type == 1}
    {include file="frontend/plugins/boxalino/product_finder/quick_search.tpl" Data = $Data}
{else}
    {include file="frontend/plugins/boxalino/product_finder/product_finder.tpl" Data = $Data}
{/if}
