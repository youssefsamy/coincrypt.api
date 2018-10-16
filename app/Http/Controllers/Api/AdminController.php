<?php

namespace App\Http\Controllers\Api;

use App\Constants\MarketOrderType;
use App\Constants\TransactionType;
use App\Entities\AdminSetting;
use App\Entities\BitcashTransaction;
use App\Entities\BitcoinTransaction;
use App\Entities\BitgoldTransaction;
use App\Entities\CoinAddress;
use App\Entities\CoinWithdrawHistory;
use App\Entities\DigibyteTransaction;
use App\Entities\DogecoinTransaction;
use App\Entities\EthereumTransaction;
use App\Entities\HelpPage;
use App\Entities\LitecoinTransaction;
use App\Entities\MoneroTransaction;
use App\Entities\Profile;
use App\Entities\RippleTransaction;
use App\Entities\StellarTransaction;
use App\Entities\TLTransaction;
use App\Constants\TransactionStatus;
use App\Entities\ZcashTransaction;
use App\Libs\JsonRPCClient;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AdminController extends Controller
{

}
