<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Flash;
use App\Services\AuthService;
use InvalidArgumentException;
use Throwable;

final class AuthController extends Controller
{
    public function login(): void
    {
        if ($this->isPost()) {
            $this->validateCsrf();
            remember_old_input($_POST);

            $success = (new AuthService())->login(trim((string) $_POST['email']), (string) $_POST['password']);
            if (!$success) {
                Flash::error('Invalid email or password.');
                $this->redirect('/login');
            }

            clear_old_input();
            Flash::success('Welcome back.');
            $user = Auth::user();
            $this->redirect(match ($user['role_slug'] ?? '') {
                'patient' => '/patient/dashboard',
                'paramedic' => '/ambulance/dashboard',
                'doctor' => '/doctor/dashboard',
                'hospital_admin' => '/admin/hospital',
                'system_admin' => '/admin/system',
                default => '/',
            });
        }

        $this->render('auth/login', ['title' => 'Login']);
    }

    public function register(): void
    {
        if ($this->isPost()) {
            $this->validateCsrf();
            remember_old_input($_POST);

            try {
                (new AuthService())->registerPatient($_POST);
                clear_old_input();
                Flash::success('Registration complete. Your QR code is ready.');
                $this->redirect('/patient/dashboard');
            } catch (InvalidArgumentException $exception) {
                Flash::error($exception->getMessage());
                $this->redirect('/register');
            } catch (Throwable $exception) {
                Flash::error('Registration failed: ' . $exception->getMessage());
                $this->redirect('/register');
            }
        }

        $this->render('auth/register', ['title' => 'Register']);
    }

    public function logout(): void
    {
        $this->validateCsrf();
        Auth::logout();
        Flash::info('You have been logged out.');
        $this->redirect('/login');
    }
}

