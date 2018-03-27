<?php
/**
 * Class Shopware_Plugins_Frontend_Boxalino_Helper_P13NHelper
 */
class Shopware_Plugins_Frontend_Boxalino_Helper_P13NHelper {

    /**
     * @var null
     */
    private static $instance = null;

    /**
     * @var null
     */
    private static $bxClient = null;

    /**
     * @var Enlight_Controller_Request_Request
     */
    private $request;

    /**
     * @var Shopware_Components_Config
     */
    private $config;


    /**
     * @var
     */
    private $currentSearchChoice;

    /**
     * @var
     */
    private $navigation;

    /**
     * @var array
     */
    private static $choiceContexts = array();

    /**
     * @var Shopware_Plugins_Frontend_Boxalino_Benchmark
     */
    private $benchmark;

    private $prefixContextParameter = null;

    /**
     * Shopware_Plugins_Frontend_Boxalino_Helper_P13NHelper constructor.
     */
    private function __construct() {
        $this->benchmark = Shopware_Plugins_Frontend_Boxalino_Benchmark::instance();
        $this->config = Shopware()->Config();
        $libPath = __DIR__ . '/../lib';
        require_once($libPath . '/BxClient.php');
        \com\boxalino\bxclient\v1\BxClient::LOAD_CLASSES($libPath);
        $this->initializeBXClient();
    }

    /**
     * initializeBXClient
     */
    protected function initializeBXClient() {

        $account = $this->config->get('boxalino_account');
        $password = $this->config->get('boxalino_password');
        $isDev = $this->config->get('boxalino_dev');
        $host = $this->config->get('boxalino_host');
        $p13n_username = $this->config->get('boxalino_p13_user_name');
        $p13n_password = $this->config->get('boxalino_p13_user_password');
        $domain = $this->config->get('boxalino_domain');
        self::$bxClient = new \com\boxalino\bxclient\v1\BxClient($account, $password, $domain, $isDev, $host, null, null, null, $p13n_username, $p13n_password);
        self::$bxClient->setTimeout(5000);
        if(isset($_REQUEST['dev_bx_test_mode']) && $_REQUEST['dev_bx_test_mode'] == 'true') {
            self::$bxClient->setTestMode(true);
        }
    }

    /**
     * @return null|Shopware_Plugins_Frontend_Boxalino_Helper_P13NHelper
     */
    public static function instance() {
        if (self::$instance == null)
            self::$instance = new Shopware_Plugins_Frontend_Boxalino_Helper_P13NHelper();
        return self::$instance;
    }

    /**
     * @param $queryText
     * @return mixed|string
     */
    public function getSearchChoice($queryText) {

        if($queryText == null) {
            $choice = $this->config->get('boxalino_navigation_widget_name');
            if($choice == null) {
                $choice = "navigation";
            }
            $this->currentSearchChoice = $choice;
            $this->navigation = true;
            return $choice;
        }

        $choice = $this->config->get('boxalino_search_widget_name');
        if($choice == null) {
            $choice = "search";
        }
        $this->currentSearchChoice = $choice;
        return $choice;
    }

    /**
     * @param $filters
     * @return array
     */
    protected function extractFilter($filters) {
        $bxFilters = array();
        foreach ($filters as $field => $filter) {
            $bxFilters[] = new \com\boxalino\bxclient\v1\BxFilter($field, $filter);
        }
        return $bxFilters;
    }

    public function getPrefixContextParameter(){
        return $this->prefixContextParameter;
    }

    public function setPrefixContextParameter($prefix) {
        $this->prefixContextParameter = $prefix;
    }

    public function getHttpRefererParameters() {
      $address = $_SERVER['HTTP_REFERER'];
      $params = array();
      foreach($_REQUEST as $k => $v) {
          $params[$k] = $v;
      }
      $rowParams = explode('&', substr ($address,strpos($address, '?')+1, strlen($address)));
      foreach ($rowParams as $index => $param){
          $keyValue = explode("=", $param);
          $keyValue = str_replace('[]', '', $keyValue);
          if(!isset($params[$keyValue[0]])) {
            $params[$keyValue[0]] = array();
          }
          $params[$keyValue[0]][] = $keyValue[1];
      }
      return $params;
    }

    protected function checkPrefixContextParameter($prefix){
        $params = $this->getHttpRefererParameters();
        foreach ($params as $key => $value) {
            if(strpos($key, $prefix) === 0) {
                self::$bxClient->addRequestContextParameter($key, $value);
            }
            //if($keyValue[0] == 'dev_bx_disp') {
              self::$bxClient->addToRequestMap($key, $value);
            //}
        }
    }

    protected function checkFilterParameter() {
        $params = $this->getHttpRefererParameters();
        $filters = [];
        foreach ($params as $key => $values) {
            if(!is_array($values)) {
              $values = [$values];
            }
            foreach($values as $k => $v) {
              $values[$k] = rawurldecode($v);
            }
            if(strpos($key, 'bx_') === 0) {
                $filters[$key] = new \com\boxalino\bxclient\v1\BxFilter(substr($key, 3), $values);
            }
        }
        return $filters;
    }

    public function addFinder($hitCount = 1, $choiceId = 'productfinder', $filter=[], $type = 'product', $finder_type = 1){
        $this->flushResponses();
        $this->resetRequests();
        $lang = $this->getShortLocale();
        $this->currentSearchChoice = $choiceId;
        $bxRequest = new \com\boxalino\bxclient\v1\BxParametrizedRequest($lang, $choiceId, $hitCount);
        $this->setPrefixContextParameter($bxRequest->getRequestWeightedParametersPrefix());
        $this->checkPrefixContextParameter($this->getPrefixContextParameter());
        $bxRequest->setGroupBy($this->getEntityIdFieldName($type));
        $bxRequest->setHitsGroupsAsHits(true);
        $bxRequest->setReturnFields($this->getReturnFields());

        $filters = [];
        if($finder_type == 1) {
            foreach ($filter as $field => $value){
                $filters[] = new \com\boxalino\bxclient\v1\BxFilter($field, array($value));
            }
        }
        $filters = array_merge($filters, $this->checkFilterParameter());
        $filters = array_merge($filters, $this->getSystemFilters($type, 'finder'));
        $bxRequest->setFilters($filters);
        $this->addBxRequest($bxRequest, $type);
    }

    protected function addBxRequest($bxRequest, $type = 'product') {
        self::$bxClient->addRequest($bxRequest);
        self::$choiceContexts[$bxRequest->getChoiceId()][] = $type;
    }

    /**
     * @param string $queryText
     * @param int $pageOffset
     * @param int $hitCount
     * @param string $type
     * @param null $sort
     * @param array $options
     * @param array $filters
     */
    public function addSearch($queryText = "", $pageOffset = 0, $hitCount = 10, $type = "product", $sort = null, $options = array(), $filters = array(), $stream = false, $overrideChoice = null) {

        $choiceId = is_null($overrideChoice) ? $this->getSearchChoice($queryText) : $overrideChoice;
        $returnFields = $this->getReturnFields($type);
        $lang = $this->getShortLocale();
        $bxRequest = new \com\boxalino\bxclient\v1\BxSearchRequest($lang, $queryText, $hitCount, $choiceId);
        $requestFilters = $this->getSystemFilters($type, $queryText, false, $stream);
        $requestFilters = array_merge($requestFilters, $this->extractFilter($filters));
        $bxRequest->setFilters($requestFilters);
        $bxRequest->setGroupBy($this->getEntityIdFieldName($type));
        $bxRequest->setReturnFields($returnFields);
        $bxRequest->setOffset($pageOffset);
        $facets = $this->prepareFacets($options);
        $bxRequest->setFacets($facets);

        if ($sort != null && is_array($sort)) {
            foreach ($sort as $s) {
                $bxRequest->addSortField($s['field'], $s['reverse']);
            }
        }

        self::$bxClient->addRequest($bxRequest);
        self::$choiceContexts[$choiceId][] = $type;

    }

    private function getVoucherData($voucher_id) {

        $data = array();
        $db = Shopware()->Db();
        $sql = $db->select()->from('s_emarketing_vouchers')
            ->where('id = ?', $voucher_id);
        $stmt = $db->query($sql);
        if($stmt->rowCount()) {
            $data = $stmt->fetch();
        }
        return $data;
    }

    public function addVoucher($choiceId) {
        $data = [];
        $lang = $this->getShortLocale();
        $bxRequest = new \com\boxalino\bxclient\v1\BxRequest($lang, $choiceId, 1);
        $bxRequest->setReturnFields(['products_voucher_id']);
        self::$bxClient->addRequest($bxRequest);
        $customerID = $this->getCustomerID();
        self::$bxClient->addRequestContextParameter('_system_customerid', $customerID);
        $bxResponse = $this->getResponse();

        $voucherIdFromHits = $bxResponse->getHitFieldValues(['products_voucher_id'], 'voucher');

        foreach ($voucherIdFromHits as $voucherIdFromHit) {

          $voucherId = $voucherIdFromHit['products_voucher_id'][0];

          if(strpos($voucherId, 'voucher_') === 0) {
              $voucherId = str_replace('voucher_', '', $voucherId);
          }
        }
        $data = array_merge($data, $this->getVoucherData($voucherId));
        return $data;
    }

    public function addBanner($config){
      $this->flushResponses();
      $this->resetRequests();
      $type = 'bxi_content';
      $returnFields = $this->getReturnFields($type);
      $lang = $this->getShortLocale();
      $choiceId = $config['choiceId_banner'];
      $max = $config['max_banner'];
      $min = $config['min_banner'];
      $bxRequest = new \com\boxalino\bxclient\v1\BxParametrizedRequest($lang, $choiceId, $max, $min);
      $this->setPrefixContextParameter($bxRequest->getRequestWeightedParametersPrefix());
      $this->checkPrefixContextParameter($this->getPrefixContextParameter());
      $bxRequest->setGroupBy($this->getEntityIdFieldName($type));
      $bxRequest->setReturnFields($returnFields);
      $bxRequest->setOffset(0);

      self::$bxClient->addRequest($bxRequest);
      self::$choiceContexts[$choiceId][] = $type;

      $bxResponse = $this->getResponse();
      $hitCount = count($bxResponse->getHitIds($choiceId));

      $bannerData = [

        'id' => $bxResponse->getExtraInfo('banner_jssor_id', '', $choiceId),
        'style' => $bxResponse->getExtraInfo('banner_jssor_style', '', $choiceId),
        'slides_style' => $bxResponse->getExtraInfo('banner_jssor_slides_style', '', $choiceId),
        'max_width' => $bxResponse->getExtraInfo('banner_jssor_max_width', '', $choiceId),
        'css' => $bxResponse->getExtraInfo('banner_jssor_css', '', $choiceId),
        'loading_screen' => $bxResponse->getExtraInfo('banner_jssor_loading_screen', '', $choiceId),
        'bullet_navigator' => $bxResponse->getExtraInfo('banner_jssor_bullet_navigator', '', $choiceId),
        'arrow_navigator' => $bxResponse->getExtraInfo('banner_jssor_arrow_navigator', '', $choiceId),
        'function' => $bxResponse->getExtraInfo('banner_jssor_function', '', $choiceId),
        'options' => $bxResponse->getExtraInfo('banner_jssor_options', '', $choiceId),
        'break' => $this->getBannerJssorSlideGenericJS('products_bxi_bxi_jssor_break', '', $choiceId),
        'transition' => $this->getBannerJssorSlideGenericJS('products_bxi_bxi_jssor_transition', '', $choiceId),
        'control' => $this->getBannerJssorSlideGenericJS('products_bxi_bxi_jssor_control', '', $choiceId),
        'slides' => $this->getBannerSlides($choiceId),
        'hitCount' => $hitCount

      ];

      return $bannerData;
    }

    public function getBannerSlides($choiceId) {

        $slides = $this->getResponse()->getHitFieldValues(array('products_bxi_bxi_jssor_slide', 'products_bxi_bxi_name'), $choiceId);
        $counters = array();
        foreach($slides as $id => $vals) {
            $slides[$id]['div'] = $this->getBannerSlide($id, $vals, $counters);
        }
        return $slides;
    }

    public function getBannerSlide($id, $vals, &$counters) {
        $language = $this->getShortLocale();
        if(isset($vals['products_bxi_bxi_jssor_slide']) && sizeof($vals['products_bxi_bxi_jssor_slide']) > 0) {
            $json = $vals['products_bxi_bxi_jssor_slide'][0];

            $slide = json_decode($json, true);
            if(isset($slide[$language])) {
                $json = $slide[$language];

                for($i=1; $i<10; $i++) {

                    if(!isset($counters[$i])) {
                        $counters[$i] = 0;
                    }

                    $pieces = explode('BX_COUNTER'.$i, $json);
                    foreach($pieces as $j => $piece) {
                        if($j >= sizeof($pieces)-1) {
                            continue;
                        }
                        $pieces[$j] .= $counters[$i]++;
                    }
                    $json = implode('', $pieces);

                }
                return $json;
            }
        }
        return '';
    }

    public function getBannerJssorSlideGenericJS($key) {
        $language = $this->getShortLocale();

        $slides = $this->getHitFieldValues(array($key), 'banner');

        $jsArray = array();
        foreach($slides as $id => $vals) {
            if(isset($vals[$key]) && sizeof($vals[$key]) > 0) {

                $jsons = json_decode($vals[$key][0], true);
                if(isset($jsons[$language])) {
                    $json = $jsons[$language];

                    //fix some special case an extra '}' appears wrongly at the end
                    $minus = 2;
                    if(substr($json, strlen($json)-1, 1) == '}') {
                        $minus = 3;
                    }

                    //removing the extra [] around
                    $json = substr($json, 1, strlen($json)-$minus);

                    $jsArray[] = $json;
                }
            }
        }

        return '[' . implode(',', $jsArray) . ']';
    }

    public function getCustomerID(){
        $address = $_SERVER['HTTP_REFERER'];
        $params = explode('&', substr ($address,strpos($address, '?')+1, strlen($address)));

        foreach ($params as $param){
            if(strpos($param, 'bx_customer_id') === 0) {
                $customerID = explode("=", $param)[1];
            }
        }
        return is_null($customerID) ? $_SESSION['Shopware']['sUserId'] : $customerID;
    }

    public function isBxLogSet() {
        $address = $_SERVER['HTTP_REFERER'];
        $params = explode('&', substr ($address,strpos($address, '?')+1, strlen($address)));

        foreach ($params as $param){
            if(strpos($param, 'dev_bx_log') === 0) {
               return true;
            }
        }
        return false;
    }

    public function addPortfolio($data){
        if($_REQUEST['dev_bx_debug'] == 'true'){
            $t1 = microtime(true);
        }
        if($_REQUEST['dev_bx_debug'] == 'true'){
            $t2 = microtime(true);
        }
        $test['overall'] = microtime(true);
        $lang = $this->getShortLocale();
        $bxRequest = new \com\boxalino\bxclient\v1\BxSearchRequest($lang, "", 0, $data['choiceId_portfolio']);
        self::$bxClient->addRequest($bxRequest);
        $bxRequest = null;


        if($_REQUEST['dev_bx_debug'] == 'true'){
            $t2 = (microtime(true) - $t2) * 1000 ;
            $this->addNotification("Pre portfolio widget request took: " . $t2 . "ms.");
        }
        if($_REQUEST['dev_bx_debug'] == 'true'){
            $t3 = microtime(true);
        }
        $response = $this->getResponse();
        if($_REQUEST['dev_bx_debug'] == 'true'){
            $t3 = (microtime(true) - $t3) * 1000 ;
            $this->addNotification("Portfolio widget request took " . $t3 . "ms.");
        }
        if($_REQUEST['dev_bx_debug'] == 'true'){
            $t4 = microtime(true);
        }
        $groups = json_decode($response->getExtraInfo('portfolio_json', null, $data['choiceId_portfolio']), true);
        foreach ($groups as $i => $group) {
            $groups[$i]['account_id'] = $this->getCustomerID();
        }
        return $groups;
        $this->flushResponses();
        $this->resetRequests();
        $rebuyChoice = $data['choiceId_re-buy'];
        $rebuyMax = (int)$data['article_slider_max_number_rebuy'];
        $reorientChoice = $data['choiceId_re-orient'];
        $reorientMax = (int)$data['article_slider_max_number_reorient'];
        $newbuyChoice = 'newbuy';
        $requestFilters = $this->getSystemFilters("product", "", true);
        $returnFields = $this->getReturnFields();
        $choices = [$rebuyChoice, $newbuyChoice, $reorientChoice];
        $newbuyRecommendations = [];
        $rebuyRecommendations = [];
        foreach ($groups as $index => $group) {
            if($_REQUEST['dev_bx_debug'] == 'true'){
                $t6 = microtime(true);
            }
            $groupRequest = array();
            foreach ($choices as $choice) {
                $max = $choice == 'reorient' ? $reorientMax : $rebuyMax;
                $choiceFilter = array_merge($requestFilters, [new \com\boxalino\bxclient\v1\BxFilter("category_id", $group['context_parameter'])]);
                $groupRequest[] = $this->createParametrizedPortfolioRequest($returnFields, $choiceFilter, $lang, $choice, $max);
            }
            self::$bxClient->addBundleRequest($groupRequest);
            $request = null;
            $customerID = $this->getCustomerID();
            if (isset($_REQUEST['bx_customer_id'])) {
                $customerID = $_REQUEST['bx_customer_id'];
            }
            self::$bxClient->addRequestContextParameter('_system_customerid', $customerID);
            if($_REQUEST['dev_bx_debug'] == 'true'){
                $t6 = (microtime(true) - $t6) * 1000 ;
                $this->addNotification("Pre bundle request for group {$group['name']} took: " . $t6 . "ms.");
            }
        }
        if($_REQUEST['dev_bx_debug'] == 'true'){
            $t4 = (microtime(true) - $t4) * 1000 ;
            $this->addNotification("Total time of pre bundle request took: " . $t4 . "ms.");
        }
        if($_REQUEST['dev_bx_debug'] == 'true'){
            $t5 = microtime(true);
        }
        $response = $this->getResponse(true);

        if($_REQUEST['dev_bx_debug'] == 'true'){
            $t5 = (microtime(true) - $t5) * 1000 ;
            $this->addNotification("Bundle request took: " . $t5 . "ms.");
        }

        foreach ($groups as $index => $group) {
            if($_REQUEST['dev_bx_debug'] == 'true'){
                $t7 = microtime(true);
            }
            $groupTime['total_after'] = microtime(true);
            $groupData = $group;
            $values = $this->convertToFieldArray(
                $response->getHitFieldValues(['products_ordernumber'], $reorientChoice, true, $index),
                'products_ordernumber');
            $groupData['reorient']['sArticles'] = $this->getLocalArticles($values);


            $purchaseDates = $response->getHitFieldValues(['purchase_date'], $rebuyChoice, true, $index);

            $firstDate = reset(reset($purchaseDates)['purchase_date']);

            if($response->getTotalHitCount($rebuyChoice, true, $index) > 0 &&
                getdate(strtotime($firstDate))['year'] != 1970){

                $values = $this->convertToFieldArray(
                    $response->getHitFieldValues(['products_ordernumber'], $rebuyChoice, true, $index),
                    'products_ordernumber');
                $articles = $this->getLocalArticles($values);
                $addArticles = array();
                foreach ($articles as $i => $article) {
                    $add = array_shift($purchaseDates);
                    $date = reset($add['purchase_date']);
                    if(getdate(strtotime($date))['year'] != 1970) {
                        $article['bxTransactionDate'] = reset($add['purchase_date']);
                        $addArticles[] = $article;
                    }
                }
                $articles = null;
                $groupData['rebuy']['sArticles'] = $addArticles;
                $rebuyRecommendations[] = $groupData;
            } else {
                $values = $this->convertToFieldArray(
                    $response->getHitFieldValues(['products_ordernumber'], $newbuyChoice, true, $index),
                    'products_ordernumber');
                $groupData['rebuy']['sArticles'] = $this->getLocalArticles($values);
                $groupData['rebuy']['title'] = $groupData['rebuy']['alternative title'];
                $newbuyRecommendations[] = $groupData;
            }
            if($_REQUEST['dev_bx_debug'] == 'true'){
                $t7 = (microtime(true) - $t7) * 1000 ;
                $this->addNotification("Post bundle request for group {$group['name']} took: " . $t7 . "ms.");
            }
        }
        $this->flushResponses();
        $this->resetRequests();
        usort($rebuyRecommendations, function($a, $b){
            return sizeof($a['rebuy']['sArticles']) < sizeof($b['rebuy']['sArticles']);
        });

        $portfolioData = array_merge($rebuyRecommendations, $newbuyRecommendations);
        if($_REQUEST['dev_bx_debug'] == 'true'){
            $t1 = (microtime(true) - $t1) * 1000 ;
            $this->addNotification("Total time for all requests: " . ($t3 + $t5) . "ms.");
            $this->addNotification("Total time of addPortfolio: " . $t1 . "ms.");
        }
        if($_REQUEST['portfolio_data'] == 'true'){
            echo "<pre>";
            var_dump($portfolioData);exit;
        }
        return $portfolioData;
    }


    protected function createParametrizedPortfolioRequest($returnFields, $requestFilters, $lang, $choice, $max){

        $request = new \com\boxalino\bxclient\v1\BxParametrizedRequest($lang, $choice, $max, 10, ['products_ordernumber']);
        $request->setReturnFields($returnFields);
        $request->setGroupBy('products_group_id');
        $request->setFilters($requestFilters);
        $request->setHitsGroupsAsHits(true);
        return $request;
    }

    /**
     * @param $options
     * @return \com\boxalino\bxclient\v1\BxFacets
     */
    protected function prepareFacets($options) {
        $bxFacets = new \com\boxalino\bxclient\v1\BxFacets();

        foreach ($options as $fieldName => $option) {
            if ($fieldName == 'category') {
                $bxFacets->addCategoryFacet($option['value'], 2, -1, false, $option['label']);
                continue;
            }
            if ($fieldName == 'discountedPrice') {
                $bxFacets->addPriceRangeFacet($option['value'], 2, $option['label']);
                continue;
            }
            $value = isset($option['value']) && count($option['value']) ? $option['value'] : null;
            $type = isset($option['type']) ? $option['type'] : 'list';
            $bounds = isset($option['bounds']) ? $option['bounds'] : false;
            $label = isset($option['label']) ? $option['label'] : $fieldName;
            $order = isset($option['order']) ? $option['order'] : 2;
            $bxFacets->addFacet($fieldName, $value, $type, $label, $order, $bounds);
        }
        return $bxFacets;
    }

    /**
     * @param string $type
     * @return null
     */
    public function getFacets($type = "product") {

        $count = array_search($type, self::$choiceContexts[$this->currentSearchChoice]);
        $facets = $this->getResponse()->getFacets($this->currentSearchChoice, true, $count);
        if (empty($facets)) {
            return null;
        }
        return $facets;
    }

    /**
     * @param string $type
     * @return array
     */
    protected function getReturnFields($type = "product") {
        $returnFields = array($this->getEntityIdFieldName($type));
        if ($type == 'product') {
            $returnFields = array_merge($returnFields, ['id', 'score', 'products_bx_type', 'title', 'products_ordernumber', 'discountedPrice', 'products_bx_grouped_price', 'products_active', 'products_bx_grouped_active']);
        }

        elseif ($type == 'bxi_content') {
          $returnFields = array_merge($returnFields, ['title', 'products_bxi_bxi_jssor_slide', 'products_bxi_bxi_jssor_transition', 'products_bxi_bxi_name', 'products_bxi_bxi_jssor_control', 'products_bxi_bxi_jssor_break']);
        }

        else {
            $returnFields = array_merge($returnFields, ['id', 'score', 'products_bx_type', 'products_blog_title', 'products_blog_id', 'products_blog_category_id']);
        }
        $additionalFields = explode(',', $this->config->get('boxalino_returned_fields'));
        if (isset($additionalFields) && $additionalFields[0] != '') {
            $returnFields = array_merge($returnFields, $additionalFields);
        }
        return $returnFields;
    }

    /**
     * @param $notification
     * @param string $type
     */
    public function addNotification($notification, $type='debug') {
        self::$bxClient->addNotification($type, $notification);
    }

    /**
     * @param string $type
     * @return mixed|string
     */
    public function getEntityIdFieldName($type = 'product') {

        $entityIdFieldName = $this->config->get('boxalino_entity_id');
        if (!isset($entityIdFieldName) || $entityIdFieldName === '') {
            if ($type == 'product'){
                $entityIdFieldName = 'products_group_id';
            } else if($type == 'blog'){
                $entityIdFieldName = 'products_blog_id';
            } else {
                $entityIdFieldName = 'id';
            }
        }
        return $entityIdFieldName;
    }

    public function getShopId(){
        return $shop_id = $this->config->get('boxalino_overwrite_shop') != '' ? (int) $this->config->get('boxalino_overwrite_shop') : Shopware()->Shop()->getId();
    }

    /**
     * @param string $type
     * @param string $query
     * @param bool $recommendation
     * @return array
     */
    private function getSystemFilters($type = 'product', $query = '', $recommendation = false, $stream = false){
        $filters = array();

        $filters[] = new \com\boxalino\bxclient\v1\BxFilter('products_bx_type', array($type));
        if ($type == 'blog') {
            $filters[] = new \com\boxalino\bxclient\v1\BxFilter('products_blog_active', array('1'));
            $filters[] = new \com\boxalino\bxclient\v1\BxFilter('products_blog_shop_id', array($this->getShopId()));
        }
        if ($type == 'product') {
            $filters[] = new \com\boxalino\bxclient\v1\BxFilter('products_active', array('1'));
            $filters[] = new \com\boxalino\bxclient\v1\BxFilter('products_bx_parent_active', array('1'));
            $shop_id = $this->getShopId();
            $filters[] = new \com\boxalino\bxclient\v1\BxFilter('products_shop_id', array($shop_id));
            if ($query == '' && !$recommendation && !$stream) {
                if(Shopware()->Shop()->getCategory()->getId() != $this->Request()->getParam('sCategory')) {
                    $filters[] = new \com\boxalino\bxclient\v1\BxFilter('category_id', array($this->Request()->getParam('sCategory')));
                }
            }
        }

        if ($recommendation === true) {
            $filters[] = new \com\boxalino\bxclient\v1\BxFilter('products_bx_purchasable', array('1'));
        }
        return $filters;
    }

    /**
     * @param $queryText
     * @param bool $with_blog
     * @param bool $no_result
     * @return array
     */
    public function autocomplete($queryText, $with_blog = false, $no_result = false) {
        if(!$this->config->get('boxalino_noresults_recommendation_enabled') && $no_result) {
            return [];
        }
        if($_REQUEST['dev_bx_debug'] == 'true'){
            $t1 = microtime(true);
        }
        $search_choice = $no_result === true ? "noresults" : $this->getSearchChoice($queryText);
        $auto_complete_choice = $this->config->get('boxalino_autocomplete_widget_name');
        $textual_Limit = $this->config->get('boxalino_textual_suggestion_limit', 3);
        $product_limit = $this->config->get('boxalino_product_suggestion_limit', 3);
        $blog_limit = $this->config->get('boxalino_blog_suggestion_limit', 3);

        $searches = ($with_blog === false) ? array('product') : array('product','blog');
        $bxRequests = array();
        foreach ($searches as $i => $search){
            if($search == 'blog') {
               $textual_Limit = $blog_limit;
               $product_limit = $blog_limit;
            }
            $bxRequest = new \com\boxalino\bxclient\v1\BxAutocompleteRequest($this->getShortLocale(),
                $queryText, $textual_Limit, $product_limit, $auto_complete_choice,
                $search_choice
            );

            $searchRequest = $bxRequest->getBxSearchRequest();
            $return_fields = $this->getReturnFields($search);
            $searchRequest->setReturnFields($return_fields);
            $searchRequest->setGroupBy($this->getEntityIdFieldName($search));
            $searchRequest->setFilters($this->getSystemFilters($search));
            $bxRequests[] = $bxRequest;
        }
        if($_REQUEST['dev_bx_debug'] == 'true'){
            $this->addNotification("Ajax Search autocomplete pre request took: " . (microtime(true) - $t1) * 1000 . "ms");
        }
        if($_REQUEST['dev_bx_debug'] == 'true'){
            $t2 = microtime(true);
        }
        self::$bxClient->setAutocompleteRequests($bxRequests);
        self::$bxClient->autocomplete();
        if($_REQUEST['dev_bx_debug'] == 'true'){
            $this->addNotification("Ajax Search autocomplete request took: " . (microtime(true) - $t2) * 1000 . "ms");
        }
        $template_properties = array();
        $bxAutocompleteResponses = self::$bxClient->getAutocompleteResponses();

        foreach ($searches as $index => $search) {
            $bxAutocompleteResponse = $bxAutocompleteResponses[$index];
            if($bxAutocompleteResponse->getResponse()->prefixSearchResult->totalHitCount == 0 && $index == 0 && sizeof($bxAutocompleteResponse->getTextualSuggestions()) == 0 && strpos($queryText, '*') === false) {
                $template_properties = $this->autocomplete($queryText . "*", $with_blog, false);
            } else if ($bxAutocompleteResponse->getResponse()->prefixSearchResult->totalHitCount == 0 && $index == 0 && sizeof($bxAutocompleteResponse->getTextualSuggestions()) == 0) {
                if($no_result) {
                    break;
                }
                self::$bxClient->flushResponses();
                $template_properties = $this->autocomplete("", false, true);
            } else {
                if($_REQUEST['dev_bx_debug'] == 'true'){
                    $t3 = microtime(true);
                }
                $template_properties = array_merge($template_properties, $this->createAjaxData($bxAutocompleteResponse, $queryText, $search, $no_result));
                if($_REQUEST['dev_bx_debug'] == 'true'){
                    $this->addNotification("Ajax Search autocomplete createAjaxData took: " . (microtime(true) - $t3) * 1000 . "ms");
                }
            }
        }
        if($_REQUEST['dev_bx_debug'] == 'true'){
            $this->addNotification("Ajax Search autocomplete took in total: " . (microtime(true) - $t1) * 1000 . "ms");
        }
        return $template_properties;
    }

    /**
     * @param $ids
     * @return array
     */
    public function getBlogs($ids) {

        if(empty($ids)) {
            return $ids;
        }
        $repository = Shopware()->Models()->getRepository('Shopware\Models\Blog\Blog');
        $builder = $repository->getListQueryBuilder(array(), array());
        $query = $builder
            ->andWhere($builder->expr()->in('blog.id', $ids))
            ->getQuery();
        $blogs = $query->getArrayResult();
        $orderedBlogs = array();
        foreach ($ids as $id) {
            foreach ($blogs as $blog) {
                if($blog['id'] == $id) {
                    $orderedBlogs[] = $blog;
                    break;
                }
            }
        }
        return $blogs;
    }

    protected function getHitIdsFromAutocompleteResponse($response, $type, $field = 'id', $choice = null) {
        $ids = [];
        if($type == 'product') {
            $choice = is_null($choice) ? $this->currentSearchChoice : $choice;
            $ids = $this->convertToFieldArray(
                $response->getHitFieldValues([$field], $choice, true, 0),
                $field);
        } else {
            $ids = $response->getHitIds($this->currentSearchChoice, true, 0, 10);
        }
        return $ids;
    }

    /**
     * @param $autocompleteResponse
     * @param $queryText
     * @param string $type
     * @param bool $no_result
     * @return array
     */
    protected function createAjaxData($autocompleteResponse, $queryText, $type = 'product', $no_result = false) {

        if ($no_result === true) {
            $ids = $this->getHitIdsFromAutocompleteResponse($autocompleteResponse->getBxSearchResponse(), $type, 'products_ordernumber', 'noresults');
            $sResults = $this->getLocalArticles($ids);
            $router = Shopware()->Front()->Router();
            foreach ($sResults as $key => $result) {
                $sResults[$key]['name'] = $result['articleName'];
                $sResults[$key]['link'] = $router->assemble(array(
                    'controller' => 'detail',
                    'sArticle' => $result['articleID'],
                    'title' => $result['articleName']
                ));
            }
            return array(
                'bxNoResult' => true,
                'sSearchResults' => array(
                    'sResults' => $sResults
                )
            );
        }
        if($_REQUEST['dev_bx_debug'] == 'true'){
            $t1 = microtime(true);
        }
        $choice = $this->getSearchChoice($queryText);
        $suggestions = array();
        $hitIds = array();
        foreach ($autocompleteResponse->getTextualSuggestions() as $i => $suggestion) {
            $hits = $autocompleteResponse->getTextualSuggestionTotalHitCount($suggestion);
            $suggestions[$suggestion] = array('text' => $suggestion, 'html' => $autocompleteResponse->getTextualSuggestionHighlighted($suggestion), 'hits' => $hits);
            if ($i == 0) {
                if (count($autocompleteResponse->getBxSearchResponse()->getHitIds($choice, true, 0, 10)) == 0) {
                    $hitIds = $this->getHitIdsFromAutocompleteResponse($autocompleteResponse->getBxSearchResponse($suggestion), $type, 'products_ordernumber');
                }
            }
            if ($suggestion == $queryText) {
                $hitIds = $this->getHitIdsFromAutocompleteResponse($autocompleteResponse->getBxSearchResponse($suggestion), $type, 'products_ordernumber');
            }
        }
        if (empty($hitIds)) {
            $hitIds = $this->getHitIdsFromAutocompleteResponse($autocompleteResponse->getBxSearchResponse(), $type, 'products_ordernumber');
        }
        if($_REQUEST['dev_bx_debug'] == 'true'){
            $this->addNotification("Ajax Search autocomplete createAjaxData suggestions took: " . (microtime(true) - $t1) * 1000 . "ms");
        }
        if ($type == 'product') {
            if($_REQUEST['dev_bx_debug'] == 'true'){
                $t2 = microtime(true);
            }
            $sResults = $this->getLocalArticles($hitIds);
            if($_REQUEST['dev_bx_debug'] == 'true'){
                $this->addNotification("Ajax Search autocomplete createAjaxData getLocalArticles took: " . (microtime(true) - $t2) * 1000 . "ms." . json_encode($hit));
            }
            $router = Shopware()->Front()->Router();
            foreach ($sResults as $key => $result) {
                $sResults[$key]['name'] = $result['articleName'];
                $sResults[$key]['link'] = $router->assemble(array(
                    'controller' => 'detail',
                    'sArticle' => $result['articleID'],
                    'title' => $result['articleName']
                ));
            }
            $productData = array(
                'sSearchRequest' => array('sSearch' => $queryText),
                'sSearchResults' => array(
                    'sResults' => $sResults,
                    'sArticlesCount' => $autocompleteResponse->getBxSearchResponse()->getTotalHitCount($this->currentSearchChoice),
                    'sSuggestions' => $suggestions
                )
            );
            if($_REQUEST['dev_bx_debug'] == 'true'){
                $this->addNotification("Ajax Search autocomplete createAjaxData product took: " . (microtime(true) - $t2) * 1000 . "ms");
            }
            return $productData;
        } else {
            if($_REQUEST['dev_bx_debug'] == 'true'){
               $t3 = microtime(true);
            }
            if(empty($hitIds)) {
                return array();
            }
            $blog_ids = array();
            foreach ($hitIds as $index => $id){
                $blog_ids[$index] = str_replace('blog_', '', $id);
            }
            $router =  Shopware()->Router();
            if($_REQUEST['dev_bx_debug'] == 'true'){
                $t4 = microtime(true);
            }
            $blogEntries = $this->getBlogs($blog_ids);
            if($_REQUEST['dev_bx_debug'] == 'true'){
                $this->addNotification("Ajax Search autocomplete createAjaxData blog getBlogs took: " . (microtime(true) - $t4) * 1000 . "ms");
                $t4 = microtime(true);
            }
            $blogs = array_map(function($blog) use ($router) {
                return array(
                    'id' => $blog['id'],
                    'title' => $blog['title'],
                    'link' => $router->assemble(array(
                        'sViewport' => 'blog', 'action' => 'detail', 'blogArticle' => $blog['id']
                    ))
                );
            }, $blogEntries);
            if($_REQUEST['dev_bx_debug'] == 'true'){
                $this->addNotification("Ajax Search autocomplete createAjaxData blog array_map took: " . (microtime(true) - $t4) * 1000 . "ms");
            }
            $blogData = array(
                'bxBlogSuggestions' => $blogs,
                'bxBlogSuggestionTotal' => $autocompleteResponse->getBxSearchResponse()->getTotalHitCount()
            );
            if($_REQUEST['dev_bx_debug'] == 'true'){
                $this->addNotification("Ajax Search autocomplete createAjaxData blog took: " . (microtime(true) - $t3) * 1000 . "ms");
            }
            return $blogData;
        }
    }

    /**
     * @param $index
     * @return mixed
     */
    public function getRequest($index){
        return self::$bxClient->getRequest($index);
    }

    protected $logResponse = true;

    /**
     * @return mixed
     */
    public function getResponse($chooseAll=false){
        if($this->logResponse && $_REQUEST['dev_bx_debug']) {
            $start = microtime(true);
        }
        $response = self::$bxClient->getResponse($chooseAll);
        if($this->logResponse && $_REQUEST['dev_bx_debug']) {
            $time = (microtime(true) - $start) * 1000 ;
            $this->addNotification("Response took: " . $time . "ms.");
            $this->logResponse = false;
        }
        return $response;
    }

    /**
     * @param $values
     * @param $field
     * @return array
     */
    public function convertToFieldArray($values, $field) {
        $returnValues = [];
        foreach ($values as $value) {
            $returnValues[] = $value[$field][0];
        }
        return $returnValues;
    }

    /**
     * @param $choiceId
     * @param int $max
     * @param int $min
     * @param int $offset
     * @param array $context
     * @param string $type
     * @param bool $execute
     * @param array $excludes
     * @param bool $isBlog
     * @param array $requestContextParams
     * @param bool $isPortfolio
     * @return array|mixed
     */
    public function getRecommendation($choiceId, $max = 5, $min = 5, $offset = 0, $context = array(), $type = '',
                                      $execute = true, $excludes = array(), $isBlog = false, $requestContextParams = array(), $isPortfolio = false) {

        if(!$execute){
            if ($max >= 0) {
                $articleType = $isBlog ? 'blog' : 'product';
                $bxRequest = new \com\boxalino\bxclient\v1\BxRecommendationRequest($this->getShortLocale(), $choiceId, $max, $min);
                $bxRequest->setGroupBy($this->getEntityIdFieldName($articleType));
                $excludeFilter = !empty($excludes) ? [new \com\boxalino\bxclient\v1\BxFilter('products_group_id', $excludes, true)] : [];
                $systemFilter = $isBlog ? $this->getSystemFilters($articleType) : $this->getSystemFilters($articleType, '', true);
                $filters = array_merge($systemFilter, $excludeFilter);
                $bxRequest->setReturnFields($this->getReturnFields($articleType));
                $bxRequest->setOffset($offset);
                switch($type) {
                    case 'basket':
                        if(is_array($context)) {
                            $basketProducts = array();
                            foreach ($context as $product) {
                                $basketProducts[] = $product;
                            }
                            $bxRequest->setBasketProductWithPrices($this->getEntityIdFieldName(), $basketProducts);
                        }
                        break;
                    case 'product':
                        if(!is_array($context)) {
                            $bxRequest->setProductContext('products_group_id', $context);
                        }
                        break;
                    case 'category':
                        if(!is_null($context)) {
                            $filterField = "category_id";
                            $filterValues = is_array($context) ? $context : array($context);
                            $filters[] = new \com\boxalino\bxclient\v1\BxFilter($filterField, $filterValues);
                        }
                        break;
                    case 'bxi_content':
                        $bxRequest->setGroupBy('id');
                        $filters = array(new \com\boxalino\bxclient\v1\BxFilter('bx_type', array('bxi_content'), false));
                        $bxRequest->setFilters($filters);
                        $categoryValues = is_array($context) ? $context : array($context);
                        self::$bxClient->addRequestContextParameter('current_category_id', $categoryValues);
                        break;
                    case 'blog':
                        $bxRequest->setProductContext('id', $context);
                        break;
                    case 'portfolio_blog':
                        $filterField = "di_portfolio_group";
                        $filterValues = is_array($context) ? $context : array($context);
                        $filters[] = new \com\boxalino\bxclient\v1\BxFilter($filterField, $filterValues);
                        break;
                    case 'blog_product':
                        $bxRequest->setProductContext('id', $context);
                        break;
                    default:
                        break;
                }

                foreach ($requestContextParams as $key => $requestContextParam) {
                    self::$bxClient->addRequestContextParameter($key, $requestContextParam);
                }
                $bxRequest->setFilters($filters);
                if($isPortfolio) {
                    $bxRequest->setHitsGroupsAsHits(true);
                }
                self::$bxClient->addRequest($bxRequest);
            }
            return array();
        }

        $values = $isBlog ? $this->getEntitiesIds('blog') : $this->convertToFieldArray(
            $this->getResponse()->getHitFieldValues(['products_ordernumber'], $choiceId, true, 0),
            'products_ordernumber');
        return $values;
    }

    /**
     * Flush BxClient responses
     */
    public function flushResponses() {
        self::$bxClient->flushResponses();
    }

    /**
     * Reset BxClient requests
     */
    public function resetRequests() {
        self::$bxClient->resetRequests();
    }

    /**
     * @param $hitId
     * @param string $field
     * @param string $type
     * @return mixed
     */
    public function getHitFieldsValues($hitId, $field = 'id', $type = "product") {

        $count = array_search($type, self::$choiceContexts[$this->currentSearchChoice]);
        return self::$bxClient->getResponse()->getHitFieldValue($this->currentSearchChoice, $hitId, $field, $count);
    }

    /**
     * @param $hitId
     * @param $info_key
     * @param string $default_value
     * @param string $type
     * @return mixed
     */
    public function getHitExtraInfo($hitId, $info_key, $default_value = '',  $type = "product") {

        $count = array_search($type, self::$choiceContexts[$this->currentSearchChoice]);
        return self::$bxClient->getResponse()->getHitExtraInfo($this->currentSearchChoice, $hitId, $info_key, $default_value, $count);
    }

    /**
     * @param $hitId
     * @param $field
     * @param string $type
     * @return mixed
     */
    public function getHitVariable($hitId, $field, $type = "product") {

        $count = array_search($type, self::$choiceContexts[$this->currentSearchChoice]);
        return self::$bxClient->getResponse()->getHitVariable($this->currentSearchChoice, $hitId, $field, $count);
    }

    /**
     * @param string $type
     * @return mixed
     */
    public function getEntitiesIds($type = "product") {

        $count = array_search($type, self::$choiceContexts[$this->currentSearchChoice]);
        return $this->getResponse()->getHitIds($this->currentSearchChoice, true, $count, 10, $this->getEntityIdFieldName($type));
    }

    /**
     * @param $choice
     * @param string $field
     * @param int $count
     * @return mixed
     */
    public function getRecommendationHitFieldValues($choice, $field = 'id', $count = 0) {
        return $this->getResponse()->getHitFieldValues([$field], $choice, true, $count);
    }

    /**
     * @param $field
     * @param string $type
     * @return array
     */
    public function getHitFieldValues($field, $type = "product") {
        $count = array_search($type, self::$choiceContexts[$this->currentSearchChoice]);
        $values = $this->convertToFieldArray(
            $this->getResponse()->getHitFieldValues([$field], $this->currentSearchChoice, true, $count),
            $field);
        return $values;
    }

    /**
     * @param $queryText
     * @param string $type
     * @return mixed
     */
    public function getSubPhraseEntitiesIds($queryText, $type = "product") {

        $count = array_search($type, self::$choiceContexts[$this->currentSearchChoice]);
        return $this->getResponse()->getSubPhraseHitIds($queryText, $this->currentSearchChoice, $count, 'products_ordernumber');
    }

    /**
     * @param $queryText
     * @param $field
     * @param string $type
     * @return array
     */
    public function getSubPhraseFieldValues($queryText, $field, $type = "product"){
        $count = array_search($type, self::$choiceContexts[$this->currentSearchChoice]);
        $values = $this->convertToFieldArray(
            $this->getResponse()->getSubPhraseHitFieldValues($queryText, [$field], $this->currentSearchChoice, $count),
            $field);
        return $values;

    }

    /**
     * @param string $type
     * @return mixed
     */
    public function areResultsCorrectedAndAlsoProvideSubPhrases($type = "product"){
        $count = array_search($type, self::$choiceContexts[$this->currentSearchChoice]);
        return $this->getResponse()->areResultsCorrectedAndAlsoProvideSubPhrases($this->currentSearchChoice, $count);
    }

    /**
     * @param string $type
     * @return mixed
     */
    public function getCorrectedQuery($type = "product") {

        $count = array_search($type, self::$choiceContexts[$this->currentSearchChoice]);
        return $this->getResponse()->getCorrectedQuery($this->currentSearchChoice, $count);
    }

    /**
     * @param string $type
     * @return mixed
     */
    public function areResultsCorrected($type = "product") {

        $count = array_search($type, self::$choiceContexts[$this->currentSearchChoice]);
        return $this->getResponse()->areResultsCorrected($this->currentSearchChoice, $count);
    }

    /**
     * @param string $type
     * @return mixed
     */
    public function getSubPhrasesQueries($type = "product") {

        $count = array_search($type, self::$choiceContexts[$this->currentSearchChoice]);
        return $this->getResponse()->getSubPhrasesQueries($this->currentSearchChoice, $count);
    }

    /**
     * @param string $type
     * @return mixed
     */
    public function areThereSubPhrases($type = "product") {

        $count = array_search($type, self::$choiceContexts[$this->currentSearchChoice]);
        return $this->getResponse()->areThereSubPhrases($this->currentSearchChoice, $count);
    }

    /**
     * @param $queryText
     * @param string $type
     * @return mixed
     */
    public function getSubPhraseTotalHitCount($queryText, $type = "product") {

        $count = array_search($type, self::$choiceContexts[$this->currentSearchChoice]);
        return $this->getResponse()->getSubPhraseTotalHitCount($queryText, $this->currentSearchChoice, $count);
    }

    /**
     * @param string $type
     * @return mixed
     */
    public function getTotalHitCount($type = "product"){

        $count = array_search($type, self::$choiceContexts[$this->currentSearchChoice]);
        if ($count === false) {
            return 0;
        }
        return $this->getResponse()->getTotalHitCount($this->currentSearchChoice, true, $count);
    }

    /**
     * @param $choice_id
     * @param string $default
     * @param int $count
     * @return mixed
     */
    public function getSearchResultTitle($choice_id, $default = '', $count = 0) {
        return $this->getResponse()->getResultTitle($choice_id, $count, $default);

    }

    /**
     * Sets request instance
     *
     * @param Enlight_Controller_Request_Request $request
     */
    public function setRequest(Enlight_Controller_Request_Request $request) {
        $this->request = $request;
    }

    /**
     * Returns request instance
     *
     * @return Enlight_Controller_Request_Request
     */
    public function Request() {
        return $this->request;
    }

    /**
     * @return string
     */
    public function getShortLocale() {
        $locale = Shopware()->Shop()->getLocale();
        $shortLocale = $locale->getLocale();
        $position = strpos($shortLocale, '_');
        if ($position !== false)
            $shortLocale = substr($shortLocale, 0, $position);
        return $shortLocale;
    }

    /**
     * @return mixed
     */
    public function getSearchLimit() {
        return $this->config->get('maxlivesearchresults', 6);
    }

    /**
     * @return mixed|string
     */
    public static function getAccount() {
        $config = Shopware()->Config();
        return $config->get('boxalino_dev') == 1 ?
            $config->get('boxalino_account') . '_dev' :
            $config->get('boxalino_account');
    }

    /**
     * @param null $arguments
     * @return array
     */
    public function getBasket($arguments = null) {
        $basket = Shopware()->Modules()->Basket()->sGetBasket();
        if ($arguments !== null && (!$basket || !$basket['content'])) {
            $basket = $arguments->getSubject()->View()->sBasket;
        }
        return $basket;
    }

    /**
     * @param $ids
     * @return array
     * @throws Zend_Db_Statement_Exception
     */
    public function convertIds($ids){
        $convertedIds = array();
        $db = Shopware()->Db();
        $sql = $db->select()->from(array('a_d' => 's_articles_details'), array('ordernumber'))
            ->where('a_d.articleID IN(?)', $ids)
            ->where('a_d.kind = ?', 1)
            ->order(new Zend_Db_Expr('FIELD(a_d.articleID,' . implode(',', $ids).')'));
        $stmt = $db->query($sql);
        while($row = $stmt->fetch()) {
            $convertedIds[] = $row['ordernumber'];
        }
        return $convertedIds;
    }

    /**
     * @param $ids
     * @return mixed
     */
    public function getLocalArticles($ids) {
        if (empty($ids)) {
            return array();
        }
//        $ids = $this->convertIds($ids);
        $unsortedArticles = Shopware()->Container()->get('legacy_struct_converter')->convertListProductStructList(
            Shopware()->Container()->get('shopware_storefront.list_product_service')->getList(
                $ids,
                Shopware()->Container()->get('shopware_storefront.context_service')->getProductContext()
            )
        );
        $articles = array();
        foreach ($ids as $id) {
            if(isset($unsortedArticles[$id])){
                $articles[$unsortedArticles[$id]['ordernumber']] = $unsortedArticles[$id];
            }
        }
        return $articles;
    }

    public function callNotification($force=false) {
        self::$bxClient->finalNotificationCheck($force);
    }
}
