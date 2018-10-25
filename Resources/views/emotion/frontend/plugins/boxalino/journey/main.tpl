{$dependencies}
{foreach $narrative as $visualElement}
    {$bxRender->renderElement($visualElement.visualElement)}
{/foreach}