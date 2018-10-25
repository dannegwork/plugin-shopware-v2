{extends file='parent:frontend/index/index.tpl'}
{block name="frontend_index_before_page" append}{$report_script}{/block}
{block name="frontend_index_footer" append}
    {if $bxHelper}
        {$bxHelper->callNotification($bxForce)}
    {/if}
{/block}

{block name="frontend_index_header_meta_tags" append}
    <meta name="bx_debug" content="{$smarty.now|date_format:"%Y-%m-%d %H:%M:%S"}" />
{/block}

{block name="frontend_index_header_javascript_jquery_lib"}
    {$smarty.block.parent}
    <script>
        document.asyncReady(function() {
            $(document).ready(function () {

                $.subscribe('plugin/swProductSlider/onLoadItemsSuccess', function(e,o,r) {
                    if(r.length === 0) {
                        if ($(o.$el).closest('.emotion--element').length > 0) {
                            $(o.$el).closest('.emotion--element').remove();
                        } else {
                            $(o.$el).parent().parent().remove();
                        }
                    }
                    var replace = $(o.$el).find('.bx_replace');
                    if(replace.length > 0) {
                        $(o.$el).prev('.panel--title.is--underline.product-slider--title').text(replace.text());
                        replace.remove();
                    }
                });
            });
        });
    </script>

{/block}


