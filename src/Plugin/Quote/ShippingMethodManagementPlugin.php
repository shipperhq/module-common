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


namespace ShipperHQ\Common\Plugin\Quote;

class ShippingMethodManagementPlugin
{
    /**
     * Quote repository.
     *
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;
    /**
     * @var \ShipperHQ\Shipper\Helper\Data
     */
    protected $shipperDataHelper;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;
    /**
     * @var \ShipperHQ\Shipper\Helper\LogAssist
     */
    private $shipperLogger;

    public function __construct(
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Checkout\Model\Session $checkoutSession,
        \ShipperHQ\Shipper\Helper\LogAssist $shipperLogger
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->checkoutSession = $checkoutSession;
        $this->shipperLogger = $shipperLogger;
    }

    /**
     * Persist shipping address details so they are available when rates re-requested
     *
     * @param \Magento\Quote\Model\ShippingMethodManagement $subject
     * @param callable $proceed
     * @param $cartId
     * @param \Magento\Quote\Api\Data\AddressInterface $address
     * @return \Magento\Quote\Api\Data\ShippingMethodInterface[]
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundEstimateByExtendedAddress(
        \Magento\Quote\Model\ShippingMethodManagement $subject,
        $proceed,
        $cartId,
        \Magento\Quote\Api\Data\AddressInterface $address
    ) {

        $result = $proceed($cartId, $address);
        $this->saveShippingAddress($cartId);
        return $result;
    }

    /**
     * Persist shipping address details so they are available when rates re-requested
     *
     * @param \Magento\Quote\Model\ShippingMethodManagement $subject
     * @param callable $proceed
     * @param $cartId
     * @param \Magento\Quote\Api\Data\EstimateAddressInterface $address
     * @return \Magento\Quote\Api\Data\ShippingMethodInterface[]
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundEstimateByAddress(
        \Magento\Quote\Model\ShippingMethodManagement $subject,
        $proceed,
        $cartId,
        \Magento\Quote\Api\Data\EstimateAddressInterface $address
    ) {
        $result = $proceed($cartId, $address);
        $this->saveShippingAddress($cartId);
        return $result;
    }

    protected function saveShippingAddress($cartId)
    {
        $quote = $this->quoteRepository->getActive($cartId);
        $address = $quote->getShippingAddress();
        $region = $address->getRegion();
        if (!is_null($region) && $region instanceof \Magento\Customer\Model\Data\Region) {
            $regionString = $region->getRegion();
            $address->setRegion($regionString);
        }
        try {
            //SHQ16-1770 for guest checkout need to save address otherwise all rates aren't available on
            // quote when re-requesting
            $address->save();
        } catch (\Exception $e) {
            $this->shipperLogger->postCritical(
                'Shipperhq_Shipper',
                'Exception raised whilst saving shipping address',
                $e->getMessage()
            );
        }
    }
}
