<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $roles  = ['owner', 'admin', 'teknisi', 'noc'];
        $isDemo = fn () => (bool) config('app.ui_demo', app()->isLocal());

        View::composer('*', function ($view) use ($roles, $isDemo) {
            // Di luar mode demo, yang belum login dianggap "guest", bukan Owner.
            $role = strtolower((string) (auth()->user()->role ?? ($isDemo() ? 'owner' : 'guest')));
            if (! in_array($role, $roles, true)) {
                $role = 'guest';
            }

            if ($isDemo()) {
                if (in_array(request('as'), $roles, true)) {
                    session(['ui_as' => request('as')]);
                }
                $role = session('ui_as', $role);
            }

            $view->with([
                'currentRole' => $role,
                'isOwner'     => $role === 'owner',
                'canManage'   => in_array($role, ['owner', 'admin'], true),
            ]);
        });
    }
}