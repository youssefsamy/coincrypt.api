<?php

namespace App\Http\Controllers\Api;

use App\Constants\MarketOrderType;
use App\Constants\TransactionStatus;
use App\Constants\TransactionType;
use App\Constants\VerifyStatus;
use App\Entities\BitcashTransaction;
use App\Entities\BitcoinTransaction;
use App\Entities\BitgoldTransaction;
use App\Entities\CoinAddress;
use App\Entities\DigibyteTransaction;
use App\Entities\DogecoinTransaction;
use App\Entities\EthereumTransaction;
use App\Entities\LitecoinTransaction;
use App\Entities\MoneroTransaction;
use App\Entities\Profile;
use App\Entities\RippleOrder;
use App\Entities\RippleTransaction;
use App\Entities\SMSCode;
use App\Entities\StellarTransaction;
use App\Entities\TLTransaction;
use App\Entities\ZcashTransaction;
use App\Libs\GoogleAuthenticator;
use App\Libs\Utility;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class AccountController extends Controller
{
    //
    public function getInfo() {
        $user = Auth::user();

        $profile = Profile::where('user_id', $user->id)->first();
        return response()->json([
            'id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'role' => $user->role,
            'profile' => $profile,
            'success' => true
        ]);
    }


    public function changePassword(Request $request) {
        $input = $request->input();

        $user = Auth::user();
        if (\Hash::check($input['old_password'], $user->password)) {
            $user->password = bcrypt($input['password']);
            $user->save();

            return response()->json(['success' => true]);
        } else {
            return response()->json(['success' => false, 'error' => 'Current password is wrong.']);
        }
    }

    public function saveProfile(Request $request) {
        $input =$request->input();

        $user = Auth::user();

        $profile = Profile::where('user_id', $user->id)->first();

        if (!$profile) {
            $profile = new Profile();
            $profile->user_id = $user->id;
        }

        $profile->firstname = $input['firstname'];
        $profile->lastname = $input['lastname'];
        $profile->phonenumber = $input['phonenumber'];
        $profile->city = $input['city'];
        $profile->address = $input['address'];
        $profile->postalcode = $input['postalcode'];
        $profile->verify_status = VerifyStatus::PENDING;

        $profile->save();

        return response()->json([
            'success' => true,
            'profile' => $profile
        ]);
    }
}
