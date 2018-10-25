{if $Data.render_option == 1}
    {$Data.dependencies}
    {foreach $Data.narrative as $visualElement}
        {$Data.bxRender->renderElement($visualElement.visualElement)}
    {/foreach}
{else}
    <div class="narrative-container"></div>
    <script>
        $(document).ready(function() {
            var controller  = '{url controller=BxNarrative choice_id={$Data.choiceId} additional={$Data.additional_choiceId}}';
            $.ajax({
                type: "GET",
                url: controller
            }).done(function(res) {
                $('.narrative-container').html(res);
                }, function (err) {

            });
        });
    </script>
{/if}