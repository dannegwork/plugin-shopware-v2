<script>
    document.asyncReady(function() {
        $(document).ready(function() {
            var facetOptions = {$facetOptions|json_encode};
            if($('.panel--paging').length) {
                $('.panel--paging').addClass('listing--paging');
            }
            if($('.sidebar-filter--content').length) {
                expandFacets(facetOptions);
            }
            $.subscribe('plugin/swListingActions/onOpenFilterPanel', function() {
                expandFacets(facetOptions);
            });

            $.subscribe('plugin/swListingActions/onGetFilterResultFinished', function(m,r,i) {
                if(facetOptions['mode'] === 'filter_ajax_reload'){
                    setTimeout(function() {
                        $('div.filter--active-container').children().each(function(i, e) {
                            var param = $(e).attr('data-filter-param');
                            if(param !== 'reset') {
                                var label = $('.filter--facet-container').find("label[for='"+param+"']").html();
                                var replaceHtml = $(e).find('span').prop('outerHTML') + label;
                                $(e).html(replaceHtml);
                            }
                        });
                    }, 1);
                }

                var el = $('.bx-tab-article-count');
                if(el.length){
                    el.text(i.totalCount);
                }
            });
            $.overridePlugin('swFilterComponent',{
                open: function(closeSiblings) {
                    var me = this;
                    me.$el.addClass(me.opts.collapseCls);
                    $.publish('plugin/swFilterComponent/onOpen', [ me ]);
                },
                updateValueList: function(data) {
                    var me = this, $elements, values, ids, activeIds, checkedIds;

                    $elements = me.convertToElementList(me.$inputs);
                    values = me.getValues(data, $elements);
                    values = me.convertValueIds(values);
                    ids = me.getValueIds(values);
                    activeIds = me.getActiveValueIds(values);
                    checkedIds = me.getElementValues(
                        me.getCheckedElements($elements)
                    );

                    if (me.validateComponentShouldBeDisabled(data, values, checkedIds)) {
                        me.disableAll($elements, values);
                        return;
                    }
                    $elements.each(function(index, $element) {
                        var val = $element.val() + '';
                        var value = me.findValue(val, values);
                        var disable = me.validateElementShouldBeDisabled($element, activeIds, ids, checkedIds, value);
                        me.disable($element, disable);
                        me.setDisabledClass($element.parents('.filter-panel--input'), disable);
                        if(!disable && value !== null) {
                            $element.parents('.filter-panel--input').next().text(value['label']);
                        }
                    });

                    me.disableComponent(me.allDisabled($elements));
                }
            });
            StateManager.destroyPlugin('*[data-filter-type]','swFilterComponent');
            StateManager.updatePlugin('*[data-filter-type]','swFilterComponent');

            var snippetValues = {
                "more": '{s namespace="boxalino/intelligence" name="filter/morevalues"}{/s}',
                "less": '{s namespace="boxalino/intelligence" name="filter/lessvalues"}{/s}'
            };
            $(".show-more-values").on('click', function () {
                var header = $(this);
                var content = header.parent().find('.hidden-items');
                content.slideToggle(500, function () {
                    header.text(function () {
                        return content.is(":visible") ? snippetValues['less'] : snippetValues['more'];
                    });
                });
            });
            $('.search-remove').on('click', function() {
                if($(this).hasClass('icon--cross')) {
                    var searchInput = $(this).prev();
                    if(searchInput.val() !== ''){
                        toggleSearchIcon($(this));
                    }
                    searchInput.val("");
                    $(this).parent().next().find('.show-more-values').show();
                    $(this).parent().next().find('.filter-panel--option').each(function(i, e) {
                        var label = $(e).find('label');
                        label.html(label.text());
                        if($(e).hasClass('hidden-items')){
                            $(e).hide();
                        } else {
                            $(e).show();
                        }
                    });
                }
            });
            $(".bx--facet-search").on('keyup', function() {
                var text = $(this).val(),
                    iconElement =  $(this).next();
                if(text === ''){
                    iconElement.trigger('click');
                } else {
                    var options = $(this).parent().next().find('.filter-panel--option');
                    var regMatch = new RegExp(escapeRegExp(text), 'gi'),
                        regMatch2 = new RegExp(escapeRegExp(text.slice(0, text.length-1)), 'gi');
                    $(this).parent().next().find('.show-more-values').hide();
                    options.each(function(i, e) {
                        var label = $(e).find('label').text(),
                            match = null,
                            m = label.match(regMatch),
                            m2 = label.match(/\'/g);
                        if(Array.isArray(m2)){
                            if(m){
                                match = m;
                            }else {
                                if(text.length > 1){
                                    var quoteMatches = [],
                                        reg =  /(\')/g;
                                    while((m2 = reg.exec(label)) !== null) {
                                        quoteMatches.push(m2);
                                    }
                                    var label2 = label.replace(reg, '');
                                    quoteMatches.forEach(function (m) {
                                        if(match === null){
                                            while((m2 = regMatch2.exec(label2)) !== null) {
                                                if(m2.index < m.index && match === null){
                                                    match = label.slice(m2.index, m2.index + text.length+1);
                                                }
                                            }
                                        }
                                    });
                                }
                            }
                        } else {
                            if(m){
                                match = m[0];
                            }
                        }
                        if(match) {
                            $(e).find('label').html(label.replace(match, '<strong>'+match+'</strong>'));
                            $(e).show();
                        } else {
                            $(e).hide();
                        }
                    });
                }
                if(text.length > 0 && iconElement.hasClass('icon--search')) {
                    toggleSearchIcon(iconElement);
                } else if(text.length === 0 && iconElement.hasClass('icon--cross')) {
                    toggleSearchIcon(iconElement);
                }
            });
            function escapeRegExp(text) {
                text.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, '\\$&');
                text = text.replace(/[a|\u00E0|\u00E1|\u00E2|\u00E3|\u00E4](?![^[]*\])/gi, '[a|ä|\u00E0|\u00E1|\u00E2|\u00E3|\u00E4]');
                text = text.replace(/[(e|\u00E8|\u00E9|\u00EA|\u00EB)](?![^[]*\])/gi, '[e|é|\u00E8|\u00E9|\u00EA|\u00EB]');
                text = text.replace(/[i|\u00EC|\u00ED|\u00EE|\u00EF](?![^[]*\])/gi, '[i|\u00EC|\u00ED|\u00EE|\u00EF]');
                text = text.replace(/[o|\u00F2|\u00F3|\u00F4|\u00F5|\u00F6](?![^[]*\])/gi, '[o|\u00F2|\u00F3|\u00F4|\u00F5|\u00F6]');
                text = text.replace(/[u|\u00F9|\u00FA|\u00FB|\u00FC](?![^[]*\])/gi, '[u|\u00F9|\u00FA|\u00FB|\u00FC]');
                return text;
            }
            function expandFacets(facetOptions) {
                var filters = $('#filter').find('.filter-panel');
                setTimeout(function(filters, facetOptions){
                    filters.each(function(i, e) {
                        var fieldName = $.trim($(e).find('.filter-panel--title').text());
                        if(facetOptions.hasOwnProperty(fieldName) && facetOptions[fieldName]['expanded'] === true) {
                            $(this).addClass("is--collapsed");
                        }
                    });
                }, 1, filters, facetOptions);
            }
            function toggleSearchIcon(iconElement) {
                iconElement.toggleClass('icon--search');
                iconElement.toggleClass('icon--cross');
            }
        });
    });
</script>
<script>
    StateManager.updatePlugin('*[data-listing-actions="true"]', 'swListingActions');
    StateManager.updatePlugin('*[data-range-slider="true"]', 'swRangeSlider');
</script>
{namespace name="frontend/listing/listing_actions"}
<div class="listing--sidebar">
    <div class="sidebar-filter">
        <div class="sidebar-filter--content">
            {if $criteria && $facets}
                {block name='frontend_listing_actions_filter'}
                    {$listingMode = {config name=listingMode}}

                    <div class="action--filter-options off-canvas{if $facets|count <= 0} is--hidden{/if}{if $listingMode != 'full_page_reload'} is--ajax-reload{/if}">

                        {block name='frontend_listing_actions_filter_close_button'}
                            <a href="#" class="filter--close-btn" data-show-products-text="{s name="ListingActionsCloseFilterShowProducts"}{/s}">
                                {s name="ListingActionsCloseFilter"}{/s} <i class="icon--arrow-right"></i>
                            </a>
                        {/block}

                        {block name='frontend_listing_actions_filter_container'}
                            <div class="filter--container">
                                {block name='frontend_listing_actions_filter_form'}
                                    <form id="filter"
                                          method="get"
                                          data-filter-form="true"
                                          data-is-in-sidebar="{if $theme.sidebarFilter}true{else}false{/if}"
                                          data-listing-url="{$countCtrlUrl}"
                                          data-is-filtered="{if $criteria}{$criteria->getUserConditions()|count}{else}0{/if}"
                                          data-load-facets="{if $listingMode == 'filter_ajax_reload'}true{else}false{/if}"
                                          data-instant-filter-result="{if $listingMode != 'full_page_reload'}true{else}false{/if}"
                                          class="{if $listingMode != 'full_page_reload'} is--instant-filter{/if}">

                                        {if $listingMode === 'full_page_reload'}
                                            {include file="frontend/listing/actions/filter-apply-button.tpl" clsSuffix='filter--actions-top'}
                                        {/if}

                                        {block name="frontend_listing_actions_filter_form_page"}
                                            <input type="hidden" name="{$shortParameters['sPage']}" value="1"/>
                                        {/block}

                                        {block name="frontend_listing_actions_filter_form_search"}
                                            {if $term}
                                                <input type="hidden" name="{$shortParameters['sSearch']}" value="{$term|escape}"/>
                                            {/if}
                                        {/block}

                                        {block name="frontend_listing_actions_filter_form_sort"}
                                            {if $sSort}
                                                <input type="hidden" name="{$shortParameters['sSort']}" value="{$sSort|escape}"/>
                                            {/if}
                                        {/block}

                                        {block name="frontend_listing_actions_filter_form_perpage"}
                                            {if $criteria && $criteria->getLimit()}
                                                <input type="hidden" name="{$shortParameters['sPerPage']}" value="{$criteria->getLimit()|escape}"/>
                                            {/if}
                                        {/block}

                                        {block name="frontend_listing_actions_filter_form_category"}
                                            {if !$sCategoryContent && $sCategoryCurrent != $sCategoryStart && {controllerName} != 'search'}
                                                <input type="hidden" name="{$shortParameters['sCategory']}" value="{$sCategoryCurrent|escape}" />
                                            {/if}
                                        {/block}

                                        {block name="frontend_listing_actions_filter_form_facets"}
                                            <div class="filter--facet-container">
                                                {foreach $facets as $facet}
                                                    {if $facet->getTemplate() !== null}
                                                        {if $facet->getTemplate() == 'frontend/listing/filter/facet-value-list.tpl'}
                                                            {$type = 'value-list'}
                                                            {$filterType = 'value-list'}
                                                            {$listingMode = {config name="listingMode"}}
                                                            {if $listingMode == 'filter_ajax_reload'}
                                                                {$type = 'value-list-single'}
                                                                {$filterType = 'value-list-single'}
                                                            {/if}
                                                            <div class="filter-panel filter--multi-selection filter-facet--{$filterType} facet--{$facet->getFacetName()|escape:'htmlall'}"
                                                                 data-filter-type="{$filterType}"
                                                                 data-facet-name="{$facet->getFacetName()}"
                                                                 data-field-name="{$facet->getFieldName()|escape:'htmlall'}">
                                                                {block name="frontend_listing_filter_facet_multi_selection_flyout"}
                                                                    <div class="filter-panel--flyout">
                                                                        {block name="frontend_listing_filter_facet_multi_selection_title"}
                                                                            <label class="filter-panel--title" for="{$facet->getFieldName()|escape:'htmlall'}">{$facet->getLabel()|escape}</label>
                                                                        {/block}
                                                                        {block name="frontend_listing_filter_facet_multi_selection_icon"}
                                                                            <span class="filter-panel--icon"></span>
                                                                        {/block}
                                                                        {block name="frontend_listing_filter_facet_value_list_search_field"}
                                                                            {if $bxFacets->getFacetExtraInfo({$facetOptions.{$facet->getLabel()|trim}.fieldName|trim}, 'visualisation') == 'search'}
                                                                                <div class="bx-{$facet->getFacetName()|escape:'htmlall'}-search" style="padding: 0rem 0rem 1rem 0rem;position:relative;0.5px solid">
                                                                                    <input class="bx--facet-search" type="search" style="width: 100%;border: 1px solid;" />
                                                                                    <span class="icon--search search-remove" style="padding: 0.3rem .8rem 0.3rem 0.8rem;
                                            position: absolute;
                                            top:0rem;right:0rem;z-index:2;
                                            border: 0 none; background: transparent;outline:none;
                                            text-transform: none;font-size: 2rem;">
                                                                                    </span>
                                                                                </div>
                                                                            {/if}
                                                                        {/block}
                                                                        {block name="frontend_listing_filter_facet_multi_selection_content"}
                                                                        {$inputType = 'checkbox'}
                                                                        {if $filterType == 'radio'}
                                                                            {$inputType = 'radio'}
                                                                        {/if}
                                                                        {$indicator = $inputType}
                                                                        {$isMediaFacet = false}
                                                                        {if $facet|is_a:'\Shopware\Bundle\SearchBundle\FacetResult\MediaListFacetResult'}
                                                                            {$isMediaFacet = true}
                                                                            {$indicator = 'media'}
                                                                        {/if}
                                                                        <div class="filter-panel--content input-type--{$indicator}">
                                                                            {block name="frontend_listing_filter_facet_multi_selection_list"}
                                                                                <ul class="filter-panel--option-list">
                                                                                    {$showMore = false}
                                                                                    {assign var="hiddenClass" value=''}
                                                                                    {assign var="optionStyle" value=''}
                                                                                    {foreach $facet->getValues() as $option}

                                                                                        {if $showMore == false}
                                                                                            {if $facet->getFacetName() == 'property'}
                                                                                                {if $bxFacets->isFacetValueHidden({$facetOptions.{$facet->getLabel()|trim}.fieldName|trim},
                                                                                                {$option->getLabel()|substr:0:{{$option->getLabel()|strrpos:'('}-1}|cat:'_bx_'|cat:$option->getId()|trim}) === true}
                                                                                                    {assign var="showMore" value=true}
                                                                                                {/if}
                                                                                            {else}
                                                                                                {if $bxFacets->isFacetValueHidden({$facetOptions.{$facet->getLabel()|trim}.fieldName},
                                                                                                {$option->getLabel()|substr:0:{{$option->getLabel()|strrpos:'('}-1}|trim}) === true}
                                                                                                    {assign var="showMore" value=true}
                                                                                                {/if}
                                                                                            {/if}
                                                                                            {if $showMore == true}
                                                                                                {assign var="hiddenClass" value=' hidden-items'}
                                                                                                {assign var="optionStyle" value='style="display:none"'}
                                                                                            {/if}
                                                                                        {/if}
                                                                                        {block name="frontend_listing_filter_facet_multi_selection_option"}
                                                                                            <li class="filter-panel--option{$hiddenClass}" {$optionStyle}>
                                                                                                {block name="frontend_listing_filter_facet_multi_selection_option_container"}
                                                                                                    <div class="option--container">
                                                                                                        {block name="frontend_listing_filter_facet_multi_selection_input"}
                                                                                                            <span class="filter-panel--input filter-panel--{$inputType}">
                                                                                                            {$name = "__{$facet->getFieldName()|escape:'htmlall'}__{$option->getId()|escape:'htmlall'}"}
                                                                                                            {if $filterType == 'radio'}
                                                                                                                {$name = {$facet->getFieldName()|escape:'htmlall'} }
                                                                                                            {/if}
                                                                                                            <input type="{$inputType}"id="__{$facet->getFieldName()|escape:'htmlall'}__{$option->getId()|escape:'htmlall'}"name="{$name}"value="{$option->getId()|escape:'htmlall'}"{if $option->isActive()}checked="checked" {/if}/>
                                                                                                            <span class="input--state {$inputType}--state">&nbsp;</span>
                                                                                                            </span>
                                                                                                        {/block}

                                                                                                        {block name="frontend_listing_filter_facet_multi_selection_label"}
                                                                                                            <label class="filter-panel--label" for="__{$facet->getFieldName()|escape:'htmlall'}__{$option->getId()|escape:'htmlall'}">
                                                                                                                {if $facet|is_a:'\Shopware\Bundle\SearchBundle\FacetResult\MediaListFacetResult'}
                                                                                                                    {$mediaFile = {link file='frontend/_public/src/img/no-picture.jpg'}}
                                                                                                                    {if $option->getMedia()}
                                                                                                                        {$mediaFile = $option->getMedia()->getFile()}
                                                                                                                    {/if}

                                                                                                                    <img class="filter-panel--media-image" src="{$mediaFile}" alt="{$option->getLabel()|escape:'htmlall'}" />
                                                                                                                {else}
                                                                                                                    {$option->getLabel()|escape}
                                                                                                                {/if}
                                                                                                            </label>
                                                                                                        {/block}
                                                                                                    </div>
                                                                                                {/block}
                                                                                            </li>
                                                                                        {/block}
                                                                                    {/foreach}
                                                                                    {if $showMore == true}
                                                                                        <li style="cursor:pointer" class="show-more-values">{s namespace="boxalino/intelligence" name="filter/morevalues"}{/s}</li>
                                                                                    {/if}
                                                                                </ul>
                                                                            {/block}
                                                                        </div>
                                                                    {/block}
                                                                </div>
                                                            {/block}
                                                        </div>
                                                        {else}
                                                            {include file=$facet->getTemplate() facet=$facet}
                                                        {/if}
                                                    {/if}
                                                {/foreach}
                                            </div>
                                        {/block}

                                        {block name="frontend_listing_actions_filter_active_filters"}
                                            <div class="filter--active-container"
                                                 data-reset-label="{s name='ListingFilterResetAll'}{/s}">
                                            </div>
                                        {/block}

                                        {if $listingMode === 'full_page_reload'}
                                            {include file="frontend/listing/actions/filter-apply-button.tpl" clsSuffix='filter--actions-bottom'}
                                        {/if}
                                    </form>
                                {/block}
                            </div>
                        {/block}
                    </div>
                {/block}
            {/if}
        </div>
    </div>
</div>