<?php
/**
 * WebShopApps
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * WebShopApps Common
 *
 * @category WebShopApps
 * @package WebShopApps_Common
 * @copyright Copyright (c) 2015 Zowta LLC (http://www.WebShopApps.com)
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @author WebShopApps Team sales@webshopapps.com
 *
 */
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace WebShopApps\Common\Helper;


/**
 * Common data helper
 */
class Data extends  \Magento\Framework\App\Helper\AbstractHelper
{

    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $moduleManager;


    function __construct(\Magento\Framework\Module\Manager $moduleManager,
                         \Magento\Framework\App\Helper\Context $context) {
        parent::__construct($context);
        $this->moduleManager = $moduleManager;
    }




    public function isEnterpriseEdition() {

        if (!$this->_moduleManager->isEnabled('Enterprise_Cms')) {
            return false;
        }

        return true;
    }

    /**
     * Get Config Value
     *
     * @param $configField
     * @return mixed
     */
    public function getConfigValue($configField)
    {
        return $this->scopeConfig->getValue($configField,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

    }

}