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

        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload('writer-chat', 'suggest'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload('writer-chat', 'suggest_lang_en'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload('writer-chat', 'suggest_area_synaxarium'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload('writer-chat', 'suggest_month_7'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload('writer-chat', 'suggest_day_5'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload('writer-chat', 'suggest_confirm_date_yes'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload('writer-chat', 'suggest_scope_yearly'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->messagePayload('writer-chat', 'Saint Tekle Haymanot'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload('writer-chat', 'suggest_skip'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->messagePayload('writer-chat', 'A yearly celebration suggestion from Telegram.'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload('writer-chat', 'suggest_main_yes'))->assertOk();
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
            'title' => 'Saint Tekle Haymanot',
            'status' => 'pending',
        ]);

        $suggestion = ContentSuggestion::query()->latest()->first();

        $this->assertNotNull($suggestion);
        $this->assertSame('yearly', $suggestion->structuredValue('entry_scope'));
        $this->assertTrue((bool) $suggestion->structuredValue('is_main'));
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

        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload('writer-mezmur-chat', 'suggest'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload('writer-mezmur-chat', 'suggest_lang_en'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload('writer-mezmur-chat', 'suggest_area_mezmur'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload('writer-mezmur-chat', 'suggest_month_6'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload('writer-mezmur-chat', 'suggest_day_12'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload('writer-mezmur-chat', 'suggest_confirm_date_yes'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->messagePayload('writer-mezmur-chat', 'Amazing Grace'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->messagePayload('writer-mezmur-chat', 'https://example.com/mezmur'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload('writer-mezmur-chat', 'suggest_skip'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload('writer-mezmur-chat', 'suggest_confirm'))->assertOk();

        $this->assertDatabaseHas('content_suggestions', [
            'user_id' => $writer->id,
            'source' => 'telegram',
            'type' => 'mezmur',
            'content_area' => 'mezmur',
            'language' => 'en',
            'ethiopian_month' => 6,
            'ethiopian_day' => 12,
            'title' => 'Amazing Grace',
            'url' => 'https://example.com/mezmur',
            'status' => 'pending',
        ]);

        $this->assertNull(TelegramBotState::getActive('writer-mezmur-chat', 'suggest'));
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
