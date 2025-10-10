<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\DB;

class UpdateSessionUserId
{
    /**
     * Handle the Login event.
     *
     * Automatically populates the user_id column in the sessions table
     * when a user logs in. This makes session management and tracking
     * much easier without needing to extract from payload.
     *
     * @param  \Illuminate\Auth\Events\Login  $event
     * @return void
     */
    public function handle(Login $event): void
    {
        // Only update if using database session driver
        if (config('session.driver') !== 'database') {
            return;
        }

        // Get the current session ID
        $sessionId = session()->getId();

        // Update the user_id column for the current session
        DB::table(config('session.table', 'sessions'))
            ->where('id', $sessionId)
            ->update(['user_id' => $event->user->id]);
    }
}
