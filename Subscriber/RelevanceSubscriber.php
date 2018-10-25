<?php
namespace Boxalino\Subscriber;

use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use Shopware\Components\Plugin\ConfigReader;

class RelevanceSubscriber  extends AbstractSubscriber
    implements  SubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatchSecure_Backend_Performance' => 'onBackendPerformance',
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_BoxalinoPerformance' => 'boxalinoBackendControllerPerformance'
        ];
    }

    public function onBackendPerformance(Enlight_Event_EventArgs $arguments)
    {
        try {
            $controller = $arguments->getSubject();
            $view = $controller->View();
            $view->addTemplateDir($this->Path() . 'Views/');
            if ($arguments->getRequest()->getActionName() === 'load') {
                $view->extendsTemplate('backend/boxalino_performance/store/listing_sorting.js');
            }
        } catch (\Exception $e) {
            $this->logException($e, __FUNCTION__);
        }
    }

    public function boxalinoBackendControllerPerformance()
    {
        Shopware()->Template()->addTemplateDir(Shopware()->Plugins()->Frontend()->Boxalino()->Path() . 'Views/');
        return Shopware()->Plugins()->Frontend()->Boxalino()->Path() . "/Controllers/Backend/BoxalinoPerformance.php";

    }

}