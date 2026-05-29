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

use ShipperHQ\Lib\Type\BaseOption;

/**
 * Class Option
 *
 * @package ShipperHQ_Common
 */
class Option extends BaseOption
{
    /*
     * @param Checkout\Service $checkoutService
     */
    public function __construct(Checkout\Service $checkoutService)
    {
        parent::__construct($checkoutService);
    }
}
