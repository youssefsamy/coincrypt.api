<?php

namespace App\Http\Controllers\Api;

use App\Entities\Profile;
use App\Entities\ResetPassword;
use App\Events\SendEmailEvent;
use App\Mail\ForgotPasswordMail;
use App\Mail\VerifyAccountMail;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserController extends Controller
{
    //
    public function register(Request $request) {
        $input = $request->input();

        if (User::where('email', $input['email'])->count() > 0) {
            return response()->json(['success' => false, 'error' => 'Email exist']);
        }

//        if (User::where('name', $input['name'])->count() > 0) {
//            return response()->json(['success' => false, 'error' => 'Username exist']);
//        }

        $user = new User();

        $confirmation_code = str_random(30);

        $user->email = $input['email'];
        $user->password = bcrypt($input['password']);
        $user->confirmed = 0;
        $user->confirmation_code = $confirmation_code;
        $user->role = 'user';
        // $user->ref_id = isset($input['ref_id']) ? $input['ref_id'] : null;
        $user->save();

        $profile = new Profile();
        $profile->user_id = $user->id;
        $profile->country = $input['country'];
        $profile->verify_status = 0;
        $profile->save();

        event(new SendEmailEvent($user, new VerifyAccountMail($user)));

        return response()->json(['success' => true, 'user_id' => $user->id]);
    }

    public function login(Request $request) {
        $credentials = $request->only('email', 'password');

        $user = User::where('email', $credentials['email'])->first();

        if (!$user) {
            return response()->json(['error' => 'Email is wrong', 'success' => false]);
        }

        if ($user->confirmed == 0 || $user->confirmed == null) {
            return response()->json(['error' => 'Please check your email and click the confirmation link before signing in.', 'success' => false]);
        }

        try {
            // verify the credentials and create a token for the user
            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'Password is wrong', 'success' => false]);
            }
        } catch (JWTException $e) {
            // something went wrong
            return response()->json(['error' => 'token error'], 500);
        }

        $user = Auth::user();

        $profile = Profile::where('user_id', $user->id)->first();
        return response()->json([
            'success' => true,
            'id' => $user->id,
            'email' => $user->email,
            'role' => $user->role,
            'token' => $token,
            'profile' => $profile
        ]);
    }

    public function verifyAccount($confirmation_code, Request $request) {
        if (!$confirmation_code) {
            return response('Confirmation code is wrong');
        }

        $user = User::where('confirmation_code', $confirmation_code)->first();

        if (!$user) {
            return response('Confirmation code is wrong');
        }

        $user->confirmation_code = '';
        $user->confirmed = 1;

        $user->save();

        return redirect(env('CLIENT_URL').'/confirm');
    }

    public function sendActivateEmail(Request $request) {
        $input = $request->input();

        $email = $input['email'];

        $user = User::where('email', $email)->first();

        if (!$user) {
            return response()->json(['success' => false, 'error' => 'Wrong Email']);
        }

        if ($user->confirmed == 1) {
            return response()->json(['success' => false, 'error' => 'Your email was already confirmed.']);
        }

        if (empty($user->confirmation_code)) {
            $confirmation_code = str_random(30);
            $user->confirmation_code = $confirmation_code;
            $user->save();
        }

        event(new SendEmailEvent($user, new VerifyAccountMail($user)));

        return response()->json(['success' => true]);
    }

    public function sendForgotEmail(Request $request) {
        $input = $request->input();

        $email = $input['email'];

        $user = User::where('email', $email)->first();

        if (!$user) {
            return response()->json(['success' => false, 'error' => 'Email does not exist']);
        }

        $resetPassword = ResetPassword::where('user_id', $user->id)->first();
        if (!$resetPassword) {
            $resetPassword = new ResetPassword();
            $resetPassword->user_id = $user->id;
        }

        $resetPassword->confirm_code = str_random(50);
        $resetPassword->validate_time = Carbon::now()->addMinute(15);

        $resetPassword->save();

        event(new SendEmailEvent($user, new ForgotPasswordMail($user, $resetPassword->confirm_code)));

        return response()->json(['success' => true]);
    }

    public function resetPassword(Request $request) {
        $input = $request->input();

        $user = User::find($input['user_id']);
        if(!$user) {
            return response()->json(['success' => false, 'error' => 'User Not Exist.']);
        }

        $user->password = bcrypt($input['password']);
        $user->save();

        return response()->json(['success' => true]);
    }

    public function confirmResetCode(Request $request) {
        $input = $request->input();

        $code = $input['code'];

        if (!$code) {
            return response()->json(['success' => false]);
        }

        $resetPassword = ResetPassword::where([['confirm_code', $code], ['validate_time', '>=', Carbon::now()]])->first();

        if (!$resetPassword) {
            return response()->json(['success' => false]);
        }

        $user_id = $resetPassword->user_id;
        $resetPassword->delete();

        return response()->json(['success' => true, 'user_id' => $user_id]);
    }
}
