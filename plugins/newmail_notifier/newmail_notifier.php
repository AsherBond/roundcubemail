<?php

/**
 * New Mail Notifier plugin
 *
 * Supports three methods of notification:
 * 1. Basic   - focus browser window and change favicon
 * 2. Sound   - play wav file
 * 3. Desktop - display desktop notification (using window.Notification API)
 *
 * @author Aleksander Machniak <alec@alec.pl>
 *
 * Copyright (C) Kolab Systems AG
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see https://www.gnu.org/licenses/.
 */

class newmail_notifier extends rcube_plugin
{
    public $task = 'mail|settings';

    private $rc;
    private $notified;
    private $opt = [];
    private $exceptions = [];

    /**
     * Plugin initialization
     */
    #[Override]
    public function init()
    {
        $this->rc = rcmail::get_instance();

        // Preferences hooks
        if ($this->rc->task == 'settings') {
            $this->add_hook('preferences_list', [$this, 'prefs_list']);
            $this->add_hook('preferences_save', [$this, 'prefs_save']);
        } else { // if ($this->rc->task == 'mail') {
            // add script when not in ajax and not in frame and only in main window
            if ($this->rc->output->type == 'html' && empty($_REQUEST['_framed']) && $this->rc->action == '') {
                $this->add_texts('localization/');
                $this->rc->output->add_label('newmail_notifier.title', 'newmail_notifier.body');
                $this->include_script('newmail_notifier.js');
            }

            if ($this->rc->action == 'refresh') {
                // Load configuration
                $this->load_config();

                $this->opt['basic'] = $this->rc->config->get('newmail_notifier_basic');
                $this->opt['sound'] = $this->rc->config->get('newmail_notifier_sound');
                $this->opt['desktop'] = $this->rc->config->get('newmail_notifier_desktop');

                if (!empty($this->opt)) {
                    // Get folders to skip checking for
                    $exceptions = ['drafts_mbox', 'sent_mbox', 'trash_mbox', 'junk_mbox'];
                    foreach ($exceptions as $folder) {
                        $folder = $this->rc->config->get($folder);
                        if (strlen($folder) && $folder != 'INBOX') {
                            $this->exceptions[] = $folder;
                        }
                    }

                    $this->add_hook('new_messages', [$this, 'notify']);
                }
            }
        }
    }

    /**
     * Handler for user preferences form (preferences_list hook)
     */
    public function prefs_list($args)
    {
        if ($args['section'] != 'mailbox') {
            return $args;
        }

        // Load configuration
        $this->load_config();

        // Load localization and configuration
        $this->add_texts('localization/');

        if (!empty($_REQUEST['_framed'])) {
            $this->rc->output->add_label('newmail_notifier.title', 'newmail_notifier.testbody',
                'newmail_notifier.desktopunsupported', 'newmail_notifier.desktopenabled',
                'newmail_notifier.desktopdisabled'
            );
            $this->include_script('newmail_notifier.js');
        }

        // Check that configuration is not disabled
        $dont_override = (array) $this->rc->config->get('dont_override', []);

        foreach (['basic', 'desktop', 'sound'] as $type) {
            $key = 'newmail_notifier_' . $type;
            if (!in_array($key, $dont_override)) {
                $field_id = '_' . $key;
                $input = new html_checkbox(['name' => $field_id, 'id' => $field_id, 'value' => 1]);
                $content = $input->show($this->rc->config->get($key))
                    . ' ' . html::a(['href' => '#', 'onclick' => 'newmail_notifier_test_' . $type . '(); return false'],
                        $this->gettext('test'));

                $args['blocks']['new_message']['options'][$key] = [
                    'title' => html::label($field_id, rcube::Q($this->gettext($type))),
                    'content' => $content,
                ];
            }
        }

        $type = 'desktop_timeout';
        $key = 'newmail_notifier_' . $type;

        if (!in_array($key, $dont_override)) {
            $field_id = '_' . $key;
            $select = new html_select(['name' => $field_id, 'id' => $field_id, 'class' => 'custom-select']);

            foreach ([5, 10, 15, 30, 45, 60] as $sec) {
                $label = $this->rc->gettext(['name' => 'afternseconds', 'vars' => ['n' => $sec]]);
                $select->add($label, $sec);
            }

            $args['blocks']['new_message']['options'][$key] = [
                'title' => html::label($field_id, rcube::Q($this->gettext('desktoptimeout'))),
                'content' => $select->show((int) $this->rc->config->get($key)),
            ];
        }

        return $args;
    }

    /**
     * Handler for user preferences save (preferences_save hook)
     */
    public function prefs_save($args)
    {
        if ($args['section'] != 'mailbox') {
            return $args;
        }

        // Load configuration
        $this->load_config();

        // Check that configuration is not disabled
        $dont_override = (array) $this->rc->config->get('dont_override', []);

        foreach (['basic', 'desktop', 'sound'] as $type) {
            $key = 'newmail_notifier_' . $type;
            if (!in_array($key, $dont_override)) {
                $args['prefs'][$key] = !empty(rcube_utils::get_input_value('_' . $key, rcube_utils::INPUT_POST));
            }
        }

        $option = 'newmail_notifier_desktop_timeout';
        if (!in_array($option, $dont_override)) {
            if ($value = (int) rcube_utils::get_input_value('_' . $option, rcube_utils::INPUT_POST)) {
                $args['prefs'][$option] = $value;
            }
        }

        return $args;
    }

    /**
     * Handler for new message action (new_messages hook)
     */
    public function notify($args)
    {
        // Already notified or unexpected input
        if ($this->notified || empty($args['diff']['new'])) {
            return $args;
        }

        $mbox = $args['mailbox'];
        $storage = $this->rc->get_storage();
        $delimiter = $storage->get_hierarchy_delimiter();

        // Skip exception (sent/drafts) folders (and their subfolders)
        foreach ($this->exceptions as $folder) {
            if (str_starts_with($mbox . $delimiter, $folder . $delimiter)) {
                return $args;
            }
        }

        // Check if any of new messages is UNSEEN
        $deleted = $this->rc->config->get('skip_deleted') ? 'UNDELETED ' : '';
        $search = $deleted . 'UNSEEN UID ' . $args['diff']['new'];
        $unseen = $storage->search_once($mbox, $search);

        if ($unseen->count()) {
            $this->notified = true;

            $this->rc->output->set_env('newmail_notifier_timeout', $this->rc->config->get('newmail_notifier_desktop_timeout'));
            $this->rc->output->command('plugin.newmail_notifier',
                [
                    'basic' => $this->opt['basic'],
                    'sound' => $this->opt['sound'],
                    'desktop' => $this->opt['desktop'],
                ]
            );
        }

        return $args;
    }
}
