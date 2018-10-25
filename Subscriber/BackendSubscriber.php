<?php
namespace Boxalino\Subscriber;

use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use Shopware\Components\Plugin\ConfigReader;

class BackendSubscriber  extends AbstractSubscriber
    implements  SubscriberInterface
{
    /**
     * @var string
     */
    private $pluginBaseDirectory;

    /**
     * @param string $pluginBaseDirectory
     */
    public function __construct($pluginBaseDirectory)
    {
        $this->pluginBaseDirectory = $pluginBaseDirectory;
        parent::__construct();
    }


    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatch_Backend_Emotion' => 'onPostDispatchBackendEmotion'
        ];
    }

    public function onPostDispatchBackendEmotion(Enlight_Event_EventArgs $args) {
        $view = $args->getSubject()->View();
        $view->addTemplateDir($this->pluginBaseDirectory . '/Resources/views/');
        if ($args->getRequest()->getActionName() === 'index') {
            $view->extendsTemplate('backend/boxalino_emotion/app.js');
            $view->extendsTemplate('backend/boxalino_narrative/app.js');
        }
    }


}