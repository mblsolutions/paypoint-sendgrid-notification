<?php

namespace MBLSolutions\SendgridNotification\Support\Concerns;

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Auth;

trait HasAuthUser
{
    protected ?User $authUser;

    protected function initAuthUser()
    {
        $this->authUser = optional(Auth::guard(config('notification.auth_guard')))->user()??null;
    }

    public function getAuthUser(): ?User
    {
        return $this->authUser;
    }
}