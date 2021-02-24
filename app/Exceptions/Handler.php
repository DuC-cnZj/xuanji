<?php

namespace App\Exceptions;

use Throwable;
use Illuminate\Support\Arr;
use League\CommonMark\CommonMarkConverter;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Convert the given exception to an array.
     *
     * @param  \Throwable  $e
     * @return array
     */
    protected function convertExceptionToArray(Throwable $e)
    {
        $msg = $this->getErrorHtml(collect($e->getTrace())->map(function ($trace) {
            return Arr::except($trace, ['args']);
        })
            ->map(
                fn ($item) => collect($item)->map(fn ($v, $k) => "$k: $v")
            )
            ->values()
            ->map
            ->implode("\n```\n")
            ->slice(0, 8)
            ->implode('') . '...', $e->getMessage());

        return ['msg' => $msg];
    }

    public function getErrorHtml($data, $header)
    {
        return $this->md()->convertToHtml(
            <<<MSG
### $header
$data
MSG
        );
    }

    public function md()
    {
        return new CommonMarkConverter([
            'html_input'         => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }
}
