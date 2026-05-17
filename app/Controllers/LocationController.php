<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\MapsService;

final class LocationController extends Controller
{
    public function search(): void
    {
        $query = trim((string) ($_GET['query'] ?? ''));
        if ($query === '') {
            $this->json(['error' => 'Type a location to search.'], 422);
        }

        $location = (new MapsService())->geocode($query);
        if ($location === null) {
            $this->json(['error' => 'No matching Google Maps location found.'], 404);
        }

        $this->json(['location' => $location]);
    }
}
