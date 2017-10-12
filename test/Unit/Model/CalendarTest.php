<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace ShipperHQ\Common\Test\Unit\Model\Calendar;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

class CalendarTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \ShipperHQ\Shipper\Model\Backend\Config\Source\EnvironmentScope
     */
    protected $model;

    protected $localeDate;

    /** @var ObjectManagerHelper */
    protected $objectManagerHelper;

    protected function setUp()
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);

        $this->checkoutServiceMock = $this->getMockBuilder('\ShipperHQ\Common\Model\Checkout\Service')
            ->disableOriginalConstructor()
            ->getMock();
        $this->localeDateMock = $this->getMockBuilder('\Magento\Framework\Stdlib\DateTime\TimezoneInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = $this->objectManagerHelper->getObject(
            'ShipperHQ\Common\Model\Calendar',
            [
                'localeDate' => $this->localeDateMock,
                'checkoutService' => $this->checkoutServiceMock
            ]
        );
    }

    public function testSatSunBlackoutDays()
    {
        $calendarDetail = [
            'blackoutDates' => [],
            "blackoutDays" => [
                6,
                7
            ],
            "timeSlots" => [],
            "showDate" => true,
            "maxDays" => 5,
            'start' => 1476856800000 / 1000,
            "startDate" => 1476856800000,
            "startDateStr" => "19-10-2016 00:00:00 -0600", //wednesday
            'dateFormat' => 'm/d/y',
            'timezone' => 'America/Boise'
        ];
        $dateOptions = $this->model->getDateOptions($calendarDetail);
//        if($dateOptions) {
//            foreach($dateOptions as $key => $display){
//                echo $display;
//                echo ' | ';
//            }
//        }
//        else {
//            echo '          no $dateOptions returned';
//        }
        $this->assertArrayHasKey('10/19/16', $dateOptions);
        $this->assertArrayNotHasKey('10/22/16', $dateOptions);
        $this->assertArrayNotHasKey('10/23/16', $dateOptions);
        //number days is 5 but two of those are blackout days so total number of days is 3
        $this->assertCount(3, $dateOptions);

    }

    public function testGetDateOptionsCountCorrect()
    {
        $calendarDetail = [
            'blackoutDates' => [
                "2016-05-30",
                "2016-07-04",
                "2016-09-05",
                "2016-10-10",
                "2016-11-11",
                "2016-11-24",
                "2016-12-25",
                "2017-01-01",
                "2017-01-16",
                "2017-02-20",
                "2017-05-29",
                "2017-07-04",
                "2017-09-04",
                "2017-10-09",
                "2017-11-11",
                "2017-11-23",
                "2017-12-25"
            ],
            "blackoutDays" => [
                6,
                7
            ],
            "timeSlots" => [],
            "showDate" => true,
            "maxDays" => 5,
            'start' => 1493915990813 / 1000,
            "startDate" => 1493915990813,
            "startDateStr" => "04-05-2017 12:39:81 -0400",
            'dateFormat' => 'm/d/y',
            'timezone' => 'America/New_York'
        ];

        $this->assertCount(3, $this->model->getDateOptions($calendarDetail));

    }

    public function testAllDaysAreBlackout()
    {
        $calendarDetail = [
            'blackoutDates' => [
                "2016-05-30",
                "2016-07-04",
                "2016-09-05",
                "2016-10-10",
                "2016-11-11",
                "2016-11-24",
                "2016-12-25",
                "2017-01-01",
                "2017-01-16",
                "2017-02-20",
                "2017-05-29",
                "2017-07-04",
                "2017-09-04",
                "2017-10-09",
                "2017-11-11",
                "2017-11-23",
                "2017-12-25"
            ],
            "blackoutDays" => [
                1, 2, 3, 4, 5,
                6,
                7
            ],
            "timeSlots" => [],
            "showDate" => true,
            "maxDays" => 5,
            'start' => 1468191600000 / 1000,
            "startDate" => 1468191600000,
            "startDateStr" => "11-07-2016 00:00:00 +0100",
            'dateFormat' => 'm/d/y',
            'timezone' => 'America/New_York'
        ];

        $this->assertCount(0, $this->model->getDateOptions($calendarDetail));
    }

    public function testBlackoutDateExcluded()
    {
        $calendarDetail = [
            'blackoutDates' => [
                "2016-07-04",
            ],
            "blackoutDays" => [
            ],
            "timeSlots" => [],
            "showDate" => true,
            "maxDays" => 5,
            'start' => 1467385200000 / 1000, //1st July 2016
            "startDate" => 1467385200000, //1st July 2016
            "startDateStr" => "01-07-2016 00:00:00 +0000",
            'dateFormat' => 'm/d/y',
            'timezone' => 'America/New_York'
        ];
        $dateOptions = $this->model->getDateOptions($calendarDetail);
        $this->assertArrayHasKey('07/01/16', $dateOptions);
        $this->assertArrayNotHasKey('07/04/16', $dateOptions);
        $this->assertCount(4, $dateOptions);
    }

    public function testgetDateFromTimestamp()
    {
        $start = 1475726400000 / 1000;
        $timeInfo = date("m/d/y h:i:s a", $start);

        $timezone = 'America/Boise';
        $return = $this->model->getDateFromTimestamp($start, $timezone, 'm/d/y');
        $this->assertEquals('10/05/16', $return);
    }

    public function testDateReturnedIsTheSame()
    {
        $date = '07/15/16';
        $timezone = 'America/New_York';

        $return = $this->model->getDateFromDate($date, $timezone, 'm/d/y');
        $this->assertEquals($date, $return);
    }

    public function testGetDayOfWeekFromDate()
    {
        $date = '10/23/16';
        $timezone = 'America/Boise';
        $expected = 7;
        $this->assertEquals($expected, $this->model->getDayOfWeekFromDate($date, $timezone));
    }

    public function testGetDayOfWeekFromTimestamp()
    {
        $timestamp = 1472014800000;
        $timezone = 'America/New_York';
        $expected = 3;
        $this->assertEquals($expected, $this->model->getDayOfWeekFromTimestamp($timestamp, $timezone));
    }

    public function testGetTimeSlots()
    {
        $calendarDetail = [
            'blackoutDates' => [
                "",
            ],
            "blackoutDays" => [
                0 => 6,
                1 => 7,
            ],
            'timeSlots' =>
                [
                    0 =>
                        [
                            'timeStart' => '09:00:00',
                            'timeEnd' => '17:00:00',
                            'interval' => 240,
                        ],
                ],
            "showDate" => true,
            "maxDays" => 5,
            'start' => 1476338400 / 1000, //1st July 2016
            "startDate" => 1476338400000, //1st July 2016
            "startDateStr" => "13-10-2016 00:00:00 -0600",
            'dateFormat' => 'm/d/y',
            'timezone' => 'America/Boise',
            'default_date_timestamp' => 1476338400  //1st July 2016
        ];
        $selectedDate = '5/4/17';
        $timeZones = $this->model->getDeliveryTimeSlots($calendarDetail, $selectedDate);
        $this->assertCount(2, $timeZones);
    }

    /*
     * Calendar details with time slots for today
     * Verify that some time slots are not available as they are in the past
     * - Need to modify time stamps for today and timezone
     */
    public function testGetTimeSlotsTodayPickup()
    {
        $calendarDetail = [
            'blackoutDates' => [
                "",
            ],
            "blackoutDays" => [
                0 => 7,
            ],
            'timeSlots' =>
                [
                    0 =>
                        array(
                            'timeStart' => '01:00:00',
                            'timeEnd' => '18:00:00',
                            'interval' => 60,
                        ),
                ],
            "showDate" => true,
            "maxDays" => 5,
            'start' => 1494302400000 / 1000, //9th May 2017
            "startDate" => 1494302400000, ///9th May 2017
            "startDateStr" => "09-05-2017 00:00:00 -0400",
            'dateFormat' => 'm/d/y',
            'timezone' => 'Europe/London',
            'default_date_timestamp' => 1494320000 //09 May 2017 4:53am
        ];
        $selectedDate = '05/09/17';
        $timeZones = $this->model->getDeliveryTimeSlots($calendarDetail, $selectedDate);
//        if($timeZones) {
//            foreach($timeZones as $key => $display){
//                echo $display;
//                echo ' | ';
//            }
//        }
//        else {
//            echo '          no $timeZones returned';
//        }
        $this->assertCount(8, $timeZones);
    }

    /*
    * Calendar details with time slots including 24 hour lead time
     * Date selected is first available date so includes this lead time
     * Not all time slots will be available
     * -  Need to modify time stamps for tomorrow and timezone
    */
    public function testGetTimeSlotsTomorrowWithLeadTimePickup()
    {
        $calendarDetail = [
            'blackoutDates' => [
                "",
            ],
            "blackoutDays" => [
                0 => 7,
            ],
            'timeSlots' =>
                [
                    0 =>
                        array(
                            'timeStart' => '01:00:00',
                            'timeEnd' => '18:00:00',
                            'interval' => 60,
                        ),
                ],
            "showDate" => true,
            "maxDays" => 5,
            'start' => 1496332800000 / 1000, //1st June 2017
            "startDate" => 1496332800000, ///1st June 2017
            "startDateStr" => "02-06-2017 00:00:00 +0800",
            'dateFormat' => 'm/d/y',
            'timezone' => 'Australia/Perth',
            'default_date_timestamp' => 1496370180 //2 June 2017 10:23am
        ];
        $selectedDate = '06/02/17';
        $timeZones = $this->model->getDeliveryTimeSlots($calendarDetail, $selectedDate);
//        if($timeZones) {
//            foreach($timeZones as $key => $display){
//                echo $display;
//                echo ' | ';
//            }
//        }
//        else {
//            echo '          no $timeZones returned';
//        }
        $this->assertCount(7, $timeZones);
    }

    /**
     * Calendar details with time slots including 24 hour lead time
     * Date selected is in future
     * Should return all available time slots as date is future
     * -  Need to modify time stamps for tomorrow and timezone
     */
    public function testGetTimeSlotsDateInFutureLeadTimeInHours()
    {
        $calendarDetail = [
            'blackoutDates' => [
                "",
            ],
            "blackoutDays" => [
                0 => 7,
            ],
            'timeSlots' =>
                [
                    0 =>
                        array(
                            'timeStart' => '01:00:00',
                            'timeEnd' => '18:00:00',
                            'interval' => 60,
                        ),
                ],
            "showDate" => true,
            "maxDays" => 5,
            'start' => 1496332800000 / 1000, //2nd June 2017
            "startDate" => 1496332800000, ///2nd June 2017
            "startDateStr" => "02-06-2017 00:00:00 +0800",
            'dateFormat' => 'm/d/y',
            'timezone' => 'Australia/Perth',
            'default_date_timestamp' => 1496361180 //2 June 2017 10:23am
        ];
        $selectedDate = '06/02/17';
        $timeZones = $this->model->getDeliveryTimeSlots($calendarDetail, $selectedDate);
//        if($timeZones) {
//            foreach($timeZones as $key => $display){
//                echo $display;
//                echo ' | ';
//            }
//        }
//        else {
//            echo '          no $timeZones returned';
//        }
        $this->assertCount(10, $timeZones);
        $dayBefore = '06/01/17';
        $dayAfter = '06/03/17';
        $generatedDayBeforeTimeSlots = $this->model->getDeliveryTimeSlots($calendarDetail, $dayBefore);
        $this->assertCount(17, $generatedDayBeforeTimeSlots, ' Time slots for 1st June returned incorrectly');

        $generatedDayAfterTimeSlots = $this->model->getDeliveryTimeSlots($calendarDetail, $dayAfter);
        $this->assertCount(17, $generatedDayAfterTimeSlots, ' Time slots for 3rd June returned incorrectly');

    }

    public function testGetTimeSlotsPickupCarrierNewYorkTimeZone()
    {
        $calendarDetail = [
            'blackoutDates' => [
                "",
            ],
            "blackoutDays" => [],
            'timeSlots' =>
                [
                    0 =>
                        array(
                            'timeStart' => '06:00:00',
                            'timeEnd' => '17:30:00',
                            'interval' => 60,
                        ),
                ],
            "showDate" => true,
            "maxDays" => 5,
            'start' => 1507780800000 / 1000, //12-10-2017 00:00:00 -0400
            "startDate" => 1507780800, ///12th October 2017
            "startDateStr" => "12-10-2017 00:00:00 -0400",
            'dateFormat' => 'm/d/y',
            'timezone' => 'America/New_York',
            'default_date_timestamp' => 1508371200 //19 October 2017 00:00:00 GMT,
        ];
        $selectedDate = '10/19/17';
        $generatedTimeSlots = $this->model->getDeliveryTimeSlots($calendarDetail, $selectedDate);
//        if($generatedTimeSlots) {
//            foreach($generatedTimeSlots as $key => $display){
//                echo $display;
//                echo ' | ';
//            }
//        }
//        else {
//            echo '          no $generatedTimeSlots returned';
//        }
        $this->assertCount(12, $generatedTimeSlots, ' Time slots for 19 Oct returned incorrectly');

        //check the day before and the day after just in case the calculated date based on the default date is wrong!
        $dayBefore = '10/18/17';
        $dayAfter = '10/20/17';
        $generatedDayBeforeTimeSlots = $this->model->getDeliveryTimeSlots($calendarDetail, $dayBefore);
        $this->assertCount(12, $generatedDayBeforeTimeSlots, ' Time slots for 18 Oct returned incorrectly');

        $generatedDayAfterTimeSlots = $this->model->getDeliveryTimeSlots($calendarDetail, $dayAfter);
        $this->assertCount(12, $generatedDayAfterTimeSlots, ' Time slots for 18 Oct returned incorrectly');

    }

    public function testGetTimeSlotsPickupCarrierHawaii()
    {
        $calendarDetail = [
            'blackoutDates' => [
                "",
            ],
            "blackoutDays" => [],
            'timeSlots' =>
                [
                    0 =>
                        array(
                            'timeStart' => '06:00:00',
                            'timeEnd' => '17:30:00',
                            'interval' => 60,
                        ),
                ],
            "showDate" => true,
            "maxDays" => 5,
            'start' => 1507780800000 / 1000, //12-10-2017 00:00:00 -0400
            "startDate" => 1507780800, ///12th October 2017
            "startDateStr" => "12-10-2017 00:00:00 -0400",
            'dateFormat' => 'm/d/y',
            'timezone' => 'America/Adak', //Hawaii - 10 hours behind GMT
            'default_date_timestamp' => 1508371200, //19 October 2017 00:00:00 GMT,
            'default_date' => '10/19/2017' //this is the selected date
        ];
        $selectedDate = '10/19/17';
        $generatedTimeSlots = $this->model->getDeliveryTimeSlots($calendarDetail, $selectedDate);
        if($generatedTimeSlots) {
            foreach($generatedTimeSlots as $key => $display){
                echo $display;
                echo ' | ';
            }
        }
        else {
            echo '          no $generatedTimeSlots returned';
        }
        $this->assertCount(12, $generatedTimeSlots);
    }

//    public function testGetCurrentDate()
//    {
//        $dateFormat =  'm/d/y';
//        $timezone = 'America/Boise';
//        $currentDate = $this->model->getCurrentDate($timezone, $dateFormat);
//        echo '         Get Current Date: ';
//        echo $currentDate;
//
//        $this->assertEquals('10/13/16', $currentDate);
//    }
}
