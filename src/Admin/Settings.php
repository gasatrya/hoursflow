<?php
namespace HoursFlow\Admin;

use HoursFlow\Config\Schema;
use HoursFlow\Config\Validator;

defined( 'ABSPATH' ) || exit;

/**
 * Conditional Settings API registration for the atomic configuration option.
 */
final class Settings {

	const PAGE_SLUG          = 'hoursflow';
	const PAGE_CAPABILITY    = 'manage_options';
	const TIMEZONE_SECTION   = 'hoursflow_timezone_section';
	const APPEARANCE_SECTION = 'hoursflow_appearance_section';
	const SCHEDULE_SECTION   = 'hoursflow_schedule_section';
	const OPEN_CTA_SECTION   = 'hoursflow_open_cta_section';
	const CLOSED_CTA_SECTION = 'hoursflow_closed_cta_section';

	private const HIRE_URL             = 'https://gasatrya.com/';
	private const DONATION_URL         = 'https://gasatrya.com/donate/';
	private const DEFAULT_OPENING_TIME = '09:00';
	private const DEFAULT_CLOSING_TIME = '17:00';

	/**
	 * @var string|false|null
	 */
	private $page_hook;

	/**
	 * @var array<string, mixed>|null
	 */
	private $editor_config;

	/**
	 * Create the settings controller.
	 */
	public function __construct() {
		$this->page_hook     = null;
		$this->editor_config = null;
	}

	/**
	 * Register the admin hooks used by the settings screen.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_init', array( $this, 'registerSetting' ) );
		add_action( 'admin_init', array( $this, 'handleReset' ) );
		add_action( 'admin_menu', array( $this, 'registerPage' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueueAssets' ) );

		if ( function_exists( 'add_filter' ) ) {
			add_filter(
				'option_page_capability_' . self::PAGE_SLUG,
				array( $this, 'optionPageCapability' )
			);
		}
	}

	/**
	 * Register the one atomic option and its settings screen sections.
	 *
	 * @return void
	 */
	public function registerSetting() {
		register_setting(
			'hoursflow',
			Schema::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'show_in_rest'      => false,
			)
		);

		$this->registerSections();
	}

	/**
	 * Register the page beneath the WordPress Settings menu.
	 *
	 * @return string|false
	 */
	public function registerPage() {
		$this->page_hook = add_options_page(
			__( 'HoursFlow Settings', 'hoursflow' ),
			__( 'HoursFlow', 'hoursflow' ),
			self::PAGE_CAPABILITY,
			self::PAGE_SLUG,
			array( $this, 'renderPage' )
		);

		if ( is_string( $this->page_hook ) && current_user_can( self::PAGE_CAPABILITY ) ) {
			add_action(
				'load-' . $this->page_hook,
				array( $this, 'registerContextualHelp' )
			);
		}

		return $this->page_hook;
	}

	/**
	 * Register contextual help tabs for this settings screen.
	 *
	 * @return void
	 */
	public function registerContextualHelp() {
		if ( ! current_user_can( self::PAGE_CAPABILITY )
			|| ! function_exists( 'get_current_screen' )
			|| ! is_string( $this->page_hook )
		) {
			return;
		}

		$screen = get_current_screen();
		if ( ! is_object( $screen )
			|| ! is_callable( array( $screen, 'add_help_tab' ) )
			|| ! isset( $screen->id )
			|| $screen->id !== $this->page_hook
		) {
			return;
		}

		$state_label = array(
			'open'   => __( 'open', 'hoursflow' ),
			'closed' => __( 'closed', 'hoursflow' ),
		);
		$help_tabs   = array(
			array(
				'id'      => 'hoursflow-timezone-help',
				'title'   => __( 'Business timezone', 'hoursflow' ),
				'content' => '<p>'
					. esc_html__(
						'Choose UTC or a named IANA region/city timezone, such as UTC, America/New_York, or Asia/Jakarta.',
						'hoursflow'
					)
					. '</p><p>'
					. esc_html__(
						'HoursFlow evaluates weekly hours in the business timezone, not the visitor, browser, server, or WordPress site timezone. Raw UTC offsets such as UTC+1 are not supported.',
						'hoursflow'
					)
					. '</p>',
			),
			array(
				'id'      => 'hoursflow-appearance-help',
				'title'   => __( 'CTA appearance', 'hoursflow' ),
				'content' => '<p>'
					. esc_html__(
						'Use the native color picker to choose optional six-digit hexadecimal colors such as #166534 or #FFFFFF.',
						'hoursflow'
					)
					. '</p><p>'
					. esc_html__(
						'When a legacy stored value is blank, its picker shows the plugin default (#166534 for the background or #FFFFFF for the text). Legacy blanks remain valid and use that default at runtime; saving the displayed picker value stores an explicit color. These global CTA colors are shared by the shortcode and every HoursFlow block. The effective background and text colors must meet WCAG 2.2 AA contrast for normal text.',
						'hoursflow'
					)
					. '</p>',
			),
			array(
				'id'      => 'hoursflow-weekly-hours-help',
				'title'   => __( 'Weekly hours', 'hoursflow' ),
				'content' => '<p>'
					. esc_html__(
						'For each weekday, choose closed or exactly one period and enter exact 24-hour HH:MM local business time, for example 09:30.',
						'hoursflow'
					)
					. '</p><p>'
					. esc_html__(
						'A closed day has no opening or closing times. Opening and closing times must differ; a closing time earlier than the opening time means overnight. Do not enter 24:00 or multiple periods.',
						'hoursflow'
					)
					. '</p>',
			),
			array(
				'id'      => 'hoursflow-open-cta-help',
				'title'   => __( 'CTA while open', 'hoursflow' ),
				'content' => '<p>'
					. esc_html(
						sprintf(
							/* translators: %s: the business state, either open or closed. */
							__( 'Provide a required plain-text label and action for the CTA shown while the business is %s. Labels and status text must be plain text; angle brackets are not allowed.', 'hoursflow' ),
							$state_label['open']
						)
					)
					. '</p><p>'
					. esc_html__(
						'Use a root-relative URL such as /booking/, an HTTPS URL such as https://example.com/book, or a telephone action such as tel:+123456789. An optional plain-text status may accompany the CTA; leave it blank to omit it.',
						'hoursflow'
					)
					. '</p>',
			),
			array(
				'id'      => 'hoursflow-closed-cta-help',
				'title'   => __( 'CTA while closed', 'hoursflow' ),
				'content' => '<p>'
					. esc_html(
						sprintf(
							/* translators: %s: the business state, either open or closed. */
							__( 'Provide a required plain-text label and action for the CTA shown while the business is %s. Labels and status text must be plain text; angle brackets are not allowed.', 'hoursflow' ),
							$state_label['closed']
						)
					)
					. '</p><p>'
					. esc_html__(
						'Use a root-relative URL such as /booking/, an HTTPS URL such as https://example.com/book, or a telephone action such as tel:+123456789. An optional plain-text status may accompany the CTA; leave it blank to omit it.',
						'hoursflow'
					)
					. '</p>',
			),
		);

		foreach ( $help_tabs as $help_tab ) {
			$screen->add_help_tab( $help_tab );
		}
	}

	/**
	 * Keep the options.php capability aligned with the menu and page guard.
	 *
	 * @param string $capability The capability supplied by WordPress.
	 * @return string
	 */
	public function optionPageCapability( $capability ) {
		unset( $capability );

		return self::PAGE_CAPABILITY;
	}

	/**
	 * Register the settings sections and fields on admin_init.
	 *
	 * @return void
	 */
	public function registerSections() {
		/* The Settings API provides both functions during admin_init. */
		if ( ! function_exists( 'add_settings_section' ) || ! function_exists( 'add_settings_field' ) ) {
			return;
		}

		add_settings_section(
			self::TIMEZONE_SECTION,
			__( 'Business timezone', 'hoursflow' ),
			array( $this, 'renderTimezoneSection' ),
			self::PAGE_SLUG
		);

		add_settings_field(
			'hoursflow_timezone',
			__( 'Business timezone', 'hoursflow' ),
			array( $this, 'renderTimezoneField' ),
			self::PAGE_SLUG,
			self::TIMEZONE_SECTION,
			array(
				'label_for' => 'hoursflow-timezone',
			)
		);

		add_settings_section(
			self::APPEARANCE_SECTION,
			__( 'CTA appearance', 'hoursflow' ),
			array( $this, 'renderAppearanceSection' ),
			self::PAGE_SLUG
		);

		add_settings_field(
			'hoursflow_appearance_background_color',
			__( 'Background color', 'hoursflow' ),
			array( $this, 'renderAppearanceField' ),
			self::PAGE_SLUG,
			self::APPEARANCE_SECTION,
			array(
				'color'     => 'background_color',
				'label_for' => 'hoursflow-appearance-background-color',
			)
		);

		add_settings_field(
			'hoursflow_appearance_text_color',
			__( 'Text color', 'hoursflow' ),
			array( $this, 'renderAppearanceField' ),
			self::PAGE_SLUG,
			self::APPEARANCE_SECTION,
			array(
				'color'     => 'text_color',
				'label_for' => 'hoursflow-appearance-text-color',
			)
		);

		add_settings_section(
			self::SCHEDULE_SECTION,
			__( 'Weekly hours', 'hoursflow' ),
			array( $this, 'renderScheduleSection' ),
			self::PAGE_SLUG
		);

		add_settings_field(
			'hoursflow_schedule',
			__( 'Monday to Sunday', 'hoursflow' ),
			array( $this, 'renderScheduleField' ),
			self::PAGE_SLUG,
			self::SCHEDULE_SECTION
		);

		add_settings_section(
			self::OPEN_CTA_SECTION,
			__( 'CTA while open', 'hoursflow' ),
			array( $this, 'renderOpenCtaSection' ),
			self::PAGE_SLUG
		);

		add_settings_field(
			'hoursflow_cta_open',
			__( 'Open CTA', 'hoursflow' ),
			array( $this, 'renderCtaField' ),
			self::PAGE_SLUG,
			self::OPEN_CTA_SECTION,
			array(
				'state'     => 'open',
				'label_for' => 'hoursflow-cta-open-label',
			)
		);

		add_settings_section(
			self::CLOSED_CTA_SECTION,
			__( 'CTA while closed', 'hoursflow' ),
			array( $this, 'renderClosedCtaSection' ),
			self::PAGE_SLUG
		);

		add_settings_field(
			'hoursflow_cta_closed',
			__( 'Closed CTA', 'hoursflow' ),
			array( $this, 'renderCtaField' ),
			self::PAGE_SLUG,
			self::CLOSED_CTA_SECTION,
			array(
				'state'     => 'closed',
				'label_for' => 'hoursflow-cta-closed-label',
			)
		);
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public function renderPage() {
		if ( ! current_user_can( self::PAGE_CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to access these settings.', 'hoursflow' ) );
			return;
		}

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'HoursFlow Settings', 'hoursflow' ) . '</h1>';
		$this->renderResetNotice();
		$this->renderSettingsErrors();
		echo '<div id="hoursflow-settings-layout">';
		echo '<form action="options.php" method="post">';
		settings_fields( 'hoursflow' );
		do_settings_sections( self::PAGE_SLUG );
		echo '<div class="hoursflow-settings-actions">';
		submit_button( __( 'Save Changes', 'hoursflow' ), 'primary', 'submit', false );
		submit_button(
			__( 'Reset to Defaults', 'hoursflow' ),
			'secondary',
			'hoursflow_reset',
			false,
			array(
				'formnovalidate'               => 'formnovalidate',
				'data-hoursflow-reset-confirm' => __(
					'Are you sure you want to reset all settings to defaults?',
					'hoursflow'
				),
			)
		);
		echo '</div>';
		echo '</form>';
		$this->renderPreview();
		$this->renderDeveloperPromotion();
		echo '</div>';
		echo '</div>';
	}

	/**
	 * Delete the saved configuration when an authorized reset is requested.
	 *
	 * @return void
	 */
	public function handleReset() {
		if ( ! isset( $_POST['hoursflow_reset'], $_POST['_wpnonce'] ) ) {
			return;
		}

		if ( ! current_user_can( self::PAGE_CAPABILITY ) ) {
			return;
		}

		if ( ! check_admin_referer( 'hoursflow-options' ) ) {
			return;
		}

		delete_option( Schema::OPTION_NAME );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'            => self::PAGE_SLUG,
					'hoursflow-reset' => '1',
				),
				admin_url( 'options-general.php' )
			)
		);
		exit;
	}

	/**
	 * Display confirmation after settings have been reset.
	 *
	 * @return void
	 */
	public function renderResetNotice() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$reset = isset( $_GET['hoursflow-reset'] ) ? sanitize_key( wp_unslash( $_GET['hoursflow-reset'] ) ) : '';

		if ( self::PAGE_SLUG !== $page
			|| '1' !== $reset
			|| ! current_user_can( self::PAGE_CAPABILITY )
		) {
			return;
		}

		echo '<div class="notice notice-success is-dismissible"><p>'
			. esc_html__( 'Settings have been reset to defaults.', 'hoursflow' )
			. '</p></div>';
	}

	/**
	 * Enqueue the page behavior only for this settings page and its capability.
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 * @return void
	 */
	public function enqueueAssets( $hook_suffix ) {
		if ( $this->page_hook !== $hook_suffix || ! current_user_can( self::PAGE_CAPABILITY ) ) {
			return;
		}

		if ( ! function_exists( 'wp_enqueue_script' ) || ! function_exists( 'wp_enqueue_style' )
			|| ! function_exists( 'plugins_url' )
		) {
			return;
		}

		$plugin_file = defined( 'HOURSFLOW_PLUGIN_FILE' )
			? HOURSFLOW_PLUGIN_FILE
			: dirname( __DIR__, 2 ) . DIRECTORY_SEPARATOR . 'hoursflow.php';
		$version     = defined( 'HOURSFLOW_VERSION' ) ? HOURSFLOW_VERSION : null;

		wp_enqueue_script(
			'hoursflow-admin-settings',
			plugins_url( 'assets/admin/settings.js', $plugin_file ),
			array(),
			$version,
			true
		);

		wp_enqueue_style(
			'hoursflow-admin-settings-style',
			plugins_url( 'assets/admin/settings.css', $plugin_file ),
			array(),
			$version
		);
	}

	/**
	 * Render standard Settings API errors in an announced region.
	 *
	 * @return void
	 */
	public function renderSettingsErrors() {
		$errors = get_settings_errors( Schema::OPTION_NAME );
		if ( empty( $errors ) ) {
			return;
		}

		echo '<div role="alert" aria-label="'
			. esc_attr__( 'HoursFlow settings errors', 'hoursflow' )
			. '">';
		settings_errors( Schema::OPTION_NAME );
		echo '</div>';
	}

	/**
	 * Render the timezone section description.
	 *
	 * @return void
	 */
	public function renderTimezoneSection() {
		echo '<p class="description">'
			. esc_html__(
				'Choose the named timezone used for all weekly hours. Times are entered as local business time.',
				'hoursflow'
			)
			. '</p>';
	}

	/**
	 * Render the weekly hours section description.
	 *
	 * @return void
	 */
	public function renderScheduleSection() {
		echo '<p class="description">'
			. esc_html__(
				'Set each day to closed or one period. A closing time earlier than the opening time is an overnight period.',
				'hoursflow'
			)
			. '</p>';
	}

	/**
	 * Render the open CTA section description.
	 *
	 * @return void
	 */
	public function renderOpenCtaSection() {
		$this->renderCtaSectionDescription( 'open' );
	}

	/**
	 * Render the closed CTA section description.
	 *
	 * @return void
	 */
	public function renderClosedCtaSection() {
		$this->renderCtaSectionDescription( 'closed' );
	}

	/**
	 * Render the business timezone field.
	 *
	 * @param array<string, mixed> $args Field arguments.
	 * @return void
	 */
	public function renderTimezoneField( $args = array() ) {
		unset( $args );

		$config   = $this->getEditorConfig();
		$timezone = is_string( $config['timezone'] ) ? $config['timezone'] : '';

		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Dynamic attributes are escaped by getErrorAttributes().
		echo '<select id="hoursflow-timezone" name="hoursflow_config[timezone]"'
			. $this->getErrorAttributes(
				'hoursflow-timezone-description',
				array( 'hoursflow_timezone' )
			)
			. ' required="required">';
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WordPress supplies this escaped choice markup.
		echo $this->getTimezoneChoice( $timezone );
		echo '</select>';
		echo '<p class="description" id="hoursflow-timezone-description">'
			. esc_html__(
				'Select UTC or a named IANA region/city timezone. Manual UTC offsets are not supported.',
				'hoursflow'
			)
			. '</p>';
	}

	/**
	 * Render the appearance section description.
	 *
	 * @return void
	 */
	public function renderAppearanceSection() {
		echo '<p class="description">'
			. esc_html__(
				'Choose global CTA background and text colors for the shortcode and HoursFlow blocks. Colors must meet WCAG 2.2 AA contrast for normal text.',
				'hoursflow'
			)
			. '</p>';
	}

	/**
	 * Render one global appearance color field.
	 *
	 * @param array<string, mixed> $args Field arguments.
	 * @return void
	 */
	public function renderAppearanceField( $args = array() ) {
		$color = isset( $args['color'] ) && in_array( $args['color'], array( 'background_color', 'text_color' ), true )
			? $args['color']
			: null;
		if ( null === $color ) {
			return;
		}

		$config         = $this->getEditorConfig();
		$value          = isset( $config['appearance'][ $color ] ) && is_string( $config['appearance'][ $color ] )
			? $config['appearance'][ $color ]
			: '';
		$defaults       = Schema::defaultAppearance();
		$display_value  = '' === $value ? $defaults[ $color ] : $value;
		$id             = 'hoursflow-appearance-' . str_replace( '_', '-', $color );
		$description_id = $id . '-description';
		$error_code     = 'hoursflow_appearance_' . $color;
		$description    = 'background_color' === $color
			? __( 'Global CTA background color. Plugin default: #166534.', 'hoursflow' )
			: __( 'Global CTA text color. Plugin default: #FFFFFF.', 'hoursflow' );

		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Every dynamic attribute in this control is escaped.
		echo '<p><input type="color" id="' . esc_attr( $id )
			. '" name="hoursflow_config[appearance][' . esc_attr( $color ) . ']" value="'
			. esc_attr( $display_value )
			. '"'
			. $this->getErrorAttributes( $description_id, array( $error_code ) )
			. ' />';
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</p>';
		echo '<p class="description" id="' . esc_attr( $description_id ) . '">'
			. esc_html( $description )
			. '</p>';
	}

	/**
	 * Render all seven weekday schedule fieldsets.
	 *
	 * @param array<string, mixed> $args Field arguments.
	 * @return void
	 */
	public function renderScheduleField( $args = array() ) {
		unset( $args );

		$config = $this->getEditorConfig();

		echo '<div id="hoursflow-schedule">';
		echo '<p class="description" id="hoursflow-schedule-closed-description">'
			. esc_html__(
				'Check Closed all day to omit period times for a closed day.',
				'hoursflow'
			)
			. '</p>';
		echo '<p class="description" id="hoursflow-schedule-time-description">'
			. esc_html__(
				'Enter exact 24-hour HH:MM local business time, for example 09:30. A closing time earlier than the opening time means overnight.',
				'hoursflow'
			)
			. '</p>';

		$day_numbers  = array(
			'monday'    => 1,
			'tuesday'   => 2,
			'wednesday' => 3,
			'thursday'  => 4,
			'friday'    => 5,
			'saturday'  => 6,
			'sunday'    => 0,
		);
		$open_label   = __( 'Open', 'hoursflow' );
		$closed_label = __( 'Closed', 'hoursflow' );

		foreach ( Schema::days() as $day ) {
			$entry       = $config['schedule'][ $day ];
			$is_closed   = 'closed' === $entry['type'];
			$state       = $is_closed ? 'closed' : 'open';
			$state_label = $is_closed ? $closed_label : $open_label;
			$opens       = $is_closed ? self::DEFAULT_OPENING_TIME : $entry['opens'];
			$closes      = $is_closed ? self::DEFAULT_CLOSING_TIME : $entry['closes'];
			$opens_id    = 'hoursflow-schedule-' . $day . '-opens';
			$closes_id   = 'hoursflow-schedule-' . $day . '-closes';
			$closed_id   = 'hoursflow-schedule-' . $day . '-closed';
			$disabled    = $is_closed ? ' disabled="disabled"' : '';
			$required    = $is_closed ? '' : ' required="required"';

			// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- All fieldset attributes are escaped below.
			echo '<fieldset class="hoursflow-schedule-day hoursflow-schedule-day--' . esc_attr( $state )
				. '" data-hoursflow-schedule-day="' . esc_attr( $day )
				. '" data-hoursflow-schedule-state="' . esc_attr( $state )
				. '" data-hoursflow-open-label="' . esc_attr( $open_label )
				. '" data-hoursflow-closed-label="' . esc_attr( $closed_label )
				. '">';
			// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The localized weekday label is escaped.
			echo '<legend>' . esc_html( $this->getWeekdayLabel( $day, $day_numbers ) ) . '</legend>';
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The schedule key is escaped.
			echo '<input type="hidden" name="hoursflow_config[schedule]['
				. esc_attr( $day )
				. '][type]" value="period" />';
			echo '<div class="hoursflow-schedule-summary">';
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The localized state label is escaped.
			echo '<span class="hoursflow-schedule-state" data-hoursflow-schedule-state-text="1">'
				. esc_html( $state_label )
				. '</span>';
			echo '<label class="hoursflow-schedule-closed-toggle" for="' . esc_attr( $closed_id ) . '">';
			// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- All checkbox attributes are escaped.
			echo '<input type="checkbox" id="' . esc_attr( $closed_id ) . '" name="hoursflow_config[schedule]['
				. esc_attr( $day )
				. '][type]" value="closed" data-hoursflow-closed-toggle="1"'
				. ' aria-controls="'
				. esc_attr( $opens_id . ' ' . $closes_id )
				. '"'
				. $this->getErrorAttributes(
					'hoursflow-schedule-closed-description',
					array(
						'hoursflow_schedule_' . $day,
						'hoursflow_schedule_' . $day . '_type',
					)
				)
				. ( $is_closed ? ' checked="checked"' : '' )
				. ' />';
			// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
			echo esc_html__( 'Closed all day', 'hoursflow' );
			echo '</label>';
			echo '</div>';

			echo '<div class="hoursflow-schedule-period">';
			echo '<div class="hoursflow-schedule-row hoursflow-schedule-row--opening">';
			echo '<label for="' . esc_attr( $opens_id ) . '">'
				. esc_html__( 'Opening time', 'hoursflow' )
				. '</label>';
			// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- All opening-time attributes are escaped.
			echo '<input type="text" id="' . esc_attr( $opens_id )
				. '" name="hoursflow_config[schedule][' . esc_attr( $day ) . '][opens]" value="'
				. esc_attr( $opens )
				. '" placeholder="' . esc_attr__( 'Example: 09:00', 'hoursflow' )
				. '" maxlength="5" pattern="[0-9]{2}:[0-9]{2}" inputmode="numeric"'
				. ' autocomplete="off" data-hoursflow-time-input="1"'
				. $this->getErrorAttributes(
					'hoursflow-schedule-time-description',
					array(
						'hoursflow_schedule_' . $day,
						'hoursflow_schedule_' . $day . '_opens',
					)
				)
				. $disabled . $required . ' />';
			// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '</div>';
			echo '<div class="hoursflow-schedule-row hoursflow-schedule-row--closing">';
			echo '<label for="' . esc_attr( $closes_id ) . '">'
				. esc_html__( 'Closing time', 'hoursflow' )
				. '</label>';
			// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- All closing-time attributes are escaped.
			echo '<input type="text" id="' . esc_attr( $closes_id )
				. '" name="hoursflow_config[schedule][' . esc_attr( $day ) . '][closes]" value="'
				. esc_attr( $closes )
				. '" placeholder="' . esc_attr__( 'Example: 17:00', 'hoursflow' )
				. '" maxlength="5" pattern="[0-9]{2}:[0-9]{2}" inputmode="numeric"'
				. ' autocomplete="off" data-hoursflow-time-input="1"'
				. $this->getErrorAttributes(
					'hoursflow-schedule-time-description',
					array(
						'hoursflow_schedule_' . $day,
						'hoursflow_schedule_' . $day . '_closes',
					)
				)
				. $disabled . $required . ' />';
			// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '</div>';
			echo '</div>';
			echo '</fieldset>';
		}

		echo '</div>';
	}

	/**
	 * Render the non-submitting live CTA preview.
	 *
	 * @return void
	 */
	private function renderPreview() {
		$config   = $this->getEditorConfig();
		$defaults = Schema::defaultAppearance();
		$cta      = isset( $config['cta'] ) && is_array( $config['cta'] )
			? $config['cta']
			: array();
		$open     = isset( $cta['open'] ) && is_array( $cta['open'] )
			? $cta['open']
			: array();

		$open_label  = isset( $open['label'] ) && is_string( $open['label'] ) ? $open['label'] : '';
		$open_status = isset( $open['status'] ) && is_string( $open['status'] ) ? $open['status'] : '';
		$background  = isset( $config['appearance']['background_color'] )
			&& is_string( $config['appearance']['background_color'] )
			&& preg_match( '/\\A#[0-9A-Fa-f]{6}\\z/', $config['appearance']['background_color'] )
			? $config['appearance']['background_color']
			: $defaults['background_color'];
		$text_color  = isset( $config['appearance']['text_color'] )
			&& is_string( $config['appearance']['text_color'] )
			&& preg_match( '/\\A#[0-9A-Fa-f]{6}\\z/', $config['appearance']['text_color'] )
			? $config['appearance']['text_color']
			: $defaults['text_color'];
		$style       = '--hoursflow-cta-background-color: ' . $background
			. '; --hoursflow-cta-text-color: ' . $text_color . ';';

		echo '<div id="hoursflow-cta-preview" role="region" aria-labelledby="hoursflow-cta-preview-heading"'
			. ' data-hoursflow-preview="1" data-hoursflow-preview-state="open"'
			. ' data-hoursflow-preview-default-background-color="'
			. esc_attr( $defaults['background_color'] )
			. '" data-hoursflow-preview-default-text-color="'
			. esc_attr( $defaults['text_color'] )
			. '">';
		echo '<h2 id="hoursflow-cta-preview-heading">'
			. esc_html__( 'Live CTA preview', 'hoursflow' )
			. '</h2>';
		echo '<p class="description" id="hoursflow-cta-preview-instructions">'
			. esc_html__(
				'Preview the open and closed CTA with unsaved settings changes. This visual preview never navigates or performs the configured action.',
				'hoursflow'
			)
			. '</p>';
		echo '<div class="hoursflow-cta-preview__state-group" role="group" aria-labelledby="hoursflow-cta-preview-state-label">';
		echo '<span id="hoursflow-cta-preview-state-label" class="hoursflow-cta-preview__state-label">'
			. esc_html__( 'Preview state', 'hoursflow' )
			. '</span>';
		echo '<button type="button" class="hoursflow-cta-preview__state-button"'
			. ' data-hoursflow-preview-state-button="open" aria-pressed="true"'
			. ' aria-controls="hoursflow-cta-preview-content">'
			. esc_html__( 'Open', 'hoursflow' )
			. '</button>';
		echo '<button type="button" class="hoursflow-cta-preview__state-button"'
			. ' data-hoursflow-preview-state-button="closed" aria-pressed="false"'
			. ' aria-controls="hoursflow-cta-preview-content">'
			. esc_html__( 'Closed', 'hoursflow' )
			. '</button>';
		echo '</div>';
		echo '<div id="hoursflow-cta-preview-content" class="hoursflow-cta hoursflow-cta--open"'
			. ' data-hoursflow-preview-content="1" data-hoursflow-preview-state="open" style="'
			. esc_attr( $style ) . '">';
		echo '<span class="hoursflow-cta__link" data-hoursflow-preview-label="1">'
			. esc_html( $open_label )
			. '</span>';
		if ( '' === trim( $open_status ) ) {
			echo '<span class="hoursflow-cta__status" data-hoursflow-preview-status="1" hidden="hidden">';
		} else {
			echo '<span class="hoursflow-cta__status" data-hoursflow-preview-status="1">';
		}
		echo esc_html( $open_status ) . '</span>';
		echo '</div>';
		echo '</div>';
	}

	/**
	 * Render the settings-page developer promotion.
	 *
	 * @return void
	 */
	private function renderDeveloperPromotion() {
		echo '<div class="hoursflow-developer-promotion" role="region" aria-labelledby="hoursflow-developer-promotion-heading">';
		echo '<h2 id="hoursflow-developer-promotion-heading">'
			. esc_html__( 'Need a WordPress Developer?', 'hoursflow' )
			. '</h2>';
		echo '<p>'
			. esc_html__(
				'I build custom plugins, themes, and high-performance WordPress sites for businesses that need more than off-the-shelf solutions.',
				'hoursflow'
			)
			. '</p>';
		echo '<p class="hoursflow-developer-promotion__hire-row">';
		echo '<a class="button button-primary hoursflow-developer-promotion__hire" href="'
			. esc_url( self::HIRE_URL, array( 'https' ) )
			. '" target="_blank" rel="noopener noreferrer" aria-label="'
			. esc_attr__( 'Hire a WordPress developer (opens in a new tab)', 'hoursflow' )
			. '">'
			. esc_html__( 'Hire Me', 'hoursflow' )
			. '</a>';
		echo '</p>';
		echo '<hr />';
		echo '<p class="hoursflow-developer-promotion__links">';
		echo '<span class="hoursflow-developer-promotion__link">';
		echo '<span class="dashicons dashicons-coffee hoursflow-developer-promotion__coffee" aria-hidden="true"></span>';
		echo '<a class="hoursflow-developer-promotion__support" href="'
			. esc_url( self::DONATION_URL, array( 'https' ) )
			. '" target="_blank" rel="noopener noreferrer" aria-label="'
			. esc_attr__( 'Buy me a coffee to support HoursFlow (opens in a new tab)', 'hoursflow' )
			. '">'
			. esc_html__( 'Buy me a coffee', 'hoursflow' )
			. '</a>';
		echo '</span>';
		echo '</p>';
		echo '</div>';
	}

	/**
	 * Render one CTA state field group.
	 *
	 * @param array<string, mixed> $args Field arguments.
	 * @return void
	 */
	public function renderCtaField( $args = array() ) {
		$state = isset( $args['state'] ) && in_array( $args['state'], array( 'open', 'closed' ), true )
			? $args['state']
			: null;
		if ( null === $state ) {
			return;
		}

		$config                = $this->getEditorConfig();
		$cta                   = isset( $config['cta'][ $state ] ) && is_array( $config['cta'][ $state ] )
			? $config['cta'][ $state ]
			: array(
				'label'  => '',
				'action' => '',
				'status' => '',
			);
		$base_id               = 'hoursflow-cta-' . $state;
		$label_id              = $base_id . '-label';
		$action_id             = $base_id . '-action';
		$status_id             = $base_id . '-status';
		$label_description_id  = $label_id . '-description';
		$action_description_id = $action_id . '-description';
		$status_description_id = $status_id . '-description';
		$label_placeholder     = 'open' === $state
			? __( 'Example: Call now', 'hoursflow' )
			: __( 'Example: Book online', 'hoursflow' );
		$action_placeholder    = 'open' === $state
			? __( 'Example: tel:+123456789', 'hoursflow' )
			: __( 'Example: /booking/', 'hoursflow' );
		$status_placeholder    = 'open' === $state
			? __( 'Example: Open now', 'hoursflow' )
			: __( 'Example: Reopens tomorrow at 09:00', 'hoursflow' );

		echo '<div id="' . esc_attr( $base_id ) . '">';
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The complete description is escaped.
		echo '<p class="description">'
			. esc_html(
				sprintf(
					/* translators: %s: the business state, either open or closed. */
					__( 'Configure the CTA shown when the business is %s. Enter administrator content as plain text.', 'hoursflow' ),
					'open' === $state ? __( 'open', 'hoursflow' ) : __( 'closed', 'hoursflow' )
				)
			)
			. '</p>';

		echo '<p><label for="' . esc_attr( $label_id ) . '">'
			. esc_html__( 'CTA label', 'hoursflow' )
			. '</label><br />';
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- All label-control attributes are escaped.
		echo '<input type="text" class="regular-text" id="' . esc_attr( $label_id )
			. '" name="hoursflow_config[cta][' . esc_attr( $state ) . '][label]" value="'
			. esc_attr( $cta['label'] )
			. '" placeholder="' . esc_attr( $label_placeholder )
			. '" required="required" autocomplete="off"'
			. $this->getErrorAttributes(
				$label_description_id,
				array( 'hoursflow_cta_' . $state, 'hoursflow_cta_' . $state . '_label' )
			)
			. ' />';
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</p>';
		echo '<p class="description" id="' . esc_attr( $label_description_id ) . '">'
			. esc_html__( 'Required plain-text button label; angle brackets are not allowed.', 'hoursflow' )
			. '</p>';

		echo '<p><label for="' . esc_attr( $action_id ) . '">'
			. esc_html__( 'CTA action', 'hoursflow' )
			. '</label><br />';
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- All action-control attributes are escaped.
		echo '<input type="text" class="regular-text" id="' . esc_attr( $action_id )
			. '" name="hoursflow_config[cta][' . esc_attr( $state ) . '][action]" value="'
			. esc_attr( $cta['action'] )
			. '" placeholder="' . esc_attr( $action_placeholder )
			. '" required="required" inputmode="url" autocomplete="off"'
			. $this->getErrorAttributes(
				$action_description_id,
				array( 'hoursflow_cta_' . $state, 'hoursflow_cta_' . $state . '_action' )
			)
			. ' />';
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</p>';
		echo '<p class="description" id="' . esc_attr( $action_description_id ) . '">'
			. esc_html__( 'Required root-relative URL, HTTPS URL, or telephone action (tel:).', 'hoursflow' )
			. '</p>';

		echo '<p><label for="' . esc_attr( $status_id ) . '">'
			. esc_html__( 'CTA status', 'hoursflow' )
			. '</label><br />';
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- All status-control attributes are escaped.
		echo '<input type="text" class="regular-text" id="' . esc_attr( $status_id )
			. '" name="hoursflow_config[cta][' . esc_attr( $state ) . '][status]" value="'
			. esc_attr( $cta['status'] )
			. '" placeholder="' . esc_attr( $status_placeholder )
			. '" autocomplete="off"'
			. $this->getErrorAttributes(
				$status_description_id,
				array( 'hoursflow_cta_' . $state, 'hoursflow_cta_' . $state . '_status' )
			)
			. ' />';
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</p>';
		echo '<p class="description" id="' . esc_attr( $status_description_id ) . '">'
			. esc_html__( 'Optional plain-text status shown with this CTA.', 'hoursflow' )
			. '</p>';
		echo '</div>';
	}

	/**
	 * Validate a Settings API submission without ever partially persisting it.
	 *
	 * @param mixed $value
	 * @return array|false
	 */
	public function sanitize( $value ) {
		$validated = Validator::validate( $value );
		if ( ! is_wp_error( $validated ) ) {
			return $validated;
		}

		foreach ( $validated->get_error_codes() as $code ) {
			$field      = '';
			$error_data = $validated->get_error_data( $code );
			if ( is_array( $error_data ) && isset( $error_data['field'] ) && is_string( $error_data['field'] ) ) {
				$field = $error_data['field'];
			}

			foreach ( $validated->get_error_messages( $code ) as $message ) {
				add_settings_error(
					Schema::OPTION_NAME,
					(string) $code,
					$this->contextualizeValidationMessage( $message, $field ),
					'error'
				);
			}
		}

		return get_option( Schema::OPTION_NAME, false );
	}

	/**
	 * Return a complete, safe editor view of the stored option.
	 *
	 * Invalid or incomplete stored values are never repaired on read.
	 *
	 * @return array<string, mixed>
	 */
	private function getEditorConfig() {
		if ( null !== $this->editor_config ) {
			return $this->editor_config;
		}

		$stored              = get_option( Schema::OPTION_NAME, false );
		$validated           = Validator::validate( $stored );
		$this->editor_config = is_wp_error( $validated )
			? $this->getFreshEditorConfig()
			: $validated;

		return $this->editor_config;
	}

	/**
	 * @return array<string, mixed>
	 */
	private function getFreshEditorConfig() {
		$schedule = array();
		foreach ( Schema::days() as $day ) {
			$schedule[ $day ] = Schema::closedScheduleEntry();
		}

		return array(
			'timezone'   => '',
			'schedule'   => $schedule,
			'cta'        => array(
				'open'   => array(
					'label'  => '',
					'action' => '',
					'status' => '',
				),
				'closed' => array(
					'label'  => '',
					'action' => '',
					'status' => '',
				),
			),
			'appearance' => array(
				'background_color' => '',
				'text_color'       => '',
			),
		);
	}

	/**
	 * Get WordPress's localized timezone choices without its manual-offset group.
	 *
	 * @param string $selected
	 * @return string
	 */
	private function getTimezoneChoice( $selected ) {
		if ( ! function_exists( 'wp_timezone_choice' ) ) {
			return '';
		}

		$choices = wp_timezone_choice( $selected );
		if ( ! is_string( $choices ) ) {
			return '';
		}

		$manual_label = 'Manual Offsets';
		if ( function_exists( 'translate' ) && function_exists( 'esc_attr' ) ) {
			// phpcs:ignore WordPress.WP.I18n.LowLevelTranslationFunction,WordPress.WP.I18n.TextDomainMismatch -- Matching a WordPress-core label requires the default domain.
			$manual_label = esc_attr( translate( 'Manual Offsets', 'default' ) );
		}

		$pattern  = '/<optgroup\\b[^>]*\\blabel\\s*=\\s*["\']'
			. preg_quote( $manual_label, '/' )
			. '["\'][^>]*>.*?<\\/optgroup>/is';
		$filtered = preg_replace( $pattern, '', $choices );

		return null === $filtered ? $choices : $filtered;
	}

	/**
	 * @param string $state
	 * @return void
	 */
	private function renderCtaSectionDescription( $state ) {
		$state_label = 'open' === $state
			? __( 'open', 'hoursflow' )
			: __( 'closed', 'hoursflow' );

		echo '<p class="description">'
			. esc_html(
				sprintf(
					/* translators: %s: the business state, either open or closed. */
					__( 'Set the label, action, and optional status for the CTA used while the business is %s.', 'hoursflow' ),
					$state_label
				)
			)
			. '</p>';
	}

	/**
	 * Return a localized weekday label. WordPress numbers Sunday as zero.
	 *
	 * @param string             $day
	 * @param array<string, int> $day_numbers
	 * @return string
	 */
	private function getWeekdayLabel( $day, $day_numbers = array() ) {
		if ( empty( $day_numbers ) ) {
			$day_numbers = array(
				'monday'    => 1,
				'tuesday'   => 2,
				'wednesday' => 3,
				'thursday'  => 4,
				'friday'    => 5,
				'saturday'  => 6,
				'sunday'    => 0,
			);
		}

		global $wp_locale;
		if ( isset( $wp_locale ) && is_object( $wp_locale ) && method_exists( $wp_locale, 'get_weekday' )
			&& isset( $day_numbers[ $day ] )
		) {
			$label = $wp_locale->get_weekday( $day_numbers[ $day ] );
			if ( is_string( $label ) && '' !== $label ) {
				return $label;
			}
		}

		$fallbacks = array(
			'monday'    => __( 'Monday', 'hoursflow' ),
			'tuesday'   => __( 'Tuesday', 'hoursflow' ),
			'wednesday' => __( 'Wednesday', 'hoursflow' ),
			'thursday'  => __( 'Thursday', 'hoursflow' ),
			'friday'    => __( 'Friday', 'hoursflow' ),
			'saturday'  => __( 'Saturday', 'hoursflow' ),
			'sunday'    => __( 'Sunday', 'hoursflow' ),
		);

		return isset( $fallbacks[ $day ] ) ? $fallbacks[ $day ] : $day;
	}

	/**
	 * Associate matching Settings API errors with a form control.
	 *
	 * @param string             $description_id
	 * @param array<int, string> $error_codes
	 * @return string
	 */
	private function getErrorAttributes( $description_id, $error_codes ) {
		$description_ids = array( $description_id );
		$has_error       = false;

		foreach ( get_settings_errors( Schema::OPTION_NAME ) as $error ) {
			if ( ! isset( $error['code'] ) || ! in_array( $error['code'], $error_codes, true ) ) {
				continue;
			}

			$description_ids[] = 'setting-error-' . $error['code'];
			$has_error         = true;
		}

		$attributes = ' aria-describedby="' . esc_attr( implode( ' ', array_unique( $description_ids ) ) ) . '"';
		if ( $has_error ) {
			$attributes .= ' aria-invalid="true"';
		}

		return $attributes;
	}

	/**
	 * Add a stable, localized field context to a validator notice.
	 *
	 * @param string $message
	 * @param string $field
	 * @return string
	 */
	private function contextualizeValidationMessage( $message, $field ) {
		if ( '' === $field ) {
			return $message;
		}

		$parts = explode( '.', $field );
		$root  = $parts[0];

		if ( 'timezone' === $root ) {
			return sprintf(
				/* translators: 1: field label, 2: validation message. */
				__( '%1$s: %2$s', 'hoursflow' ),
				__( 'Business timezone', 'hoursflow' ),
				$message
			);
		}

		if ( 'schedule' === $root ) {
			if ( ! isset( $parts[1] ) || ! in_array( $parts[1], Schema::days(), true ) ) {
				/* translators: %s: validation message. */
				return sprintf( __( 'Weekly hours: %s', 'hoursflow' ), $message );
			}

			$day_label = $this->getWeekdayLabel( $parts[1] );
			if ( ! isset( $parts[2] ) ) {
				/* translators: 1: field label, 2: validation message. */
				return sprintf( __( '%1$s: %2$s', 'hoursflow' ), $day_label, $message );
			}

			$schedule_labels = array(
				'type'   => __( 'Day status', 'hoursflow' ),
				'opens'  => __( 'Opening time', 'hoursflow' ),
				'closes' => __( 'Closing time', 'hoursflow' ),
			);
			if ( ! isset( $schedule_labels[ $parts[2] ] ) ) {
				/* translators: 1: field label, 2: validation message. */
				return sprintf( __( '%1$s: %2$s', 'hoursflow' ), $day_label, $message );
			}

			return sprintf(
				/* translators: 1: section label, 2: field label, 3: validation message. */
				__( '%1$s — %2$s: %3$s', 'hoursflow' ),
				$day_label,
				$schedule_labels[ $parts[2] ],
				$message
			);
		}

		if ( 'cta' === $root ) {
			if ( ! isset( $parts[1] ) || ! in_array( $parts[1], array( 'open', 'closed' ), true ) ) {
				/* translators: %s: validation message. */
				return sprintf( __( 'CTA settings: %s', 'hoursflow' ), $message );
			}

			$state_label = 'open' === $parts[1]
				? __( 'Open CTA', 'hoursflow' )
				: __( 'Closed CTA', 'hoursflow' );
			if ( ! isset( $parts[2] ) ) {
				/* translators: 1: field label, 2: validation message. */
				return sprintf( __( '%1$s: %2$s', 'hoursflow' ), $state_label, $message );
			}

			$cta_labels = array(
				'label'  => __( 'Label', 'hoursflow' ),
				'action' => __( 'Action', 'hoursflow' ),
				'status' => __( 'Status', 'hoursflow' ),
			);
			if ( ! isset( $cta_labels[ $parts[2] ] ) ) {
				/* translators: 1: field label, 2: validation message. */
				return sprintf( __( '%1$s: %2$s', 'hoursflow' ), $state_label, $message );
			}

			return sprintf(
				/* translators: 1: section label, 2: field label, 3: validation message. */
				__( '%1$s — %2$s: %3$s', 'hoursflow' ),
				$state_label,
				$cta_labels[ $parts[2] ],
				$message
			);
		}

		if ( 'appearance' === $root ) {
			$appearance_labels = array(
				'background_color' => __( 'Background color', 'hoursflow' ),
				'text_color'       => __( 'Text color', 'hoursflow' ),
			);
			if ( isset( $parts[1] ) && isset( $appearance_labels[ $parts[1] ] ) ) {
				return sprintf(
					/* translators: 1: field label, 2: validation message. */
					__( '%1$s: %2$s', 'hoursflow' ),
					$appearance_labels[ $parts[1] ],
					$message
				);
			}

			/* translators: %s: validation message. */
			return sprintf( __( 'Appearance: %s', 'hoursflow' ), $message );
		}

		if ( 'config' === $root ) {
			/* translators: %s: validation message. */
			return sprintf( __( 'Configuration: %s', 'hoursflow' ), $message );
		}

		return $message;
	}
}
