<?php
/**
 *
 * ShipperHQ Shipping Module
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
    /**
     * Customer Address repository
     *
     * @var \Magento\Customer\Api\AddressRepositoryInterface
     */
    private $addressRepository;

    public function __construct(
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Checkout\Model\Session $checkoutSession,
        \ShipperHQ\Shipper\Helper\LogAssist $shipperLogger,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->checkoutSession = $checkoutSession;
        $this->shipperLogger = $shipperLogger;
        $this->addressRepository = $addressRepository;

    }

    /**
     *Persist shipping address details so they are available when rates re-requested
     *
     * @param \Magento\Quote\Model\ShippingMethodManagement $subject
     * @param callable $proceed
     * @param $cartId
     * @param \Magento\Quote\Api\Data\AddressInterface $address
     * @return \Magento\Quote\Api\Data\ShippingMethodInterface[]
     *
     */
    public function aroundEstimateByExtendedAddress(\Magento\Quote\Model\ShippingMethodManagement $subject, $proceed,
                                                    $cartId, \Magento\Quote\Api\Data\AddressInterface $address)
    {
        $result = $proceed($cartId, $address);
        $this->saveShippingAddress($cartId);
        return $result;
    }

    /**
     *Persist shipping address details so they are available when rates re-requested
     *
     * @param \Magento\Quote\Model\ShippingMethodManagement $subject
     * @param callable $proceed
     * @param $cartId
     * @param \Magento\Quote\Api\Data\EstimateAddressInterface $address
     * @return \Magento\Quote\Api\Data\ShippingMethodInterface[]
     *
     */
    public function aroundEstimateByAddress(\Magento\Quote\Model\ShippingMethodManagement $subject, $proceed,
                                            $cartId, \Magento\Quote\Api\Data\EstimateAddressInterface $address)
    {
        $result = $proceed($cartId, $address);
        $this->saveShippingAddress($cartId);
        return $result;
    }

    /**
     *Add customers address type to shipping address on quote
     *
     * @param \Magento\Quote\Model\ShippingMethodManagement $subject
     * @param callable $proceed
     * @param $cartId
     * @param int $addressId
     * @return \Magento\Quote\Api\Data\ShippingMethodInterface[]
     *
     */
    public function aroundEstimateByAddressId(\Magento\Quote\Model\ShippingMethodManagement $subject, $proceed, $cartId, $addressId)
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);

        // no methods applicable for empty carts or carts with virtual products
        if ($quote->isVirtual() || 0 == $quote->getItemsCount()) {
            return $proceed($cartId, $addressId);
        }
        $address = $this->addressRepository->getById($addressId);

        if($custom = $address->getCustomAttributes()) {
            foreach ($custom as $custom_attribute) {
                if($custom_attribute->getAttributeCode() == 'destination_type') {
                    $quote->getShippingAddress()->setData('destination_type', $custom_attribute->getValue());
                }
            }
        }

        return $proceed($cartId, $addressId);
    }

    protected function saveShippingAddress($cartId)
    {
        $quote = $this->quoteRepository->getActive($cartId);
        $address = $quote->getShippingAddress();
        $region = $address->getRegion();
        if(!is_null($region) && $region instanceof \Magento\Customer\Model\Data\Region) {
            $regionString = $region->getRegion();
            $address->setRegion($regionString);
        }
        try {
            //SHQ16-1770 for guest checkout need to save address otherwise all rates aren't available on quote when re-requesting
            $address->save();
        }
        catch(\Exception $e) {
            $this->shipperLogger->postCritical('Shipperhq_Shipper', 'Exception raised whilst saving shipping address',
                $e->getMessage());
        }
    }
}
