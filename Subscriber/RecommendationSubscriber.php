<?php
namespace Boxalino\Subscriber;


use Enlight_Hook_HookArgs;

class RecommendationSubscriber  extends AbstractSubscriber
    implements  SubscriberInterface
{
    protected $listingHook = true;

    protected $showListing = true;

    public static function getSubscribedEvents()
    {
        return [
            'sMarketing::sGetAlsoBoughtArticles::replace', 'alsoBoughtRec',
            'sMarketing::sGetSimilaryShownArticles::replace', 'similarRec',
            'Shopware_Controllers_Frontend_Listing::indexAction::replace', 'onListingHook',
            'Shopware_Controllers_Widgets_Listing::listingCountAction::replace', 'onAjaxListingHook',
            'Shopware_Controllers_Frontend_Listing::getEmotionConfiguration::replace', 'onEmotionConfiguration',
            'Shopware_Controllers_Frontend_Listing::manufacturerAction::replace', 'onManufacturer'
        ];
    }

    public function alsoBoughtRec(Enlight_Hook_HookArgs $arguments){
        try{
            $arguments->setReturn($this->frontendInterceptor->alsoBoughtRecommendation($arguments));
        } catch (\Exception $e) {
            $this->logException($e, __FUNCTION__, Shopware()->Front()->Request()->getRequestUri());
            $arguments->setReturn(
                $arguments->getSubject()->executeParent(
                    $arguments->getMethod(),
                    $arguments->getArgs()
                ));
        }
        return null;
    }

    public function similarRec(Enlight_Hook_HookArgs $arguments){
        try{
            $arguments->setReturn($this->frontendInterceptor->similarRecommendation($arguments));
        } catch (\Exception $e) {
            $this->logException($e, __FUNCTION__, Shopware()->Front()->Request()->getRequestUri());
            $arguments->setReturn(
                $arguments->getSubject()->executeParent(
                    $arguments->getMethod(),
                    $arguments->getArgs()
                ));
        }
        return null;
    }

    public function onListingHook(Enlight_Hook_HookArgs $arguments){
        if(!Shopware()->Config()->get('boxalino_active') || !Shopware()->Config()->get('boxalino_navigation_enabled')) {
            $this->listingHook = false;
        }
        $arguments->getSubject()->executeParent(
            $arguments->getMethod(),
            $arguments->getArgs()
        );
        if($arguments->getSubject()->Response()->isRedirect()) {
            return;
        }
        try {
            $listingReturn = null;
            if($this->showListing && $this->listingHook) {
                $listingReturn = $this->searchInterceptor->listing($arguments);
            }
            if(is_null($listingReturn) && $this->listingHook) {
                $this->listingHook = false;
                $arguments->setReturn(
                    $arguments->getSubject()->executeParent(
                        $arguments->getMethod(),
                        $arguments->getArgs()
                    ));
            } else {
                $arguments->setReturn($listingReturn);
            }
        } catch (\Exception $e) {
            $this->logException($e, __FUNCTION__, $arguments->getSubject()->Request()->getRequestUri());
            $this->listingHook = false;
            $arguments->setReturn(
                $arguments->getSubject()->executeParent(
                    $arguments->getMethod(),
                    $arguments->getArgs()
                ));
        }
    }

    public function onAjaxListingHook(Enlight_Hook_HookArgs $arguments){
        try {
            $ajaxListingReturn = $this->searchInterceptor->listingAjax($arguments);
            if(is_null($ajaxListingReturn)) {
                $arguments->setReturn(
                    $arguments->getSubject()->executeParent(
                        $arguments->getMethod(),
                        $arguments->getArgs()
                    ));
            } else {
                $arguments->setReturn($ajaxListingReturn);
            }
        } catch (\Exception $e) {
            $this->logException($e, __FUNCTION__, $arguments->getSubject()->Request()->getRequestUri());
            $arguments->setReturn(
                $arguments->getSubject()->executeParent(
                    $arguments->getMethod(),
                    $arguments->getArgs()
                ));
        }
    }

    public function onEmotionConfiguration(Enlight_Hook_HookArgs $arguments) {
        if($arguments->getArgs()[1] === false) {
            $request = $arguments->getSubject()->Request();
            if($request->getParam('sPage') && $this->isLandingPage($request->getParam('sCategory'))) {
                $request->setParam('sPage', 0);
            }
            $return = $arguments->getSubject()->executeParent(
                $arguments->getMethod(),
                $arguments->getArgs()
            );
            if($this->listingHook) {
                $request = $arguments->getSubject()->Request();
                $id = $request->getParam('sCategory', null);
                $this->showListing = $return['showListing'];
                if($this->searchInterceptor->findStreamIdByCategoryId($id)) {
                    $this->showListing = true;
                }
                $return['showListing'] = false;
            }
            $arguments->setReturn($return);
        } else {
            $arguments->setReturn($arguments->getSubject()->executeParent(
                $arguments->getMethod(),
                $arguments->getArgs()
            ));
        }
    }

    public function onManufacturer(Enlight_Hook_HookArgs $arguments) {
        if(!Shopware()->Config()->get('boxalino_active') || !Shopware()->Config()->get('boxalino_navigation_enabled')) {
            $arguments->getSubject()->executeParent(
                $arguments->getMethod(),
                $arguments->getArgs()
            );
        } else {
            try{
                $this->searchInterceptor->listing($arguments);
            }catch (\Exception $e) {
                $this->logException($e, __FUNCTION__, $arguments->getSubject()->Request()->getRequestUri());
                $arguments->getSubject()->executeParent(
                    $arguments->getMethod(),
                    $arguments->getArgs()
                );
            }
        }

    }

}