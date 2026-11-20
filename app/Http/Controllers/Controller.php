<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;

/**
 * Base controller.
 *
 * Laravel 12's default controller is empty. `authorize()` comes from
 * AuthorizesRequests, which is not included by default — and without it a
 * policy call throws a 500 rather than returning 403, which looks like a crash
 * instead of a denial.
 */
abstract class Controller
{
    use AuthorizesRequests, ValidatesRequests;
}
