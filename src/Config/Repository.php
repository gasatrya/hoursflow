<?php
namespace OpenNow\Config;

defined('ABSPATH') || exit;

/**
 * Read-only runtime view of the persisted configuration.
 */
final class Repository
{
    /**
     * Return a deterministic, safely revalidated runtime configuration.
     *
     * This method never repairs or writes the stored option.
     *
     * @return array<string, mixed>
     */
    public function getRuntimeConfig()
    {
        $stored = \get_option(Schema::OPTION_NAME, false);
        $runtime = Schema::runtimeDefaults();

        if (!is_array($stored)) {
            return $runtime;
        }

        if (array_key_exists('timezone', $stored)) {
            $runtime['timezone'] = Validator::canonicalTimezone($stored['timezone']);
        }

        $raw_schedule = array_key_exists('schedule', $stored) && is_array($stored['schedule'])
            ? $stored['schedule']
            : array();

        foreach (Schema::days() as $day) {
            $entry = array_key_exists($day, $raw_schedule)
                ? Validator::canonicalScheduleEntry($raw_schedule[$day])
                : null;
            $runtime['schedule'][$day] = null === $entry
                ? Schema::closedScheduleEntry()
                : $entry;
        }

        $raw_cta = array_key_exists('cta', $stored) && is_array($stored['cta'])
            ? $stored['cta']
            : array();

        foreach (array('open', 'closed') as $state) {
            $runtime['cta'][$state] = array_key_exists($state, $raw_cta)
                ? Validator::canonicalCtaState($raw_cta[$state])
                : null;
        }

        $raw_appearance = array_key_exists('appearance', $stored) && is_array($stored['appearance'])
            ? $stored['appearance']
            : array();
        $background = array_key_exists('background_color', $raw_appearance)
            ? $raw_appearance['background_color']
            : null;
        $text = array_key_exists('text_color', $raw_appearance)
            ? $raw_appearance['text_color']
            : null;

        $runtime['appearance'] = Color::effectivePair($background, $text);

        return $runtime;
    }

    /**
     * @return array<string, mixed>
     */
    public function get()
    {
        return $this->getRuntimeConfig();
    }

    /**
     * Snake-case alias for integrations that use WordPress-style method names.
     *
     * @return array<string, mixed>
     */
    public function get_runtime_config()
    {
        return $this->getRuntimeConfig();
    }
}
