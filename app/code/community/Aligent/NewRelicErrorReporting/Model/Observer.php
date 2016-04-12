<?php

class Aligent_NewRelicErrorReporting_Model_Observer
{

    public function controllerActionPreDispatch($oEvent) {
        $oNewRelicError = Mage::getSingleton('aligent_newrelicerrorreporting/newrelicerror');

        $aNewRelicErrorParams = $oNewRelicError->getParams();

        // Explicitly raise an alert when the cookie store code and the actual store code differ.
        if (isset($aNewRelicErrorParams['store_code']) && isset($aNewRelicErrorParams['store_code_cookie']) &&
            $aNewRelicErrorParams['store_code'] != $aNewRelicErrorParams['store_code_cookie'] &&
            $aNewRelicErrorParams['store_code_cookie'] !== false) {

            $oNewRelicError->setMessage('Mage store code does not match cookie.');
        }
    }

    public function controllerActionPostDispatch($oEvent){
        $oNewRelicError = Mage::getSingleton('aligent_newrelicerrorreporting/newrelicerror');
        $oNewRelicError->dispatch();
    }

}