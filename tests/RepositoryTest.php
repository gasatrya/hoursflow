<?php
namespace OpenNow\Tests;

use OpenNow\Config\Repository;
use OpenNow\Config\Schema;
use PHPUnit\Framework\TestCase;

final class RepositoryTest extends TestCase
{
    protected function setUp(): void
    {
        opennow_reset_wp_stubs();
    }

    public function testAbsentAndLegacyOptionsReturnSafeDeterministicRuntimeShape(): void
    {
        $repository = new Repository();

        $runtime = $repository->getRuntimeConfig();
        $this->assertSame(Schema::runtimeDefaults(), $runtime);
        $this->assertSame(array(), $GLOBALS['opennow_test_option_calls']);

        $GLOBALS['opennow_test_options'][Schema::OPTION_NAME] = 'legacy value';
        $runtime = $repository->get();

        $this->assertSame(Schema::runtimeDefaults(), $runtime);
        $this->assertSame(array(), $GLOBALS['opennow_test_option_calls']);
    }

    public function testRepositorySalvagesEachSectionWithoutWritingBack(): void
    {
        $stored = array(
            'timezone' => ' +02:00 ',
            'schedule' => array(
                'monday' => array(
                    'type' => 'period',
                    'opens' => ' 22:00 ',
                    'closes' => '02:00',
                ),
                'tuesday' => array('type' => 'closed', 'unexpected' => true),
                'thursday' => array('type' => 'period', 'opens' => '09:00', 'closes' => '09:00'),
                'friday' => array('type' => 'closed'),
            ),
            'cta' => array(
                'open' => array(
                    'label' => ' Call Now ',
                    'action' => 'HTTPS://example.com/call',
                    'status' => '',
                ),
                'closed' => array(
                    'label' => '<unsafe>',
                    'action' => '/booking/',
                    'status' => '',
                ),
            ),
            'appearance' => array(
                'background_color' => '#FFFFFF',
                'text_color' => '#FFFFFF',
            ),
        );
        $GLOBALS['opennow_test_options'][Schema::OPTION_NAME] = $stored;

        $runtime = (new Repository())->getRuntimeConfig();

        $this->assertNull($runtime['timezone']);
        $this->assertSame(
            array('type' => 'period', 'opens' => '22:00', 'closes' => '02:00'),
            $runtime['schedule']['monday']
        );
        $this->assertSame(array('type' => 'closed'), $runtime['schedule']['tuesday']);
        $this->assertSame(array('type' => 'closed'), $runtime['schedule']['wednesday']);
        $this->assertSame(array('type' => 'closed'), $runtime['schedule']['thursday']);
        $this->assertSame(array('type' => 'closed'), $runtime['schedule']['saturday']);
        $this->assertSame(
            array(
                'label' => 'Call Now',
                'action' => 'HTTPS://example.com/call',
                'status' => '',
            ),
            $runtime['cta']['open']
        );
        $this->assertNull($runtime['cta']['closed']);
        $this->assertSame(Schema::defaultAppearance(), $runtime['appearance']);
        $this->assertSame($stored, $GLOBALS['opennow_test_options'][Schema::OPTION_NAME]);
        $this->assertSame(array(), $GLOBALS['opennow_test_option_calls']);
        $this->assertCount(7, $runtime['schedule']);
    }

    public function testRepositoryUsesAValidMixedEffectiveColorPair(): void
    {
        $GLOBALS['opennow_test_options'][Schema::OPTION_NAME] = array(
            'appearance' => array(
                'background_color' => '#000000',
                'text_color' => '',
            ),
        );

        $runtime = (new Repository())->getRuntimeConfig();

        $this->assertSame(
            array(
                'background_color' => '#000000',
                'text_color' => '#FFFFFF',
            ),
            $runtime['appearance']
        );
    }
}
