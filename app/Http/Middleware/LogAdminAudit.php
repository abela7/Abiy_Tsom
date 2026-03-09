<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\AuditLog;
use App\Support\AdminAudit;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Records sanitized audit events for admin write requests.
 */
class LogAdminAudit
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! AdminAudit::shouldLog($request)) {
            return $next($request);
        }

        $route = $request->route();
        $target = AdminAudit::targetDetails($route);
        $requestSummary = AdminAudit::requestSummary($request);
        $changedFields = AdminAudit::changedFields($request);
        $routeName = $route?->getName();
        $action = AdminAudit::actionLabel(
            $routeName,
            $request->method(),
            $target['target_type']
        );

        try {
            $response = $next($request);

            $this->storeAuditLog(
                $request,
                $routeName,
                $action,
                $target,
                $requestSummary,
                $changedFields,
                $response->getStatusCode()
            );

            return $response;
        } catch (ValidationException $exception) {
            $this->storeAuditLog(
                $request,
                $routeName,
                $action,
                $target,
                $requestSummary,
                $changedFields,
                $exception->status,
                ['exception' => class_basename($exception)]
            );

            throw $exception;
        } catch (Throwable $exception) {
            $statusCode = $exception instanceof HttpExceptionInterface
                ? $exception->getStatusCode()
                : 500;

            $this->storeAuditLog(
                $request,
                $routeName,
                $action,
                $target,
                $requestSummary,
                $changedFields,
                $statusCode,
                ['exception' => class_basename($exception)]
            );

            throw $exception;
        }
    }

    /**
     * @param  array{target_type: string|null, target_id: string|null, target_label: string|null, route_parameters: array<string, mixed>}  $target
     * @param  array<string, mixed>  $requestSummary
     * @param  list<string>  $changedFields
     * @param  array<string, mixed>  $extraMeta
     */
    private function storeAuditLog(
        Request $request,
        ?string $routeName,
        string $action,
        array $target,
        array $requestSummary,
        array $changedFields,
        int $statusCode,
        array $extraMeta = []
    ): void {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        AuditLog::create([
            'admin_user_id' => $request->user()?->getAuthIdentifier(),
            'route_name' => $routeName,
            'action' => $action,
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'target_type' => $target['target_type'],
            'target_id' => $target['target_id'],
            'target_label' => $target['target_label'],
            'status_code' => $statusCode,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'request_summary' => $requestSummary,
            'changed_fields' => $changedFields,
            'meta' => array_merge([
                'route_parameters' => $target['route_parameters'],
            ], $extraMeta),
        ]);
    }
}
