<?php

namespace App\Http\Controllers\Backend\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\SigninUserRequest;
use App\Traits\HttpResponses;
use App\Http\Requests\SignupUserRequest;
// use App\Http\Resources\UserResource;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Session;

class AuthController extends Controller
{
    use HttpResponses;

    public function signin(SigninUserRequest $request)
    {
        $request->validated($request->all());
        $user = User::with('roleRlt.roles.permissions.permissionTitles')->where('email', $request->email)->first();
        // $user = UserResource::make($user);

        if (!Auth::attempt($request->only('email', 'password'))) {
            if (!$user) {
                return $this->error([
                    'status' => 'error',
                    'message' => 'Email not found!'
                ], 200);
            } else {
                return $this->error([
                    'status' => 'error',
                    'message' => 'Password incorrect!'
                ], 200);
            }
        }

        $session_timeout = config('session.lifetime');
        $nextLogoutDateTime = Carbon::now()->addMinutes($session_timeout)->format('Y-m-d H:i:s');
        $exprireOnBrowserClose = config('session.expire_on_close');
        $token = $user->createToken('API Token of ' . $user->name . ' ( ID: ' . $user->id . ' )')->plainTextToken;
        return $this->success([
            'status' => 'success',
            'user' => $user,
            'token' => $token,
            'nextLogoutDateTime' => $nextLogoutDateTime,
            'exprireOnBrowserClose' => $exprireOnBrowserClose,
        ]);
    }

    public function signup(SignupUserRequest $request)
    {
        $request->validated($request->all());
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password)
        ]);
        $user->sendEmailVerificationNotification();

        if (!$user) {
            return $this->error([
                'status' => 'error',
                'message' => 'User not created.'
            ], 200);
        } else {
            $session_timeout = config('session.lifetime');
            $nextLogoutDateTime = Carbon::now()->addMinutes($session_timeout)->format('Y-m-d H:i:s');
            $exprireOnBrowserClose = config('session.expire_on_close');
            $token = $user->createToken('API Token of ' . $user->name . ' ( ID: ' . $user->id . ' )')->plainTextToken;
            return $this->success([
                'status' => 'success',
                'user' => $user,
                'token' => $token,
                'nextLogoutDateTime' => $nextLogoutDateTime,
                'exprireOnBrowserClose' => $exprireOnBrowserClose,
            ]);
        }
    }

    public function logout()
    {
        Session::flush();
        Auth::logout();
        
        return $this->success([
            'status' => 'success',
            'message' => 'Logged out successfully.'
        ]);
    }

    public function verifyEmail(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'hash' => 'required|string'
        ]);

        $user = User::findOrFail($request->id);

        if ($user->hasVerifiedEmail()) {
            return $this->error([
                'status' => 'error',
                'message' => 'Email already verified.'
            ], 200);
        }

        if ($user->markEmailAsVerified()) {
            $message = 'Email verified successfully.';
            return redirect()->away(env('FRONTEND_URL') . '/auth')->with('success', $message);
        } else {
            $message = 'Email verification failed.';
            return redirect()->away(env('FRONTEND_URL') . '/auth/verify-email')->with('error', $message);
        }
    }
    
    public function resendVerificationEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return $this->error([
                'status' => 'error',
                'message' => 'User not found.'
            ], 200);
        }

        if ($user->hasVerifiedEmail()) {
            return $this->error([
                'status' => 'error',
                'message' => 'Email already verified.'
            ], 200);
        }

        $user->sendEmailVerificationNotification();

        return $this->success([
            'status' => 'success',
            'message' => 'Email verification link sent successfully.'
        ]);
    }
    
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status == Password::RESET_LINK_SENT) {
            return $this->success([
                'status' => 'success',
                'message' => 'Please check your email. Reset password link sent successfully.'
            ]);
        }

        if ($status == Password::RESET_THROTTLED) {
            $created_at = DB::table('password_reset_tokens')->where('email', $request->email)->first()->created_at;
            $created_at = Carbon::parse($created_at);
            $now = Carbon::now();
            $diff = $created_at->diffInSeconds($now);
            $diff = 60 - $diff;

            return $this->error([
                'status' => 'error',
                'message' => 'Reset password link already sent. Please try again after ' . $diff . ' seconds.'
            ], 200);
        }

        return $this->error([
            'status' => 'error',
            'message' => 'Reset password link not sent.'
        ], 200);
    }

    public function resetPassword(Request $request)
    {
        return redirect()->away(env('FRONTEND_URL') . '/auth/reset-password?token=' . $request->token . '&email=' . $request->email);
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed'
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password)
                ])->save();
            }
        );

        if ($status == Password::PASSWORD_RESET) {
            return $this->success([
                'status' => 'success',
                'message' => 'Password reset successfully.'
            ]);
        }

        return $this->error([
            'status' => 'error',
            'message' => 'Password reset failed.'
        ], 200);
    }

    public function resetPasswordProcess(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed'
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password)
                ])->save();
            }
        );

        if ($status == Password::PASSWORD_RESET) {
            return $this->success([
                'status' => 'success',
                'message' => 'Password reset successfully.'
            ]);
        }

        return $this->error([
            'status' => 'error',
            'message' => 'Password reset failed.'
        ], 400);
    }
}
