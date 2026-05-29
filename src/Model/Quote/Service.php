<?php
/**
 * ShipperHQ
 *
 * @category ShipperHQ
 * @package ShipperHQ\Common
 * @copyright Copyright (c) 2015 Zowta LTD and Zowta LLC (http://www.ShipperHQ.com)
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @author ShipperHQ Team sales@shipperhq.com
 */
namespace ShipperHQ\Common\Model\Quote;

use ShipperHQ\Lib\Quote\AbstractService;

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
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;
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

    protected $address;
    protected $quote;

    /**
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Quote\Model\Cart\ShippingMethodConverter $converter
     * @param \ShipperHQ\Shipper\Helper\LogAssist $shipperLogger
     * @codeCoverageIgnore
     */
    public function __construct(
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Quote\Model\Cart\ShippingMethodConverter $converter,
        \ShipperHQ\Shipper\Helper\LogAssist $shipperLogger
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->converter = $converter;
        $this->shipperLogger = $shipperLogger;
    }

    /**
     * Remove carrier shipping rates
     *
     * @param $address
     * @param $carrierCode
     * @param $carriergroupId
     * @param bool $addressId
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function cleanDownRates($address, $carrierCode, $carriergroupId, $addressId = false)
    {
        $currentRates = $address->getGroupedAllShippingRates();

        foreach ($currentRates as $code => $rates) {
            //prevent duplicate rates from non-SHQ carriers if enabled
            if ($code == $carrierCode) {
                foreach ($rates as $rate) {
                    if ($carriergroupId == '' || $rate->getCarriergroupId() == $carriergroupId) {
                        $rate->isDeleted(true);
                    }
                }
            }
        }
    }
}
