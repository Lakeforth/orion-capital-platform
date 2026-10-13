<?php

namespace App\Services;

use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Log;

class UserService
{
    public function convertLeadToCustomer(User $user): bool
    {
        try {
            $user->removeRole('lead');
            $user->addRole('user');
            return true;
        } catch (Exception $e) {
            Log::error("Failed to convert lead ID {$user->id}: " . $e->getMessage());
            return false;
        }
    }


    public function batchConvertLeadsToCustomers(array $ids): array
    {
        $results = [
            'success_count' => 0,
            'error_count' => 0
        ];

        foreach ($ids as $id) {
            try {
                $user = User::findOrFail($id);
                $this->convertLeadToCustomer($user);
                $results['success_count']++;
            } catch (Exception $e) {
                $results['error_count']++;
                logger()->error("Failed to convert lead ID {$id}: " . $e->getMessage());
            }
        }

        return $results;
    }
}
