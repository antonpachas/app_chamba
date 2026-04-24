<?php

namespace App\Services;

use App\Exceptions\DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

final class StoredProcedureService
{
    /**
     * @return array<int, object>
     */
    private function call(string $procedure, array $bindings): array
    {
        $placeholders = implode(',', array_fill(0, count($bindings), '?'));

        try {
            return DB::select('CALL '.$procedure.'('.$placeholders.')', $bindings);
        } catch (QueryException $e) {
            $message = $e->getPrevious()?->getMessage() ?? $e->getMessage();

            throw new DomainException($message, $e);
        }
    }

    public function registerUser(
        string $fullName,
        string $email,
        ?string $phone,
        string $passwordHash,
        string $role,
    ): int {
        $rows = $this->call('sp_register_user', [$fullName, $email, $phone, $passwordHash, $role]);

        return (int) ($rows[0]->user_id ?? 0);
    }

    public function createProviderProfile(
        int $userId,
        ?string $businessName,
        ?string $description,
        ?string $whatsapp,
        ?string $contactPhone,
        ?string $addressText,
        int $districtId,
    ): int {
        $rows = $this->call('sp_create_provider_profile', [
            $userId,
            $businessName,
            $description,
            $whatsapp,
            $contactPhone,
            $addressText,
            $districtId,
        ]);

        return (int) ($rows[0]->provider_profile_id ?? 0);
    }

    public function updateProviderProfile(
        int $providerProfileId,
        ?string $businessName,
        ?string $description,
        ?string $whatsapp,
        ?string $contactPhone,
        ?string $addressText,
        int $districtId,
    ): void {
        $this->call('sp_update_provider_profile', [
            $providerProfileId,
            $businessName,
            $description,
            $whatsapp,
            $contactPhone,
            $addressText,
            $districtId,
        ]);
    }

    public function createProviderService(
        int $providerProfileId,
        int $categoryId,
        string $title,
        string $description,
        ?float $basePrice,
        string $priceType,
    ): int {
        $rows = $this->call('sp_create_provider_service', [
            $providerProfileId,
            $categoryId,
            $title,
            $description,
            $basePrice,
            $priceType,
        ]);

        return (int) ($rows[0]->service_id ?? 0);
    }

    public function updateProviderService(
        int $serviceId,
        int $categoryId,
        string $title,
        string $description,
        ?float $basePrice,
        string $priceType,
    ): void {
        $this->call('sp_update_provider_service', [
            $serviceId,
            $categoryId,
            $title,
            $description,
            $basePrice,
            $priceType,
        ]);
    }

    public function setServiceStatus(int $serviceId, bool $isActive): void
    {
        $this->call('sp_set_service_status', [$serviceId, $isActive ? 1 : 0]);
    }

    public function createServiceRequest(
        int $clientUserId,
        int $providerServiceId,
        ?string $message,
        string $contactChannel,
    ): int {
        $rows = $this->call('sp_create_service_request', [
            $clientUserId,
            $providerServiceId,
            $message,
            $contactChannel,
        ]);

        return (int) ($rows[0]->service_request_id ?? 0);
    }

    public function closeServiceRequest(int $serviceRequestId): void
    {
        $this->call('sp_close_service_request', [$serviceRequestId]);
    }

    public function createReview(
        int $serviceRequestId,
        int $clientUserId,
        int $rating,
        ?string $comment,
    ): int {
        $rows = $this->call('sp_create_review', [
            $serviceRequestId,
            $clientUserId,
            $rating,
            $comment,
        ]);

        return (int) ($rows[0]->provider_profile_id ?? 0);
    }

    /**
     * @return array<int, object>
     */
    public function toggleFavorite(int $userId, int $providerProfileId): string
    {
        $rows = $this->call('sp_toggle_favorite', [$userId, $providerProfileId]);

        return (string) ($rows[0]->action_result ?? '');
    }

    /**
     * @return array<int, object>
     */
    public function searchProviderServices(
        ?int $categoryId,
        ?int $districtId,
        ?string $keyword,
        ?float $userLat,
        ?float $userLng,
        ?float $radiusKm,
    ): array {
        return $this->call('sp_search_provider_services', [
            $categoryId,
            $districtId,
            $keyword,
            $userLat,
            $userLng,
            $radiusKm,
        ]);
    }

    /**
     * @return array<int, object>
     */
    public function getProviderDashboard(int $providerProfileId): array
    {
        return $this->call('sp_get_provider_dashboard', [$providerProfileId]);
    }

    /**
     * @return array<int, object>
     */
    public function listUserFavorites(int $userId): array
    {
        return $this->call('sp_list_user_favorites', [$userId]);
    }
}
