<?php
namespace OpenNow\Config;

defined('ABSPATH') || exit;

/**
 * The single persisted configuration schema used by OpenNow.
 */
final class Schema
{
    const VERSION = 1;
    const OPTION_NAME = 'opennow_config';
    const SCHEMA_OPTION_NAME = 'opennow_schema_version';
    const DEFAULT_BACKGROUND_COLOR = '#166534';
    const DEFAULT_TEXT_COLOR = '#FFFFFF';
    const MIN_CONTRAST_RATIO = 4.5;

    /**
     * @return array<int, string>
     */
    public static function days()
    {
        return array(
            'monday',
            'tuesday',
            'wednesday',
            'thursday',
            'friday',
            'saturday',
            'sunday',
        );
    }

    /**
     * @return array<string, string>
     */
    public static function closedScheduleEntry()
    {
        return array('type' => 'closed');
    }

    /**
     * @return array<string, string>
     */
    public static function defaultAppearance()
    {
        return array(
            'background_color' => self::DEFAULT_BACKGROUND_COLOR,
            'text_color' => self::DEFAULT_TEXT_COLOR,
        );
    }

    /**
     * Runtime defaults intentionally contain no CTA or timezone configuration.
     *
     * @return array<string, mixed>
     */
    public static function runtimeDefaults()
    {
        $schedule = array();
        foreach (self::days() as $day) {
            $schedule[$day] = self::closedScheduleEntry();
        }

        return array(
            'timezone' => null,
            'schedule' => $schedule,
            'cta' => array(
                'open' => null,
                'closed' => null,
            ),
            'appearance' => self::defaultAppearance(),
        );
    }
}
