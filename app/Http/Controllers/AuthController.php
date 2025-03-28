<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FolioService;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request, FolioService $folio)
    {
        $request->validate([
            'tenant' => 'required',
            'username' => 'required',
            'password' => 'required'
        ]);

        // Check if the username and password match the expected values
        if ($request->username !== 'diku_admin' || $request->password !== 'admin') {
            \Log::error('Login failed: Invalid credentials', [
                'provided_username' => $request->username,
            ]);
            return back()->with('error', 'Invalid username or password');
        }

        try {
            $token = $folio->authenticate(
                $request->tenant,
                $request->username,
                $request->password
            );

            // Regenerate session ID for security
            $request->session()->regenerate();
            
            // Store auth data
            session()->put([
                'folio_token' => $token,
                'folio_tenant' => $request->tenant,
                'username' => $request->username
            ]);

            // Debug output
            \Log::debug('Login successful', session()->all());

            // Force redirect to dashboard
            return redirect()->to(route('dashboard'));

        } catch (\Exception $e) {
            \Log::error('Login failed', ['error' => $e->getMessage()]);
            return back()->with('error', $e->getMessage());
        }
    }

    public function logout()
    {
        session()->forget(['folio_token', 'folio_tenant']);
        return redirect()->route('login');
    }
}