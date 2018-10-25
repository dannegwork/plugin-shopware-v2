<?php
namespace Boxalino\Subscriber;

use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use Shopware\Components\Plugin\ConfigReader;

class IndexingSubscriber  extends AbstractSubscriber
    implements  SubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Dispatcher_ControllerPath_Backend_BoxalinoExport' => 'boxalinoBackendControllerExport',
            'Enlight_Controller_Action_PostDispatch_Backend_Customer' => 'onBackendCustomerPostDispatch',
//            'Theme_Compiler_Collect_Plugin_Javascript' => 'addJsFiles',
//            'Theme_Compiler_Collect_Plugin_Less' => 'addLessFiles',
        ];
    }

    public function boxalinoBackendControllerExport() {
        Shopware()->Template()->addTemplateDir($this->getPath() . 'Resources/views/');
        return $this->getPath()  . "/Controllers/Backend/BoxalinoExport.php";
    }

    /**
     * Called when the BackendCustomerPostDispatch Event is triggered
     *
     * @param Enlight_Event_EventArgs $args
     */
    public function onBackendCustomerPostDispatch(Enlight_Event_EventArgs $args) {

        /**@var $view Enlight_View_Default*/
        $view = $args->getSubject()->View();

        // Add template directory
        $view->addTemplateDir($this->getPath() . 'Resources/views/');

        //if the controller action name equals "load" we have to load all application components
        if ($args->getRequest()->getActionName() === 'load') {
            $view->extendsTemplate('backend/customer/model/customer_preferences/attribute.js');
            $view->extendsTemplate('backend/customer/model/customer_preferences/list.js');
            $view->extendsTemplate('backend/customer/view/list/customer_preferences/list.js');
            $view->extendsTemplate('backend/customer/view/detail/customer_preferences/window.js');
            $view->extendsTemplate('backend/boxalino_export/view/main/window.js');
            $view->extendsTemplate('backend/boxalino_config/view/main/window.js');

            //if the controller action name equals "index" we have to extend the backend customer application
            if ($args->getRequest()->getActionName() === 'index') {
                $view->extendsTemplate('backend/customer/customer_preferences_app.js');
                $view->extendsTemplate('backend/boxalino_export/boxalino_export_app.js');
                $view->extendsTemplate('backend/boxalino_config/boxalino_config_app.js');
            }
        }
    }

    /**
     * In shopware 5, the less and jss files are added automatically
     * if they`re located in the right folder
     *
     * @deprecated
     * @param Enlight_Event_EventArgs $args
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function addJsFiles(Enlight_Event_EventArgs $args) {
        $jsFiles = array(
            __DIR__ . '/Views/responsive/frontend/_resources/javascript/jquery.bx_register_add_article.js',
            __DIR__ . '/Views/responsive/frontend/_resources/javascript/jquery.search_enhancements.js',
            __DIR__ . '/Views/responsive/frontend/_resources/javascript/boxalinoFacets.js',
            __DIR__ . '/Views/responsive/frontend/_resources/javascript/jssor.slider-26.2.0.min.js'
        );
        return new \Doctrine\Common\Collections\ArrayCollection($jsFiles);
    }

    /**
     * The files have been migrated to the proper
     * @deprecated
     * @param Enlight_Event_EventArgs $args
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function addLessFiles(Enlight_Event_EventArgs $args) {
        $less = array(
            new \Shopware\Components\Theme\LessDefinition(
                array(),
                array(__DIR__ . '/Views/responsive/frontend/_resources/less/cart_recommendations.less'),
                __DIR__
            ), new \Shopware\Components\Theme\LessDefinition(
                array(),
                array(__DIR__ . '/Views/responsive/frontend/_resources/less/search.less'),
                __DIR__
            ), new \Shopware\Components\Theme\LessDefinition(
                array(),
                array(__DIR__ . '/Views/responsive/frontend/_resources/less/portfolio.less'),
                __DIR__
            ), new \Shopware\Components\Theme\LessDefinition(
                array(),
                array(__DIR__ . '/Views/responsive/frontend/_resources/less/productfinder.less'),
                __DIR__
            ), new \Shopware\Components\Theme\LessDefinition(
                array(),
                array(__DIR__ . '/Views/responsive/frontend/_resources/less/blog_recommendations.less'),
                __DIR__
            )
        );
        return new \Doctrine\Common\Collections\ArrayCollection($less);
    }
}