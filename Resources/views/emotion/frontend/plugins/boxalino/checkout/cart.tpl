{extends file='parent:frontend/checkout/cart.tpl'}

{block name="frontend_checkout_cart_premium" prepend}
    <div class="bx-cart-recommendation">
        {include file="frontend/_includes/product_slider.tpl"
        Data=$Bx
        articles=$sRecommendations
        sliderMode={''}
        sliderArrowControls={''}
        sliderAnimationSpeed=500
        sliderAutoSlideSpeed={''}
        sliderAutoSlide={''}
        productBoxLayout="emotion"
        fixedImageSize="true"}
    </div>
{/block}