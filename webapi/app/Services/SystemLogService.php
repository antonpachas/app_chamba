<?php

namespace App\Services;

use App\Exceptions\DomainException;
use App\Models\SystemLog;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

final class SystemLogService
{
    private const TRACE_MAX = 12000;

    public function record(
        string $level,
        string $message,
        string $channel = 'app',
        ?Throwable $throwable = null,
        ?Request $request = null,
        ?int $httpStatus = null,
        array $context = [],
    ): void {
        if (! Schema::hasTable('system_logs')) {
            return;
        }

        try {
            $message = mb_substr(trim($message), 0, 500);
            if ($message === '') {
                $message = 'Error sin mensaje';
            }

            $req = $request ?? (app()->bound('request') ? request() : null);
            $userId = null;
            if ($req?->user()) {
                $userId = (int) $req->user()->id;
            }

            SystemLog::query()->create([
                'level' => $level,
                'channel' => $channel,
                'message' => $message,
                'exception_class' => $throwable ? $throwable::class : null,
                'file' => $throwable ? $this->shortPath($throwable->getFile()) : null,
                'line' => $throwable?->getLine(),
                'trace' => $throwable ? $this->formatTrace($throwable) : null,
                'context' => $context !== [] ? $context : $this->buildContext($req, $throwable),
                'http_status' => $httpStatus,
                'request_method' => $req?->method(),
                'request_path' => $req ? mb_substr($req->path(), 0, 500) : null,
                'user_id' => $userId,
                'ip' => $req?->ip(),
                'created_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::warning('No se pudo guardar system_log en BD', ['error' => $e->getMessage()]);
        }
    }

    public function recordFromThrowable(Throwable $e, ?Request $request = null): void
    {
        if ($this->shouldSkip($e)) {
            return;
        }

        $level = $this->resolveLevel($e);
        $httpStatus = $e instanceof HttpException ? $e->getStatusCode() : null;
        $channel = str_starts_with((string) ($request?->path() ?? request()->path()), 'api/')
            ? 'api'
            : 'app';

        $this->record(
            $level,
            $e->getMessage(),
            $channel,
            $e,
            $request,
            $httpStatus,
        );
    }

    private function shouldSkip(Throwable $e): bool
    {
        if ($e instanceof ValidationException) {
            return true;
        }
        if ($e instanceof AuthenticationException) {
            return true;
        }
        if ($e instanceof NotFoundHttpException) {
            return true;
        }

        return false;
    }

    private function resolveLevel(Throwable $e): string
    {
        if ($e instanceof DomainException) {
            return 'warning';
        }
        if ($e instanceof HttpException) {
            return $e->getStatusCode() >= 500 ? 'error' : 'warning';
        }

        return 'error';
    }

    private function shortPath(string $path): string
    {
        $base = base_path();
        if (str_starts_with($path, $base)) {
            return ltrim(substr($path, strlen($base)), '\\/');
        }

        return mb_substr($path, 0, 500);
    }

    private function formatTrace(Throwable $e): ?string
    {
        $trace = $e->getTraceAsString();
        if ($trace === '') {
            return null;
        }

        if (mb_strlen($trace) > self::TRACE_MAX) {
            return mb_substr($trace, 0, self::TRACE_MAX).'…';
        }

        return $trace;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildContext(?Request $request, ?Throwable $e): array
    {
        $ctx = [];
        if ($request) {
            $ctx['url'] = $request->fullUrl();
            $ctx['user_agent'] = mb_substr((string) $request->userAgent(), 0, 300);
            $ctx['query'] = $request->query();
        }
        if ($e?->getPrevious()) {
            $ctx['previous'] = $e->getPrevious()->getMessage();
        }

        return $ctx;
    }
}
