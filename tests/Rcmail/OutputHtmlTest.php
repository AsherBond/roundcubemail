<?php

namespace Roundcube\Tests\Rcmail;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Test class to test rcmail_output_html class
 */
class OutputHtmlTest extends TestCase
{
    /**
     * Test check_skin()
     */
    public function test_check_skin()
    {
        $rcmail = \rcube::get_instance();
        $output = new \rcmail_output_html();

        $this->assertTrue($output->check_skin('elastic'));
        $this->assertFalse($output->check_skin('unknown'));
    }

    /**
     * Test get_skin_file()
     */
    public function test_get_skin_file()
    {
        $rcmail = \rcube::get_instance();
        $output = new \rcmail_output_html();

        $output->set_skin('elastic');

        $this->assertSame('skins/elastic/ui.js', $output->get_skin_file('ui.js'));
        $this->assertFalse($output->get_skin_file('unknown'));
    }

    /**
     * Test get_template_logo()
     */
    public function test_logo()
    {
        $rcmail = \rcube::get_instance();
        $output = new \rcmail_output_html();
        $reflection = new \ReflectionClass('rcmail_output_html');
        $set_skin = $reflection->getProperty('skin_name');
        $set_template = $reflection->getProperty('template_name');
        $get_template_logo = $reflection->getMethod('get_template_logo');

        $set_skin->setAccessible(true);
        $set_template->setAccessible(true);
        $get_template_logo->setAccessible(true);

        $set_skin->setValue($output, 'elastic');

        $rcmail->config->set('skin_logo', 'img00');

        $set_template->setValue($output, 'login');
        $result = $get_template_logo->invokeArgs($output, []);
        $this->assertSame('img00', $result);

        $set_template->setValue($output, 'mail');
        $result = $get_template_logo->invokeArgs($output, ['small']);
        $this->assertSame('img00', $result);
        $result = $get_template_logo->invokeArgs($output, ['link']);
        $this->assertNull($result);

        $rcmail->config->set('skin_logo', [
            'elastic:login[small]' => 'img01',
            'elastic:login' => 'img02',
            'elastic:*[small]' => 'img03',
            'larry:*' => 'img04',
            '*:login[small]' => 'img05',
            '*:login' => 'img06',
            '*[print]' => 'img07',
            '*' => 'img08',
        ]);

        $set_template->setValue($output, 'login');
        $result = $get_template_logo->invokeArgs($output, ['favicon']);
        $this->assertNull($result);

        $set_template->setValue($output, 'login');
        $result = $get_template_logo->invokeArgs($output, ['favicon', 'template']);
        $this->assertSame('img02', $result);

        $set_template->setValue($output, 'login');
        $result = $get_template_logo->invokeArgs($output, ['favicon', 'all']);
        $this->assertSame('img02', $result);

        $set_template->setValue($output, 'login');
        $result = $get_template_logo->invokeArgs($output, ['small']);
        $this->assertSame('img01', $result);

        $set_template->setValue($output, 'login');
        $result = $get_template_logo->invokeArgs($output, []);
        $this->assertSame('img02', $result);

        $set_template->setValue($output, 'mail');
        $result = $get_template_logo->invokeArgs($output, ['small']);
        $this->assertSame('img03', $result);

        $set_template->setValue($output, 'mail');
        $result = $get_template_logo->invokeArgs($output, []);
        $this->assertSame('img08', $result);

        $set_template->setValue($output, '_test_');
        $result = $get_template_logo->invokeArgs($output, []);
        $this->assertSame('img08', $result);

        $set_template->setValue($output, '_test_');
        $result = $get_template_logo->invokeArgs($output, ['print']);
        $this->assertSame('img07', $result);

        $set_template->setValue($output, '_test_');
        $result = $get_template_logo->invokeArgs($output, ['print', 'template']);
        $this->assertSame('img07', $result);

        $set_skin->setValue($output, 'larry');

        $set_template->setValue($output, 'login');
        $result = $get_template_logo->invokeArgs($output, ['favicon']);
        $this->assertNull($result);

        $set_template->setValue($output, 'login');
        $result = $get_template_logo->invokeArgs($output, ['favicon', 'template']);
        $this->assertSame('img06', $result);

        $set_template->setValue($output, 'login');
        $result = $get_template_logo->invokeArgs($output, ['favicon', 'all']);
        $this->assertSame('img04', $result);

        $set_template->setValue($output, 'login');
        $result = $get_template_logo->invokeArgs($output, ['small']);
        $this->assertSame('img05', $result);

        $set_template->setValue($output, 'login');
        $result = $get_template_logo->invokeArgs($output, []);
        $this->assertSame('img04', $result);

        $set_template->setValue($output, '_test_');
        $result = $get_template_logo->invokeArgs($output, []);
        $this->assertSame('img04', $result);

        $set_template->setValue($output, '_test_');
        $result = $get_template_logo->invokeArgs($output, ['print', 'template']);
        $this->assertSame('img07', $result);

        $set_template->setValue($output, '_test_');
        $result = $get_template_logo->invokeArgs($output, ['print']);
        $this->assertSame('img07', $result);

        $set_skin->setValue($output, '_test_');

        $set_template->setValue($output, 'login');
        $result = $get_template_logo->invokeArgs($output, ['favicon']);
        $this->assertNull($result);

        $set_template->setValue($output, 'login');
        $result = $get_template_logo->invokeArgs($output, ['print', 'template']);
        $this->assertSame('img06', $result);

        $set_template->setValue($output, 'login');
        $result = $get_template_logo->invokeArgs($output, ['small']);
        $this->assertSame('img05', $result);

        $set_template->setValue($output, 'login');
        $result = $get_template_logo->invokeArgs($output, []);
        $this->assertSame('img06', $result);

        $set_template->setValue($output, '_test_');
        $result = $get_template_logo->invokeArgs($output, ['print']);
        $this->assertSame('img07', $result);

        $set_template->setValue($output, '_test_');
        $result = $get_template_logo->invokeArgs($output, ['_test_']);
        $this->assertNull($result);

        $set_template->setValue($output, '_test_');
        $result = $get_template_logo->invokeArgs($output, []);
        $this->assertSame('img08', $result);

        $set_template->setValue($output, '_test_');
        $result = $get_template_logo->invokeArgs($output, ['print', 'template']);
        $this->assertSame('img07', $result);

        $set_template->setValue($output, '_test_');
        $result = $get_template_logo->invokeArgs($output, ['print']);
        $this->assertSame('img07', $result);

        $rcmail->config->set('skin_logo', [
            'elastic:login[small]' => 'img09',
            'elastic:login' => 'img10',
            'larry:*' => 'img11',
            'elastic[small]' => 'img12',
            'login[small]' => 'img13',
            'login' => 'img14',
            '[print]' => 'img15',
            '*' => 'img16',
        ]);

        $set_skin->setValue($output, 'elastic');

        $set_template->setValue($output, 'login');
        $result = $get_template_logo->invokeArgs($output, ['small']);
        $this->assertSame('img09', $result);

        $set_template->setValue($output, 'mail');
        $result = $get_template_logo->invokeArgs($output, ['small']);
        $this->assertNull($result);

        $set_skin->setValue($output, '_test_');

        $set_template->setValue($output, 'login');
        $result = $get_template_logo->invokeArgs($output, ['small']);
        $this->assertSame('img13', $result);

        $set_template->setValue($output, 'login');
        $result = $get_template_logo->invokeArgs($output, []);
        $this->assertSame('img14', $result);

        $set_template->setValue($output, '_test_');
        $result = $get_template_logo->invokeArgs($output, ['print']);
        $this->assertSame('img15', $result);

        $set_template->setValue($output, '_test_');
        $result = $get_template_logo->invokeArgs($output, ['_test_', 'all']);
        $this->assertSame('img16', $result);

        $set_template->setValue($output, '_test_');
        $result = $get_template_logo->invokeArgs($output, ['_test_', 'template']);
        $this->assertNull($result);

        $set_template->setValue($output, '_test_');
        $result = $get_template_logo->invokeArgs($output, ['_test_']);
        $this->assertNull($result);

        $set_template->setValue($output, '_test_');
        $result = $get_template_logo->invokeArgs($output, []);
        $this->assertSame('img16', $result);

        $rcmail->config->set('skin_logo', [
            'elastic:[print]' => 'img17',
            'elastic:messageprint' => 'img18',
            'elastic:*' => 'img19',
        ]);

        $set_skin->setValue($output, 'elastic');

        $set_template->setValue($output, 'login');
        $result = $get_template_logo->invokeArgs($output, ['print']);
        $this->assertSame('img17', $result);

        $set_template->setValue($output, 'messageprint');
        $result = $get_template_logo->invokeArgs($output, ['_test_', 'template']);
        $this->assertSame('img18', $result);

        $set_template->setValue($output, 'contactprint');
        $result = $get_template_logo->invokeArgs($output, ['print', 'template']);
        $this->assertSame('img17', $result);

        $set_template->setValue($output, 'contactprint');
        $result = $get_template_logo->invokeArgs($output, ['_test_', 'template']);
        $this->assertNull($result);

        $set_template->setValue($output, 'contactprint');
        $result = $get_template_logo->invokeArgs($output, ['_test_', 'all']);
        $this->assertSame('img19', $result);

        $set_template->setValue($output, 'contactprint');
        $result = $get_template_logo->invokeArgs($output, []);
        $this->assertSame('img19', $result);
    }

    /**
     * Test text to html conversion
     *
     * @dataProvider provide_conditions_cases
     */
    #[DataProvider('provide_conditions_cases')]
    public function test_conditions($input, $output)
    {
        $object = new \rcmail_output_html();
        $result = $object->just_parse($input);

        $this->assertSame($output, $result);
    }

    /**
     * Data for test_conditions()
     */
    public static function provide_conditions_cases(): iterable
    {
        $txt = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt '
            . 'ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco '
            . 'laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in '
            . 'voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat '
            . 'non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.';

        return [
            ["_start_<roundcube:if condition='1' />A<roundcube:endif />_end_", '_start_A_end_'],
            ["_start_<roundcube:if condition='0' />A<roundcube:else />B<roundcube:endif />_end_", '_start_B_end_'],
            ["_start_<roundcube:if condition='0'/>A<roundcube:else/>B<roundcube:endif/>_end_", '_start_B_end_'],
            ["_start_<roundcube:if condition='0'>A<roundcube:else>B<roundcube:endif>_end_", '_start_B_end_'],
            ["_start_<roundcube:if condition='0' />A<roundcube:elseif condition='1' />B<roundcube:else />C<roundcube:endif />_end_", '_start_B_end_'],
            ["_start_<roundcube:if condition='1' /><roundcube:if condition='0' />A<roundcube:else />B<roundcube:endif />C<roundcube:else />D<roundcube:endif />_end_", '_start_BC_end_'],
            ["_start_<roundcube:if condition='1' /><roundcube:if condition='1' />A<roundcube:else />B<roundcube:endif />C<roundcube:else />D<roundcube:endif />_end_", '_start_AC_end_'],
            ["_start_<roundcube:if condition='1' /><roundcube:if condition='0' />A<roundcube:elseif condition='1' />B<roundcube:else />C<roundcube:endif />D<roundcube:else />E<roundcube:endif />_end_", '_start_BD_end_'],
            ["_start_<roundcube:if condition='0' />A<roundcube:elseif condition='1' /><roundcube:if condition='0' />B<roundcube:else /><roundcube:if condition='1' />C<roundcube:endif />D<roundcube:endif /><roundcube:else />E<roundcube:endif />_end_", '_start_CD_end_'],
            ["_start_<roundcube:if condition='0'>A<roundcube:elseif condition='1'><roundcube:if condition='0'>B<roundcube:else><roundcube:if condition='1'>C<roundcube:endif>D<roundcube:endif><roundcube:else>E<roundcube:endif>_end_", '_start_CD_end_'],
            ["_start_<roundcube:if condition='1'>A<roundcube:elseif condition='1'>B<roundcube:elseif condition='1'>C<roundcube:endif>_end_", '_start_A_end_'],
            ["_start_<roundcube:if condition='0'>A<roundcube:elseif condition='1'>B<roundcube:elseif condition='1'>C<roundcube:endif>_end_", '_start_B_end_'],
            ["_start_<roundcube:if condition='0'>A<roundcube:elseif condition='0'>B<roundcube:elseif condition='1'>C<roundcube:endif>_end_", '_start_C_end_'],
            // #8065
            [
                "_start_<roundcube:if condition='0'>Condition 1 {$txt} {$txt}<roundcube:elseif condition='1'>Condition 2 {$txt} {$txt}"
                    . "<roundcube:elseif condition='0'>Condition 3 {$txt} {$txt}<roundcube:elseif condition='0'>Condition 4 {$txt} {$txt}"
                    . "<roundcube:elseif condition='0'>Condition 5 {$txt} {$txt}<roundcube:elseif condition='0'>Condition 6 {$txt} {$txt}"
                    . '<roundcube:endif>_end_',
                "_start_Condition 2 {$txt} {$txt}_end_",
            ],
            // some invalid code
            ["_start_<roundcube:if condition='1' />_end_", '_start__end_'],
            ["_start_<roundcube:if condition='0' />_end_", '_start_'],
            ["_start_<roundcube:if condition='1' />A<roundcube:else />_end_", '_start_A'],
            ["_start_<roundcube:if condition='1' />A<roundcube:elseif condition='1' />_end_", '_start_A'],
            ['_start_<roundcube:if />A<roundcube:endif />_end_', '_start__end_'],
        ];
    }

    /**
     * Test reset()
     */
    public function test_reset()
    {
        $rcmail = \rcube::get_instance();
        $output = new \rcmail_output_html();

        $this->assertNull($output->reset());
    }

    /**
     * Test abs_url()
     */
    public function test_abs_url()
    {
        $rcmail = \rcube::get_instance();
        $output = new \rcmail_output_html();

        $this->assertSame('test', $output->abs_url('test'));
        $this->assertSame('skins/elastic/ui.js', $output->abs_url('/ui.js'));
    }

    /**
     * Test asset_url()
     */
    public function test_asset_url()
    {
        $rcmail = \rcube::get_instance();
        $output = new \rcmail_output_html();

        $this->assertSame('http://test', $output->asset_url('http://test'));
        $this->assertSame('static.php/ui.js', $output->asset_url('/ui.js'));
        $this->assertSame('static.php/skins/elastic/ui.js', $output->asset_url('/ui.js', true));
    }

    /**
     * Test button()
     */
    public function test_button()
    {
        $rcmail = \rcube::get_instance();
        $output = new \rcmail_output_html();

        $this->assertSame('', $output->button([]));

        // TODO: Test more cases
        $this->markTestIncomplete();
    }

    /**
     * Test form_tag()
     */
    public function test_form_tag()
    {
        $rcmail = \rcube::get_instance();
        $output = new \rcmail_output_html();

        $this->assertSame('<form action="vendor/bin/phpunit?_task=cli" method="get">test</form>', $output->form_tag([], 'test'));
    }

    /**
     * Test request_form()
     */
    public function test_request_form()
    {
        $rcmail = \rcube::get_instance();
        $output = new \rcmail_output_html();

        $this->assertSame('<form action="./" method="get">test</form>', $output->request_form([], 'test'));
    }

    /**
     * Test search_form()
     */
    public function test_search_form()
    {
        $rcmail = \rcube::get_instance();
        $output = new \rcmail_output_html();

        $expected = '<form name="rcmqsearchform" onsubmit="rcmail.command(\'search\'); return false"'
            . ' action="vendor/bin/phpunit?_task=cli" method="get"><label for="rcmqsearchbox" class="voice">Search terms</label>'
            . '<input name="_q" class="no-bs" id="rcmqsearchbox" placeholder="Search..." type="text"></form>';

        $this->assertSame($expected, $output->search_form([]));
    }

    /**
     * Test charset_selector()
     */
    public function test_charset_selector()
    {
        $rcmail = \rcube::get_instance();
        $output = new \rcmail_output_html();

        $result = $output->charset_selector([]);

        $this->assertTrue(strpos($result, '<select name="_charset">') === 0);
        $this->assertTrue(strpos($result, '<option value="UTF-8" selected="selected">UTF-8 (Unicode)</option>') !== false);
    }
}
