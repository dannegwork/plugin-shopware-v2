<script>
    StateManager.updatePlugin('*[data-listing-actions="true"]', 'swListingActions');
</script>
<div class="content--wrapper" style="margin-left: 16.25rem;">
    <div class="content listing--content">
        <div class="listing--wrapper visible--xl visible--l visible--m visible--s visible--xs has--sidebar-filter">
            <div data-listing-actions="true" data-buffertime="0" class="listing--actions is--rounded without-facets">
                <div class="action--filter-btn">
                    <a href="#" class="filter--trigger btn is--small" data-filter-trigger="true" data-offcanvas="true" data-offcanvasselector=".action--filter-options" data-closebuttonselector=".filter--close-btn">
                        <i class="icon--filter"></i>
                        Filter
                        <span class="action--collapse-icon"></span>
                    </a>
                </div>
                {include file="frontend/listing/actions/action-sorting.tpl"}
                <script>
                    document.asyncReady(function() {
                        $(document).ready(function () {
                            $('.sort--field.action--field').on('change', function(e) {
                                var selectValue = this.options[e.target.selectedIndex].value;
                                $('input[name=o]').each(function(i, el){
                                    el.value = selectValue;
                                    $("#filter").submit();
                                });

                            });
                        });
                    });
                </script>

                {include file="frontend/listing/actions/action-pagination.tpl"}

            </div>
            <div class="listing--container">
                <div class="listing" data-ajax-wishlist="true" data-compare-ajax="true">
                    {$i=0}
                    {foreach $bxSubRenderings as $subRendering}
                        {$additionalParameter=[]}
                        {$additionalParameter['list_index'] = $i++}
                        {$bxRender->renderElement($subRendering.visualElement, $additionalParameter)}
                    {/foreach}
                </div>
            </div>
            <div class="listing--bottom-paging">
                {include file="frontend/listing/actions/action-pagination.tpl"}
            </div>
        </div>
    </div>
</div>