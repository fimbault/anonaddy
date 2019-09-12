<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FALaravel\Support\Authenticator;

class BackupCodeController extends Controller
{
    public function __construct()
    {
        $this->middleware('throttle:3,1')->only('login');
    }

    public function index(Request $request)
    {
        $authenticator = app(Authenticator::class)->boot($request);

        if ($authenticator->isAuthenticated() || ! $request->user()->two_factor_enabled) {
            return redirect('/');
        }

        return view('auth.backup_code');
    }

    public function login(Request $request)
    {
        $this->validate($request, [
            'backup_code' => 'required',
        ]);

        if (! Hash::check($request->backup_code, user()->two_factor_backup_code)) {
            return back()->withErrors([
                'backup_code' => __('The backup code was invalid.')
            ]);
        }

        $twoFactor = app('pragmarx.google2fa');

        user()->update([
            'two_factor_enabled' => false,
            'two_factor_secret' => $twoFactor->generateSecretKey(),
            'two_factor_backup_code' => null
        ]);

        if ($request->session()->has('intended_path')) {
            return redirect($request->session()->pull('intended_path'));
        }

        return redirect()->intended($request->redirectPath);
    }
}
