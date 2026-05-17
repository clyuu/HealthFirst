<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;

final class HomeController extends Controller
{
    public function index(): void
    {
        if (Auth::check()) {
            $this->redirect($this->dashboardForRole((string) (Auth::user()['role_slug'] ?? '')));
        }

        $this->render('home/index', [
            'title' => 'HealthFirst Emergency Medical System',
        ]);
    }

    private function dashboardForRole(string $role): string
    {
        return match ($role) {
            'patient' => '/patient/dashboard',
            'paramedic' => '/paramedic/dashboard',
            'doctor' => '/doctor/dashboard',
            'hospital_staff' => '/hospital/dashboard',
            'hospital_admin' => '/admin/hospital',
            'system_admin' => '/admin/system',
            default => '/',
        };
    }
}
