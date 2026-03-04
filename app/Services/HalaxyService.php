<?php

namespace App\Services;

use App\Models\Business;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Http;

class HalaxyService
{
    const API_BASE = 'https://api.halaxy.com/v1';

    public function __construct(protected Business $business) {}

    protected function request(string $path, array $query = []): array
    {
        $response = Http::withToken($this->business->halaxy_api_key)
            ->withHeaders(['Accept' => 'application/json'])
            ->get(self::API_BASE . $path, $query);

        return $response->json() ?? [];
    }

    /**
     * Get completed appointments since a given datetime.
     * Halaxy appointments with status 'COMPLETED' and end time in the past.
     */
    public function getCompletedAppointmentsSince(CarbonInterface $since): array
    {
        $data = $this->request('/appointments', [
            'date_from' => $since->format('Y-m-d'),
            'status'    => 'COMPLETED',
            'per_page'  => 200,
        ]);

        return $data['data'] ?? $data['entry'] ?? [];
    }

    public function getPatient(string $patientId): ?array
    {
        $data = $this->request("/patients/{$patientId}");
        return $data ?: null;
    }

    public function testConnection(): bool
    {
        $data = $this->request('/practitioners', ['per_page' => 1]);
        return ! empty($data);
    }
}
