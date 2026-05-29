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


namespace ShipperHQ\Common\Model;

use ShipperHQ\Lib\Type\BasePickup;
use Magento\Framework\Stdlib\DateTime;

/**
 * Class Calendar
 *
 * @package ShipperHQ_Common
 */
class Pickup extends BasePickup
{
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $localeDate;

    /*
     * @param DateTime\TimezoneInterface $localeDate
     */
    public function __construct(
        DateTime\TimezoneInterface $localeDate,
        Checkout\Service $checkoutService,
        AdminOrder\Service $adminService
    ) {
        $this->localeDate = $localeDate;
        parent::__construct($checkoutService, $adminService);
    }

    public function processPickupDetails($carrierRate, $carrierGroupDetail)
    {
        $pickupDetails = parent::processPickupDetails($carrierRate, $carrierGroupDetail);
        //transform for current locale
        return $pickupDetails;
    }
}
