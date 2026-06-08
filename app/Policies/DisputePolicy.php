<?php

namespace App\Policies;

use App\Models\Dispute;
use App\Models\User;

class DisputePolicy
{
    public function view(User $user, Dispute $dispute): bool
    {
        $order = $dispute->order;

        return $user->isAdmin() || $user->id === $order->user_id || $user->id === $order->vendor_id;
    }

    public function create(User $user): bool
    {
        return ! $user->isAdmin();
    }

    public function reply(User $user, Dispute $dispute): bool
    {
        return $this->view($user, $dispute);
    }

    public function resolve(User $user, Dispute $dispute): bool
    {
        return $user->isAdmin();
    }
}
