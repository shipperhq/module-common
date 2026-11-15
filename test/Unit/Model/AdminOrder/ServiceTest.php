<?php
/**
 * ShipperHQ
 *
 * @category ShipperHQ
 * @package ShipperHQ\Common
 * @copyright Copyright (c) 2014 Zowta LTD and Zowta LLC (http://www.ShipperHQ.com)
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

namespace ShipperHQ\Common\Test\Unit\Model\AdminOrder;

use Magento\Backend\Model\Session as AdminSession;
use Magento\Backend\Model\Session\Quote as QuoteSession;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Cart\ShippingMethodConverter;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use PHPUnit\Framework\TestCase;
use ShipperHQ\Common\Model\AdminOrder\Service;
use ShipperHQ\Shipper\Helper\LogAssist;

class ServiceTest extends TestCase
{
    public function testRequestShippingRatesReturnsEmptyArrayWhenAddressHasNoRates()
    {
        $address = $this->createStub(Address::class);
        $address->method('__call')->willReturnSelf();
        $address->method('getGroupedAllShippingRates')->willReturn([]);

        $quote = $this->createStub(Quote::class);
        $quote->method('getShippingAddress')->willReturn($address);

        $quoteSession = $this->createStub(QuoteSession::class);
        $quoteSession->method('getQuote')->willReturn($quote);

        $model = new Service(
            $quoteSession,
            $this->createStub(AdminSession::class),
            $this->createStub(ShippingMethodConverter::class),
            $this->createStub(LogAssist::class),
            $this->createStub(CartRepositoryInterface::class)
        );

        $this->assertSame([], $model->reqeustShippingRates(1, 'carrier', ''));
    }
}
