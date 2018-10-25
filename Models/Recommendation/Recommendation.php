<?php
namespace Boxalino\Models\Recommendation;


class Recommendation
{
    CONST BX_RECOMMENDATION_TYPE_BASKET = "basket";
    CONST BX_RECOMMENDATION_TYPE_PRODUCT = "product";
    CONST BX_RECOMMENDATION_TYPE_CATEGORY = "category";
    CONST BX_RECOMMENDATION_TYPE_BXI_CONTENT = "bxi_content";
    CONST BX_RECOMMENDATION_TYPE_BLOG = "blog";
    CONST BX_RECOMMENDATION_TYPE_PORTFOLIO_BLOG = "portfolio_blog";
    CONST BX_RECOMMENDATION_TYPE_BLOG_PRODUCT = "blog_product";

    public function getProductRecommendationBlocks()
    {
        return [
            'sRelatedArticles' => 'boxalino_accessories_recommendation',
            'sSimilarArticles' => 'boxalino_similar_recommendation',
            'boughtArticles' => 'boxalino_complementary_recommendation',
            'viewedArticles' => 'boxalino_related_recommendation'
        ];
    }


}