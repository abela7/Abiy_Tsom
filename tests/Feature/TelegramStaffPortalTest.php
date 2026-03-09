<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ContentSuggestion;
use App\Models\Member;
use App\Models\TelegramBotState;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramStaffPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_cannot_open_staff_portal(): void
    {
        config()->set('services.telegram.bot_token', 'test-bot-token');
        config()->set('services.telegram.webhook_secret', 'telegram-secret');

        Member::create([
            'baptism_name' => 'Member One',
            'token' => str_repeat('a', 64),
            'locale' => 'en',
            'theme' => 'sepia',
            'telegram_chat_id' => 'member-chat',
        ]);

        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => true], 200),
        ]);

        $response = $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'telegram-secret')
            ->postJson(route('webhooks.telegram'), $this->callbackPayload('member-chat', 'staff_portal'));

        $response->assertOk();

        Http::assertSent(function (ClientRequest $request): bool {
            return str_contains($request->url(), '/sendMessage')
                && data_get($request->data(), 'text') === __('app.telegram_staff_portal_access_denied');
        });

        $this->assertDatabaseCount('telegram_bot_states', 0);
    }

    public function test_writer_can_submit_synaxarium_suggestion_via_telegram_wizard(): void
    {
        config()->set('services.telegram.bot_token', 'test-bot-token');
        config()->set('services.telegram.webhook_secret', 'telegram-secret');

        $writer = User::create([
            'name' => 'Writer One',
            'username' => 'writerone',
            'email' => 'writer@example.com',
            'password' => 'password',
            'role' => 'writer',
            'telegram_chat_id' => 'writer-chat',
        ]);

        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => true], 200),
        ]);

        $header = ['X-Telegram-Bot-Api-Secret-Token' => 'telegram-secret'];

        // New flow: area → date → scope → language → fields → confirm
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload('writer-chat', 'suggest'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload('writer-chat', 'suggest_area_synaxarium'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload('writer-chat', 'suggest_month_7'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload('writer-chat', 'suggest_day_5'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload('writer-chat', 'suggest_confirm_date_yes'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload('writer-chat', 'suggest_scope_yearly'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload('writer-chat', 'suggest_first_lang_en'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->messagePayload('writer-chat', 'Saint Tekle Haymanot'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload('writer-chat', 'suggest_skip'))->assertOk(); // skip image
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->messagePayload('writer-chat', 'A yearly celebration suggestion from Telegram.'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload('writer-chat', 'suggest_main_yes'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload('writer-chat', 'suggest_other_lang_skip'))->assertOk(); // skip 2nd lang
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload('writer-chat', 'suggest_confirm'))->assertOk();

        $this->assertDatabaseHas('content_suggestions', [
            'user_id' => $writer->id,
            'source' => 'telegram',
            'type' => 'sinksar',
            'content_area' => 'synaxarium',
            'language' => 'en',
            'ethiopian_month' => 7,
            'ethiopian_day' => 5,
            'entry_scope' => 'yearly',
            'status' => 'pending',
        ]);

        $suggestion = ContentSuggestion::query()->latest()->first();

        $this->assertNotNull($suggestion);
        $this->assertSame('yearly', $suggestion->structuredValue('entry_scope'));
        $this->assertTrue((bool) $suggestion->structuredValue('is_main'));
        $this->assertSame('Saint Tekle Haymanot', $suggestion->structuredValue('title_en'));
        $this->assertNull(TelegramBotState::getActive('writer-chat', 'suggest'));
    }

    public function test_writer_can_submit_mezmur_suggestion_via_telegram_wizard(): void
    {
        config()->set('services.telegram.bot_token', 'test-bot-token');
        config()->set('services.telegram.webhook_secret', 'telegram-secret');

        $writer = User::create([
            'name' => 'Writer Two',
            'username' => 'writertwo',
            'email' => 'writer2@example.com',
            'password' => 'password',
            'role' => 'writer',
            'telegram_chat_id' => 'writer-mezmur-chat',
        ]);

        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => true], 200),
        ]);

        $header = ['X-Telegram-Bot-Api-Secret-Token' => 'telegram-secret'];

        // New flow: area → date → language → fields → confirm
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload('writer-mezmur-chat', 'suggest'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload('writer-mezmur-chat', 'suggest_area_mezmur'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload('writer-mezmur-chat', 'suggest_month_6'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload('writer-mezmur-chat', 'suggest_day_12'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload('writer-mezmur-chat', 'suggest_confirm_date_yes'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload('writer-mezmur-chat', 'suggest_first_lang_en'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->messagePayload('writer-mezmur-chat', 'Amazing Grace'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->messagePayload('writer-mezmur-chat', 'https://example.com/mezmur'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload('writer-mezmur-chat', 'suggest_skip'))->assertOk(); // skip detail
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload('writer-mezmur-chat', 'suggest_other_lang_skip'))->assertOk(); // skip 2nd lang
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload('writer-mezmur-chat', 'suggest_confirm'))->assertOk();

        $this->assertDatabaseHas('content_suggestions', [
            'user_id' => $writer->id,
            'source' => 'telegram',
            'type' => 'mezmur',
            'content_area' => 'mezmur',
            'language' => 'en',
            'ethiopian_month' => 6,
            'ethiopian_day' => 12,
            'status' => 'pending',
        ]);

        $suggestion = ContentSuggestion::query()->latest()->first();
        $this->assertNotNull($suggestion);
        $this->assertSame('Amazing Grace', $suggestion->structuredValue('title_en'));
        $this->assertSame('https://example.com/mezmur', $suggestion->structuredValue('url_en'));

        $this->assertNull(TelegramBotState::getActive('writer-mezmur-chat', 'suggest'));
    }

    public function test_writer_can_submit_bible_reading_with_both_languages(): void
    {
        config()->set('services.telegram.bot_token', 'test-bot-token');
        config()->set('services.telegram.webhook_secret', 'telegram-secret');

        $writer = User::create([
            'name' => 'Writer Bible',
            'username' => 'writerbible',
            'email' => 'bible@example.com',
            'password' => 'password',
            'role' => 'writer',
            'telegram_chat_id' => 'writer-bible-chat',
        ]);

        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => true], 200),
        ]);

        $header = ['X-Telegram-Bot-Api-Secret-Token' => 'telegram-secret'];
        $chat = 'writer-bible-chat';

        // Flow: area → date → language → reference/summary/text → other lang → reference/summary/text → confirm
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload($chat, 'suggest'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload($chat, 'suggest_area_bible_reading'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload($chat, 'suggest_today'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload($chat, 'suggest_confirm_date_yes'))->assertOk();

        // Choose English first
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload($chat, 'suggest_first_lang_en'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->messagePayload($chat, 'John 3:16'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->messagePayload($chat, 'God so loved the world'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload($chat, 'suggest_skip'))->assertOk(); // skip full text

        // Add Amharic version
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload($chat, 'suggest_other_lang_yes'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->messagePayload($chat, 'ዮሐንስ 3:16'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload($chat, 'suggest_skip'))->assertOk(); // skip summary
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload($chat, 'suggest_skip'))->assertOk(); // skip text

        // Confirm
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload($chat, 'suggest_confirm'))->assertOk();

        $this->assertDatabaseHas('content_suggestions', [
            'user_id' => $writer->id,
            'source' => 'telegram',
            'type' => 'bible',
            'content_area' => 'bible_reading',
            'language' => 'both',
            'status' => 'pending',
        ]);

        $suggestion = ContentSuggestion::query()->latest()->first();
        $this->assertNotNull($suggestion);
        $this->assertSame('John 3:16', $suggestion->structuredValue('reference_en'));
        $this->assertSame('God so loved the world', $suggestion->structuredValue('summary_en'));
        $this->assertSame('ዮሐንስ 3:16', $suggestion->structuredValue('reference_am'));
        $this->assertSame('en', $suggestion->structuredValue('first_language'));
        $this->assertNull(TelegramBotState::getActive($chat, 'suggest'));
    }

    /**
     * @return array<string, mixed>
     */
    private function callbackPayload(string $chatId, string $action): array
    {
        return [
            'callback_query' => [
                'id' => 'cb-'.$action,
                'from' => [
                    'id' => 1001,
                    'language_code' => 'en',
                ],
                'data' => $action,
                'message' => [
                    'message_id' => 10,
                    'chat' => [
                        'id' => $chatId,
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function messagePayload(string $chatId, string $text): array
    {
        return [
            'message' => [
                'message_id' => 11,
                'chat' => [
                    'id' => $chatId,
                ],
                'from' => [
                    'id' => 1001,
                    'language_code' => 'en',
                ],
                'text' => $text,
            ],
        ];
    }
}
