<?php
namespace OpenNow\Tests;

use OpenNow\Admin\Settings;
use OpenNow\Config\Schema;
use PHPUnit\Framework\TestCase;

final class SettingsTest extends TestCase
{
    protected function setUp(): void
    {
        opennow_reset_wp_stubs();
    }

    public function testSettingsRegistersOnlyTheAtomicOptionOnAdminInit(): void
    {
        $settings = new Settings();
        $settings->register();

        $this->assertArrayHasKey('admin_init', $GLOBALS['opennow_test_hooks']);
        $this->assertCount(1, $GLOBALS['opennow_test_hooks']['admin_init']);
        $this->assertSame(array(), $GLOBALS['opennow_test_registered_settings']);

        do_action('admin_init');

        $this->assertArrayHasKey(
            Schema::OPTION_NAME,
            $GLOBALS['opennow_test_registered_settings']
        );
        $registration = $GLOBALS['opennow_test_registered_settings'][Schema::OPTION_NAME];
        $this->assertSame('opennow', $registration['group']);
        $this->assertSame('array', $registration['args']['type']);
        $this->assertFalse($registration['args']['show_in_rest']);
        $this->assertIsArray($registration['args']['sanitize_callback']);
        $this->assertSame('sanitize', $registration['args']['sanitize_callback'][1]);
        $this->assertCount(5, $GLOBALS['opennow_test_settings_sections'][Settings::PAGE_SLUG]);
        $this->assertCount(1, $GLOBALS['opennow_test_settings_fields'][Settings::PAGE_SLUG][Settings::SCHEDULE_SECTION]);
        $appearance_fields = $GLOBALS['opennow_test_settings_fields'][Settings::PAGE_SLUG][Settings::APPEARANCE_SECTION];
        $this->assertCount(2, $appearance_fields);
        $this->assertSame(
            'opennow-appearance-background-color',
            $appearance_fields['opennow_appearance_background_color']['args']['label_for']
        );
        $this->assertSame(
            'opennow-appearance-text-color',
            $appearance_fields['opennow_appearance_text_color']['args']['label_for']
        );
    }

    public function testSettingsPageUsesManageOptionsAndSettingsApiNonce(): void
    {
        $settings = new Settings();
        $settings->register();

        do_action('admin_menu');
        do_action('admin_init');

        $page = $GLOBALS['opennow_test_admin_pages'][Settings::PAGE_SLUG];
        $this->assertSame(Settings::PAGE_CAPABILITY, $page['capability']);
        $this->assertSame(
            Settings::PAGE_CAPABILITY,
            apply_filters('option_page_capability_opennow', 'different_capability')
        );

        ob_start();
        call_user_func($page['callback']);
        $output = (string) ob_get_clean();

        $this->assertStringContainsString('<form action="options.php" method="post">', $output);
        $this->assertStringContainsString('name="option_page" value="opennow"', $output);
        $this->assertStringContainsString('name="_wpnonce" value="test-nonce"', $output);
        $this->assertStringContainsString('name="opennow_config[timezone]"', $output);
        $this->assertStringContainsString('name="opennow_config[cta][open][label]"', $output);
        $this->assertStringContainsString('name="opennow_config[cta][closed][action]"', $output);
        $this->assertStringContainsString('name="opennow_config[appearance][background_color]"', $output);
        $this->assertStringContainsString('name="opennow_config[appearance][text_color]"', $output);
        $this->assertStringNotContainsString('type="hidden" name="opennow_config[appearance]', $output);
        $this->assertSame(7, substr_count($output, 'data-opennow-schedule-day='));
        $this->assertSame(7, substr_count($output, 'checked="checked"'));
        $this->assertSame(array(), $GLOBALS['opennow_test_option_calls']);
        $this->assertStringContainsString('value="America/New_York"', $output);
        $this->assertStringContainsString('value="UTC"', $output);
        $this->assertStringNotContainsString('Manual Offsets', $output);
        $this->assertStringNotContainsString('value="UTC+1"', $output);
    }

    public function testSettingsPageRendersAnAccessibleNonNavigatingPreviewFromEditorValues(): void
    {
        $config = $this->validConfig();
        $config['cta']['open']['label'] = 'Call & Go';
        $config['cta']['open']['status'] = 'Open "today" & later';
        $config['appearance'] = array(
            'background_color' => '#000000',
            'text_color' => '#FFFFFF',
        );
        $GLOBALS['opennow_test_options'][Schema::OPTION_NAME] = $config;

        $settings = new Settings();
        $settings->register();
        do_action('admin_menu');
        do_action('admin_init');

        ob_start();
        $settings->renderPage();
        $output = (string) ob_get_clean();
        $xpath = $this->parseHtml($output);

        $layout = $xpath->query('//*[@id="opennow-settings-layout"]')->item(0);
        $this->assertInstanceOf(\DOMElement::class, $layout);
        $this->assertCount(1, $xpath->query('./form', $layout));

        $preview = $xpath->query('./div[@id="opennow-cta-preview"]', $layout)->item(0);
        $this->assertInstanceOf(\DOMElement::class, $preview);
        $this->assertSame('region', $preview->getAttribute('role'));
        $this->assertSame('opennow-cta-preview-heading', $preview->getAttribute('aria-labelledby'));
        $this->assertSame('open', $preview->getAttribute('data-opennow-preview-state'));
        $this->assertSame('#166534', $preview->getAttribute('data-opennow-preview-default-background-color'));
        $this->assertSame('#FFFFFF', $preview->getAttribute('data-opennow-preview-default-text-color'));

        $group = $xpath->query('.//*[@role="group"]', $preview)->item(0);
        $this->assertInstanceOf(\DOMElement::class, $group);
        $this->assertSame('opennow-cta-preview-state-label', $group->getAttribute('aria-labelledby'));

        $open_button = $xpath->query('.//button[@data-opennow-preview-state-button="open"]', $preview)->item(0);
        $closed_button = $xpath->query('.//button[@data-opennow-preview-state-button="closed"]', $preview)->item(0);
        $this->assertInstanceOf(\DOMElement::class, $open_button);
        $this->assertInstanceOf(\DOMElement::class, $closed_button);
        $this->assertSame('button', $open_button->getAttribute('type'));
        $this->assertSame('button', $closed_button->getAttribute('type'));
        $this->assertSame('true', $open_button->getAttribute('aria-pressed'));
        $this->assertSame('false', $closed_button->getAttribute('aria-pressed'));
        $this->assertSame('opennow-cta-preview-content', $open_button->getAttribute('aria-controls'));
        $this->assertSame('opennow-cta-preview-content', $closed_button->getAttribute('aria-controls'));
        $this->assertSame('Open', trim($open_button->textContent));
        $this->assertSame('Closed', trim($closed_button->textContent));

        $content = $xpath->query('.//*[@id="opennow-cta-preview-content"]', $preview)->item(0);
        $this->assertInstanceOf(\DOMElement::class, $content);
        $this->assertStringContainsString('opennow-cta--open', $content->getAttribute('class'));
        $this->assertStringContainsString('--opennow-cta-background-color: #000000;', $content->getAttribute('style'));
        $this->assertStringContainsString('--opennow-cta-text-color: #FFFFFF;', $content->getAttribute('style'));

        $label = $xpath->query('.//*[@data-opennow-preview-label="1"]', $preview)->item(0);
        $status = $xpath->query('.//*[@data-opennow-preview-status="1"]', $preview)->item(0);
        $this->assertSame('Call & Go', $label->textContent);
        $this->assertSame('Open "today" & later', $status->textContent);
        $this->assertFalse($status->hasAttribute('hidden'));
        $this->assertStringContainsString('Call &amp; Go', $output);
        $this->assertCount(0, $xpath->query('.//a | .//*[@href]', $preview));
        $this->assertCount(0, $xpath->query('.//*[@name]', $preview));
        $this->assertStringNotContainsString('tel:+123456789', $preview->textContent);
    }

    public function testSettingsPreviewUsesDefaultColorsAndHidesABlankStatus(): void
    {
        $GLOBALS['opennow_test_options'][Schema::OPTION_NAME] = $this->validConfig();
        $settings = new Settings();
        $settings->register();
        do_action('admin_menu');
        do_action('admin_init');

        ob_start();
        $settings->renderPage();
        $output = (string) ob_get_clean();
        $xpath = $this->parseHtml($output);

        $preview = $xpath->query('//*[@id="opennow-cta-preview"]')->item(0);
        $content = $xpath->query('.//*[@id="opennow-cta-preview-content"]', $preview)->item(0);
        $status = $xpath->query('.//*[@data-opennow-preview-status="1"]', $preview)->item(0);

        $this->assertStringContainsString('--opennow-cta-background-color: #166534;', $content->getAttribute('style'));
        $this->assertStringContainsString('--opennow-cta-text-color: #FFFFFF;', $content->getAttribute('style'));
        $this->assertTrue($status->hasAttribute('hidden'));
        $this->assertSame('', $status->textContent);
        $this->assertStringContainsString('unsaved settings changes', $preview->textContent);
        $this->assertStringContainsString('never navigates', $preview->textContent);
    }

    public function testSettingsPageRendersASecureDeveloperPromotionAfterThePreview(): void
    {
        $settings = new Settings();
        $settings->register();
        do_action('admin_menu');
        do_action('admin_init');

        ob_start();
        $settings->renderPage();
        $output = (string) ob_get_clean();
        $xpath = $this->parseHtml($output);

        $layout = $xpath->query('//*[@id="opennow-settings-layout"]')->item(0);
        $this->assertInstanceOf(\DOMElement::class, $layout);
        $this->assertCount(3, $xpath->query('./*', $layout));
        $this->assertSame('form', $xpath->query('./*[1]', $layout)->item(0)->nodeName);
        $this->assertSame('opennow-cta-preview', $xpath->query('./*[2]', $layout)->item(0)->getAttribute('id'));

        $promotion = $xpath->query('./div[contains(@class, "opennow-developer-promotion")]', $layout)->item(0);
        $this->assertInstanceOf(\DOMElement::class, $promotion);
        $this->assertSame($promotion, $xpath->query('./*[3]', $layout)->item(0));
        $this->assertSame('region', $promotion->getAttribute('role'));
        $this->assertSame('opennow-developer-promotion-heading', $promotion->getAttribute('aria-labelledby'));
        $this->assertStringContainsString('Need a WordPress Developer?', $promotion->textContent);
        $this->assertStringContainsString(
            'I build custom plugins, themes, and high-performance WordPress sites for businesses that need more than off-the-shelf solutions.',
            $promotion->textContent
        );

        $hire = $xpath->query('.//a[contains(@class, "opennow-developer-promotion__hire")]', $promotion)->item(0);
        $donation = $xpath->query('.//a[contains(@class, "opennow-developer-promotion__support")]', $promotion)->item(0);
        $review = $xpath->query('.//a[contains(@class, "opennow-developer-promotion__review")]', $promotion)->item(0);
        $this->assertInstanceOf(\DOMElement::class, $hire);
        $this->assertInstanceOf(\DOMElement::class, $donation);
        $this->assertInstanceOf(\DOMElement::class, $review);
        $this->assertSame(
            'https://gasatrya.com/?utm_source=plugin&utm_medium=opennow-sidebar',
            $hire->getAttribute('href')
        );
        $this->assertSame(
            'https://gasatrya.com/donate/?utm_source=plugin&utm_medium=opennow-sidebar',
            $donation->getAttribute('href')
        );
        $this->assertSame(
            'https://wordpress.org/support/plugin/opennow/reviews/#new-post',
            $review->getAttribute('href')
        );
        $this->assertSame('Hire Me', trim($hire->textContent));
        $this->assertSame('Buy me a coffee', trim($donation->textContent));
        $this->assertSame('Rate this plugin', trim($review->textContent));

        foreach (array($hire, $donation, $review) as $link) {
            $this->assertSame('_blank', $link->getAttribute('target'));
            $this->assertSame('noopener noreferrer', $link->getAttribute('rel'));
            $this->assertStringContainsString('opens in a new tab', $link->getAttribute('aria-label'));
        }

        $coffee = $xpath->query('.//span[contains(@class, "dashicons-coffee")]', $promotion)->item(0);
        $star = $xpath->query('.//span[contains(@class, "dashicons-star-filled")]', $promotion)->item(0);
        $separator = $xpath->query('.//span[contains(@class, "opennow-developer-promotion__separator")]', $promotion)->item(0);
        $this->assertInstanceOf(\DOMElement::class, $coffee);
        $this->assertInstanceOf(\DOMElement::class, $star);
        $this->assertInstanceOf(\DOMElement::class, $separator);
        $this->assertSame('true', $coffee->getAttribute('aria-hidden'));
        $this->assertSame('true', $star->getAttribute('aria-hidden'));
        $this->assertSame('·', trim($separator->textContent));

        $this->assertStringContainsString('utm_source=plugin&amp;utm_medium=opennow-sidebar', $output);
        $this->assertStringNotContainsString('buttonflow', strtolower($output));
        $this->assertCount(0, $xpath->query('.//*[@style]', $promotion));
        $this->assertCount(0, $xpath->query('.//img | .//script | .//iframe | .//link | .//object | .//embed', $promotion));
    }

    public function testAuthorizedSettingsPageLoadsExactlyFiveContextualHelpTabs(): void
    {
        $settings = new Settings();
        $settings->register();
        do_action('admin_menu');

        $load_hook = 'load-settings_page_opennow';
        $this->assertArrayHasKey($load_hook, $GLOBALS['opennow_test_hooks']);
        $this->assertArrayNotHasKey('load-settings_page_other', $GLOBALS['opennow_test_hooks']);
        $this->assertCount(1, $GLOBALS['opennow_test_hooks'][$load_hook]);
        $this->assertSame(
            array($settings, 'registerContextualHelp'),
            $GLOBALS['opennow_test_hooks'][$load_hook][0]['callback']
        );

        $GLOBALS['opennow_test_current_screen'] = new \OpenNow_Test_Screen('settings_page_opennow');
        do_action($load_hook);

        $tabs = $GLOBALS['opennow_test_current_screen']->help_tabs;
        $this->assertCount(5, $tabs);
        $this->assertSame(
            array(
                'opennow-timezone-help',
                'opennow-appearance-help',
                'opennow-weekly-hours-help',
                'opennow-open-cta-help',
                'opennow-closed-cta-help',
            ),
            array_column($tabs, 'id')
        );
        $this->assertSame(
            array(
                'Business timezone',
                'CTA appearance',
                'Weekly hours',
                'CTA while open',
                'CTA while closed',
            ),
            array_column($tabs, 'title')
        );

        $this->assertStringContainsString('America/New_York', $tabs[0]['content']);
        $this->assertStringContainsString('visitor, browser, server, or WordPress site timezone', $tabs[0]['content']);
        $this->assertStringContainsString('Raw UTC offsets', $tabs[0]['content']);
        $this->assertStringContainsString('native color picker', $tabs[1]['content']);
        $this->assertStringContainsString('legacy stored value is blank', $tabs[1]['content']);
        $this->assertStringContainsString('plugin default', $tabs[1]['content']);
        $this->assertStringContainsString('shortcode and every OpenNow block', $tabs[1]['content']);
        $this->assertStringContainsString('WCAG 2.2 AA', $tabs[1]['content']);
        $this->assertStringContainsString('exact 24-hour HH:MM', $tabs[2]['content']);
        $this->assertStringContainsString('exactly one period', $tabs[2]['content']);
        $this->assertStringContainsString('closed day has no opening or closing times', $tabs[2]['content']);
        $this->assertStringContainsString('overnight', $tabs[2]['content']);
        $this->assertStringContainsString('24:00 or multiple periods', $tabs[2]['content']);

        foreach (array(3, 4) as $cta_tab_index) {
            $this->assertStringContainsString('plain-text', $tabs[$cta_tab_index]['content']);
            $this->assertStringContainsString('angle brackets are not allowed', $tabs[$cta_tab_index]['content']);
            $this->assertStringContainsString('/booking/', $tabs[$cta_tab_index]['content']);
            $this->assertStringContainsString('https://example.com/book', $tabs[$cta_tab_index]['content']);
            $this->assertStringContainsString('tel:+123456789', $tabs[$cta_tab_index]['content']);
            $this->assertStringContainsString('optional plain-text status', $tabs[$cta_tab_index]['content']);
            $this->assertStringContainsString('leave it blank to omit it', $tabs[$cta_tab_index]['content']);
            $this->assertSame(2, substr_count($tabs[$cta_tab_index]['content'], '<p>'));
        }
    }

    public function testContextualHelpRequiresTheExactScreenAndCapability(): void
    {
        $settings = new Settings();
        $settings->register();
        do_action('admin_menu');

        $load_hook = 'load-settings_page_opennow';
        $wrong_screen = new \OpenNow_Test_Screen('settings_page_other');
        $GLOBALS['opennow_test_current_screen'] = $wrong_screen;
        do_action($load_hook);
        $this->assertCount(0, $wrong_screen->help_tabs);

        $unauthorized_screen = new \OpenNow_Test_Screen('settings_page_opennow');
        $GLOBALS['opennow_test_current_screen'] = $unauthorized_screen;
        $GLOBALS['opennow_test_current_user_can'] = false;
        do_action($load_hook);
        $this->assertCount(0, $unauthorized_screen->help_tabs);
    }

    public function testUnauthorizedPageDoesNotRegisterAContextualHelpLoadHook(): void
    {
        $GLOBALS['opennow_test_current_user_can'] = false;
        $settings = new Settings();
        $settings->register();
        do_action('admin_menu');

        $this->assertArrayNotHasKey(
            'load-settings_page_opennow',
            $GLOBALS['opennow_test_hooks']
        );
    }

    public function testDescriptionMarkupUsesSiblingParagraphsForAppearanceAndCtaControls(): void
    {
        $config = $this->validConfig();
        $config['appearance'] = array(
            'background_color' => '#000000',
            'text_color' => '#FFFFFF',
        );
        $GLOBALS['opennow_test_options'][Schema::OPTION_NAME] = $config;
        $settings = new Settings();

        ob_start();
        $settings->renderAppearanceField(array('color' => 'background_color'));
        $settings->renderAppearanceField(array('color' => 'text_color'));
        $settings->renderCtaField(array('state' => 'open'));
        $settings->renderCtaField(array('state' => 'closed'));
        $output = (string) ob_get_clean();

        $this->assertStringNotContainsString('<span class="description"', $output);
        $xpath = $this->parseHtml($output);
        $this->assertCount(0, $xpath->query('//span[contains(concat(" ", normalize-space(@class), " "), " description ")]'));

        $controls = array(
            'opennow-appearance-background-color' => array(
                'name' => 'opennow_config[appearance][background_color]',
                'value' => '#000000',
                'type' => 'color',
            ),
            'opennow-appearance-text-color' => array(
                'name' => 'opennow_config[appearance][text_color]',
                'value' => '#FFFFFF',
                'type' => 'color',
            ),
            'opennow-cta-open-label' => array(
                'name' => 'opennow_config[cta][open][label]',
                'value' => 'Call Now',
                'placeholder' => 'Example: Call now',
            ),
            'opennow-cta-open-action' => array(
                'name' => 'opennow_config[cta][open][action]',
                'value' => 'tel:+123456789',
                'placeholder' => 'Example: tel:+123456789',
            ),
            'opennow-cta-open-status' => array(
                'name' => 'opennow_config[cta][open][status]',
                'value' => '',
                'placeholder' => 'Example: Open now',
            ),
            'opennow-cta-closed-label' => array(
                'name' => 'opennow_config[cta][closed][label]',
                'value' => 'Book online',
                'placeholder' => 'Example: Book online',
            ),
            'opennow-cta-closed-action' => array(
                'name' => 'opennow_config[cta][closed][action]',
                'value' => '/booking/',
                'placeholder' => 'Example: /booking/',
            ),
            'opennow-cta-closed-status' => array(
                'name' => 'opennow_config[cta][closed][status]',
                'value' => '',
                'placeholder' => 'Example: Reopens tomorrow at 09:00',
            ),
        );

        foreach ($controls as $id => $control) {
            $nodes = $xpath->query('//input[@id="' . $id . '"]');
            $this->assertCount(1, $nodes);
            $input = $nodes->item(0);
            $this->assertSame($id, $input->getAttribute('id'));
            $this->assertSame($control['name'], $input->getAttribute('name'));
            $this->assertSame($control['value'], $input->getAttribute('value'));
            if (isset($control['type'])) {
                $this->assertSame($control['type'], $input->getAttribute('type'));
                $this->assertFalse($input->hasAttribute('placeholder'));
                $this->assertFalse($input->hasAttribute('maxlength'));
                $this->assertFalse($input->hasAttribute('pattern'));
                $this->assertFalse($input->hasAttribute('inputmode'));
                $this->assertFalse($input->hasAttribute('autocomplete'));
            } else {
                $this->assertSame($control['placeholder'], $input->getAttribute('placeholder'));
            }
            $description_id = $id . '-description';
            $this->assertSame($description_id, $input->getAttribute('aria-describedby'));

            $description = $input->parentNode->nextSibling;
            $this->assertInstanceOf(\DOMElement::class, $description);
            $this->assertSame('p', $description->nodeName);
            $this->assertSame('description', $description->getAttribute('class'));
            $this->assertSame($description_id, $description->getAttribute('id'));

            if (false !== strpos($id, 'opennow-cta-')) {
                $labels = $xpath->query('//label[@for="' . $id . '"]');
                $this->assertCount(1, $labels);
                $this->assertSame($id, $labels->item(0)->getAttribute('for'));
            }
        }
    }

    public function testCtaValidationErrorsKeepFieldAssociationsAndAriaInvalid(): void
    {
        $config = $this->validConfig();
        $GLOBALS['opennow_test_options'][Schema::OPTION_NAME] = $config;
        $invalid = $config;
        $invalid['cta']['open']['label'] = '<strong>Call Now</strong>';
        $invalid['cta']['closed']['action'] = 'javascript:alert(1)';

        (new Settings())->sanitize($invalid);
        $settings = new Settings();
        ob_start();
        $settings->renderCtaField(array('state' => 'open'));
        $settings->renderCtaField(array('state' => 'closed'));
        $output = (string) ob_get_clean();
        $xpath = $this->parseHtml($output);

        $open_label = $xpath->query('//input[@id="opennow-cta-open-label"]')->item(0);
        $this->assertSame('true', $open_label->getAttribute('aria-invalid'));
        $this->assertSame(
            'opennow-cta-open-label-description setting-error-opennow_cta_open_label',
            $open_label->getAttribute('aria-describedby')
        );

        $closed_action = $xpath->query('//input[@id="opennow-cta-closed-action"]')->item(0);
        $this->assertSame('true', $closed_action->getAttribute('aria-invalid'));
        $this->assertSame(
            'opennow-cta-closed-action-description setting-error-opennow_cta_closed_action',
            $closed_action->getAttribute('aria-describedby')
        );
    }

    public function testUnauthorizedUserCannotRenderSettingsPage(): void
    {
        $settings = new Settings();
        $GLOBALS['opennow_test_current_user_can'] = false;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('You do not have permission');

        $settings->renderPage();
    }

    public function testAdminScriptLoadsOnlyOnTheAuthorizedSettingsPage(): void
    {
        $settings = new Settings();
        $settings->register();
        do_action('admin_menu');

        do_action('admin_enqueue_scripts', 'settings_page_other');
        $this->assertSame(array(), $GLOBALS['opennow_test_enqueued_scripts']);
        $this->assertSame(array(), $GLOBALS['opennow_test_enqueued_styles']);

        $GLOBALS['opennow_test_current_user_can'] = false;
        do_action('admin_enqueue_scripts', 'settings_page_opennow');
        $this->assertSame(array(), $GLOBALS['opennow_test_enqueued_scripts']);
        $this->assertSame(array(), $GLOBALS['opennow_test_enqueued_styles']);

        $GLOBALS['opennow_test_current_user_can'] = true;
        do_action('admin_enqueue_scripts', 'settings_page_opennow');

        $this->assertArrayHasKey('opennow-admin-settings', $GLOBALS['opennow_test_enqueued_scripts']);
        $script = $GLOBALS['opennow_test_enqueued_scripts']['opennow-admin-settings'];
        $this->assertStringEndsWith('/assets/admin/settings.js', $script['src']);
        $this->assertFileExists(dirname(__DIR__) . '/assets/admin/settings.js');
        $this->assertSame(array(), $script['deps']);
        $this->assertTrue($script['args']);

        $this->assertArrayHasKey('opennow-admin-settings-style', $GLOBALS['opennow_test_enqueued_styles']);
        $style = $GLOBALS['opennow_test_enqueued_styles']['opennow-admin-settings-style'];
        $this->assertStringEndsWith('/assets/admin/settings.css', $style['src']);
        $this->assertFileExists(dirname(__DIR__) . '/assets/admin/settings.css');
        $this->assertSame(array(), $style['deps']);
        $this->assertSame('all', $style['media']);
    }

    public function testAppearanceFieldsUseNativePickersAndDisplayEffectiveDefaults(): void
    {
        $settings = new Settings();

        ob_start();
        $settings->renderAppearanceSection();
        $section = (string) ob_get_clean();
        $this->assertStringContainsString('global CTA background and text colors', $section);
        $this->assertStringContainsString('for the shortcode and OpenNow blocks', $section);
        $this->assertStringContainsString('must meet WCAG 2.2 AA contrast for normal text', $section);

        ob_start();
        $settings->renderAppearanceField(array('color' => 'background_color'));
        $settings->renderAppearanceField(array('color' => 'text_color'));
        $output = (string) ob_get_clean();

        $this->assertSame(2, substr_count($output, 'type="color"'));
        $this->assertStringContainsString('name="opennow_config[appearance][background_color]" value="#166534"', $output);
        $this->assertStringContainsString('name="opennow_config[appearance][text_color]" value="#FFFFFF"', $output);
        $this->assertStringContainsString('Global CTA background color. Plugin default: #166534.', $output);
        $this->assertStringContainsString('Global CTA text color. Plugin default: #FFFFFF.', $output);
        $this->assertStringNotContainsString('type="text"', $output);
        $this->assertStringNotContainsString('placeholder=', $output);
        $this->assertStringNotContainsString('maxlength=', $output);
        $this->assertStringNotContainsString('pattern=', $output);
        $this->assertStringNotContainsString('inputmode=', $output);
        $this->assertStringNotContainsString('autocomplete=', $output);

        $config = $this->validConfig();
        $GLOBALS['opennow_test_options'][Schema::OPTION_NAME] = $config;
        $settings = new Settings();

        ob_start();
        $settings->renderAppearanceField(array('color' => 'background_color'));
        $settings->renderAppearanceField(array('color' => 'text_color'));
        $legacy_blank_output = (string) ob_get_clean();
        $this->assertStringContainsString('name="opennow_config[appearance][background_color]" value="#166534"', $legacy_blank_output);
        $this->assertStringContainsString('name="opennow_config[appearance][text_color]" value="#FFFFFF"', $legacy_blank_output);

        $config['appearance'] = array(
            'background_color' => '#000000',
            'text_color' => '#FFFFFF',
        );
        $GLOBALS['opennow_test_options'][Schema::OPTION_NAME] = $config;
        $settings = new Settings();

        ob_start();
        $settings->renderAppearanceField(array('color' => 'background_color'));
        $settings->renderAppearanceField(array('color' => 'text_color'));
        $custom_output = (string) ob_get_clean();
        $this->assertStringContainsString('name="opennow_config[appearance][background_color]" value="#000000"', $custom_output);
        $this->assertStringContainsString('name="opennow_config[appearance][text_color]" value="#FFFFFF"', $custom_output);
    }

    public function testAppearanceErrorsAreFieldSpecificAndConnectedToEachControl(): void
    {
        $config = $this->validConfig();
        $config['appearance'] = array(
            'background_color' => '#FFFFFF',
            'text_color' => '#FFFFFF',
        );
        $GLOBALS['opennow_test_options'][Schema::OPTION_NAME] = $config;

        (new Settings())->sanitize($config);
        $settings = new Settings();

        ob_start();
        $settings->renderAppearanceField(array('color' => 'background_color'));
        $settings->renderAppearanceField(array('color' => 'text_color'));
        $output = (string) ob_get_clean();

        $this->assertSame(2, substr_count($output, 'aria-invalid="true"'));
        $this->assertStringContainsString('opennow-appearance-background-color-description setting-error-opennow_appearance_background_color', $output);
        $this->assertStringContainsString('opennow-appearance-text-color-description setting-error-opennow_appearance_text_color', $output);
        $this->assertStringContainsString('Background color: The background and text colors must meet WCAG AA contrast for normal text.', $GLOBALS['opennow_test_settings_errors'][0]['message']);
        $this->assertStringContainsString('Text color: The background and text colors must meet WCAG AA contrast for normal text.', $GLOBALS['opennow_test_settings_errors'][1]['message']);
    }

    public function testInvalidSubmissionReturnsTheExactExistingOptionAndAddsErrors(): void
    {
        $existing = array('retained' => 'unchanged');
        $GLOBALS['opennow_test_options'][Schema::OPTION_NAME] = $existing;

        $result = (new Settings())->sanitize(array('timezone' => '+02:00'));

        $this->assertSame($existing, $result);
        $this->assertCount(0, array_filter(
            $GLOBALS['opennow_test_option_calls'],
            static function ($call): bool {
                return 'update_option' === $call['function'];
            }
        ));
        $this->assertNotEmpty($GLOBALS['opennow_test_settings_errors']);
        $this->assertSame(Schema::OPTION_NAME, $GLOBALS['opennow_test_settings_errors'][0]['setting']);
        $this->assertSame('error', $GLOBALS['opennow_test_settings_errors'][0]['type']);
        $this->assertStringStartsWith('opennow_', $GLOBALS['opennow_test_settings_errors'][0]['code']);

        ob_start();
        (new Settings())->renderSettingsErrors();
        $error_output = (string) ob_get_clean();
        $this->assertStringContainsString('role="alert"', $error_output);
        $this->assertStringContainsString('setting-error-opennow_', $error_output);

        ob_start();
        (new Settings())->renderTimezoneField();
        $timezone_output = (string) ob_get_clean();
        $this->assertStringContainsString('aria-invalid="true"', $timezone_output);
        $this->assertStringContainsString('setting-error-opennow_timezone', $timezone_output);
    }

    public function testInvalidSubmissionWithoutAnExistingOptionReturnsFalse(): void
    {
        $result = (new Settings())->sanitize(array());

        $this->assertFalse($result);
        $this->assertNotEmpty($GLOBALS['opennow_test_settings_errors']);
    }

    /**
     * @dataProvider representativeInvalidUpdates
     */
    public function testRepresentativeInvalidUpdatesPreserveTheLastKnownGoodConfiguration(
        $mutator,
        $expected_code,
        $expected_context
    ): void {
        $existing = $this->validConfig();
        $GLOBALS['opennow_test_options'][Schema::OPTION_NAME] = $existing;
        $submitted = $existing;
        $mutator($submitted);

        $result = (new Settings())->sanitize($submitted);

        $this->assertSame($existing, $result);
        $matching_errors = array_values(array_filter(
            $GLOBALS['opennow_test_settings_errors'],
            static function ($error) use ($expected_code): bool {
                return $expected_code === $error['code'];
            }
        ));
        $this->assertNotEmpty($matching_errors);
        $this->assertStringContainsString($expected_context, $matching_errors[0]['message']);
    }

    public function representativeInvalidUpdates(): array
    {
        return array(
            'invalid timezone' => array(
                static function (&$config): void {
                    $config['timezone'] = '+02:00';
                },
                'opennow_timezone',
                'Business timezone',
            ),
            'equal interval' => array(
                static function (&$config): void {
                    $config['schedule']['monday']['closes'] = '09:00';
                },
                'opennow_schedule_monday',
                'Monday',
            ),
            'unsafe action' => array(
                static function (&$config): void {
                    $config['cta']['open']['action'] = 'javascript:alert(1)';
                },
                'opennow_cta_open_action',
                'Open CTA',
            ),
        );
    }

    public function testACompleteClosedWeekNeedsNoOpeningOrClosingValues(): void
    {
        $config = $this->validConfig();
        foreach (Schema::days() as $day) {
            $config['schedule'][$day] = array('type' => 'closed');
        }

        $result = (new Settings())->sanitize($config);

        $this->assertIsArray($result);
        foreach (Schema::days() as $day) {
            $this->assertSame(array('type' => 'closed'), $result['schedule'][$day]);
        }
        $this->assertSame(array(), $GLOBALS['opennow_test_settings_errors']);
    }

    public function testScheduleMarkupKeepsSevenAccessibleStatefulGroups(): void
    {
        $config = $this->validConfig();
        $GLOBALS['wp_locale'] = new class() {
            public function get_weekday($weekday_number)
            {
                return 'Localized weekday ' . $weekday_number;
            }
        };
        $config['schedule']['monday'] = array(
            'type' => 'period',
            'opens' => '09:00',
            'closes' => '17:00',
        );
        $GLOBALS['opennow_test_options'][Schema::OPTION_NAME] = $config;
        $settings = new Settings();

        ob_start();
        $settings->renderScheduleField();
        $output = (string) ob_get_clean();
        $xpath = $this->parseHtml($output);

        $this->assertSame(7, $xpath->query('//fieldset[@data-opennow-schedule-day]')->length);
        $this->assertStringContainsString('<legend>Localized weekday 1</legend>', $output);
        $this->assertStringContainsString('24-hour HH:MM local business time', $output);
        $this->assertStringContainsString('means overnight', $output);
        $this->assertSame(7, substr_count($output, 'value="period"'));
        $this->assertSame(7, substr_count($output, 'placeholder="Example: 09:00"'));
        $this->assertSame(7, substr_count($output, 'placeholder="Example: 17:00"'));

        foreach (Schema::days() as $day) {
            $fieldset = $xpath->query(
                '//fieldset[@data-opennow-schedule-day="' . $day . '"]'
            )->item(0);
            $this->assertInstanceOf(\DOMElement::class, $fieldset);
            $is_open = 'monday' === $day;
            $state = $is_open ? 'open' : 'closed';
            $this->assertStringContainsString(
                'opennow-schedule-day--' . $state,
                $fieldset->getAttribute('class')
            );
            $this->assertSame($state, $fieldset->getAttribute('data-opennow-schedule-state'));
            $this->assertSame(
                $is_open ? 'Open' : 'Closed',
                $xpath->query(
                    '//fieldset[@data-opennow-schedule-day="' . $day
                    . '"]//*[@data-opennow-schedule-state-text]'
                )->item(0)->textContent
            );
            $this->assertSame('Open', $fieldset->getAttribute('data-opennow-open-label'));
            $this->assertSame('Closed', $fieldset->getAttribute('data-opennow-closed-label'));
            $this->assertSame(
                1,
                $xpath->query(
                    '//fieldset[@data-opennow-schedule-day="' . $day
                    . '"]/div[contains(concat(" ", normalize-space(@class), " "), " opennow-schedule-summary ")]'
                )->length
            );

            $opens_id = 'opennow-schedule-' . $day . '-opens';
            $closes_id = 'opennow-schedule-' . $day . '-closes';
            $closed_id = 'opennow-schedule-' . $day . '-closed';
            $checkbox = $xpath->query('//input[@id="' . $closed_id . '"]')->item(0);
            $opens = $xpath->query('//input[@id="' . $opens_id . '"]')->item(0);
            $closes = $xpath->query('//input[@id="' . $closes_id . '"]')->item(0);

            $this->assertInstanceOf(\DOMElement::class, $checkbox);
            $this->assertInstanceOf(\DOMElement::class, $opens);
            $this->assertInstanceOf(\DOMElement::class, $closes);
            $closed_label = $xpath->query('//label[@for="' . $closed_id . '"]')->item(0);
            $this->assertInstanceOf(\DOMElement::class, $closed_label);
            $this->assertStringContainsString(
                'opennow-schedule-closed-toggle',
                $closed_label->getAttribute('class')
            );
            $this->assertSame($opens_id . ' ' . $closes_id, $checkbox->getAttribute('aria-controls'));
            $this->assertSame(
                'opennow-schedule-closed-description',
                $checkbox->getAttribute('aria-describedby')
            );
            $this->assertSame(
                $is_open ? '09:00' : '',
                $opens->getAttribute('value')
            );
            $this->assertSame(
                $is_open ? '17:00' : '',
                $closes->getAttribute('value')
            );
            $this->assertSame($is_open, $opens->hasAttribute('required'));
            $this->assertSame($is_open, $closes->hasAttribute('required'));
            $this->assertSame(! $is_open, $opens->hasAttribute('disabled'));
            $this->assertSame(! $is_open, $closes->hasAttribute('disabled'));
            $this->assertStringContainsString(
                'opennow-schedule-time-description',
                $opens->getAttribute('aria-describedby')
            );
            $this->assertStringContainsString(
                'opennow-schedule-time-description',
                $closes->getAttribute('aria-describedby')
            );
            $this->assertSame(
                1,
                $xpath->query('//label[@for="' . $opens_id . '"]')->length
            );
            $this->assertSame(
                1,
                $xpath->query('//label[@for="' . $closes_id . '"]')->length
            );
        }
    }

    public function testValidSubmissionReturnsCanonicalValue(): void
    {
        $config = $this->validConfig();
        $config['timezone'] = ' UTC ';
        $config['cta']['closed']['label'] = ' Book online ';

        $result = (new Settings())->sanitize($config);

        $this->assertIsArray($result);
        $this->assertSame('UTC', $result['timezone']);
        $this->assertSame('Book online', $result['cta']['closed']['label']);
        $this->assertSame('', $result['appearance']['background_color']);
        $this->assertSame('', $result['appearance']['text_color']);
        $this->assertSame(array(), $GLOBALS['opennow_test_settings_errors']);
    }

    private function parseHtml(string $html): \DOMXPath
    {
        $document = new \DOMDocument();
        $this->assertTrue($document->loadHTML('<!DOCTYPE html><html><body>' . $html . '</body></html>'));

        return new \DOMXPath($document);
    }

    /**
     * @return array<string, mixed>
     */
    private function validConfig(): array
    {
        $schedule = array();
        foreach (array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday') as $day) {
            $schedule[$day] = array('type' => 'closed');
        }

        return array(
            'timezone' => 'America/New_York',
            'schedule' => $schedule,
            'cta' => array(
                'open' => array(
                    'label' => 'Call Now',
                    'action' => 'tel:+123456789',
                    'status' => '',
                ),
                'closed' => array(
                    'label' => 'Book online',
                    'action' => '/booking/',
                    'status' => '',
                ),
            ),
            'appearance' => array(
                'background_color' => '',
                'text_color' => '',
            ),
        );
    }
}
