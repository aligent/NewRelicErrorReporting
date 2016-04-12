<?php

class Aligent_NewRelicErrorReporting_Model_Newrelicerror {

    protected $enabled = false;
    protected $params = array();
    protected $message = '';

    public function __construct()
    {
        if (function_exists('newrelic_add_custom_parameter')) {
            $this->enabled = true;
        }

        $this->request = Mage::app()->getRequest();
        $this->response = Mage::app()->getResponse();

        $this->addDefaultParams();
    }

    protected function addDefaultParams()
    {
        $app = Mage::app();
        $cartSession = Mage::getSingleton('checkout/session');

        $oCookies = Mage::getModel('core/cookie');

        if (class_exists('Phoenix_VarnishCache_Helper_Cache')) {
            $bHasNoCache = $oCookies->get(Phoenix_VarnishCache_Helper_Cache::NO_CACHE_COOKIE) !== false;
        }
        $vStoreCodeCookie = $oCookies->get(Mage_Core_Model_Store::COOKIE_NAME);
        $bHasSeenStoreModal = $oCookies->get('store_selected') !== false;
        $vFrontendSessionId = $oCookies->get('frontend');

        $this->setParams(array(
            'store_code'          => $app->getStore()->getCode(),
            'customer_id'         => Mage::getSingleton('customer/session')->getCustomerId(),
            'quote_id'            => $cartSession->getQuoteId(),
            'item_count'          => $cartSession->getQuote()->getItemsCount(),
            'has_no_cache_cookie' => $bHasNoCache,
            'store_code_cookie'   => $vStoreCodeCookie,
            'store_code_modal'    => $bHasSeenStoreModal,
            'frontend'            => $vFrontendSessionId,
            'module'              => $this->request->getModuleName(),
            'controller'          => $this->request->getControllerName(),
            'action'              => $this->request->getActionName(),
            'referer'             => $this->getRefererUrl(),
            'url'                 => Mage::helper('core/url')->getCurrentUrl(),
            'ajax'                => $this->request->isXmlHttpRequest(),
        ));
    }

    protected function getRefererUrl()
    {
        $refererUrl = $this->request->getServer('HTTP_REFERER');

        if ($url = $this->request->getParam(Mage_Core_Controller_Varien_Action::PARAM_NAME_REFERER_URL)) {
            $refererUrl = $url;
        }

        if ($url = $this->request->getParam(Mage_Core_Controller_Varien_Action::PARAM_NAME_BASE64_URL)) {
            $refererUrl = Mage::helper('core')->urlDecode($url);
        }

        if ($url = $this->request->getParam(Mage_Core_Controller_Varien_Action::PARAM_NAME_URL_ENCODED)) {
            $refererUrl = Mage::helper('core')->urlDecode($url);
        }

        return $refererUrl;
    }

    protected function isUrlInternal($url)
    {
        if (strpos($url, 'http') !== false) {
            /**
             * Url must start from base secure or base unsecure url
             */
            if ((strpos($url, Mage::app()->getStore()->getBaseUrl()) === 0)
                || (strpos($url, Mage::app()->getStore()->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK, true)) === 0)
            ) {
                return true;
            }
        }
        return false;
    }

    public function getEnabled()
    {
        return $this->enabled;
    }

    public function dispatch()
    {
        Mage::dispatchEvent('aligent_debug_new_relic_pre_dispatch', array('error' => $this));

        $this->logError($this->getMessage());
        $this->addCustomParameters($this->params);
        return $this;
    }

    public function getParams()
    {
        return $this->params;
    }

    public function setParams($params)
    {
        if (!is_array($params)) {
            throw new Exception('The params argument should be an array. ' . gettype($params) . 'supplied.');
        }
        $this->params = $params;
        return $this;
    }

    public function setParam($key, $value)
    {
        $this->params[$key] = $value;
    }

    public function getParam($key)
    {
        if (isset($this->params[$key])) {
            $this->params[$key];
        }
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function setMessage($message) {
        if (!is_string($message)) {
            throw new Exception('The message argument should be a string. ' . gettype($message) . 'supplied.');
        }
        return $this;
    }

    public function logError($message)
    {
        if ($this->enabled && function_exists('newrelic_notice_error') && $this->message) {
            newrelic_notice_error($message);
        }
        return $this;
    }

    public function addCustomParameters($parameters)
    {
        if ($this->enabled) {
            foreach ($parameters as $key => $value) {
                newrelic_add_custom_parameter($key, $value);
            }
        }
        return $this;
    }

}