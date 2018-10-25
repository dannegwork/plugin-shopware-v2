<?php
namespace Boxalino\Models\Interceptor;


class Interceptor
{
    CONST BX_INTERCEPTOR_REQUEST_DETAIL = 'detail';
    CONST BX_INTERCEPTOR_REQUEST_ACCOUNT = 'account';
    CONST BX_INTERCEPTOR_REQUEST_SEARCH = 'search';
    CONST BX_INTERCEPTOR_REQUEST_CAT = 'cat';
    CONST BX_INTERCEPTOR_REQUEST_RECOMMENDATION = 'recommendation';

    private $_productRecommendations = array(
        'sRelatedArticles' => 'boxalino_accessories_recommendation',
        'sSimilarArticles' => 'boxalino_similar_recommendation'
    );

    private $_productRecommendationsGeneric = array(
        'sCrossBoughtToo' => 'boxalino_complementary_recommendation',
        'sCrossSimilarShown' => 'boxalino_related_recommendation'
    );

    public function getProductRecommendationBlocks()
    {
        return [
            'sRelatedArticles' => 'boxalino_accessories_recommendation',
            'sSimilarArticles' => 'boxalino_similar_recommendation'
        ];
    }

    public function getProductRecommendationGenergicBlocks()
    {
        return [
            'sCrossBoughtToo' => 'boxalino_complementary_recommendation',
            'sCrossSimilarShown' => 'boxalino_related_recommendation'
        ];
    }
}