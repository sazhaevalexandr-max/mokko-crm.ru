<?php
declare(strict_types=1);

namespace Mokko\Http\Middleware;

use Mokko\Http\Request;
use Mokko\Http\Response;

interface Middleware
{
    public function handle(Request $request, callable $next): Response;
}
