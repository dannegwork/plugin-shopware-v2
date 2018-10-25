<?php
namespace Boxalino\Subscriber;

use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use Shopware\Components\Plugin\ConfigReader;

class TrackingSubscriber  extends AbstractSubscriber
    implements  SubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatchSecure_Frontend', 'onFrontend',
            'Shopware_Modules_Basket_AddArticle_FilterSql', 'onAddToBasket',
            'Shopware_Modules_Order_SaveOrder_ProcessDetails', 'onPurchase',
        ];
    }

    public function onFrontend(Enlight_Event_EventArgs $arguments) {
        try {
            $this->onBasket($arguments);
            return $this->frontendInterceptor->intercept($arguments);
        } catch (\Exception $e) {
            $this->logException($e, __FUNCTION__, $arguments->getSubject()->Request()->getRequestUri());
        }
    }

    public function onAddToBasket(Enlight_Event_EventArgs $arguments) {
        try {
            return $this->frontendInterceptor->addToBasket($arguments);
        } catch (\Exception $e) {
            $this->logException($e, __FUNCTION__, $arguments->getSubject()->Request()->getRequestUri());
        }
    }

    public function onPurchase(Enlight_Event_EventArgs $arguments) {
        try {
            return $this->frontendInterceptor->purchase($arguments);
        } catch (\Exception $e) {
            $this->logException($e, __FUNCTION__, $arguments->getSubject()->Request()->getRequestUri());
        }
    }

    private function onBasket(Enlight_Event_EventArgs $arguments) {
        try {
            return $this->frontendInterceptor->basket($arguments);
        } catch (\Exception $e) {
            $this->logException($e, __FUNCTION__, $arguments->getSubject()->Request()->getRequestUri());
        }
    }

}