<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 *
 * TO RUN FROM BASE MAGENTO DIR
 * ./vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist vendor/shipperhq/module-common/test/
 */

namespace ShipperHQ\Common\Test\Unit\Model\Calendar;

use Fooman\PhpunitBridge\BaseUnitTestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class CalendarTest extends BaseUnitTestCase
{
    /**
     * @var \ShipperHQ\Shipper\Model\Backend\Config\Source\EnvironmentScope
     */
    protected $model;

    protected $localeDate;

    protected function setUp(): void
    {
        $helper = new ObjectManager($this);

        $this->checkoutServiceMock = $this->getMockBuilder('\ShipperHQ\Common\Model\Checkout\Service')
            ->disableOriginalConstructor()
            ->getMock();
        $this->localeDateMock = $this->getMockBuilder('\Magento\Framework\Stdlib\DateTime\TimezoneInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = $helper->getObject(
            'ShipperHQ\Common\Model\Calendar',
            [
                'localeDate' => $this->localeDateMock,
                'checkoutService' => $this->checkoutServiceMock
            ]
        );
    }


    /**
     * TESTS FOR UNDERLYING DATE CALCULATIONS
     *   - test getting a date in specified format usign a timestamp is correct for specified time zone
     */
    public function testgetDateFromTimestamp()
    {
        $start = 1475726400000 / 1000;
        $timeInfo = date("m/d/y h:i:s a", $start);

        $timezone = 'America/Boise';
        $return = $this->model->getDateFromTimestamp($start, $timezone, 'm/d/y');
        $this->assertEquals('10/05/16', $return);
    }

    /**
     * TESTS FOR UNDERLYING DATE CALCULATIONS
     *   - test getting a date in specified format usign an input date
     */
    public function testDateReturnedIsTheSame()
    {
        $date = '07/15/16';
        $timezone = 'America/New_York';

        $return = $this->model->getDateFromDate($date, $timezone, 'm/d/y');
        $this->assertEquals($date, $return);
    }

    /**
     * TESTS FOR UNDERLYING DATE CALCULATIONS
     *   - test getting a day of the week using an input date
     */
    public function testGetDayOfWeekFromDate()
    {
        $date = '10/23/16 12:00'; // SHQ18-104 - Added time as previously timezone converstion tossed into prev day
        $timezone = 'America/Boise';
        $expected = 7;
        $this->assertEquals($expected, $this->model->getDayOfWeekFromDate($date, $timezone));
    }

    /**
     * TESTS FOR UNDERLYING DATE CALCULATIONS
     *   - test getting a day of the week using a timestamp
     */
    public function testGetDayOfWeekFromTimestamp()
    {
        $timestamp = 1472014800000;
        $timezone = 'America/New_York';
        $expected = 3;
        $this->assertEquals($expected, $this->model->getDayOfWeekFromTimestamp($timestamp, $timezone));
    }

    /**
     * BASIC TESTS
     * Using simple calendar details with Saturday and Sunday as blackout delivery days, ensure calculated date options
     * do not include any Saturdays or Sundays
     * Also ensures that even though specified number of days is 5, since Saturday and Sunday are excluded the resulting
     * date options are only 3 days - this is correct behaviour
     */
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

    /**
     * BASIC TESTS
     * Test when all days of the week are black out days, then no date options are found
     */
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
                1,
                2,
                3,
                4,
                5,
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

    /**
     * BASIC TESTS
     * Test that a black out date is excluded from the calculated date options
     *
     */
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


    /**
     * BASIC TESTS
     * Using basic calendar details, test that correct number of time slots are returned
     */
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
            'start' => 1476338400, //1st July 2016
            "startDate" => 1476338400000, //1st July 2016
            "startDateStr" => "13-10-2016 00:00:00 -0600",
            'dateFormat' => 'm/d/y',
            'timezone' => 'America/Boise',
            'default_date_timestamp' => 1476338400,  //1st July 2016
            'default_date' => '13-10-2017' //this is the selected date
        ];
        $selectedDate = '5/4/17';
        $timeZones = $this->model->getDeliveryTimeSlots($calendarDetail, $selectedDate);
        $this->assertCount(2, $timeZones);
    }

    /**
     * BASIC TESTS
     * Using basic calendar details, make sure timeslots start/end in all the right places
     */
    public function testGetTimeSlotsBounds()
    {
        $calendarDetail = [
            'blackoutDates' => [
                "",
            ],
            "blackoutDays" => [
            ],
            'timeSlots' =>
                [
                    0 =>
                        [
                            'timeStart' => '00:00:00',
                            'timeEnd' => '01:00:00',
                            'interval' => 45,
                        ],
                ],
            "showDate" => true,
            "maxDays" => 5,
            'start' => 1476338400, //1st July 2016
            "startDate" => 1476338400000, //1st July 2016
            "startDateStr" => "13-10-2016 00:00:00 -0600",
            'dateFormat' => 'm/d/y',
            'timezone' => 'America/Boise',
            'default_date_timestamp' => 1476338400,  //1st July 2016
            'default_date' => '13-10-2017' //this is the selected date
        ];
        $selectedDate = '5/4/17';
        $timeSlots = $this->model->getDeliveryTimeSlots($calendarDetail, $selectedDate);
        $this->assertIsArray($timeSlots);
        $this->assertCount(2, $timeSlots);
        $this->assertEquals("00:00 - 00:45", array_shift($timeSlots));
        $this->assertEquals("00:45 - 01:00", array_shift($timeSlots));
    }

    /**
     * BASIC TESTS
     * Using basic calendar details, test ending timeslot of midnight
     */
    public function testGetTimeSlotsEndingMidnight()
    {
        $calendarDetail = [
            'blackoutDates' => [
                "",
            ],
            "blackoutDays" => [
            ],
            'timeSlots' =>
                [
                    0 =>
                        [
                            'timeStart' => '18:00:00',
                            'timeEnd' => '00:00:00',
                            'interval' => 60,
                        ],
                ],
            "showDate" => true,
            "maxDays" => 5,
            'start' => 1476338400, //1st July 2016
            "startDate" => 1476338400000, //1st July 2016
            "startDateStr" => "13-10-2016 00:00:00 -0600",
            'dateFormat' => 'm/d/y',
            'timezone' => 'America/Boise',
            'default_date_timestamp' => 1476338400,  //1st July 2016
            'default_date' => '13-10-2017' //this is the selected date
        ];
        $selectedDate = '5/4/17';
        $timeSlots = $this->model->getDeliveryTimeSlots($calendarDetail, $selectedDate);
        $this->assertIsArray($timeSlots);
        $this->assertCount(6, $timeSlots);
        $this->assertEquals("23:00 - 24:00", array_pop($timeSlots));
    }

    /**
     * BASIC TESTS
     * Using basic calendar details, test that timeslots that would carry into the next day are clamped to midnight
     */
    public function testGetTimeSlotsOvernight()
    {
        $calendarDetail = [
            'blackoutDates' => [
                "",
            ],
            "blackoutDays" => [
            ],
            'timeSlots' =>
                [
                    0 =>
                        [
                            'timeStart' => '18:30:00',
                            'timeEnd' => '02:30:00',
                            'interval' => 60,
                        ],
                ],
            "showDate" => true,
            "maxDays" => 5,
            'start' => 1476338400, //1st July 2016
            "startDate" => 1476338400000, //1st July 2016
            "startDateStr" => "13-10-2016 00:00:00 -0600",
            'dateFormat' => 'm/d/y',
            'timezone' => 'America/Boise',
            'default_date_timestamp' => 1476338400,  //1st July 2016
            'default_date' => '13-10-2017' //this is the selected date
        ];
        $selectedDate = '5/4/17';
        $timeSlots = $this->model->getDeliveryTimeSlots($calendarDetail, $selectedDate);
        $this->assertIsArray($timeSlots);
        $this->assertCount(6, $timeSlots);
        $this->assertEquals("18:30 - 19:30", array_shift($timeSlots));
        $this->assertEquals("23:30 - 24:00", array_pop($timeSlots));
    }

    /**
     * BASIC TESTS
     * Using basic calendar details, test timeslots available all day
     */
    public function testGetTimeSlotsAllDay()
    {
        $calendarDetail = [
            'blackoutDates' => [
                "",
            ],
            "blackoutDays" => [
            ],
            'timeSlots' =>
                [
                    0 =>
                        [
                            'timeStart' => '00:00:00',
                            'timeEnd' => '00:00:00',
                            'interval' => 30,
                        ],
                ],
            "showDate" => true,
            "maxDays" => 5,
            'start' => 1476338400, //1st July 2016
            "startDate" => 1476338400000, //1st July 2016
            "startDateStr" => "13-10-2016 00:00:00 -0600",
            'dateFormat' => 'm/d/y',
            'timezone' => 'America/Boise',
            'default_date_timestamp' => 1476338400,  //1st July 2016
            'default_date' => '13-10-2017' //this is the selected date
        ];
        $selectedDate = '5/4/17';
        $timeSlots = $this->model->getDeliveryTimeSlots($calendarDetail, $selectedDate);
        $this->assertIsArray($timeSlots);
        $this->assertCount(48, $timeSlots);
        $this->assertEquals("00:00 - 00:30", array_shift($timeSlots));
        $this->assertEquals("23:30 - 24:00", array_pop($timeSlots));
    }

    /**
     * BASIC TESTS
     * Using basic calendar details, test interval = 2 returns a half day timeslot
     */
    public function testGetTimeSlotsHalfDay()
    {
        $calendarDetail = [
            'blackoutDates' => [
                "",
            ],
            "blackoutDays" => [
            ],
            'timeSlots' =>
                [
                    0 =>
                        [
                            'timeStart' => '08:00:00',
                            'timeEnd' => '17:00:00',
                            'interval' => 2,
                        ],
                ],
            "showDate" => true,
            "maxDays" => 5,
            'start' => 1476338400, //1st July 2016
            "startDate" => 1476338400000, //1st July 2016
            "startDateStr" => "13-10-2016 00:00:00 -0600",
            'dateFormat' => 'm/d/y',
            'timezone' => 'America/Boise',
            'default_date_timestamp' => 1476338400,  //1st July 2016
            'default_date' => '13-10-2017' //this is the selected date
        ];
        $selectedDate = '5/4/17';
        $timeSlots = $this->model->getDeliveryTimeSlots($calendarDetail, $selectedDate);
        $this->assertIsArray($timeSlots);
        $this->assertCount(2, $timeSlots);
        $this->assertArrayHasKey("08:00_12:30", $timeSlots);
        $this->assertEquals("08:00 - 12:30", $timeSlots['08:00_12:30']);
    }

    /**
     *
     * Calendar details with time slots for today
     * This test is calculating the time slots for today, the current exact time is BEFORE the first available start
     * time
     * Verify that some time slots are not available as they are in the past
     *
     * - Automatic Setup
     * Step 1: set the current time stamp right now , calculate the end time stamp based on todays date
     * Step 2: set date with 2 digit year (mm/dd/yy) into variable below for step 3
     * Step 3: enter the correct number of hour time slots expected, from the time right now in GMT plus 3 hours until 5pm start
     * e.g. time now is 1:51 AM, 6 hours is 7:51 AM - first slot is 8 am = 9 time slots
     */
    public function testGetTimeSlotsTodayPickup()
    {
        $timeNow = new \DateTime('now', new \DateTimeZone('Europe/London'));
        //step 1 setup
        $currentTimeStampRightNow = $timeNow->getTimestamp(); //Friday, March 9, 2018 9:31:07 PM GMT
        $currentDate = $timeNow->format('Y-m-d');
        $timeOfStart = '01:00:00';
        $timeOfEnd = '17:00:00';
        $currentDateAtEnd = new \DateTime($currentDate .' ' .$timeOfEnd, new \DateTimeZone('Europe/London'));
        $endTimeStamp = $currentDateAtEnd->getTimestamp();
        //step 2
        $todaysDate_in_month_day_shortyear = $timeNow->format('m/d/y');
        //step 3 calculate available slots
        $correctAnswer = 0; //just in case the tests are run where the current time + 6 hours is after the end time
        $rightNowPlus6Hours = $currentTimeStampRightNow + 21600; //Friday, March 10, 2018 13:31:07 AM !! NEXT DAY

        $availableTimeForTimeSlots = $endTimeStamp - $rightNowPlus6Hours;
        if($availableTimeForTimeSlots > 0) {
            $correctAnswerFloat = floor($availableTimeForTimeSlots/3600); //hard coded value since we are using 1 hour time slots
            $correctAnswer = (int)$correctAnswerFloat;
        }

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
                        [
                            'timeStart' => $timeOfStart, //'01:00:00',
                            'timeEnd' => $timeOfEnd, //'17:00:00',
                            'interval' => 60,
                        ],
                ],
            "showDate" => true,
            "maxDays" => 5,
            'start' => $rightNowPlus6Hours,
            "startDate" => $rightNowPlus6Hours,
            "startDateStr" => "03-09-2018 3:07:18 -0600",
            'dateFormat' => 'm/d/y',
            'timezone' => 'Europe/London',
            'default_date_timestamp' => $currentTimeStampRightNow,
            'default_date' => $todaysDate_in_month_day_shortyear //this is the selected date
        ];
        $selectedDate = $todaysDate_in_month_day_shortyear;
        $timeSlots = $this->model->getDeliveryTimeSlots($calendarDetail, $selectedDate);

        if($availableTimeForTimeSlots > 0) {
            $this->assertIsArray($timeSlots);
            $this->assertCount($correctAnswer, $timeSlots);
        } else {
            $this->assertIsBool($timeSlots);
        }
    }

    /**
     * Calendar details with time slots including 24 hour lead time
     * Date selected is first available date so includes this lead time - i.e. tomorrow
     * Not all time slots will be available
     *
     * TO SETUP THIS TEST
     *  * Use Epoch Time Converter
     * https://www.epochconverter.com
     * Step 1: set the current time stamp right now into variable below for step 1
     * Step 2: set tomorows date with 2 digit year (mm/dd/yy) into variable below for step 2
     * Step 3: enter the correct number of hour time slots expected, from the time tomorrow in Perth time until 5pm
     *         start
     * e.g. time tomorrow is 9:51 AM, therefore first slot is 10 am = 8 time slots
     *
     */
    public function testGetTimeSlotsTomorrowWithLeadTimePickup()
    {
        $timeNow = new \DateTime('now', new \DateTimeZone('Europe/London'));
        //step 1
        $currentTimeStampRightNow = $timeNow->getTimestamp(); //Friday, March 9, 2018 9:34:40 PM GMT
        $tomorrow = new \DateTime('tomorrow',  new \DateTimeZone('Europe/London'));
        $tomorrowsDate = $tomorrow->format('Y-m-d');
        $timeOfStart = '01:00:00';
        $timeOfEnd = '17:00:00';
        $currentDateAtEnd = new \DateTime($tomorrowsDate .' ' .$timeOfEnd, new \DateTimeZone('Europe/London'));
        $endTimeStamp = $currentDateAtEnd->getTimestamp();

        //step 2
        $tomorrowsDate_in_month_day_shortyear = $timeNow->modify('+ 1 day')->format('m/d/y');
        //step 3
        $rightNowPlus24Hours = $currentTimeStampRightNow + 86400; //Friday, March 10, 2018 9:34:40 PM GMT
        $correctAnswer = 0; //just in case the tests are run where the current time + 6 hours is after the end time

        $availableTimeForTimeSlots = $endTimeStamp - $rightNowPlus24Hours;

        if($availableTimeForTimeSlots > 0) {
            $correctAnswerFloat = floor($availableTimeForTimeSlots/3600); //hard coded value since we are using 1 hour time slots
            $correctAnswer = (int)$correctAnswerFloat;
        }

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
                        [
                            'timeStart' => $timeOfStart, //'01:00:00',
                            'timeEnd' => $timeOfEnd,//'17:00:00',
                            'interval' => 60,
                        ],
                ],
            "showDate" => true,
            "maxDays" => 5,
            'start' => $rightNowPlus24Hours,
            "startDate" => $rightNowPlus24Hours,
            "startDateStr" => "02-06-2017 00:00:00 +0800",
            'dateFormat' => 'm/d/y',
            'timezone' => 'Europe/London',
            'default_date_timestamp' => $rightNowPlus24Hours,
            'default_date' => $tomorrowsDate_in_month_day_shortyear
        ];
        $selectedDate = $tomorrowsDate_in_month_day_shortyear;
        $timeZones = $this->model->getDeliveryTimeSlots($calendarDetail, $selectedDate);

        if($availableTimeForTimeSlots > 0) {
            $this->assertIsArray($timeZones);
            $this->assertCount($correctAnswer, $timeZones);
        } else {
            $this->assertIsBool($timeZones);
        }
    }

    /**
     * Calendar details with time slots including 24 hour lead time
     * Date selected is in future
     * Should return all available time slots as date is future
     *
     * i.e. Default date timestamp is 2nd June at 10:23am
     * Test the selected date of 2nd June - returns 10 time slots
     * Test selected date of day before 1st June 2017
     * Test selected date of day after 3rd June 2017
     *
     * *
     *  * Use Epoch Time Converter
     * https://www.epochconverter.com
     * Step 1: set the current time stamp right now into variable below for step 1
     * Step 2: set tomorows date with 2 digit year (mm/dd/yy) into variable below for step 2
     * Step 3: enter the correct number of hour time slots expected, from the time tomorrow in Perth time until 5pm
     *         start
     * e.g. time tomorrow is 9:51 AM, therefore first slot is 10 am = 8 time slots
     * Step 4: set date for day after tomorrow into variable for step 4 (2 days from today)
     * Step 5: set date for three days from today for step 5
     *
     */
    public function testGetTimeSlotsDateInFutureLeadTimeInHours()
    {
        //$this->markTestSkipped('MNB-1797 Logic moved into the WebService');
        $timeZone =  new \DateTimeZone('Australia/Perth');
        //step 1
        $timeNow = new \DateTime('now',$timeZone);
        $currentTimeStampRightNow = $timeNow->getTimestamp(); //Friday, March 9, 2018 9:34:40 PM GMT

        //step 2
//        $tomorrowsDate_in_month_day_shortyear = '03/10/18';
        $tomorrow = new \DateTime('tomorrow', $timeZone);
        $tomorrowsDate = $tomorrow->format('Y-m-d');
        $tomorrowsDate_in_month_day_shortyear = $tomorrow->format('m/d/Y');
        $timeOfStart = '01:00:00';
        $timeOfEnd = '17:00:00';
        $currentDateAtEnd = new \DateTime($tomorrowsDate .' ' .$timeOfEnd, $timeZone);
        $endTimeStamp = $currentDateAtEnd->getTimestamp();

        $rightNowPlus24Hours = $currentTimeStampRightNow + 86400; //Friday, March 10, 2018 3:19:52 PM GMT-06:00

        //step 3
        $correctAnswer = 0;
        $availableTimeForTimeSlots = $endTimeStamp - $rightNowPlus24Hours;
        if($availableTimeForTimeSlots > 0) {
            $correctAnswerFloat = floor($availableTimeForTimeSlots/3600); //hard coded value since we are using 1 hour time slots
            $correctAnswer = (int)$correctAnswerFloat;
        }

        //step 4
        $datePlusTwo = new \DateTime('now', $timeZone);
        $datePlusTwo->modify('+2 day');
        $twoDaysFromNow =  $datePlusTwo->format('Y-m-d');

        //step5
        $datePlusThree = new \DateTime('now', $timeZone);
        $datePlusThree->modify('+3 day');
        $threeDaysFromNow =  $datePlusThree->format('Y-m-d');

        $calendarDetail = [
            'blackoutDates' => [
                "",
            ],
            "blackoutDays" => [
                "",
            ],
            'timeSlots' =>
                [
                    0 =>
                        [
                            'timeStart' => '01:00:00',
                            'timeEnd' => '17:00:00',
                            'interval' => 60,
                        ],
                ],
            "showDate" => true,
            "maxDays" => 5,
            'start' => $rightNowPlus24Hours, //2nd June 2017
            "startDate" => $rightNowPlus24Hours, ///2nd June 2017
            "startDateStr" => "02-06-2017 00:00:00 +0800",
            'dateFormat' => 'm/d/y',
            'timezone' => $timeZone->getName(), //'Australia/Perth'
            'default_date_timestamp' => $rightNowPlus24Hours, //2 June 2017 10:23am
            'default_date' => $tomorrowsDate_in_month_day_shortyear
        ];
        $selectedDate = $tomorrowsDate_in_month_day_shortyear;
        $timeZones = $this->model->getDeliveryTimeSlots($calendarDetail, $selectedDate);
        if($availableTimeForTimeSlots > 0) {
            $this->assertIsArray($timeZones);
            $this->assertCount($correctAnswer, $timeZones);
            $nextDayTimeSlots = $this->model->getDeliveryTimeSlots($calendarDetail, $twoDaysFromNow);
            $this->assertCount(16, $nextDayTimeSlots, ' Time slots for two days returned incorrectly');
            $threeDaysTimeSlots = $this->model->getDeliveryTimeSlots($calendarDetail, $threeDaysFromNow);
            $this->assertCount(16, $threeDaysTimeSlots, ' Time slots for three days returned incorrectly');
        } else {
            $this->assertIsBool($timeZones);
        }
    }

    /**
     * Calendar details with time slots in New York timezone - to ensure calculations work for time zones behind GMT
     * Date selected is in future
     * Should return all available time slots as date is future
     */
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
                        [
                            'timeStart' => '06:00:00',
                            'timeEnd' => '17:30:00',
                            'interval' => 60,
                        ],
                ],
            "showDate" => true,
            "maxDays" => 5,
            'start' => 1507780800000 / 1000, //12-10-2017 00:00:00 -0400
            "startDate" => 1507780800, ///12th October 2017
            "startDateStr" => "12-10-2017 00:00:00 -0400",
            'dateFormat' => 'm/d/y',
            'timezone' => 'America/New_York',
            'default_date_timestamp' => 1508371200, //19 October 2017 00:00:00 GMT,
            'default_date' => '10/19/2017' //this is the selected date
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

    /**
     * Calendar details with time slots in Hawaii timezone - to ensure calculations work for time zones behind GMT
     * Date selected is in future
     * Should return all available time slots as date is future
     */
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
                        [
                            'timeStart' => '06:00:00',
                            'timeEnd' => '17:30:00',
                            'interval' => 60,
                        ],
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
//        if($generatedTimeSlots) {
//            foreach($generatedTimeSlots as $key => $display){
//                echo $display;
//                echo ' | ';
//            }
//        }
//        else {
//            echo '          no $generatedTimeSlots returned';
//        }
        $this->assertCount(12, $generatedTimeSlots);
    }

    /**
     *
     * Calendar details with time slots including 24 hour lead time
     * Date selected is in future
     * Should return all available time slots as date is future
     *
     * i.e. Default date timestamp is 2nd June at 10:23am
     * Test the selected date of 2nd June - returns 10 time slots
     * Test selected date of day before 1st June 2017
     * Test selected date of day after 3rd June 2017
     *
     *  * Use Epoch Time Converter
     * https://www.epochconverter.com
     * Step 1: set the start date timestamp to be the tomorrows date at 12 am (note this is in milliseconds)
     * Step 2: set requested date timestamp to be some time in afternoon tomorrow (3pm) - note this is epoch timestamp
     * Step 3: enter the date for day after tomorrow in dd-mm-yyyy
     */
    public function testGetCalendarDetails()
    {
        //step 1
        $timezone = new \DateTimeZone('Europe/London');
        $startDate = new \DateTime('tomorrow', $timezone);
        $startDateTimestamp = $startDate->getTimestamp() *1000;

        //step 2
        $startDate->setTime(3,0);
        $requestedDateTimeStamp = $startDate->getTimestamp();

        //step 3
        $correctAnswer = $startDate->format('d-m-Y');

        $calendarDetails = [
            "blackoutDates" => [
                "2016-12-25",
                "2016-12-26",
                "2017-01-01",
                "2017-04-14",
                "2017-04-16",
                "2017-04-17",
                "2017-05-01",
                "2017-05-29",
                "2017-12-25",
                "2017-12-26"
            ],
            "blackoutDays" => [7],
            "timeSlots" => [
                [
                    "timeStart" => "09:00:00",
                    "timeEnd" => "17:00:00",
                    "interval" => 240
                ]
            ],
            "showDate" => true,
            "maxDays" => 90,
            "startDate" => $startDateTimestamp,
            "startDateStr" => "08-11-2017 00:00:00 +0000",
            "calendarEndDate" => null,
            "calendarEndDateStr" => ""
        ];

        $carrierGroupDetail = [
            "emailOption" => null,
            "checkoutDescription" => "The Gherkin Kitchen",
            "name" => "The Gherkin Kitchen",
            "carrierGroupId" => "7867",
            "dateFormat" => "mm/dd/yyyy",
            "emailAddress" => null,
            "contactName" => null,
            "packingStation" => null,
            "timezone" => 'Europe/London'
        ];
        $carrierId = 1;
        $carrierCode = 'calendartest';
        $locale = 'en-GB';
        $deliveryDateFormat = 'yMd';
        $defaultDate = $requestedDateTimeStamp;
        $generatedCalendarDetailsArray = $this->model->getCalendarDetailsArray(
            $calendarDetails,
            $carrierGroupDetail,
            $carrierId,
            $carrierCode,
            $locale,
            $deliveryDateFormat,
            $defaultDate
        );

        $this->assertTrue($generatedCalendarDetailsArray['default_date'] == $correctAnswer);
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
