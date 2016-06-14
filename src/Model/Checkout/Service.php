<?php
/**
 *
 * ShipperHQ
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
 * Shipper HQ Shipping
 *
 * @category ShipperHQ
 * @package ShipperHQ_Common
 * @copyright Copyright (c) 2015 Zowta LLC (http://www.ShipperHQ.com)
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @author ShipperHQ Team sales@shipperhq.com
 */
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace ShipperHQ\Common\Model\Checkout;

use ShipperHQ\Lib\Checkout\AbstractService;

/**
 * Class Service
 *
 * @package ShipperHQ_Common
 */
class Service extends AbstractService
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * Quote repository.
     *
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    protected $address;

    /**
     * @param Context $context
     * @param Sidebar $sidebar
     * @param LoggerInterface $logger
     * @param Data $jsonHelper
     * @codeCoverageIgnore
     */
    public function __construct(
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->checkoutSession = $checkoutSession;
    }

    /*
     * Cache data selected at checkout for use in rate request
     */
    public function saveSelectedData($data)
    {
        $requestData = $this->checkoutSession->getShipperhqData();
      //  $key = $this->getKey($data);
        $requestData['checkout_selections'] = $data;
        $this->checkoutSession->setShipperhqData($requestData);
    }
    /*
     * Remove carrier shipping rates before re-requesting
     */
    public function cleanDownRates($carrierCode, $carriergroupId, $addressId = false)
    {
        $currentRates = $this->getAddress($addressId)->getGroupedAllShippingRates();
        foreach ($currentRates as $code => $rates) {
            if ($code == $carrierCode) {
                foreach ($rates as $rate) {
                    if ($carriergroupId == '' || $rate->getCarriergroupId() == $carriergroupId) {
                        $rate->isDeleted(true);
                    }
                }
            }

        }
    }
    /*
     * Request shipping rates for specified carrier
     */
    public function reqeustShippingRates($carrierCode, $carriergroupId, $addressId = false)
    {
        // if (empty($this->_rates)) {
        //    if(!$address->getFreeMethodWeight()) {
        //        $address->setFreeMethodWeight(Mage::getSingleton('checkout/session')->getFreemethodWeight());
        //    }

        $address = $this->getAddress();
        $rateFound = $address->requestShippingRates();
        $address->save();
        $rates = $address->getGroupedAllShippingRates();

        return $rates;
    }
    /*
     * Removed cached data selected at checkout
     */
    public function cleanDownSelectedData()
    {
        $requestData = $this->checkoutSession->getShipperhqData();
        //  $key = $this->getKey($data);
        $requestData['checkout_selections'] = [];
        $this->checkoutSession->setShipperhqData($requestData);
    }


    protected function getAddress($addressId = false)
    {
        //TODO multiaddress checkout support
//        if($addressId) {
//            $allShipAddress = $this->quote->getAllShippingAddresses();
//            foreach($allShipAddress as $shippingAddress) {
//                if($shippingAddress->getId() == $addressId) {
//                    $this->_address = $shippingAddress;
//                }
//            }
//        }

        if(is_null($this->address)) {
            //$cartId = $this->checkoutSession->getQuote()->getId();
            //$quote = $this->quoteRepository->getActive($cartId);
            $quote = $this->checkoutSession->getQuote();
            $this->address = $quote->getShippingAddress();
        }
        return $this->address;
    }

}
