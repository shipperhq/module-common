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
namespace ShipperHQ\Common\Model\AdminOrder;

use ShipperHQ\Lib\AdminOrder\AbstractService;

/**
 * Class Service
 *
 * @package ShipperHQ_Common
 */
class Service extends AbstractService
{
    /**
     * Quote repository.
     *
     * @var \Magento\Backend\Model\Session\Quote
     */
    protected $quote;
    /**
     * @var \Magento\Backend\Model\Session
     */
    protected $adminSession;
    /**
     * Shipping method converter
     *
     * @var \Magento\Quote\Model\Cart\ShippingMethodConverter
     */
    protected $converter;
    /**
     * @var \ShipperHQ\Shipper\Helper\LogAssist
     */
    private $shipperLogger;
    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    protected $address;

    /**
     * @param \Magento\Backend\Model\Session\Quote $quote
     * @param \Magento\Backend\Model\Session $adminSession
     * @param \Magento\Quote\Model\Cart\ShippingMethodConverter $converter
     * @param \ShipperHQ\Shipper\Helper\LogAssist $shipperLogger
     * @codeCoverageIgnore
     */
    public function __construct(
        \Magento\Backend\Model\Session\Quote $quote,
        \Magento\Backend\Model\Session $adminSession,
        \Magento\Quote\Model\Cart\ShippingMethodConverter $converter,
        \ShipperHQ\Shipper\Helper\LogAssist $shipperLogger,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
    ) {
        $this->quote = $quote;
        $this->adminSession = $adminSession;
        $this->converter = $converter;
        $this->shipperLogger = $shipperLogger;
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * Cache data selected at checkout for use in rate request
     *
     * @param $data
     */
    public function saveSelectedData($data)
    {
        //Beware of type here - we must use the quote as this matches where data is retrieved for admin orders
        //SHQ18-277 retain selections on existing checkout selections object if it exists
        $requestData = $this->quote->getShipperhqData();
        if (isset($requestData['checkout_selections']) && is_object($requestData['checkout_selections'])) {
            $checkoutSelections = $requestData['checkout_selections'];
        }
        else {
            $checkoutSelections = new \ShipperHQ\Lib\Rate\CarrierSelections;
        }

        foreach ($data as $dataName => $value) {
            $setFunction = 'set' .$dataName;
            call_user_func(array($checkoutSelections,$setFunction), $value);
        }
        $requestData['checkout_selections'] = $checkoutSelections;
        $this->quote->setShipperhqData($requestData);
    }

    /**
     * Remove carrier shipping rates before re-requesting
     *
     * @param $cartId
     * @param $carrierCode
     * @param $carriergroupId
     */
    public function cleanDownRates($cartId, $carrierCode, $carriergroupId)
    {
        if (is_null($cartId)) {
            return;
        }
        $currentRates = $this->getAddress()->getGroupedAllShippingRates();

        foreach ($currentRates as $code => $rates) {
            //prevent duplicate rates from non-SHQ carriers if enabled
            if ($code == $carrierCode || !strstr($code, 'shq')) {
                foreach ($rates as $rate) {
                    if ($carriergroupId == '' || $rate->getCarriergroupId() == $carriergroupId) {
                        $rate->isDeleted(true);
                    }
                }
            }
        }
    }

    /**
     * Request shipping rates for specified carrier
     *
     * @param $cartId
     * @param $carrierCode
     * @param $carriergroupId
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function reqeustShippingRates($cartId, $carrierCode, $carriergroupId)
    {
        $address = $this->getAddress();
        $address->setCollectShippingRates(true)
            ->collectShippingRates();
        $rates = $address->getGroupedAllShippingRates();
        $this->quoteRepository->save($this->quote->getQuote());

        foreach ($rates as $carrierRates) {
            foreach ($carrierRates as $rate) {
                $rateObject = $this->converter->modelToDataObject(
                    $rate,
                    $this->getQuote()->getQuoteCurrencyCode()
                );
                //mimicking output format from serviceOutputProcessor->process(... (API stuff)
                $oneRate = ['carrier_code' => $rateObject->getCarrierCode(),
                    'method_code' => $rateObject->getMethodCode(),
                    'carrier_title' => $rateObject->getCarrierTitle(),
                    'method_title' => $rateObject->getMethodTitle(),
                    'amount'    => $rateObject->getAmount(),
                    'base_amount' => $rateObject->getBaseAmount(),
                    'available' => $rateObject->getAvailable(),
                    'error_message' => $rateObject->getErrorMessage(),
                    'price_excl_tax' => $rateObject->getPriceExclTax(),
                    'price_incl_tax' => $rateObject->getPriceInclTax()];

                $output[] = $oneRate;
            }
        }
        return $output;
    }

    /**
     * Removed cached data selected at checkout
     */
    public function cleanDownSelectedData()
    {
        $requestData = $this->adminSession->getShipperhqData();
        unset($requestData['checkout_selections']);
        $this->adminSession->setShipperhqData($requestData);
    }

    /**
     * @return \Magento\Quote\Model\Quote\Address
     */
    protected function getAddress()
    {

        if ($this->address === null) {
            $this->address = $this->quote->getQuote()->getShippingAddress();
        }
        return $this->address;
    }

    /**
     * @return \Magento\Quote\Model\Quote
     */
    protected function getQuote()
    {
        return $this->quote->getQuote();
    }
}
