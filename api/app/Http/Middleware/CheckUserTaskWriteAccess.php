<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserTaskWriteAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $task = $request->route('task');

        if ($user->role === User::ROLE_ADMIN) {
            return $next($request);
        }
        if ($task && $task->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden. You do not own this task.'], 403);
        }
        if ($task && $task->deadline && $task->deadline->isPast()) {
            return response()->json(['message' => 'Forbidden. Only admins can edit overdue tasks.'], 403);
        }

        return $next($request);
    }
}
