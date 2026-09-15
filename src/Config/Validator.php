<?php
namespace OpenNow\Config;

defined( 'ABSPATH' ) || exit;

/**
 * Strict validator for submitted and stored configuration values.
 */
final class Validator {

	/**
	 * Validate the complete atomic configuration and return its canonical form.
	 *
	 * @param mixed $value
	 * @return array|\WP_Error
	 */
	public static function validate( $value ) {
		$errors = new \WP_Error();

		if ( ! is_array( $value ) ) {
			self::addError(
				$errors,
				'config',
				__( 'The OpenNow configuration must be an array.', 'opennow' )
			);
			return $errors;
		}

		$expected_keys = array( 'timezone', 'schedule', 'cta', 'appearance' );
		if ( ! self::hasExactKeys( $value, $expected_keys ) ) {
			self::addError(
				$errors,
				'config',
				__( 'The OpenNow configuration contains missing or unknown fields.', 'opennow' )
			);
		}

		$canonical = array(
			'timezone'   => null,
			'schedule'   => array(),
			'cta'        => array(
				'open'   => null,
				'closed' => null,
			),
			'appearance' => array(
				'background_color' => '',
				'text_color'       => '',
			),
		);

		if ( ! array_key_exists( 'timezone', $value ) ) {
			self::addError(
				$errors,
				'timezone',
				__( 'A named business timezone is required.', 'opennow' )
			);
		} else {
			$timezone = self::canonicalTimezone( $value['timezone'] );
			if ( null === $timezone ) {
				self::addError(
					$errors,
					'timezone',
					__( 'Enter a valid named business timezone.', 'opennow' )
				);
			} else {
				$canonical['timezone'] = $timezone;
			}
		}

		$schedule = array_key_exists( 'schedule', $value ) ? $value['schedule'] : null;
		if ( ! is_array( $schedule ) ) {
			self::addError(
				$errors,
				'schedule',
				__( 'The weekly schedule must contain one entry for every day.', 'opennow' )
			);
			foreach ( Schema::days() as $day ) {
				$canonical['schedule'][ $day ] = Schema::closedScheduleEntry();
			}
		} else {
			if ( ! self::hasExactKeys( $schedule, Schema::days() ) ) {
				self::addError(
					$errors,
					'schedule',
					__( 'The weekly schedule contains missing or unknown days.', 'opennow' )
				);
			}

			foreach ( Schema::days() as $day ) {
				$field = 'schedule.' . $day;
				if ( ! array_key_exists( $day, $schedule ) ) {
					self::addError(
						$errors,
						$field,
						__( 'Every day needs a valid closed or period entry.', 'opennow' )
					);
					$canonical['schedule'][ $day ] = Schema::closedScheduleEntry();
					continue;
				}

				$entry = self::validateScheduleEntry( $schedule[ $day ], $field, $errors );
				if ( null === $entry ) {
					$canonical['schedule'][ $day ] = Schema::closedScheduleEntry();
				} else {
					$canonical['schedule'][ $day ] = $entry;
				}
			}
		}

		$cta = array_key_exists( 'cta', $value ) ? $value['cta'] : null;
		if ( ! is_array( $cta ) ) {
			self::addError(
				$errors,
				'cta',
				__( 'The CTA settings must contain open and closed states.', 'opennow' )
			);
		} else {
			if ( ! self::hasExactKeys( $cta, array( 'open', 'closed' ) ) ) {
				self::addError(
					$errors,
					'cta',
					__( 'The CTA settings contain missing or unknown states.', 'opennow' )
				);
			}

			foreach ( array( 'open', 'closed' ) as $state ) {
				$field = 'cta.' . $state;
				if ( ! array_key_exists( $state, $cta ) ) {
					self::addError(
						$errors,
						$field,
						__( 'Each CTA state needs a label, action, and status field.', 'opennow' )
					);
					continue;
				}

				$entry = self::validateCtaState( $cta[ $state ], $field, $errors );
				if ( null !== $entry ) {
					$canonical['cta'][ $state ] = $entry;
				}
			}
		}

		$appearance = array_key_exists( 'appearance', $value ) ? $value['appearance'] : null;
		if ( ! is_array( $appearance ) ) {
			self::addError(
				$errors,
				'appearance',
				__( 'The appearance settings must contain both colors.', 'opennow' )
			);
		} else {
			if ( ! self::hasExactKeys( $appearance, array( 'background_color', 'text_color' ) ) ) {
				self::addError(
					$errors,
					'appearance',
					__( 'The appearance settings contain missing or unknown fields.', 'opennow' )
				);
			}

			$background = array_key_exists( 'background_color', $appearance )
				? Color::canonical( $appearance['background_color'] )
				: null;
			$text       = array_key_exists( 'text_color', $appearance )
				? Color::canonical( $appearance['text_color'] )
				: null;

			if ( null === $background ) {
				self::addError(
					$errors,
					'appearance.background_color',
					__( 'Use a six-digit background color such as #166534, or leave it blank.', 'opennow' )
				);
			} else {
				$canonical['appearance']['background_color'] = $background;
			}

			if ( null === $text ) {
				self::addError(
					$errors,
					'appearance.text_color',
					__( 'Use a six-digit text color such as #FFFFFF, or leave it blank.', 'opennow' )
				);
			} else {
				$canonical['appearance']['text_color'] = $text;
			}

			if ( null !== $background && null !== $text && ! self::hasSufficientContrast( $background, $text ) ) {
				self::addError(
					$errors,
					'appearance.background_color',
					__( 'The background and text colors must meet WCAG AA contrast for normal text.', 'opennow' )
				);
				self::addError(
					$errors,
					'appearance.text_color',
					__( 'The background and text colors must meet WCAG AA contrast for normal text.', 'opennow' )
				);
			}
		}

		if ( $errors->has_errors() ) {
			return $errors;
		}

		return $canonical;
	}

	/**
	 * Return a canonical named timezone or null.
	 *
	 * @param mixed $value
	 * @return string|null
	 */
	public static function canonicalTimezone( $value ) {
		if ( ! is_string( $value ) ) {
			return null;
		}

		if ( false !== strpos( $value, "\0" ) ) {
			return null;
		}

		$value = trim( $value );
		if ( '' === $value ) {
			return null;
		}

		try {
			$identifiers = \DateTimeZone::listIdentifiers();
		} catch ( \Throwable $exception ) {
			return null;
		}

		if ( ! in_array( $value, $identifiers, true ) ) {
			return null;
		}

		return $value;
	}

	/**
	 * Return a canonical schedule entry or null when the entry is invalid.
	 *
	 * @param mixed $value
	 * @return array<string, string>|null
	 */
	public static function canonicalScheduleEntry( $value ) {
		if ( ! is_array( $value ) || ! array_key_exists( 'type', $value ) || ! is_string( $value['type'] ) ) {
			return null;
		}

		if ( false !== strpos( $value['type'], "\0" ) ) {
			return null;
		}

		$type = trim( $value['type'] );
		if ( 'closed' === $type ) {
			if ( ! self::hasExactKeys( $value, array( 'type' ) ) ) {
				return null;
			}

			return array( 'type' => 'closed' );
		}

		if ( 'period' !== $type || ! self::hasExactKeys( $value, array( 'type', 'opens', 'closes' ) ) ) {
			return null;
		}

		$opens  = self::canonicalTime( $value['opens'] );
		$closes = self::canonicalTime( $value['closes'] );
		if ( null === $opens || null === $closes || $opens === $closes ) {
			return null;
		}

		return array(
			'type'   => 'period',
			'opens'  => $opens,
			'closes' => $closes,
		);
	}

	/**
	 * Return a canonical CTA state or null when the state is invalid.
	 *
	 * @param mixed $value
	 * @return array<string, string>|null
	 */
	public static function canonicalCtaState( $value ) {
		if ( ! is_array( $value ) || ! self::hasExactKeys( $value, array( 'label', 'action', 'status' ) ) ) {
			return null;
		}

		$label  = self::canonicalPlainText( $value['label'], true );
		$action = self::canonicalAction( $value['action'] );
		$status = self::canonicalPlainText( $value['status'], false );

		if ( null === $label || null === $action || null === $status ) {
			return null;
		}

		return array(
			'label'  => $label,
			'action' => $action,
			'status' => $status,
		);
	}

	/**
	 * Return sparse canonical per-state CTA overrides.
	 *
	 * Invalid containers, fields, and values are ignored independently.
	 *
	 * @param mixed $value
	 * @return array<string, array<string, string|bool>>
	 */
	public static function canonicalCtaOverrides( $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$canonical = array();
		foreach ( array( 'open', 'closed' ) as $state ) {
			if ( ! array_key_exists( $state, $value ) || ! is_array( $value[ $state ] ) ) {
				continue;
			}

			$state_overrides = array();
			foreach ( array( 'label', 'action', 'status', 'hideStatus' ) as $field ) {
				if ( ! array_key_exists( $field, $value[ $state ] ) ) {
					continue;
				}

				if ( 'hideStatus' === $field ) {
					if ( true === $value[ $state ][ $field ] ) {
						$state_overrides[ $field ] = true;
					}
					continue;
				}

				if ( 'label' === $field ) {
					$field_value = self::canonicalPlainText( $value[ $state ][ $field ], true );
				} elseif ( 'action' === $field ) {
					$field_value = self::canonicalAction( $value[ $state ][ $field ] );
				} else {
					$field_value = self::canonicalPlainText( $value[ $state ][ $field ], false );
				}

				if ( null !== $field_value ) {
					$state_overrides[ $field ] = $field_value;
				}
			}

			if ( ! empty( $state_overrides ) ) {
				$canonical[ $state ] = $state_overrides;
			}
		}

		return $canonical;
	}

	/**
	 * Return a canonical action or null when it is not one of the supported forms.
	 *
	 * @param mixed $value
	 * @return string|null
	 */
	public static function canonicalAction( $value ) {
		if ( ! is_string( $value ) ) {
			return null;
		}

		if ( 1 === preg_match( '/[\x00-\x1F\x7F-\x9F]/', $value ) ) {
			return null;
		}

		$value = trim( $value );
		if ( '' === $value || false !== strpos( $value, '\\' ) ) {
			return null;
		}

		if ( 1 === preg_match( '/\Atel:\+?[0-9][0-9 .()\-]*\z/i', $value ) ) {
			return $value;
		}

		if ( '/' === substr( $value, 0, 1 ) && '/' !== substr( $value, 1, 1 ) ) {
			if ( self::containsWhitespace( $value ) ) {
				return null;
			}

			return $value;
		}

		if ( 1 !== preg_match( '/\Ahttps:/i', $value ) || self::containsWhitespace( $value ) ) {
			return null;
		}

		if ( ! function_exists( 'wp_parse_url' ) ) {
			return null;
		}

		try {
			$parsed = \wp_parse_url( $value );
		} catch ( \Throwable $exception ) {
			return null;
		}

		if ( ! is_array( $parsed )
			|| ! isset( $parsed['scheme'] )
			|| ! is_string( $parsed['scheme'] )
			|| 0 !== strcasecmp( $parsed['scheme'], 'https' )
			|| ! array_key_exists( 'host', $parsed )
			|| ! is_string( $parsed['host'] )
			|| '' === $parsed['host']
			|| array_key_exists( 'user', $parsed )
			|| array_key_exists( 'pass', $parsed )
			|| ! self::isValidPort( $parsed )
			|| ! self::hasValidAuthorityPort( $value )
			|| ! self::isValidHostname( $parsed['host'] )
		) {
			return null;
		}

		return $value;
	}

	/**
	 * Return a canonical color value or null when it is invalid.
	 *
	 * @param mixed $value
	 * @return string|null
	 */
	public static function canonicalColor( $value ) {
		return Color::canonical( $value );
	}

	/**
	 * @param mixed     $value
	 * @param string    $field
	 * @param \WP_Error $errors
	 * @return array<string, string>|null
	 */
	private static function validateScheduleEntry( $value, $field, $errors ) {
		if ( ! is_array( $value ) ) {
			self::addError(
				$errors,
				$field,
				__( 'A schedule entry must be either closed or one period.', 'opennow' )
			);
			return null;
		}

		if ( ! array_key_exists( 'type', $value ) || ! is_string( $value['type'] )
			|| false !== strpos( $value['type'], "\0" )
		) {
			self::addError(
				$errors,
				$field . '.type',
				__( 'Choose either closed or period for this day.', 'opennow' )
			);
			return null;
		}

		$type = trim( $value['type'] );
		if ( 'closed' === $type ) {
			if ( ! self::hasExactKeys( $value, array( 'type' ) ) ) {
				self::addError(
					$errors,
					$field,
					__( 'A closed day may not contain period fields or unknown fields.', 'opennow' )
				);
				return null;
			}

			return array( 'type' => 'closed' );
		}

		if ( 'period' !== $type ) {
			self::addError(
				$errors,
				$field . '.type',
				__( 'Choose either closed or period for this day.', 'opennow' )
			);
			return null;
		}

		$valid = true;
		if ( ! self::hasExactKeys( $value, array( 'type', 'opens', 'closes' ) ) ) {
			self::addError(
				$errors,
				$field,
				__( 'A period must contain only type, opens, and closes fields.', 'opennow' )
			);
			$valid = false;
		}

		$opens = array_key_exists( 'opens', $value ) ? self::canonicalTime( $value['opens'] ) : null;
		if ( null === $opens ) {
			self::addError(
				$errors,
				$field . '.opens',
				__( 'Enter an opening time in HH:MM format.', 'opennow' )
			);
			$valid = false;
		}

		$closes = array_key_exists( 'closes', $value ) ? self::canonicalTime( $value['closes'] ) : null;
		if ( null === $closes ) {
			self::addError(
				$errors,
				$field . '.closes',
				__( 'Enter a closing time in HH:MM format.', 'opennow' )
			);
			$valid = false;
		}

		if ( null !== $opens && null !== $closes && $opens === $closes ) {
			self::addError(
				$errors,
				$field,
				__( 'Opening and closing times must be different.', 'opennow' )
			);
			$valid = false;
		}

		if ( ! $valid ) {
			return null;
		}

		return array(
			'type'   => 'period',
			'opens'  => $opens,
			'closes' => $closes,
		);
	}

	/**
	 * @param mixed     $value
	 * @param string    $field
	 * @param \WP_Error $errors
	 * @return array<string, string>|null
	 */
	private static function validateCtaState( $value, $field, $errors ) {
		if ( ! is_array( $value ) ) {
			self::addError(
				$errors,
				$field,
				__( 'Each CTA state must be an array with label, action, and status fields.', 'opennow' )
			);
			return null;
		}

		$valid = true;
		if ( ! self::hasExactKeys( $value, array( 'label', 'action', 'status' ) ) ) {
			self::addError(
				$errors,
				$field,
				__( 'Each CTA state must contain only label, action, and status fields.', 'opennow' )
			);
			$valid = false;
		}

		$label = array_key_exists( 'label', $value )
			? self::canonicalPlainText( $value['label'], true )
			: null;
		if ( null === $label ) {
			self::addError(
				$errors,
				$field . '.label',
				__( 'Enter a non-empty plain-text CTA label.', 'opennow' )
			);
			$valid = false;
		}

		$action = array_key_exists( 'action', $value ) ? self::canonicalAction( $value['action'] ) : null;
		if ( null === $action ) {
			self::addError(
				$errors,
				$field . '.action',
				__( 'Enter a valid root-relative, HTTPS, or telephone CTA action.', 'opennow' )
			);
			$valid = false;
		}

		$status = array_key_exists( 'status', $value )
			? self::canonicalPlainText( $value['status'], false )
			: null;
		if ( null === $status ) {
			self::addError(
				$errors,
				$field . '.status',
				__( 'Enter plain-text status text or leave it blank.', 'opennow' )
			);
			$valid = false;
		}

		if ( ! $valid ) {
			return null;
		}

		return array(
			'label'  => $label,
			'action' => $action,
			'status' => $status,
		);
	}

	/**
	 * @param mixed $value
	 * @param bool  $required
	 * @return string|null
	 */
	private static function canonicalPlainText( $value, $required ) {
		if ( ! is_string( $value ) ) {
			return null;
		}

		if ( false !== strpos( $value, "\0" ) ) {
			return null;
		}

		$value = trim( $value );
		if ( $required && 1 !== preg_match( '/\S/u', $value ) ) {
			return null;
		}

		if ( false !== strpos( $value, '<' ) || false !== strpos( $value, '>' ) ) {
			return null;
		}

		return $value;
	}

	/**
	 * @param mixed $value
	 * @return string|null
	 */
	private static function canonicalTime( $value ) {
		if ( ! is_string( $value ) ) {
			return null;
		}

		if ( false !== strpos( $value, "\0" ) ) {
			return null;
		}

		$value = trim( $value );
		if ( 1 !== preg_match( '/\A(?:[01][0-9]|2[0-3]):[0-5][0-9]\z/', $value ) ) {
			return null;
		}

		return $value;
	}

	/**
	 * @param string $value
	 * @return bool
	 */
	private static function containsWhitespace( $value ) {
		$match = preg_match( '/\s/u', $value );

		return false === $match || 1 === $match;
	}

	/**
	 * Ensure an empty, malformed, or out-of-range authority port is rejected
	 * even when a URL parser chooses to omit it from its result.
	 *
	 * @param string $value
	 * @return bool
	 */
	private static function hasValidAuthorityPort( $value ) {
		if ( 1 !== preg_match( '/\Ahttps:\\/\\/([^\\/?#]*)/i', $value, $matches ) ) {
			return false;
		}

		$authority = $matches[1];
		if ( '' === $authority || false !== strpos( $authority, '@' ) ) {
			return false;
		}

		if ( '[' === substr( $authority, 0, 1 ) ) {
			$closing_bracket = strpos( $authority, ']' );
			if ( false === $closing_bracket ) {
				return false;
			}

			$suffix = substr( $authority, $closing_bracket + 1 );
			if ( '' === $suffix ) {
				return true;
			}

			if ( ':' !== substr( $suffix, 0, 1 ) ) {
				return false;
			}

			return self::isValidPortText( substr( $suffix, 1 ) );
		}

		if ( false === strpos( $authority, ':' ) ) {
			return true;
		}

		if ( 1 !== substr_count( $authority, ':' ) ) {
			return false;
		}

		return self::isValidPortText( substr( $authority, strpos( $authority, ':' ) + 1 ) );
	}

	/**
	 * @param string $port
	 * @return bool
	 */
	private static function isValidPortText( $port ) {
		if ( 1 !== preg_match( '/\A[0-9]{1,5}\z/', $port ) ) {
			return false;
		}

		$port = (int) $port;
		return $port >= 1 && $port <= 65535;
	}

	/**
	 * @param array<string, mixed> $parsed
	 * @return bool
	 */
	private static function isValidPort( $parsed ) {
		if ( ! array_key_exists( 'port', $parsed ) ) {
			return true;
		}

		$port = $parsed['port'];
		if ( is_int( $port ) ) {
			return $port >= 1 && $port <= 65535;
		}

		if ( ! is_string( $port ) || 1 !== preg_match( '/\A[0-9]{1,5}\z/', $port ) ) {
			return false;
		}

		$port = (int) $port;
		return $port >= 1 && $port <= 65535;
	}

	/**
	 * @param string $host
	 * @return bool
	 */
	private static function isValidHostname( $host ) {
		if ( '' === $host || 1 === preg_match( '/[^\x00-\x7F]/', $host ) ) {
			return false;
		}

		if ( '[' === substr( $host, 0, 1 ) && ']' === substr( $host, -1 ) ) {
			$host = substr( $host, 1, -1 );
			return false !== filter_var( $host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 );
		}

		if ( false !== filter_var( $host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			return true;
		}

		if ( strlen( $host ) > 253 || false !== strpos( $host, '%' ) ) {
			return false;
		}

		$labels = explode( '.', $host );
		if ( '' === end( $labels ) ) {
			array_pop( $labels );
		}

		if ( empty( $labels ) ) {
			return false;
		}

		foreach ( $labels as $label ) {
			if ( '' === $label || strlen( $label ) > 63 ) {
				return false;
			}

			if ( 1 !== preg_match( '/\A[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\z/', $label ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @param string $background
	 * @param string $text
	 * @return bool
	 */
	private static function hasSufficientContrast( $background, $text ) {
		$effective_background = '' === $background
			? Schema::DEFAULT_BACKGROUND_COLOR
			: $background;
		$effective_text       = '' === $text
			? Schema::DEFAULT_TEXT_COLOR
			: $text;
		$ratio                = Color::contrastRatio( $effective_background, $effective_text );

		return null !== $ratio && $ratio >= Schema::MIN_CONTRAST_RATIO;
	}

	/**
	 * @param array<string, mixed> $value
	 * @param array<int, string>   $keys
	 * @return bool
	 */
	private static function hasExactKeys( $value, $keys ) {
		if ( ! is_array( $value ) || count( $value ) !== count( $keys ) ) {
			return false;
		}

		foreach ( $keys as $key ) {
			if ( ! array_key_exists( $key, $value ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @param \WP_Error $errors
	 * @param string    $field
	 * @param string    $message
	 * @return void
	 */
	private static function addError( $errors, $field, $message ) {
		$code = 'opennow_' . strtolower( (string) preg_replace( '/[^a-zA-Z0-9]+/', '_', $field ) );
		$errors->add(
			$code,
			$message,
			array( 'field' => $field )
		);
	}
}
