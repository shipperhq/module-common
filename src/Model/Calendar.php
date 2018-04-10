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

namespace ShipperHQ\Common\Model;

use ShipperHQ\Lib\Type\BaseCalendar;
use Magento\Framework\Stdlib\DateTime;

/**
 * Class Calendar
 *
 * @package ShipperHQ_Common
 */
class Calendar extends BaseCalendar
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

    public function processCalendarDetails($carrierRate, $carrierGroupDetail)
    {
        $calendarDetails = parent::processCalendarDetails($carrierRate, $carrierGroupDetail);
        //transform for current locale
        $calendarDetails['start'] = $this->localeDate->date($calendarDetails['start'], null, true)->getTimestamp();
        return $calendarDetails;
    }
}
