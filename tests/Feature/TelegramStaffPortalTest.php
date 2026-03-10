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

        $member = Member::create([
            'baptism_name' => 'Some Member',
            'token' => 'test-member-token',
            'telegram_chat_id' => 'member-chat',
        ]);

        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => true], 200),
        ]);

        $header = ['X-Telegram-Bot-Api-Secret-Token' => 'telegram-secret'];

        $this->withHeaders($header)
            ->postJson(route('webhooks.telegram'), $this->callbackPayload('member-chat', 'staff_portal'))
            ->assertOk();

        Http::assertSent(function (ClientRequest $request) {
            return str_contains($request->url(), 'sendMessage')
                && str_contains($request->body(), 'member-chat');
        });
    }

    public function test_writer_can_submit_synaxarium_suggestion_via_telegram_wizard(): void
    {
        config()->set('services.telegram.bot_token', 'test-bot-token');
        config()->set('services.telegram.webhook_secret', 'telegram-secret');

        $writer = User::create([
            'name' => 'Writer One',
            'username' => 'writerone',
            'email' => 'writer1@example.com',
            'password' => 'password',
            'role' => 'writer',
            'telegram_chat_id' => 'writer-chat',
        ]);

        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => true], 200),
        ]);

        $header = ['X-Telegram-Bot-Api-Secret-Token' => 'telegram-secret'];

        // Flow: area → date → scope → (auto Amharic) → fields → confirm
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload('writer-chat', 'suggest'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload('writer-chat', 'suggest_area_synaxarium'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload('writer-chat', 'suggest_month_7'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload('writer-chat', 'suggest_day_5'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload('writer-chat', 'suggest_confirm_date_yes'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload('writer-chat', 'suggest_scope_yearly'))->assertOk();
        // No language choice — auto Amharic
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->messagePayload('writer-chat', 'ቅዱስ ተክለ ሃይማኖት'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload('writer-chat', 'suggest_skip'))->assertOk(); // skip image
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->messagePayload('writer-chat', 'የዓመት በዓል ጥቆማ።'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload('writer-chat', 'suggest_main_yes'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload('writer-chat', 'suggest_other_lang_skip'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload('writer-chat', 'suggest_confirm'))->assertOk();

        $this->assertDatabaseHas('content_suggestions', [
            'user_id' => $writer->id,
            'source' => 'telegram',
            'type' => 'sinksar',
            'content_area' => 'synaxarium',
            'language' => 'am',
            'ethiopian_month' => 7,
            'ethiopian_day' => 5,
            'entry_scope' => 'yearly',
            'status' => 'pending',
        ]);

        $suggestion = ContentSuggestion::query()->latest()->first();

        $this->assertNotNull($suggestion);
        $this->assertSame('yearly', $suggestion->structuredValue('entry_scope'));
        $this->assertTrue((bool) $suggestion->structuredValue('is_main'));
        $this->assertSame('ቅዱስ ተክለ ሃይማኖት', $suggestion->structuredValue('title_am'));
        $this->assertSame('am', $suggestion->structuredValue('first_language'));
        $this->assertSame('awaiting_continue', TelegramBotState::getActive('writer-chat', 'suggest')?->step);
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

        // Flow: area → date → (auto Amharic) → fields → confirm
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload('writer-mezmur-chat', 'suggest'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload('writer-mezmur-chat', 'suggest_area_mezmur'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload('writer-mezmur-chat', 'suggest_month_6'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload('writer-mezmur-chat', 'suggest_day_12'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload('writer-mezmur-chat', 'suggest_confirm_date_yes'))->assertOk();
        // No language choice — auto Amharic
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->messagePayload('writer-mezmur-chat', 'አስደናቂ ጸጋ'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->messagePayload('writer-mezmur-chat', 'https://example.com/mezmur'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload('writer-mezmur-chat', 'suggest_skip'))->assertOk(); // skip detail
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload('writer-mezmur-chat', 'suggest_other_lang_skip'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload('writer-mezmur-chat', 'suggest_confirm'))->assertOk();

        $this->assertDatabaseHas('content_suggestions', [
            'user_id' => $writer->id,
            'source' => 'telegram',
            'type' => 'mezmur',
            'content_area' => 'mezmur',
            'language' => 'am',
            'ethiopian_month' => 6,
            'ethiopian_day' => 12,
            'status' => 'pending',
        ]);

        $suggestion = ContentSuggestion::query()->latest()->first();
        $this->assertNotNull($suggestion);
        $this->assertSame('አስደናቂ ጸጋ', $suggestion->structuredValue('title_am'));
        $this->assertSame('https://example.com/mezmur', $suggestion->structuredValue('url_am'));

        $this->assertSame('awaiting_continue', TelegramBotState::getActive('writer-mezmur-chat', 'suggest')?->step);
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

        // Flow: area → date → (auto Amharic) → reference/summary/text → other lang → reference/summary/text → confirm
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload($chat, 'suggest'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload($chat, 'suggest_area_bible_reading'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload($chat, 'suggest_today'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload($chat, 'suggest_confirm_date_yes'))->assertOk();

        // Phase 1: Amharic (auto-selected)
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->messagePayload($chat, 'ዮሐንስ 3:16'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->messagePayload($chat, 'እግዚአብሔር ዓለሙን እንዲሁ ወዶ'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload($chat, 'suggest_skip'))->assertOk(); // skip full text

        // Add English version
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload($chat, 'suggest_other_lang_yes'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->messagePayload($chat, 'John 3:16'))->assertOk();
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
        $this->assertSame('ዮሐንስ 3:16', $suggestion->structuredValue('reference_am'));
        $this->assertSame('እግዚአብሔር ዓለሙን እንዲሁ ወዶ', $suggestion->structuredValue('summary_am'));
        $this->assertSame('John 3:16', $suggestion->structuredValue('reference_en'));
        $this->assertSame('am', $suggestion->structuredValue('first_language'));
        $this->assertSame('awaiting_continue', TelegramBotState::getActive($chat, 'suggest')?->step);
    }

    public function test_writer_can_submit_spiritual_book_suggestion(): void
    {
        config()->set('services.telegram.bot_token', 'test-bot-token');
        config()->set('services.telegram.webhook_secret', 'telegram-secret');

        $writer = User::create([
            'name' => 'Writer Book',
            'username' => 'writerbook',
            'email' => 'book@example.com',
            'password' => 'password',
            'role' => 'writer',
            'telegram_chat_id' => 'writer-book-chat',
        ]);

        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => true], 200),
        ]);

        $header = ['X-Telegram-Bot-Api-Secret-Token' => 'telegram-secret'];
        $chat = 'writer-book-chat';

        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload($chat, 'suggest'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload($chat, 'suggest_area_spiritual_book'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload($chat, 'suggest_today'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload($chat, 'suggest_confirm_date_yes'))->assertOk();
        // No language choice — auto Amharic
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->messagePayload($chat, 'መንፈሳዊ ትግል'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->messagePayload($chat, 'https://example.com/book'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload($chat, 'suggest_skip'))->assertOk(); // skip detail
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload($chat, 'suggest_other_lang_skip'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload($chat, 'suggest_confirm'))->assertOk();

        $this->assertDatabaseHas('content_suggestions', [
            'user_id' => $writer->id,
            'source' => 'telegram',
            'type' => 'book',
            'content_area' => 'spiritual_book',
            'language' => 'am',
            'status' => 'pending',
        ]);

        $suggestion = ContentSuggestion::query()->latest()->first();
        $this->assertNotNull($suggestion);
        $this->assertSame('መንፈሳዊ ትግል', $suggestion->structuredValue('title_am'));
        $this->assertSame('https://example.com/book', $suggestion->structuredValue('url_am'));
        $this->assertSame('awaiting_continue', TelegramBotState::getActive($chat, 'suggest')?->step);
    }

    public function test_writer_can_submit_daily_message_suggestion(): void
    {
        config()->set('services.telegram.bot_token', 'test-bot-token');
        config()->set('services.telegram.webhook_secret', 'telegram-secret');

        $writer = User::create([
            'name' => 'Writer Daily',
            'username' => 'writerdaily',
            'email' => 'daily@example.com',
            'password' => 'password',
            'role' => 'writer',
            'telegram_chat_id' => 'writer-daily-chat',
        ]);

        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => true], 200),
        ]);

        $header = ['X-Telegram-Bot-Api-Secret-Token' => 'telegram-secret'];
        $chat = 'writer-daily-chat';

        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload($chat, 'suggest'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload($chat, 'suggest_area_daily_message'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload($chat, 'suggest_today'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload($chat, 'suggest_confirm_date_yes'))->assertOk();
        // No language choice — auto Amharic
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->messagePayload($chat, 'የዕለቱ መልዕክት'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->messagePayload($chat, 'ዛሬ ስለ ጸሎት ነው'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload($chat, 'suggest_other_lang_skip'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload($chat, 'suggest_confirm'))->assertOk();

        $this->assertDatabaseHas('content_suggestions', [
            'user_id' => $writer->id,
            'source' => 'telegram',
            'type' => 'reference',
            'content_area' => 'daily_message',
            'language' => 'am',
            'status' => 'pending',
        ]);

        $suggestion = ContentSuggestion::query()->latest()->first();
        $this->assertNotNull($suggestion);
        $this->assertSame('የዕለቱ መልዕክት', $suggestion->structuredValue('title_am'));
        $this->assertSame('ዛሬ ስለ ጸሎት ነው', $suggestion->structuredValue('content_detail_am'));
        $this->assertSame('am', $suggestion->structuredValue('first_language'));
        $this->assertSame('awaiting_continue', TelegramBotState::getActive($chat, 'suggest')?->step);
    }

    public function test_writer_can_submit_reference_resource_suggestion(): void
    {
        config()->set('services.telegram.bot_token', 'test-bot-token');
        config()->set('services.telegram.webhook_secret', 'telegram-secret');

        $writer = User::create([
            'name' => 'Writer Ref',
            'username' => 'writerref',
            'email' => 'ref@example.com',
            'password' => 'password',
            'role' => 'writer',
            'telegram_chat_id' => 'writer-ref-chat',
        ]);

        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => true], 200),
        ]);

        $header = ['X-Telegram-Bot-Api-Secret-Token' => 'telegram-secret'];
        $chat = 'writer-ref-chat';

        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload($chat, 'suggest'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload($chat, 'suggest_area_reference_resource'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload($chat, 'suggest_today'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload($chat, 'suggest_confirm_date_yes'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload($chat, 'suggest_resource_type_video'))->assertOk();
        // No language choice — auto Amharic
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->messagePayload($chat, 'የኦርቶዶክስ ትምህርት ቪዲዮ'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->messagePayload($chat, 'https://youtube.com/watch?v=123'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload($chat, 'suggest_skip'))->assertOk(); // skip detail
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload($chat, 'suggest_other_lang_skip'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload($chat, 'suggest_confirm'))->assertOk();

        $this->assertDatabaseHas('content_suggestions', [
            'user_id' => $writer->id,
            'source' => 'telegram',
            'type' => 'reference',
            'content_area' => 'reference_resource',
            'language' => 'am',
            'status' => 'pending',
        ]);

        $suggestion = ContentSuggestion::query()->latest()->first();
        $this->assertNotNull($suggestion);
        $this->assertSame('የኦርቶዶክስ ትምህርት ቪዲዮ', $suggestion->structuredValue('title_am'));
        $this->assertSame('https://youtube.com/watch?v=123', $suggestion->structuredValue('url_am'));
        $this->assertSame('video', $suggestion->structuredValue('resource_type'));
        $this->assertSame('awaiting_continue', TelegramBotState::getActive($chat, 'suggest')?->step);
    }

    public function test_writer_can_submit_synaxarium_with_both_languages(): void
    {
        config()->set('services.telegram.bot_token', 'test-bot-token');
        config()->set('services.telegram.webhook_secret', 'telegram-secret');

        $writer = User::create([
            'name' => 'Writer Synax',
            'username' => 'writersynax',
            'email' => 'synax@example.com',
            'password' => 'password',
            'role' => 'writer',
            'telegram_chat_id' => 'writer-synax-chat',
        ]);

        Http::fake([
            'https://api.telegram.org/*' => Http::response(['ok' => true, 'result' => true], 200),
        ]);

        $header = ['X-Telegram-Bot-Api-Secret-Token' => 'telegram-secret'];
        $chat = 'writer-synax-chat';

        // Flow: area → date → scope → (auto Amharic) → title → image → detail → is_main → offer_other_lang → title → detail → confirm
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload($chat, 'suggest'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload($chat, 'suggest_area_synaxarium'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload($chat, 'suggest_month_3'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload($chat, 'suggest_day_15'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload($chat, 'suggest_confirm_date_yes'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload($chat, 'suggest_scope_yearly'))->assertOk();
        // No language choice — auto Amharic

        // Phase 1: Amharic fields
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->messagePayload($chat, 'ቅዱስ ገብርኤል'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload($chat, 'suggest_skip'))->assertOk(); // skip image
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->messagePayload($chat, 'የቅዱስ ገብርኤል በዓል'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload($chat, 'suggest_main_yes'))->assertOk();

        // Offer other language → yes (English)
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload($chat, 'suggest_other_lang_yes'))->assertOk();

        // Phase 2: English fields (only bilingual: title, detail — skips image and choose_main)
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->messagePayload($chat, 'Saint Gabriel'))->assertOk();
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->messagePayload($chat, 'Feast of Archangel Gabriel'))->assertOk();

        // Confirm
        $this->withHeaders($header)->postJson(route('webhooks.telegram'), $this->callbackPayload($chat, 'suggest_confirm'))->assertOk();

        $this->assertDatabaseHas('content_suggestions', [
            'user_id' => $writer->id,
            'source' => 'telegram',
            'type' => 'sinksar',
            'content_area' => 'synaxarium',
            'language' => 'both',
            'ethiopian_month' => 3,
            'ethiopian_day' => 15,
            'entry_scope' => 'yearly',
            'status' => 'pending',
        ]);

        $suggestion = ContentSuggestion::query()->latest()->first();
        $this->assertNotNull($suggestion);
        $this->assertSame('ቅዱስ ገብርኤል', $suggestion->structuredValue('title_am'));
        $this->assertSame('የቅዱስ ገብርኤል በዓል', $suggestion->structuredValue('content_detail_am'));
        $this->assertSame('Saint Gabriel', $suggestion->structuredValue('title_en'));
        $this->assertSame('Feast of Archangel Gabriel', $suggestion->structuredValue('content_detail_en'));
        $this->assertTrue((bool) $suggestion->structuredValue('is_main'));
        $this->assertSame('awaiting_continue', TelegramBotState::getActive($chat, 'suggest')?->step);
    }

    /**
     * @return array<string, mixed>
     */
    private function callbackPayload(string $chatId, string $data): array
    {
        return [
            'callback_query' => [
                'id' => '1',
                'from' => ['id' => $chatId, 'is_bot' => false, 'first_name' => 'Test'],
                'message' => [
                    'message_id' => 1,
                    'chat' => ['id' => $chatId, 'type' => 'private'],
                    'date' => time(),
                    'text' => '',
                ],
                'chat_instance' => '1',
                'data' => $data,
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
                'message_id' => random_int(100, 99999),
                'from' => ['id' => $chatId, 'is_bot' => false, 'first_name' => 'Test'],
                'chat' => ['id' => $chatId, 'type' => 'private'],
                'date' => time(),
                'text' => $text,
            ],
        ];
    }
}
