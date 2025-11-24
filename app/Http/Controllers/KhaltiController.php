<?php

namespace App\Http\Controllers;

use App\Khalti\Khalti;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Plan;
use App\Models\User;
use App\Models\Utility;
use Illuminate\Http\Request;

class KhaltiController extends Controller
{
    public function planPayWithKhalti(Request $request)
    {
        $payment_setting = Utility::getAdminPaymentSetting();

        config(
            [
                'khalti.public_key' => isset($payment_setting['khalti_public_key']) ? $payment_setting['khalti_public_key'] : '',
                'khalti.sck' => isset($payment_setting['khalti_secret_key']) ? $payment_setting['khalti_secret_key'] : '',
            ]
        );
        $currency = isset($payment_setting['currency']) ? $payment_setting['currency'] : 'USD';
        $planID    = \Illuminate\Support\Facades\Crypt::decrypt($request->plan_id);
        $plan      = Plan::find($planID);
        $authuser  = \Auth::user();
        $coupon_id = '';
        if($plan)
        {
            $price = $plan->price;
            if(isset($request->coupon) && !empty($request->coupon))
            {
                $request->coupon = trim($request->coupon);
                $coupons         = Coupon::where('code', strtoupper($request->coupon))->where('is_active', '1')->first();
                if(!empty($coupons))
                {
                    $usedCoupun             = $coupons->used_coupon();
                    $discount_value         = ($price / 100) * $coupons->discount;
                    $plan->discounted_price = $price - $discount_value;

                    if($usedCoupun >= $coupons->limit)
                    {
                        return redirect()->back()->with('error', __('This coupon code has expired.'));
                    }
                    $price     = $price - $discount_value;
                    $coupon_id = $coupons->id;
                }
                else
                {
                    return redirect()->back()->with('error', __('This coupon code is invalid or has expired.'));
                }
            }

            if($price <= 0)
            {
                $authuser->plan = $plan->id;
                $authuser->save();

                $assignPlan = $authuser->assignPlan($plan->id);

                if($assignPlan['is_success'] == true && !empty($plan))
                {

                    $orderID = strtoupper(str_replace('.', '', uniqid('', true)));
                    Order::create(
                        [
                            'order_id' => $orderID,
                            'name' => null,
                            'email' => null,
                            'card_number' => null,
                            'card_exp_month' => null,
                            'card_exp_year' => null,
                            'plan_name' => $plan->name,
                            'plan_id' => $plan->id,
                            'price' => $price == null ? 0 : $price,
                            'price_currency' => $currency,
                            'txn_id' => '',
                            'payment_type' => 'Razorpay',
                            'payment_status' => 'success',
                            'receipt' => null,
                            'user_id' => $authuser->id,
                        ]
                    );
                    $res['msg']  = __("Plan successfully upgraded.");
                    $res['flag'] = 2;

                    return $res;
                }                
                else
                {
                    return Utility::error_res(__('Plan fail to upgrade.'));
                }
                $secret = !empty($payment_setting['khalti_secret_key'])?$payment_setting['khalti_secret_key']:'';
                $amount = $price;
                return $amount;
            }
        }
    }

    public function planGetKhaltiStatus(Request $request)
    {
        $admin_settings = Utility::getAdminPaymentSetting();
        $currency = $admin_settings['currency'];

        $plan = Plan::find($plan_id);
        $user = \Auth::user();

        $payload = $request->payload;
        $secret = !empty($admin_settings['khalti_secret_key'])?$admin_settings['khalti_secret_key']:'';
        $token = $payload['token'];
        $amount = $payload['amount'];
        $khalti = new Khalti();
        
        $response = $khalti->verifyPayment($secret,$token,$amount);
        Utility::referralTransaction($plan);
        $orderID = strtoupper(str_replace('.', '', uniqid('', true)));

        $order = new Order();
        $order->order_id = $orderID;
        $order->name = $user->name;
        $order->card_number = '';
        $order->card_exp_month = '';
        $order->card_exp_year = '';
        $order->plan_name = $plan->name;
        $order->plan_id = $plan->id;
        $order->price = $request->amount;
        $order->price_currency = $currency;
        $order->txn_id = time();
        $order->payment_type = __('Tap');
        $order->payment_status = 'success';
        $order->txn_id = '';
        $order->receipt = '';
        $order->user_id = $user->id;
        $order->save();
        $user = User::find($user->id);

        $assignPlan = $user->assignPlan($plan->id);


        if ($assignPlan['is_success']) {
            return redirect()->route('plans.index')->with('success', __('Plan activated Successfully.'));
        } else {
            return redirect()->route('plans.index')->with('error', __($assignPlan['error']));
        }
    }
}
