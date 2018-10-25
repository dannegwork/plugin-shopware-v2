<div class="content-main--inner">
    {foreach $bxSubRenderings as $i => $subRendering}
        {$bxRender->renderElement($subRendering.visualElement)}
    {/foreach}
</div>
