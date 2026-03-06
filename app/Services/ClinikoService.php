<?php

namespace App\Services;

use App\Models\Business;
use App\Models\BusinessIntegration;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Http;

class ClinikoService
{
    // Cliniko shards: au1, au2, au3, au4, ca1, sg1, uk1, us1
    // The correct shard is embedded in the API key itself (e.g., key ends in ==au1)

    public function __construct(protected Business $business) {}

    protected function integration(): ?BusinessIntegration
    {
        return $this->business->integration('cliniko');
    }

    protected function baseUrl(): string
    {
        $shard = $this->integration()?->getMeta('shard') ?? 'au1';

        return "https://api.{$shard}.cliniko.com/v1";
    }

    protected function request(string $path, array $query = []): array
    {
        $response = Http::withBasicAuth($this->integration()?->api_key, '')
            ->withHeaders([
                'User-Agent' => 'ReviewMate (reviewmate.com.au)',
                'Accept' => 'application/json',
            ])
            ->get($this->baseUrl().$path, $query);

        return $response->json() ?? [];
    }

    /**
     * Get appointments that were completed since a given datetime.
     * Cliniko doesn't have a "completed" status — appointments that are
     * in the past and not cancelled are considered completed.
     */
    public function getCompletedAppointmentsSince(CarbonInterface $since): array
    {
        $appointments = [];
        $page = 1;

        do {
            $data = $this->request('/appointments', [
                'q' => "appointment_start>={$since->toIso8601String()}",
                'per_page' => 100,
                'page' => $page,
                'sort' => 'appointment_start',
                'order' => 'asc',
            ]);

            $batch = $data['appointments'] ?? [];
            $appointments = array_merge($appointments, $batch);
            $page++;

            $hasMore = isset($data['links']['next']);
        } while ($hasMore && count($batch) > 0);

        // Filter: past appointments that are not cancelled
        return array_filter($appointments, function ($apt) {
            return ($apt['cancelled'] ?? false) === false
                && now()->isAfter($apt['appointment_end'] ?? now());
        });
    }

    public function getPatient(string $patientId): ?array
    {
        $data = $this->request("/patients/{$patientId}");

        return $data ?: null;
    }

    public function testConnection(): bool
    {
        $data = $this->request('/practitioners', ['per_page' => 1]);

        return isset($data['practitioners']);
    }

    /**
     * Detect shard from API key — Cliniko API keys contain the shard suffix.
     */
    public static function detectShard(string $apiKey): string
    {
        // Cliniko API keys end with a region identifier after ==
        if (preg_match('/==([a-z]{2}\d)$/', $apiKey, $matches)) {
            return $matches[1]; // e.g., 'au1', 'au2'
        }

        return 'au1'; // default Australian shard
    }
}
