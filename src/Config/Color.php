<?php
namespace OpenNow\Config;

defined( 'ABSPATH' ) || exit;

/**
 * Strict color validation and WCAG contrast calculations.
 */
final class Color {

	/**
	 * Return a trimmed color, an empty string for a valid blank, or null when invalid.
	 *
	 * @param mixed $value
	 * @return string|null
	 */
	public static function canonical( $value ) {
		if ( ! is_string( $value ) ) {
			return null;
		}

		if ( false !== strpos( $value, "\0" ) ) {
			return null;
		}

		$value = trim( $value );
		if ( '' === $value ) {
			return '';
		}

		if ( 1 !== preg_match( '/\A#[0-9A-Fa-f]{6}\z/', $value ) ) {
			return null;
		}

		return $value;
	}

	/**
	 * @param mixed $value
	 * @return bool
	 */
	public static function isValid( $value ) {
		return null !== self::canonical( $value );
	}

	/**
	 * Calculate the relative luminance of a non-blank color.
	 *
	 * @param mixed $color
	 * @return float|null
	 */
	public static function relativeLuminance( $color ) {
		$color = self::canonical( $color );
		if ( null === $color || '' === $color ) {
			return null;
		}

		$red   = hexdec( substr( $color, 1, 2 ) ) / 255;
		$green = hexdec( substr( $color, 3, 2 ) ) / 255;
		$blue  = hexdec( substr( $color, 5, 2 ) ) / 255;

		return ( 0.2126 * self::linearize( $red ) )
			+ ( 0.7152 * self::linearize( $green ) )
			+ ( 0.0722 * self::linearize( $blue ) );
	}

	/**
	 * Calculate the WCAG contrast ratio between two non-blank colors.
	 *
	 * @param mixed $first
	 * @param mixed $second
	 * @return float|null
	 */
	public static function contrastRatio( $first, $second ) {
		$first_luminance  = self::relativeLuminance( $first );
		$second_luminance = self::relativeLuminance( $second );

		if ( null === $first_luminance || null === $second_luminance ) {
			return null;
		}

		$lighter = max( $first_luminance, $second_luminance );
		$darker  = min( $first_luminance, $second_luminance );

		return ( $lighter + 0.05 ) / ( $darker + 0.05 );
	}

	/**
	 * Determine whether two submitted values have sufficient effective contrast.
	 * Blank values select their respective plugin defaults.
	 *
	 * @param mixed $background
	 * @param mixed $text
	 * @return bool
	 */
	public static function hasSufficientContrast( $background, $text ) {
		$background = self::canonical( $background );
		$text       = self::canonical( $text );

		if ( null === $background || null === $text ) {
			return false;
		}

		$effective_background = '' === $background
			? Schema::DEFAULT_BACKGROUND_COLOR
			: $background;
		$effective_text       = '' === $text
			? Schema::DEFAULT_TEXT_COLOR
			: $text;
		$ratio                = self::contrastRatio( $effective_background, $effective_text );

		return null !== $ratio && $ratio >= Schema::MIN_CONTRAST_RATIO;
	}

	/**
	 * Resolve a valid pair to the colors used at runtime. Any malformed value or
	 * an ineffective pair falls back to the complete default pair.
	 *
	 * @param mixed $background
	 * @param mixed $text
	 * @return array<string, string>
	 */
	public static function effectivePair( $background, $text ) {
		$background = self::canonical( $background );
		$text       = self::canonical( $text );

		if ( null === $background || null === $text ) {
			return Schema::defaultAppearance();
		}

		$effective_background = '' === $background
			? Schema::DEFAULT_BACKGROUND_COLOR
			: $background;
		$effective_text       = '' === $text
			? Schema::DEFAULT_TEXT_COLOR
			: $text;

		$ratio = self::contrastRatio( $effective_background, $effective_text );
		if ( null === $ratio || $ratio < Schema::MIN_CONTRAST_RATIO ) {
			return Schema::defaultAppearance();
		}

		return array(
			'background_color' => $effective_background,
			'text_color'       => $effective_text,
		);
	}

	/**
	 * @param float $channel
	 * @return float
	 */
	private static function linearize( $channel ) {
		if ( $channel <= 0.04045 ) {
			return $channel / 12.92;
		}

		return pow( ( $channel + 0.055 ) / 1.055, 2.4 );
	}
}
