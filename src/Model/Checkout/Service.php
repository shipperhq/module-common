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


namespace ShipperHQ\Common\Model\Checkout;

use Magento\Checkout\Model\Session;
use Magento\Customer\Model\Data\Region;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Cart\ShippingMethodConverter;
use ShipperHQ\Lib\Checkout\AbstractService;
use ShipperHQ\Shipper\Helper\LogAssist;

/**
 * Class Service
 *
 * @package ShipperHQ_Common
 */
class Service extends AbstractService
{
    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * Quote repository.
     *
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;
    /**
     * Shipping method converter
     *
     * @var ShippingMethodConverter
     */
    protected $converter;
    /**
     * @var LogAssist
     */
    private $shipperLogger;

    protected $address;
    protected $quote;

    /**
     * @param CartRepositoryInterface $quoteRepository
     * @param Session $checkoutSession
     * @param ShippingMethodConverter $converter
     * @param LogAssist $shipperLogger
     * @codeCoverageIgnore
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        Session                 $checkoutSession,
        ShippingMethodConverter $converter,
        LogAssist               $shipperLogger
    )
    {
        $this->quoteRepository = $quoteRepository;
        $this->checkoutSession = $checkoutSession;
        $this->converter = $converter;
        $this->shipperLogger = $shipperLogger;
    }

    /*
     * Cache data selected at checkout for use in rate request
     */
    public function saveSelectedData($data)
    {
        // SHQ18-277 retain selections on existing checkout selections object if it exists
        $requestData = $this->checkoutSession->getShipperhqData();
        if (isset($requestData['checkout_selections']) && is_object($requestData['checkout_selections'])) {
            $checkoutSelections = $requestData['checkout_selections'];
        } else {
            $checkoutSelections = new \ShipperHQ\Lib\Rate\CarrierSelections();
        }

        // MNB-1003 We don't want to send the carrier code or carrier ID for merged/rate shopped rates
        $isMergedRates = false;

        if (array_key_exists('CarrierCode', $data) &&
            ($data['CarrierCode'] == 'multicarrier' || $data['CarrierCode'] == 'shqshared')) {
            $isMergedRates = true;
        }

        foreach ($data as $dataName => $value) {
            if ($isMergedRates && ($dataName == 'CarrierCode' || $dataName == 'CarrierId')) {
                continue;
            }

            $setFunction = 'set' . $dataName;
            call_user_func([$checkoutSelections, $setFunction], $value);
        }

        $requestData['checkout_selections'] = $checkoutSelections;
        $this->checkoutSession->setShipperhqData($requestData);
    }

    /**
     * Remove carrier shipping rates before re-requesting
     *
     * @param $cartId
     * @param $carrierCode
     * @param $carriergroupId
     * @param bool $addressId
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function cleanDownRates($cartId, $carrierCode, $carriergroupId, $addressId = false)
    {
        if (is_null($cartId)) {
            return;
        }
        $currentRates = $this->getAddress($cartId, $addressId)->getGroupedAllShippingRates();

        foreach ($currentRates as $code => $rates) {
            // Prevent duplicate rates from non-SHQ carriers if enabled
            if ($code == $carrierCode || !strstr((string)$code, 'shq')) {
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
     * @param $addressData
     * @param bool $addressId
     * @return array
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function reqeustShippingRates($cartId, $carrierCode, $carriergroupId, $addressData, $addressId = false)
    {
        $address = $this->getAddress($cartId, $addressId);
        $address->addData($addressData)
            ->setCollectShippingRates(true)
            ->collectShippingRates();
        $rates = $address->getGroupedAllShippingRates();
        $output = [];
        foreach ($rates as $carrierRates) {
            foreach ($carrierRates as $rate) {
                $rateObject = $this->converter->modelToDataObject(
                    $rate,
                    $this->getQuote($cartId)->getQuoteCurrencyCode()
                );
                // Mimicking output format from serviceOutputProcessor->process(... (API stuff)
                $oneRate = [
                    'carrier_code' => $rateObject->getCarrierCode(),
                    'method_code' => $rateObject->getMethodCode(),
                    'carrier_title' => $rateObject->getCarrierTitle(),
                    'method_title' => $rateObject->getMethodTitle(),
                    'amount' => $rateObject->getAmount(),
                    'base_amount' => $rateObject->getBaseAmount(),
                    'available' => $rateObject->getAvailable(),
                    'error_message' => $rateObject->getErrorMessage(),
                    'price_excl_tax' => $rateObject->getPriceExclTax(),
                    'price_incl_tax' => $rateObject->getPriceInclTax()
                ];

                $output[] = $oneRate;
            }
        }
        return $output;
    }

    /**
     * Removed cached data selected at checkout
     */
    public function cleanDownSelectedData($selections = [])
    {
        $requestData = $this->checkoutSession->getShipperhqData();
        if (empty($selections)) {
            unset($requestData['checkout_selections']);
        } elseif (isset($requestData['checkout_selections'])) {
            $checkoutSelections = $requestData['checkout_selections'];
            foreach ($selections as $dataName) {
                $setFunction = 'set' . $dataName;
                call_user_func([$checkoutSelections, $setFunction], null);
            }
        }
        $this->checkoutSession->setShipperhqData($requestData);
    }

    /**
     * @param $cartId
     * @param $addressId
     */
    protected function saveShippingAddress($cartId, $addressId)
    {
        $address = $this->getAddress($cartId, $addressId);
        $region = $address->getRegion();
        if ($region !== null && $region instanceof Region) {
            $regionString = $region->getRegion();
            $address->setRegion($regionString);
        }
        try {
            $address->save();
        } catch (\Exception $e) {
            $this->shipperLogger->postCritical(
                'Shipperhq_Shipper',
                'Exception raised whilst saving shipping address',
                $e->getMessage()
            );
        }
    }

    /**
     * @param $cartId
     * @param bool $addressId
     * @return mixed
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function getAddress($cartId, $addressId = false)
    {
        if ($this->address === null) {
            if (!$this->quote) {
                $this->quote = $this->getQuote($cartId);
            }
            $this->address = $this->quote->getShippingAddress();
        }
        // TODO multiaddress checkout support
//        if($addressId) {
//            $allShipAddress = $this->quote->getAllShippingAddresses();
//            foreach($allShipAddress as $shippingAddress) {
//                if($shippingAddress->getId() == $addressId) {
//                    $this->_address = $shippingAddress;
//                }
//            }
//        }
        return $this->address;
    }

    protected function getQuote($cartId)
    {
        if (!$this->quote) {
            $this->quote = $this->quoteRepository->getActive($cartId);
        }
        return $this->quote;
    }
}
