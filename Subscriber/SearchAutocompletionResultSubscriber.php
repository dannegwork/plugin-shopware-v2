<?php
namespace Boxalino\Subscriber;

use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use Shopware\Components\Plugin\ConfigReader;

class SearchAutocompletionResultSubscriber  extends AbstractSubscriber
    implements  SubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_Frontend_Search_DefaultSearch' => 'onSearch',
            'Enlight_Controller_Action_Frontend_AjaxSearch_Index' => 'onAjaxSearch',
            'Enlight_Controller_Action_PostDispatchSecure_Widgets_Recommendation' => 'onRecommendation',
            'Enlight_Controller_Action_PostDispatchSecure_Widgets_Emotion' => 'onEmotion',
            'Enlight_Controller_Action_PostDispatchSecure_Frontend_Blog' => 'onBlog',
            'Enlight_Bootstrap_AfterInitResource_shopware_storefront.', 'onAjaxSearch',
        ];
    }

    public function onSearch(Enlight_Event_EventArgs $arguments) {
        try {
            return $this->searchInterceptor->search($arguments);
        } catch (\Exception $e) {
            $this->logException($e, __FUNCTION__, $arguments->getSubject()->Request()->getRequestUri());
        }
    }

    public function onAjaxSearch(Enlight_Event_EventArgs $arguments) {
        try {
            return $this->searchInterceptor->ajaxSearch($arguments);
        } catch (\Exception $e) {
            $this->logException($e, __FUNCTION__, $arguments->getSubject()->Request()->getRequestUri());
        }
    }


    public function onRecommendation(Enlight_Event_EventArgs $arguments) {
        try {
            return $this->frontendInterceptor->intercept($arguments);
        } catch (\Exception $e) {
            $this->logException($e, __FUNCTION__, $arguments->getSubject()->Request()->getRequestUri());
        }
    }

    public function onBlog(Enlight_Event_EventArgs $arguments) {

        if($arguments->getRequest()->getActionName() == 'detail'){
            try{
                $this->searchInterceptor->blog($arguments);
            }catch (\Exception $e) {
                $this->logException($e, __FUNCTION__, $arguments->getSubject()->Request()->getRequestUri());
            }
        }
    }

    public function onEmotion(Enlight_Event_EventArgs $arguments) {
        $view = $arguments->getSubject()->View();
        $view->addTemplateDir($this->getPath() . 'Resources/views/emotion/');
        $view->extendsTemplate('frontend/plugins/boxalino/listing/product-box/box-emotion.tpl');
    }


}