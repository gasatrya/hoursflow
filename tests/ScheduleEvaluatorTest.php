<?php
namespace HoursFlow\Tests;

use HoursFlow\Config\Schema;
use HoursFlow\Schedule\Evaluator;
use PHPUnit\Framework\TestCase;

final class ScheduleEvaluatorTest extends TestCase
{
    protected function setUp(): void
    {
        hoursflow_reset_wp_stubs();
    }

    public function testSameDayPeriodsAreHalfOpenAtMinutePrecision(): void
    {
        $schedule = $this->schedule(array(
            'monday' => array(
                'type' => 'period',
                'opens' => '09:00',
                'closes' => '17:00',
            ),
        ));

        $this->assertFalse($this->evaluatorAt('2024-01-08 08:59:59')->isOpen($schedule, 'UTC'));
        $this->assertTrue($this->evaluatorAt('2024-01-08 09:00:00')->isOpen($schedule, 'UTC'));
        $this->assertTrue($this->evaluatorAt('2024-01-08 16:59:59')->isOpen($schedule, 'UTC'));
        $this->assertFalse($this->evaluatorAt('2024-01-08 17:00:00')->isOpen($schedule, 'UTC'));
    }

    public function testClosedDayStartsNoPeriod(): void
    {
        $schedule = $this->schedule(array(
            'monday' => array(
                'type' => 'period',
                'opens' => '09:00',
                'closes' => '17:00',
            ),
        ));

        $this->assertFalse($this->evaluatorAt('2024-01-09 12:00:00')->isOpen($schedule, 'UTC'));
    }

    public function testOvernightPeriodsOpenOnOpeningDayAndCarryUntilExactClose(): void
    {
        $schedule = $this->schedule(array(
            'monday' => array(
                'type' => 'period',
                'opens' => '22:00',
                'closes' => '02:00',
            ),
        ));

        $this->assertTrue($this->evaluatorAt('2024-01-08 22:00:00')->isOpen($schedule, 'UTC'));
        $this->assertTrue($this->evaluatorAt('2024-01-09 01:59:59')->isOpen($schedule, 'UTC'));
        $this->assertFalse($this->evaluatorAt('2024-01-09 02:00:00')->isOpen($schedule, 'UTC'));
    }

    public function testOverlappingOvernightAndCurrentPeriodsFormAnOpenUnion(): void
    {
        $schedule = $this->schedule(array(
            'tuesday' => array(
                'type' => 'period',
                'opens' => '22:00',
                'closes' => '02:00',
            ),
            'wednesday' => array(
                'type' => 'period',
                'opens' => '01:00',
                'closes' => '03:00',
            ),
        ));

        $this->assertTrue($this->evaluatorAt('2024-01-10 01:30:00')->isOpen($schedule, 'UTC'));
        $this->assertTrue($this->evaluatorAt('2024-01-10 02:00:00')->isOpen($schedule, 'UTC'));
        $this->assertFalse($this->evaluatorAt('2024-01-10 03:00:00')->isOpen($schedule, 'UTC'));
    }

    public function testSundayOvernightCarryCrossesTheDecemberJanuaryWeekRollover(): void
    {
        $schedule = $this->schedule(array(
            'sunday' => array(
                'type' => 'period',
                'opens' => '22:00',
                'closes' => '02:00',
            ),
        ));

        $this->assertTrue($this->evaluatorAt('2023-12-31 23:00:00')->isOpen($schedule, 'UTC'));
        $this->assertTrue($this->evaluatorAt('2024-01-01 01:59:59')->isOpen($schedule, 'UTC'));
        $this->assertFalse($this->evaluatorAt('2024-01-01 02:00:00')->isOpen($schedule, 'UTC'));
    }

    public function testBusinessTimezoneIsIndependentOfProcessAndWordPressTimezone(): void
    {
        $original_timezone = date_default_timezone_get();

        try {
            date_default_timezone_set('Pacific/Auckland');
            $GLOBALS['hoursflow_test_options']['timezone_string'] = 'Asia/Tokyo';
            $schedule = $this->schedule(array(
                'monday' => array(
                    'type' => 'period',
                    'opens' => '09:00',
                    'closes' => '17:00',
                ),
            ));

            $this->assertTrue(
                $this->evaluatorAt('2024-01-08 14:00:00')->isOpen($schedule, 'America/New_York')
            );
            $this->assertSame('Pacific/Auckland', date_default_timezone_get());
        } finally {
            date_default_timezone_set($original_timezone);
        }

        $this->assertSame($original_timezone, date_default_timezone_get());
    }

    public function testInvalidInputsFailClosedButValidPreviousCarrySurvivesAnInvalidCurrentEntry(): void
    {
        $monday_period = array(
            'type' => 'period',
            'opens' => '09:00',
            'closes' => '17:00',
        );
        $monday = $this->evaluatorAt('2024-01-08 10:00:00');

        $missing_day = $this->schedule();
        unset($missing_day['monday']);
        $this->assertFalse($monday->isOpen($missing_day, 'UTC'));
        $this->assertFalse($monday->isOpen('not a schedule', 'UTC'));
        $this->assertFalse($monday->isOpen($this->schedule(array('monday' => $monday_period)), 'Not/A-Timezone'));
        $this->assertFalse($monday->isOpen($this->schedule(array(
            'monday' => array(
                'type' => 'period',
                'opens' => 'bad',
                'closes' => '17:00',
            ),
        )), 'UTC'));
        $this->assertFalse($monday->isOpen($this->schedule(array(
            'monday' => array(
                'type' => 'period',
                'opens' => '09:00',
                'closes' => '09:00',
            ),
        )), 'UTC'));

        $previous_carry = $this->schedule(array(
            'tuesday' => array(
                'type' => 'period',
                'opens' => '22:00',
                'closes' => '02:00',
            ),
            'wednesday' => array(
                'type' => 'period',
                'opens' => 'invalid',
                'closes' => '03:00',
            ),
        ));
        $this->assertTrue(
            $this->evaluatorAt('2024-01-10 01:00:00')->isOpen($previous_carry, 'UTC')
        );
    }

    public function testInjectedInstantSourceIsCalledOnceAndFailuresCloseSafely(): void
    {
        $calls = 0;
        $source = static function () use (&$calls): \DateTimeInterface {
            $calls++;
            return new \DateTimeImmutable('2024-01-08 10:00:00', new \DateTimeZone('UTC'));
        };
        $schedule = $this->schedule(array(
            'monday' => array(
                'type' => 'period',
                'opens' => '09:00',
                'closes' => '17:00',
            ),
        ));

        $this->assertTrue((new Evaluator($source))->isOpen($schedule, 'UTC'));
        $this->assertSame(1, $calls);

        $invalid_source = new Evaluator(static function () {
            return 'not a date';
        });
        $this->assertFalse($invalid_source->isOpen($schedule, 'UTC'));

        $throwing_source = new Evaluator(static function () {
            throw new \RuntimeException('clock unavailable');
        });
        $this->assertFalse($throwing_source->isOpen($schedule, 'UTC'));
    }

    public function testSpringForwardOpeningInTheGapUsesExistingWallClockMinutes(): void
    {
        $schedule = $this->schedule(array(
            'sunday' => array(
                'type' => 'period',
                'opens' => '02:30',
                'closes' => '04:00',
            ),
        ));

        $this->assertFalse(
            $this->evaluatorAt('2024-03-10 06:59:59')->isOpen($schedule, 'America/New_York')
        );
        $this->assertTrue(
            $this->evaluatorAt('2024-03-10 07:00:00')->isOpen($schedule, 'America/New_York')
        );
        $this->assertFalse(
            $this->evaluatorAt('2024-03-10 08:00:00')->isOpen($schedule, 'America/New_York')
        );
    }

    public function testSpringForwardClosingInTheGapClosesAtTheFirstExistingTimeAfterTheBoundary(): void
    {
        $schedule = $this->schedule(array(
            'sunday' => array(
                'type' => 'period',
                'opens' => '00:30',
                'closes' => '02:30',
            ),
        ));

        $this->assertTrue(
            $this->evaluatorAt('2024-03-10 06:59:59')->isOpen($schedule, 'America/New_York')
        );
        $this->assertFalse(
            $this->evaluatorAt('2024-03-10 07:00:00')->isOpen($schedule, 'America/New_York')
        );
    }

    public function testFallBackRepeatedWallClockTimesHaveTheSameScheduleResult(): void
    {
        $schedule = $this->schedule(array(
            'sunday' => array(
                'type' => 'period',
                'opens' => '01:15',
                'closes' => '01:45',
            ),
        ));

        $this->assertTrue(
            $this->evaluatorAt('2024-11-03 05:30:00')->isOpen($schedule, 'America/New_York')
        );
        $this->assertTrue(
            $this->evaluatorAt('2024-11-03 06:30:00')->isOpen($schedule, 'America/New_York')
        );
        $this->assertFalse(
            $this->evaluatorAt('2024-11-03 05:45:00')->isOpen($schedule, 'America/New_York')
        );
        $this->assertFalse(
            $this->evaluatorAt('2024-11-03 06:45:00')->isOpen($schedule, 'America/New_York')
        );
    }

    /**
     * @param array<string, array<string, string>> $entries
     * @return array<string, array<string, string>>
     */
    private function schedule(array $entries = array()): array
    {
        $schedule = array();
        foreach (Schema::days() as $day) {
            $schedule[$day] = array('type' => 'closed');
        }

        foreach ($entries as $day => $entry) {
            $schedule[$day] = $entry;
        }

        return $schedule;
    }

    private function evaluatorAt(string $instant): Evaluator
    {
        return new Evaluator(static function () use ($instant): \DateTimeInterface {
            return new \DateTimeImmutable($instant, new \DateTimeZone('UTC'));
        });
    }
}
