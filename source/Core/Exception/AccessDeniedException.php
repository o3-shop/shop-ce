<?php

/**
 * This Software is the property of Data Development and is protected
 * by copyright law - it is NOT Freeware.
 * Any unauthorized use of this software without a valid license
 * is a violation of the license agreement and will be prosecuted by
 * civil and criminal law.
 * http://www.shopmodule.com
 *
 * @copyright (C) D3 Data Development (Inh. Thomas Dartsch)
 * @author        D3 Data Development - Daniel Seifert <support@shopmodule.com>
 * @link          http://www.oxidmodule.com
 */

namespace OxidEsales\EshopCommunity\Core\Exception;

class AccessDeniedException extends \OxidEsales\Eshop\Core\Exception\StandardException
{
    public function __construct( $sMessage = "access denied", $iCode = 0, \Exception $previous = null )
    {
        parent::__construct( $sMessage, $iCode, $previous );
    }
}