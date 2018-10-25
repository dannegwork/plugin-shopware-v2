{$Data.view}
{*{block name="frontend_index_start"}{/block}*}
{*{block name="frontend_index_doctype"}*}
{*<!DOCTYPE html>*}
{*{/block}*}

{*{block name='frontend_index_html'}*}
{*<html class="no-js" lang="{s name='IndexXmlLang'}{/s}" itemscope="itemscope" itemtype="http://schema.org/WebPage">*}
{*{/block}*}

{*{block name='frontend_index_header'}*}
    {*{include file='frontend/index/header.tpl'*}

    {*sCategoryContent = $Data.sCategoryContent*}
    {*theme = $Data.theme*}
    {*sBreadcrumb = $Data.sBreadcrumb*}
    {*lessFiles = $Data.lessFiles*}

    {*}*}
{*{/block}*}

{*<body class="{block name="frontend_index_body_classes"}{strip}*}
    {*is--ctl-{controllerName|lower} is--act-{controllerAction|lower}*}
    {*{if $sUserLoggedIn} is--user{/if}*}
    {*{if $sOneTimeAccount} is--one-time-account{/if}*}
    {*{if $sTarget} is--target-{$sTarget|escapeHtml}{/if}*}
    {*{if $Data.theme.checkoutHeader && (({controllerName|lower} == "checkout" && {controllerAction|lower} != "cart") || ({controllerName|lower} == "register" && ($sTarget != "account" && $sTarget != "address")))} is--minimal-header{/if}*}
    {*{if !$Data.theme.displaySidebar} is--no-sidebar{/if}*}
    {*{/strip}{/block}" {block name="frontend_index_body_attributes"}{/block}>*}

    {*{block name='frontend_index_after_body'}{/block}*}

    {*{block name="frontend_index_page_wrap"}*}
        {*<div class="page-wrap">*}

          {* Message if javascript is disabled *}
          {*{block name="frontend_index_no_script_message"}*}
              {*<noscript class="noscript-main">*}
                  {*{include file="frontend/_includes/messages.tpl" type="warning" content="{s name="IndexNoscriptNotice"}{/s}" borderRadius=false}*}
              {*</noscript>*}
          {*{/block}*}

          {*{block name='frontend_index_before_page'}{/block}*}

          {*{block name='frontend_index_emotion_loading_overlay'}*}
              {*{if $Data.hasEmotion}*}
                  {*<div class="emotion--overlay">*}
                      {*<i class="emotion--loading-indicator"></i>*}
                  {*</div>*}
              {*{/if}*}
          {*{/block}*}

          {*{block name='frontend_index_content_main'}*}
              {*<section class="{block name="frontend_index_content_main_classes"}content-main container block-group{/block}" style="box-shadow:none;">*}

                  {* Breadcrumb *}
                  {*{block name='frontend_index_breadcrumb'}*}
                      {*{if count($Data.sBreadcrumb)}*}
                          {*<nav class="content--breadcrumb block">*}
                              {*{block name='frontend_index_breadcrumb_inner'}*}
                                  {*{include file='frontend/index/breadcrumb.tpl'}*}
                              {*{/block}*}
                          {*</nav>*}
                      {*{/if}*}
                  {*{/block}*}

                  {* Content top container *}
                  {*{block name="frontend_index_content_top"}{/block}*}

                  {*<div class="content-main--inner">*}

                    {* Sidebar left *}
                    {*{block name='frontend_index_content_left'}*}

                        {*{block name='frontend_index_controller_url'}*}
                            {* Controller url for the found products counter *}
                            {*{$countCtrlUrl = "{url module="widgets" controller="emotion" action="index" emotionId="$Data.emotion.id" controllerName="$Data.Controller" params=$Data.ajaxCountUrlParams fullPath}"}*}
                        {*{/block}*}

                        {*{include file='frontend/listing/sidebar.tpl'*}

                        {*theme = $Data.theme*}
                        {*criteria = $Data.criteria*}
                        {*facets = $Data.facets*}

                        {*}*}
                    {*{/block}*}

                        {* Main content *}
                        {*{block name='frontend_index_content_wrapper'}*}
                          {*<div class="content--wrapper">*}
                            {*{block name='frontend_index_content'}*}
                              {*<div class="content listing--content" style="margin-top:0 !important; padding-top:0 !important">*}

                                {* Banner *}
                                {*{block name="frontend_listing_index_banner"}*}
                                    {*{if !$Data.hasEmotion}*}
                                        {*{include file='frontend/listing/banner.tpl'}*}
                                    {*{/if}*}
                                {*{/block}*}

                                {* Category headline *}
                                {*{block name="frontend_listing_index_text"}*}
                                    {*{if !$Data.hasEmotion}*}
                                        {*{include file='frontend/listing/text.tpl'}*}
                                    {*{/if}*}
                                {*{/block}*}

                                {* Topseller *}
                                {*{block name="frontend_listing_index_topseller"}*}
                                    {*{if !$Data.hasEmotion && {config name=topSellerActive}}*}
                                        {*{action module=widgets controller=listing action=top_seller sCategory=$Data.sCategoryContent.id}*}
                                    {*{/if}*}
                                {*{/block}*}

                                {* Define all necessary template variables for the listing *}
                                  {*{block name="frontend_listing_index_layout_variables"}*}

                                      {*{$emotionViewports = [0 => 'xl', 1 => 'l', 2 => 'm', 3 => 's', 4 => 'xs']}*}

                                      {* Count of available product pages *}
                                      {*{$pages = 1}*}

                                      {*{if $Data.criteria}*}
                                          {*{$pages = ceil($Data.sNumberArticles / $Data.criteria->getLimit())}*}
                                      {*{/if}*}

                                      {* Layout for the product boxes *}
                                      {*{$Data.productBoxLayout = 'basic'}*}

                                      {*{if $Data.sCategoryContent.productBoxLayout !== null && $Data.sCategoryContent.productBoxLayout !== 'extend'}*}
                                          {*{$Data.productBoxLayout = $Data.sCategoryContent.productBoxLayout}*}
                                      {*{/if}*}
                                  {*{/block}*}

                                {* Listing *}
                                {*{block name="frontend_listing_index_listing"}*}
                                    {*{include file='frontend/listing/listing.tpl'*}

                                      {*bxFacets=$Data.bxFacets*}
                                      {*criteria=$Data.criteria*}
                                      {*facets=$Data.facets*}
                                      {*sNumberArticles=$Data.sNumberArticles*}
                                      {*sArticles=$Data.sArticles*}
                                      {*facetOptions=$Data.facetOptions*}

                                      {*sBanner=$Data.sBanner*}
                                      {*sBreadcrumb=$Data.sBreadcrumb*}
                                      {*sCategoryContent=$Data.sCategoryContent*}
                                      {*activeFilterGroup=$Data.activeFilterGroup*}
                                      {*ajaxCountUrlParams=$Data.ajaxCountUrlParams*}
                                      {*params=$Data.params*}
                                      {*emotions=$Data.emotions*}
                                      {*hasEmotion=$Data.hasEmotion*}
                                      {*showListing=$Data.showListing*}
                                      {*showListingDevices=$Data.showListingDevices*}
                                      {*isHomePage=$Data.isHomePage*}
                                      {*sArticles=$Data.sArticles*}
                                      {*criteria=$Data.criteria*}
                                      {*facets=$Data.facets*}
                                      {*sPage=$Data.sPage*}
                                      {*sPageIndex=$Data.sPageIndex*}
                                      {*pageSizes=$Data.pageSizes*}
                                      {*sPerPage=$Data.sPerPage*}
                                      {*sNumberArticles=$Data.sNumberArticles*}
                                      {*shortParameters=$Data.shortParameters*}
                                      {*sTemplate=$Data.sTemplate*}
                                      {*sSort=$Data.sSort*}
                                      {*sortings=$Data.sortings*}

                                      {*Shop=$Data.Shop*}
                                      {*theme=$Data.theme*}

                                      {*}*}
                                {*{/block}*}

                                {* Tagcloud *}
                                {*{block name="frontend_listing_index_tagcloud"}*}
                                    {*{if {config name=show namespace=TagCloud }}*}
                                        {*{action module=widgets controller=listing action=tag_cloud sController=listing sCategory=$Data.sCategoryContent.id}*}
                                    {*{/if}*}
                                {*{/block}*}
                              {*</div>*}
                          {*{/block}*}
                        {*</div>*}
                    {*{/block}*}

                        {* Sidebar right *}
                        {*{block name='frontend_index_content_right'}{/block}*}

                        {* Last seen products *}
                        {*{block name='frontend_index_left_last_articles'}*}
                            {*{if $Data.sLastArticlesShow && !$Data.isEmotionLandingPage}*}
                                {* Last seen products *}
                                {*<div class="last-seen-products is--hidden" data-last-seen-products="true">*}
                                    {*<div class="last-seen-products--title">*}
                                        {*{s namespace="frontend/plugins/index/viewlast" name='WidgetsRecentlyViewedHeadline'}{/s}*}
                                    {*</div>*}
                                    {*<div class="last-seen-products--slider product-slider" data-product-slider="true">*}
                                        {*<div class="last-seen-products--container product-slider--container"></div>*}
                                    {*</div>*}
                                {*</div>*}
                            {*{/if}*}
                        {*{/block}*}
                    {*</div>*}
                {*</section>*}
            {*{/block}*}

            {* removed footer *}

            {*{block name='frontend_index_body_inline'}{/block}*}
        {*</div>*}
    {*{/block}*}

    {* If required add the cookiePermission hint *}
    {*{block name='frontend_index_cookie_permission'}*}
        {*{if {config name="show_cookie_note"}}*}
            {*{include file="frontend/_includes/cookie_permission_note.tpl"}*}
        {*{/if}*}
    {*{/block}*}

{*{block name="frontend_index_header_javascript"}*}
    {*<script type="text/javascript" id="footer--js-inline">*}
        {*//<![CDATA[*}
        {*{block name="frontend_index_header_javascript_inline"}*}
            {*var timeNow = {time() nocache};*}

            {*var asyncCallbacks = [];*}

            {*document.asyncReady = function (callback) {*}
                {*asyncCallbacks.push(callback);*}
            {*};*}

            {*var controller = controller || {ldelim}*}
                {*'vat_check_enabled': '{config name='vatcheckendabled'}',*}
                {*'vat_check_required': '{config name='vatcheckrequired'}',*}
                {*'ajax_cart': '{url controller='checkout' action='ajaxCart'}',*}
                {*'ajax_search': '{url controller="ajax_search" _seo=false}',*}
                {*'register': '{url controller="register"}',*}
                {*'checkout': '{url controller="checkout"}',*}
                {*'ajax_validate': '{url controller="register"}',*}
                {*'ajax_add_article': '{url controller="checkout" action="addArticle"}',*}
                {*'ajax_listing': '{url module="widgets" controller="Listing" action="ajaxListing"}',*}
                {*'ajax_cart_refresh': '{url controller="checkout" action="ajaxAmount"}',*}
                {*'ajax_address_selection': '{url controller="address" action="ajaxSelection" fullPath forceSecure}',*}
                {*'ajax_address_editor': '{url controller="address" action="ajaxEditor" fullPath forceSecure}'*}
            {*{rdelim};*}

            {*var snippets = snippets || {ldelim}*}
                {*'noCookiesNotice': '{"{s name='IndexNoCookiesNotice'}{/s}"|escape}'*}
            {*{rdelim};*}

            {*var themeConfig = themeConfig || {ldelim}*}
                {*'offcanvasOverlayPage': '{$Data.theme.offcanvasOverlayPage}'*}
            {*{rdelim};*}

            {*var lastSeenProductsConfig = lastSeenProductsConfig || {ldelim}*}
                {*'baseUrl': '{$Data.Shop->getBaseUrl()}',*}
                {*'shopId': '{$Data.Shop->getId()}',*}
                {*'noPicture': '{link file="frontend/_public/src/img/no-picture.jpg"}',*}
                {*'productLimit': ~~('{config name="lastarticlestoshow"}'),*}
                {*'currentArticle': {ldelim}{if $Data.sArticle}*}
                    {*{foreach $sLastArticlesConfig as $key => $value}*}
                        {*'{$key}': '{$value}',*}
                    {*{/foreach}*}
                    {*'articleId': ~~('{$Data.sArticle.articleID}'),*}
                    {*'orderNumber': '{$Data.sArticle.ordernumber}',*}
                    {*'linkDetailsRewritten': '{$Data.sArticle.linkDetailsRewrited}',*}
                    {*'articleName': '{$Data.sArticle.articleName|escape:"javascript"}{if $Data.sArticle.additionaltext} {$sData.Article.additionaltext|escape:"javascript"}{/if}',*}
                    {*'imageTitle': '{$sData.Article.image.description|escape:"javascript"}',*}
                    {*'images': {ldelim}*}
                        {*{foreach $Data.sArticle.image.thumbnails as $key => $image}*}
                            {*'{$key}': {ldelim}*}
                                {*'source': '{$image.source}',*}
                                {*'retinaSource': '{$image.retinaSource}',*}
                                {*'sourceSet': '{$image.sourceSet}'*}
                            {*{rdelim},*}
                        {*{/foreach}*}
                    {*{rdelim}*}
                {*{/if}{rdelim}*}
            {*{rdelim};*}

            {*var csrfConfig = csrfConfig || {ldelim}*}
                {*'generateUrl': '{url controller="csrftoken" fullPath=false}',*}
                {*'basePath': '{$Data.Shop->getBasePath()}',*}
                {*'shopId': '{$Data.Shop->getId()}'*}
            {*{rdelim};*}
        {*{/block}*}
        {*//]]>*}
    {*</script>*}

    {*{include file="frontend/index/datepicker-config.tpl"*}

    {*Shop=$Data.Shop*}

    {*}*}

    {*{if $Data.theme.additionalJsLibraries}*}
        {*{$Data.theme.additionalJsLibraries}*}
    {*{/if}*}
{*{/block}*}

{*{block name="frontend_index_header_javascript_jquery"}*}
    {* Add the partner statistics widget, if configured *}
    {*{if !{config name=disableShopwareStatistics} }*}
        {*{include file='widgets/index/statistic_include.tpl'*}

        {*Shop = $Data.Shop*}
        {*Controller = $Data.Controller*}
        {*sArticle = $Data.sArticle*}

        {*}*}
    {*{/if}*}
{*{/block}*}

{*Include jQuery and all other javascript files at the bottom of the page*}
{*{block name="frontend_index_header_javascript_jquery_lib"}*}
    {*{compileJavascript timestamp={themeTimestamp} output="javascriptFiles"}*}
    {*{foreach $javascriptFiles as $file}*}
        {*<script{if $theme.asyncJavascriptLoading} async{/if} src="{$file}" id="main-script"></script>*}
    {*{/foreach}*}
{*{/block}*}

{*{block name="frontend_index_javascript_async_ready"}*}
    {*{include file="frontend/index/script-async-ready.tpl"}*}
{*{/block}*}
{*<script>*}
{*document.asyncReady(function() {*}
    {*$(document).ready(function() {*}
      {*StateManager.destroyPlugin('*[data-filter-type]','swFilterComponent');*}
      {*StateManager.updatePlugin('*[data-filter-type]','swFilterComponent');*}
    {*});*}
{*});*}
{*</script>*}
{*</body>*}
{*</html>*}
