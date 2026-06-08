<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VendorProfile;

class VendorPolicy
{
    public function view(?User $user, VendorProfile $vendorProfile): bool
    {
        return true;
    }

    public function update(User $user, VendorProfile $vendorProfile): bool
    {
        return $user->isAdmin() || $user->id === $vendorProfile->user_id;
    }

    public function manage(User $user): bool
    {
        return $user->isVendor() || $user->isAdmin();
    }
}
