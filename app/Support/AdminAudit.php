<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Route;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Builds sanitized audit payloads for admin actions.
 */
class AdminAudit
{
    /**
     * @var list<string>
     */
    private const WRITE_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    /**
     * @var list<string>
     */
    private const IGNORED_INPUT_KEYS = ['_token', '_method'];

    /**
     * @var list<string>
     */
    private const IGNORED_SNAPSHOT_KEYS = ['created_at', 'updated_at', 'deleted_at', 'remember_token'];

    /**
     * @var list<string>
     */
    private const EXACT_SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'current_password',
        'token',
        'bot_token',
        'telegram_bot_token',
        'ultramsg_token',
        'webhook_secret',
        'secret',
    ];

    /**
     * @var array<string, string>
     */
    private const ACTION_LABELS = [
        'admin.profile.update' => 'Updated admin profile',
        'admin.daily.scaffold' => 'Scaffolded daily content',
        'admin.daily.upload_book_pdf' => 'Uploaded spiritual book PDF',
        'admin.daily.upload_sinksar_image' => 'Uploaded Sinksar image',
        'admin.daily.delete_sinksar_image' => 'Deleted Sinksar image',
        'admin.daily.store' => 'Created daily content',
        'admin.daily.patch' => 'Updated daily content section',
        'admin.daily.update' => 'Updated daily content',
        'admin.daily.destroy' => 'Deleted daily content',
        'admin.announcements.store' => 'Created announcement',
        'admin.announcements.update' => 'Updated announcement',
        'admin.announcements.destroy' => 'Deleted announcement',
        'admin.activities.store' => 'Created activity',
        'admin.activities.update' => 'Updated activity',
        'admin.activities.destroy' => 'Deleted activity',
        'admin.fundraising.store' => 'Updated fundraising campaign',
        'admin.fundraising.delete-response' => 'Deleted fundraising response',
        'admin.fundraising.reset' => 'Reset fundraising responses',
        'admin.volunteer-invitations.store' => 'Created volunteer invitation campaign',
        'admin.volunteer-invitations.submissions.export' => 'Exported volunteer submissions',
        'admin.volunteer-invitations.submissions.delete' => 'Deleted volunteer submissions',
        'admin.volunteer-invitations.update' => 'Updated volunteer invitation campaign',
        'admin.volunteer-invitations.destroy' => 'Deleted volunteer invitation campaign',
        'admin.volunteer-invitations.activate' => 'Activated volunteer invitation campaign',
        'admin.banners.store' => 'Created banner',
        'admin.banners.update' => 'Updated banner',
        'admin.banners.destroy' => 'Deleted banner',
        'admin.banners.toggle' => 'Toggled banner visibility',
        'admin.feedback.toggle-read' => 'Updated feedback read status',
        'admin.feedback.destroy' => 'Deleted feedback',
        'admin.suggestions.clear-all' => 'Cleared content suggestions',
        'admin.suggestions.mark-used' => 'Marked suggestion as used',
        'admin.suggestions.unmark-used' => 'Reopened used suggestion',
        'admin.suggestions.reject' => 'Rejected suggestion',
        'admin.seasons.store' => 'Created season',
        'admin.seasons.update' => 'Updated season',
        'admin.themes.store' => 'Created weekly theme',
        'admin.themes.import-lectionary' => 'Imported lectionary into weekly theme',
        'admin.themes.update' => 'Updated weekly theme',
        'admin.lectionary.store' => 'Created lectionary entry',
        'admin.lectionary.update' => 'Updated lectionary entry',
        'admin.lectionary.destroy' => 'Deleted lectionary entry',
        'admin.synaxarium.monthly.store' => 'Created monthly Synaxarium entry',
        'admin.synaxarium.monthly.update' => 'Updated monthly Synaxarium entry',
        'admin.synaxarium.monthly.destroy' => 'Deleted monthly Synaxarium entry',
        'admin.synaxarium.annual.store' => 'Created annual Synaxarium entry',
        'admin.synaxarium.annual.update' => 'Updated annual Synaxarium entry',
        'admin.synaxarium.annual.destroy' => 'Deleted annual Synaxarium entry',
        'admin.synaxarium.monthly.convert' => 'Converted monthly Synaxarium entry to annual',
        'admin.synaxarium.annual.convert' => 'Converted annual Synaxarium entry to monthly',
        'admin.members.update' => 'Updated member profile',
        'admin.members.telegram-link' => 'Generated member Telegram login link',
        'admin.members.wipe-all' => 'Wiped all members',
        'admin.members.destroy' => 'Deleted member',
        'admin.members.wipe-data' => 'Wiped member data',
        'admin.members.restart-tour' => 'Restarted member tour',
        'admin.referrals.enable' => 'Enabled referral tracking',
        'admin.referrals.disable' => 'Disabled referral tracking',
        'admin.referrals.regenerate' => 'Regenerated referral code',
        'admin.tour.clear-all' => 'Cleared all tour data',
        'admin.tour.reset-member' => 'Reset member tour',
        'admin.translations.store' => 'Created translation key',
        'admin.translations.update' => 'Updated translations',
        'admin.translations.sync' => 'Synced translations',
        'admin.seo.update' => 'Updated SEO settings',
        'admin.day-assignments.update' => 'Updated day assignment',
        'admin.day-assignments.send-reminder' => 'Sent writer reminder',
        'admin.whatsapp.update' => 'Updated WhatsApp settings',
        'admin.whatsapp.test' => 'Sent WhatsApp test message',
        'admin.whatsapp.webhook' => 'Updated WhatsApp webhook settings',
        'admin.whatsapp.update-webhook-secret' => 'Updated WhatsApp webhook secret',
        'admin.whatsapp.update-reminder-once-only' => 'Updated WhatsApp reminder mode',
        'admin.whatsapp.template.update' => 'Updated WhatsApp template',
        'admin.whatsapp.template.test' => 'Sent WhatsApp template test',
        'admin.whatsapp.reminders.update' => 'Updated WhatsApp reminder',
        'admin.whatsapp.reminders.send' => 'Sent member WhatsApp reminder',
        'admin.whatsapp.reminders.disable' => 'Disabled member WhatsApp reminder',
        'admin.whatsapp.reminders.confirm' => 'Confirmed member WhatsApp reminder',
        'admin.whatsapp.reminders.destroy' => 'Deleted WhatsApp member data',
        'admin.whatsapp.members-data.destroy' => 'Deleted WhatsApp member record',
        'admin.telegram.update' => 'Updated Telegram settings',
        'admin.telegram.builder.update' => 'Updated Telegram bot builder',
        'admin.telegram.sync-menu' => 'Synced Telegram menu',
        'admin.telegram.test' => 'Sent Telegram test message',
        'admin.telegram.login-link' => 'Generated admin Telegram login link',
        'admin.admins.store' => 'Created admin user',
        'admin.admins.update' => 'Updated admin user',
        'admin.admins.destroy' => 'Deleted admin user',
        'admin.admins.telegram-link' => 'Generated admin Telegram login link',
    ];

    public static function shouldLog(Request $request): bool
    {
        $routeName = $request->route()?->getName();

        return $request->user() !== null
            && $routeName !== null
            && str_starts_with($routeName, 'admin.')
            && in_array($request->method(), self::WRITE_METHODS, true);
    }

    public static function actionLabel(?string $routeName, string $method, ?string $targetType = null): string
    {
        if ($routeName !== null && array_key_exists($routeName, self::ACTION_LABELS)) {
            return self::ACTION_LABELS[$routeName];
        }

        $resource = $targetType !== null ? Str::headline($targetType) : 'record';

        return match ($method) {
            'POST' => 'Created '.$resource,
            'PUT', 'PATCH' => 'Updated '.$resource,
            'DELETE' => 'Deleted '.$resource,
            default => 'Updated '.$resource,
        };
    }

    /**
     * @return array<string, mixed>
     */
    public static function requestSummary(Request $request): array
    {
        $input = Arr::except($request->all(), self::IGNORED_INPUT_KEYS);
        $files = self::summarizeFiles($request->allFiles());

        if ($files !== []) {
            $input['_files'] = $files;
        }

        return self::sanitizeValue($input);
    }

    /**
     * @return list<string>
     */
    public static function changedFields(Request $request): array
    {
        $payload = Arr::except($request->all(), self::IGNORED_INPUT_KEYS);
        $fields = array_keys($payload);

        if ($request->allFiles() !== []) {
            $fields[] = '_files';
        }

        sort($fields);

        return array_values($fields);
    }

    /**
     * @return array{
     *     target_type: string|null,
     *     target_id: string|null,
     *     target_label: string|null,
     *     route_parameters: array<string, mixed>,
     *     target_model: \Illuminate\Database\Eloquent\Model|null
     * }
     */
    public static function targetDetails(?Route $route): array
    {
        if ($route === null) {
            return [
                'target_type' => null,
                'target_id' => null,
                'target_label' => null,
                'route_parameters' => [],
                'target_model' => null,
            ];
        }

        $routeParameters = [];
        $targetType = null;
        $targetId = null;
        $targetLabel = null;
        $targetModel = null;

        foreach ($route->parametersWithoutNulls() as $name => $value) {
            if ($value instanceof Model) {
                $routeParameters[$name] = [
                    'type' => class_basename($value),
                    'id' => (string) $value->getKey(),
                    'label' => self::modelLabel($value),
                ];

                if ($targetType === null) {
                    $targetType = class_basename($value);
                    $targetId = (string) $value->getKey();
                    $targetLabel = self::modelLabel($value);
                    $targetModel = $value;
                }

                continue;
            }

            $routeParameters[$name] = self::sanitizeValue($value, $name);

            if ($targetType === null) {
                $targetType = Str::headline($name);
                $targetId = (string) $value;
                $targetLabel = (string) $value;
            }
        }

        return [
            'target_type' => $targetType,
            'target_id' => $targetId,
            'target_label' => $targetLabel,
            'route_parameters' => $routeParameters,
            'target_model' => $targetModel,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function modelSnapshot(?Model $model): ?array
    {
        if ($model === null) {
            return null;
        }

        /** @var array<string, mixed> $attributes */
        $attributes = Arr::except($model->getAttributes(), self::IGNORED_SNAPSHOT_KEYS);

        return self::sanitizeValue($attributes);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function refreshedModelSnapshot(?Model $model, int $statusCode): ?array
    {
        if ($model === null || $statusCode >= 400) {
            return null;
        }

        $freshModel = $model->fresh();

        return $freshModel instanceof Model
            ? self::modelSnapshot($freshModel)
            : null;
    }

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     * @param  list<string>  $changedFields
     * @param  array<string, mixed>  $requestSummary
     * @return array<string, array{before: mixed, after: mixed}>
     */
    public static function valueChanges(
        ?array $before,
        ?array $after,
        array $changedFields,
        array $requestSummary,
        string $method
    ): array {
        if ($before === null && $after === null) {
            return [];
        }

        $fields = array_values(array_filter(
            $changedFields,
            static fn (string $field): bool => $field !== '_files'
        ));

        $changes = [];

        foreach ($fields as $field) {
            $beforeValue = $before[$field] ?? null;
            $afterValue = array_key_exists($field, $after ?? [])
                ? $after[$field]
                : ($requestSummary[$field] ?? null);

            if (self::isSensitiveKey($field)) {
                $changes[$field] = [
                    'before' => '[REDACTED]',
                    'after' => '[REDACTED]',
                ];

                continue;
            }

            if (self::valuesMatch($beforeValue, $afterValue)) {
                continue;
            }

            $changes[$field] = [
                'before' => $beforeValue,
                'after' => $afterValue,
            ];
        }

        if ($changes !== [] || $method !== 'DELETE' || $before === null) {
            return $changes;
        }

        foreach (['id', 'name', 'title', 'username', 'baptism_name', 'email', 'role', 'status'] as $field) {
            if (! array_key_exists($field, $before)) {
                continue;
            }

            $changes[$field] = [
                'before' => $before[$field],
                'after' => null,
            ];
        }

        return $changes;
    }

    private static function sanitizeValue(mixed $value, ?string $key = null): mixed
    {
        if ($key !== null && self::isSensitiveKey($key)) {
            return '[REDACTED]';
        }

        if (is_array($value)) {
            $sanitized = [];
            $count = 0;

            foreach ($value as $childKey => $childValue) {
                if ($count >= 25) {
                    $sanitized['__truncated__'] = 'Additional items omitted';
                    break;
                }

                $normalizedKey = is_string($childKey) ? $childKey : (string) $childKey;
                $sanitized[$normalizedKey] = self::sanitizeValue($childValue, $normalizedKey);
                $count++;
            }

            return $sanitized;
        }

        if ($value instanceof UploadedFile) {
            return [
                'name' => $value->getClientOriginalName(),
                'size' => $value->getSize(),
                'mime_type' => $value->getClientMimeType(),
            ];
        }

        if ($value instanceof Model) {
            return [
                'type' => class_basename($value),
                'id' => (string) $value->getKey(),
                'label' => self::modelLabel($value),
            ];
        }

        if (is_string($value)) {
            $trimmed = trim($value);

            if (mb_strlen($trimmed) > 180) {
                return mb_substr($trimmed, 0, 177).'...';
            }

            return $trimmed;
        }

        return $value;
    }

    /**
     * @param  array<string, UploadedFile|array<int|string, UploadedFile>>  $files
     * @return array<string, mixed>
     */
    private static function summarizeFiles(array $files): array
    {
        $summary = [];

        foreach ($files as $key => $file) {
            $summary[$key] = self::sanitizeValue($file, $key);
        }

        return $summary;
    }

    private static function isSensitiveKey(string $key): bool
    {
        $normalized = Str::snake($key);

        if (in_array($normalized, self::EXACT_SENSITIVE_KEYS, true)) {
            return true;
        }

        return preg_match('/(password|token|secret)/i', $normalized) === 1;
    }

    private static function modelLabel(Model $model): ?string
    {
        foreach (['name', 'title', 'username', 'baptism_name', 'day_number'] as $attribute) {
            $value = $model->getAttribute($attribute);

            if (is_scalar($value) && $value !== '') {
                return (string) $value;
            }
        }

        return null;
    }

    private static function valuesMatch(mixed $before, mixed $after): bool
    {
        return json_encode($before) === json_encode($after);
    }
}
