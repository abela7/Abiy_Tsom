<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\TelegramBotBuilder;

final class TelegramBotBuilderService
{
    private const CONFIG_KEY = 'builder';
    private const DEFAULT_KEYBOARD_LABELS = [
        'menu' => 'Menu',
        'home' => 'Home',
        'today' => 'Today',
        'admin' => 'Admin panel',
        'me' => 'My links',
        'help' => 'Help',
    ];

    private const DEFAULT_COMMAND_DESCRIPTIONS = [
        'menu' => 'Show quick actions',
        'home' => 'Open member home',
        'day' => 'Open today',
        'admin' => 'Open admin dashboard',
        'help' => 'Show help',
    ];

    private const DEFAULT_NOT_LINKED_MESSAGE = 'Your account is not linked yet. Open the app and continue securely.';

    public function getConfig(): array
    {
        $stored = TelegramBotBuilder::query()->where('key', self::CONFIG_KEY)->first();
        $payload = is_array($stored?->value) ? $stored->value : [];

        return $this->mergeConfigs($this->defaults(), $payload);
    }

    public function saveConfig(array $payload): void
    {
        $normalized = $this->normalize($payload);
        TelegramBotBuilder::query()->updateOrCreate([
            'key' => self::CONFIG_KEY,
        ], [
            'value' => $normalized,
        ]);
    }

    public function menuButtonLabel(): string
    {
        return (string) data_get($this->getConfig(), 'ui.menu_button_label', 'Open Abiy Tsom');
    }

    public function welcomeMessage(): string
    {
        return (string) data_get($this->getConfig(), 'ui.welcome_message', 'Welcome to Abiy Tsom.');
    }

    public function helpMessage(): string
    {
        return (string) data_get($this->getConfig(), 'ui.help_message', 'Use the buttons below. If your account is linked, the app opens in one tap.');
    }

    public function notLinkedMessage(): string
    {
        return (string) data_get($this->getConfig(), 'ui.not_linked_message', self::DEFAULT_NOT_LINKED_MESSAGE);
    }

    public function enabledCommands(): array
    {
        $commands = (array) data_get($this->getConfig(), 'commands', []);
        $result = [];

        foreach ($commands as $command => $config) {
            if (! is_array($config) || ($config['enabled'] ?? false) !== true) {
                continue;
            }

            $description = trim((string) ($config['description'] ?? ''));
            if ($description === '') {
                $description = self::DEFAULT_COMMAND_DESCRIPTIONS[$command] ?? '';
            }

            $result[] = [
                'command' => $command,
                'description' => $description,
            ];
        }

        return $result;
    }

    public function memberButtons(): array
    {
        return (array) data_get($this->getConfig(), 'member_buttons', []);
    }

    public function adminButtons(): array
    {
        return (array) data_get($this->getConfig(), 'admin_buttons', []);
    }

    public function buttonLabel(string $key, string $scope, string $fallback): string
    {
        $buttons = $scope === 'admin' ? $this->adminButtons() : $this->memberButtons();
        $label = trim((string) data_get($buttons, $key . '.label', ''));
        if ($label === '') {
            return self::DEFAULT_KEYBOARD_LABELS[$key] ?? $fallback;
        }

        return $label;
    }

    public function commandEnabled(string $command): bool
    {
        $commands = (array) data_get($this->getConfig(), 'commands', []);
        return (bool) data_get($commands, $command . '.enabled', false);
    }

    public function buttonEnabled(string $key, string $scope): bool
    {
        $buttons = $scope === 'admin' ? $this->adminButtons() : $this->memberButtons();
        return (bool) data_get($buttons, $key . '.enabled', false);
    }

    private function normalize(array $payload): array
    {
        $defaults = $this->defaults();
        $stored = $this->mergeConfigs($defaults, $payload);

        foreach (['commands', 'member_buttons', 'admin_buttons'] as $section) {
            $items = (array) data_get($stored, $section, []);
            foreach ($items as $key => $item) {
                if (! is_array($item)) {
                    unset($items[$key]);
                    continue;
                }

                $items[$key]['label'] = trim((string) ($item['label'] ?? ''));
                $items[$key]['enabled'] = (bool) ($item['enabled'] ?? false);

                if ($items[$key]['label'] === '') {
                    if (isset(self::DEFAULT_KEYBOARD_LABELS[$key])) {
                        $items[$key]['label'] = self::DEFAULT_KEYBOARD_LABELS[$key];
                    }
                }

                if (isset(self::DEFAULT_COMMAND_DESCRIPTIONS[$key])) {
                    $items[$key]['description'] = trim((string) ($item['description'] ?? self::DEFAULT_COMMAND_DESCRIPTIONS[$key]));
                }
            }
            $stored[$section] = $items;
        }

        $stored['ui']['menu_button_label'] = trim((string) data_get($stored, 'ui.menu_button_label', 'Open Abiy Tsom'));
        $stored['ui']['welcome_message'] = trim((string) data_get($stored, 'ui.welcome_message', 'Welcome to Abiy Tsom.'));
        $stored['ui']['help_message'] = trim((string) data_get($stored, 'ui.help_message', 'Use the buttons below. If your account is linked, the app opens in one tap.'));
        $stored['ui']['not_linked_message'] = trim((string) data_get($stored, 'ui.not_linked_message', self::DEFAULT_NOT_LINKED_MESSAGE));

        if ($stored['ui']['menu_button_label'] === '') {
            $stored['ui']['menu_button_label'] = 'Open Abiy Tsom';
        }

        return $stored;
    }

    private function defaults(): array
    {
        return [
            'ui' => [
                'menu_button_label' => 'Open Abiy Tsom',
                'welcome_message' => 'Welcome to Abiy Tsom.',
                'help_message' => 'Use the buttons below. If your account is linked, the app opens in one tap.',
                'not_linked_message' => self::DEFAULT_NOT_LINKED_MESSAGE,
            ],
            'commands' => [
                'menu' => [
                    'label' => 'Menu',
                    'description' => self::DEFAULT_COMMAND_DESCRIPTIONS['menu'],
                    'enabled' => true,
                ],
                'home' => [
                    'label' => 'Home',
                    'description' => self::DEFAULT_COMMAND_DESCRIPTIONS['home'],
                    'enabled' => true,
                ],
                'day' => [
                    'label' => 'Today',
                    'description' => self::DEFAULT_COMMAND_DESCRIPTIONS['day'],
                    'enabled' => true,
                ],
                'admin' => [
                    'label' => 'Admin',
                    'description' => self::DEFAULT_COMMAND_DESCRIPTIONS['admin'],
                    'enabled' => true,
                ],
                'help' => [
                    'label' => 'Help',
                    'description' => self::DEFAULT_COMMAND_DESCRIPTIONS['help'],
                    'enabled' => true,
                ],
            ],
            'member_buttons' => [
                'home' => [
                    'label' => 'Home',
                    'enabled' => true,
                ],
                'today' => [
                    'label' => 'Today',
                    'enabled' => true,
                ],
                'me' => [
                    'label' => 'My links',
                    'enabled' => true,
                ],
                'help' => [
                    'label' => 'Help',
                    'enabled' => true,
                ],
            ],
            'admin_buttons' => [
                'admin' => [
                    'label' => 'Admin panel',
                    'enabled' => true,
                ],
                'help' => [
                    'label' => 'Help',
                    'enabled' => true,
                ],
            ],
        ];
    }

    private function mergeConfigs(array $defaults, array $stored): array
    {
        $result = $defaults;

        foreach ($stored as $group => $items) {
            if (! isset($result[$group])) {
                continue;
            }

            if (is_array($result[$group]) && is_array($items)) {
                foreach ($items as $key => $value) {
                    if (is_array($result[$group][$key] ?? null) && is_array($value)) {
                        $result[$group][$key] = array_merge($result[$group][$key], $value);
                    } else {
                        $result[$group][$key] = $value;
                    }
                }
                continue;
            }

            $result[$group] = $items;
        }

        return $result;
    }
}
