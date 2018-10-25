{block name="frontend_blog_recommendation"}
    <a href="{url controller=blog action=detail sCategory=$sArticle.category_id blogArticle=$sArticle.id}" title="{$sArticle.title|escape}">
        <div class="box--content">
            <div class="product--info">
                <div class="product--box box--{$productBoxLayout}">
            <span class="image--element">
                <span class="image--media">
                    {if isset($sArticle.media.thumbnails)}
                        {if $element.viewports && !$fixedImageSize}
                            {foreach $element.viewports as $viewport}
                                {$cols = ($viewport.endCol - $viewport.startCol) + 1}
                                {$elementSize = $cols * $cellWidth}
                                {$size = "{$elementSize}vw"}

                                {if $breakpoints[$viewport.alias]}

                                    {if $viewport.alias === 'xl' && !$emotionFullscreen}
                                        {$size = "calc({$elementSize / 100} * {$baseWidth}px)"}
                                        {$size = "(min-width: {$baseWidth}px) {$size}"}
                                    {else}
                                        {$size = "(min-width: {$breakpoints[$viewport.alias]}) {$size}"}
                                    {/if}
                                {/if}

                                {$itemSize = "{$size}{if $itemSize}, {$itemSize}{/if}"}
                            {/foreach}
                        {else}
                            {$itemSize = "200px"}
                        {/if}

                        {$srcSet = ''}
                        {$srcSetRetina = ''}

                        {foreach $sArticle.media.thumbnails as $image}
                            {$srcSet = "{if $srcSet}{$srcSet}, {/if}{$image.source} {$image.maxWidth}w"}

                            {if $image.retinaSource}
                                {$srcSetRetina = "{if $srcSetRetina}{$srcSetRetina}, {/if}{$image.retinaSource} {$image.maxWidth * 2}w"}
                            {/if}
                        {/foreach}

                        <picture>
                            <source sizes="{$itemSize}" srcset="{$srcSetRetina}" media="(min-resolution: 192dpi)" />
                            <source sizes="{$itemSize}" srcset="{$srcSet}" />

                            <img src="{$sArticle.media.thumbnails[0].source}" alt="{$sArticle.title|strip_tags|truncate:160}" />
                        </picture>
                    {else}
                        <img src="{link file='frontend/_public/src/img/no-picture.jpg'}"
                             alt="{$sArticle.title|escape}"
                             title="{$sArticle.title|escape|truncate:160}" />
                    {/if}
                </span>
            </span>
                    <div class="blog--box-title">
                        <h3>{$sArticle.title}</h3>
                    </div>
                    <div class="blog-box content">
                        <div class="blog--box-description{if !$sArticle.media} is--fluid{/if}">

                            {block name='frontend_blog_col_description_short'}
                                <div class="blog--box-description-short">
                                    {$sArticle.shortDescription|nl2br|truncate:100}
                                </div>
                            {/block}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </a>
{/block}