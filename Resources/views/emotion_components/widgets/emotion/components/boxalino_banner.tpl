<script type="text/javascript" async>
document.asyncReady(function() {

  initialJssorScale = 0;

  {$Data.id}_slider_init = function() {
      var jssor_1_SlideoTransitions = {$Data.transition};
      var jssor_1_SlideoBreaks = {$Data.break};
      var jssor_1_SlideoControls = {$Data.control};
      var jssor_1_options = {$Data.options};
      var jssor_1_slider = new $JssorSlider$({$Data.id}, jssor_1_options);
      var MAX_WIDTH = {$Data.max_width};

      function ScaleSlider() {$Data.function}

      ScaleSlider();

      $Jssor$.$AddEvent(window, "load", ScaleSlider);
      $Jssor$.$AddEvent(window, "resize", ScaleSlider);
      $Jssor$.$AddEvent(window, "orientationchange", ScaleSlider);
  }
});
</script>

<style>
    {$Data.css}
</style>

<div id={$Data.id} style={$Data.style}>
    {$Data.loading_screen}
    <div data-u="slides" style={$Data.slides_style}>
        {foreach $Data.slides as $slide}
          {$slide.div}
        {/foreach}
    </div>

    {if $Data.hitCount > 1}
      {$Data.bullet_navigator}
      {$Data.arrow_navigator}
    {/if}

</div>
<script type="text/javascript" async>
document.asyncReady(function() {
  {$Data.id}_slider_init();
});
</script>
