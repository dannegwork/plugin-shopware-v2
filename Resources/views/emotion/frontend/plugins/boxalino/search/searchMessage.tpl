{if isset($searchMessageData.bxSearchMessageTitle) && !isset($searchMessageData.bxSearchMessageMainImage)}

<div class="bx-search-message">
    <a href="{$searchMessageData.bxSearchMessageLink}">
        <span class="content bx-search-message-title">
            <h2 class="title bx-search-message-title"  style="{$searchMessageData.bxSearchMessageTitleStyle}">{$searchMessageData.bxSearchMessageTitle}</h2>
            <p class="info bx-search-message-description" style="{$searchMessageData.bxSearchMessageDescriptionStyle}">{$searchMessageData.bxSearchMessageDescription}</p>
        </span>
        <span class="image bx-search-message-side-image" style="float:right;"><img src="{$searchMessageData.bxSearchMessageSideImage}" alt="" /></span>
    </a>
</div>

{/if}

{if isset($searchMessageData.bxSearchMessageMainImage)}

<div class="bx-search-message">
    <a href="{$searchMessageData.bxSearchMessageLink}">
        <span class="content" style="float: left;">
            <strong class="title bx-search-message-title"  style="{$searchMessageData.bxSearchMessageTitleStyle}">{$searchMessageData.bxSearchMessageTitle}</strong>
        </span>
        <span class="image bx-search-message-main-image" style="float: left;">
            <img src="{$searchMessageData.bxSearchMessageMainImage}" alt="" style ="width: 100%;"/>
        </span>
    </a>
</div>

{/if}
