<?php
namespace OpenNow\Schedule;

defined( 'ABSPATH' ) || exit;

use OpenNow\Config\Schema;
use OpenNow\Config\Validator;

/**
 * Evaluate a weekly schedule at an injected absolute instant.
 */
final class Evaluator {

	/**
	 * @var callable
	 */
	private $current_instant;

	/**
	 * @param callable|null $current_instant A zero-argument DateTimeInterface source.
	 */
	public function __construct( ?callable $current_instant = null ) {
		$this->current_instant = null === $current_instant
			? static function (): \DateTimeInterface {
				return new \DateTimeImmutable( 'now', new \DateTimeZone( 'UTC' ) );
			}
			: $current_instant;
	}

	/**
	 * Return whether the business is open at the current absolute instant.
	 *
	 * @param mixed $schedule
	 * @param mixed $timezone
	 * @return bool
	 */
	public function isOpen( $schedule, $timezone ): bool {
		if ( ! is_array( $schedule ) ) {
			return false;
		}

		try {
			$canonical_timezone = Validator::canonicalTimezone( $timezone );
			if ( null === $canonical_timezone ) {
				return false;
			}

			$business_timezone = new \DateTimeZone( $canonical_timezone );
			$instant           = call_user_func( $this->current_instant );
			if ( ! $instant instanceof \DateTimeInterface ) {
				return false;
			}

			$immutable_instant = $this->immutableInstant( $instant );
			if ( ! $immutable_instant instanceof \DateTimeImmutable ) {
				return false;
			}

			$local     = $immutable_instant->setTimezone( $business_timezone );
			$days      = Schema::days();
			$day_count = count( $days );
			if ( 0 === $day_count ) {
				return false;
			}

			$day_index = (int) $local->format( 'N' ) - 1;
			if ( ! isset( $days[ $day_index ] ) ) {
				return false;
			}

			$previous_index = ( $day_index - 1 + $day_count ) % $day_count;
			$current_day    = $days[ $day_index ];
			$previous_day   = $days[ $previous_index ];
			$current_entry  = array_key_exists( $current_day, $schedule )
				? Validator::canonicalScheduleEntry( $schedule[ $current_day ] )
				: null;
			$previous_entry = array_key_exists( $previous_day, $schedule )
				? Validator::canonicalScheduleEntry( $schedule[ $previous_day ] )
				: null;
			$time           = $local->format( 'H:i' );

			if ( $this->isCurrentPeriodOpen( $current_entry, $time ) ) {
				return true;
			}

			return $this->isPreviousOvernightCarryOpen( $previous_entry, $time );
		} catch ( \Throwable $exception ) {
			return false;
		}
	}

	/**
	 * Convert a DateTimeInterface to an immutable absolute instant.
	 *
	 * @param \DateTimeInterface $instant
	 * @return \DateTimeImmutable|null
	 */
	private function immutableInstant( \DateTimeInterface $instant ) {
		if ( method_exists( '\DateTimeImmutable', 'createFromInterface' ) ) {
			$immutable = \DateTimeImmutable::createFromInterface( $instant );
			return $immutable instanceof \DateTimeImmutable ? $immutable : null;
		}

		if ( $instant instanceof \DateTimeImmutable ) {
			return $instant;
		}

		$immutable = \DateTimeImmutable::createFromFormat(
			'U.u',
			$instant->format( 'U.u' ),
			new \DateTimeZone( 'UTC' )
		);

		return $immutable instanceof \DateTimeImmutable ? $immutable : null;
	}

	/**
	 * @param mixed  $entry
	 * @param string $time
	 * @return bool
	 */
	private function isCurrentPeriodOpen( $entry, $time ): bool {
		if ( ! is_array( $entry ) || 'period' !== $entry['type'] ) {
			return false;
		}

		if ( $entry['opens'] < $entry['closes'] ) {
			return $time >= $entry['opens'] && $time < $entry['closes'];
		}

		return $time >= $entry['opens'];
	}

	/**
	 * @param mixed  $entry
	 * @param string $time
	 * @return bool
	 */
	private function isPreviousOvernightCarryOpen( $entry, $time ): bool {
		return is_array( $entry )
			&& 'period' === $entry['type']
			&& $entry['opens'] > $entry['closes']
			&& $time < $entry['closes'];
	}
}
