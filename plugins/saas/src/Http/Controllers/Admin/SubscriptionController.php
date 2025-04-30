<?php

namespace Plugin\Saas\Http\Controllers\Admin;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Plugin\Saas\Models\CustomDomain;

class SubscriptionController extends Controller
{

    /**
     * Will clear system  cache
     */
    public function paymentHistory()
    {
        try {
            $payment_history = DB::table('tl_saas_payment_histories')
                ->join('tl_users', 'tl_users.id', '=', 'tl_saas_payment_histories.user_id')
                ->orderBy('tl_saas_payment_histories.id', 'desc')
                ->select([
                    'tl_users.name as subscriber',
                    'tl_saas_payment_histories.title',
                    'tl_saas_payment_histories.method',
                    'tl_saas_payment_histories.coupon_code',
                    'tl_saas_payment_histories.currency',
                    'tl_saas_payment_histories.discount_amount',
                    'tl_saas_payment_histories.final_amount',
                    'tl_saas_payment_histories.updated_at',
                    'tl_saas_payment_histories.pid',
                    'tl_saas_payment_histories.saas_account_id as store_id'
                ])->get();

            return view('plugin/saas::subscriber.payment_history', compact('payment_history'));
        } catch (Exception $ex) {
            toastNotification('error', translate('Unable to fetch payment history!'));
            return back();
        }
    }

    /**
     * Will show custom domain request list
     */
    public function customDomainRequest()
    {
        $domain_request = CustomDomain::all();
        return view('plugin/saas::subscriber.custom_domain_request', compact('domain_request'));
    }

    /**
     * Will request to delete custom domain
     */
    public function deleteCustomDomain(Request $request)
    {
        try {
            DB::beginTransaction();
            $custom_domain = CustomDomain::find((int)$request['id']);
            $status = $custom_domain->status;

            if ($status == 1) {
                DB::table('domains')->where('id', '=', $custom_domain->domain->id)->update([
                    'domain' => $custom_domain->domain->main_domain
                ]);
            }

            $custom_domain->delete();
            DB::commit();
            toastNotification('success', translate('Custom domain request deleted successfully!'));
            return back();
        } catch (Exception $ex) {
            DB::rollBack();
            toastNotification('error', translate('Unable to delete custom domain!'));
            return back();
        }
    }

    /**
     * Will request to update custom domain
     */
    public function updateCustomDomain(Request $request)
    {
        try {
            DB::beginTransaction();
            $custom_domain = CustomDomain::find((int)$request['id']);
            $requested_status = $request['domain_status'];
            $status = $custom_domain->status;

            if ($status == 1 && $requested_status == 2) {
                DB::table('domains')->where('id', '=', $custom_domain->domain->id)->update([
                    'domain' => $custom_domain->domain->main_domain
                ]);
            }

            if ($requested_status == 1) {
                DB::table('domains')->where('id', '=', $custom_domain->domain->id)->update([
                    'domain' => $custom_domain->requested_domain
                ]);

                DB::table('tl_saas_custom_domain')
                ->where('store_id','=',$custom_domain->saasAccount->id)
                ->where('id','!=',(int)$request['id'])
                ->update([
                    'status'=>2
                ]);
            }

            $custom_domain->status = $requested_status;

            if ($requested_status == 2) {
                $custom_domain->cancelled_date=Carbon::now()->toDateTimeString();
            }
            if ($requested_status == 1) {
                $custom_domain->approved_date=Carbon::now()->toDateTimeString();
            }

            $custom_domain->update();

            customDomainRequestStatusUpdate($custom_domain->saasAccount->id);

            DB::commit();
            toastNotification('success', translate('Custom domain request status updated!'));
            return back();
        } catch (Exception $ex) {
            DB::rollBack();
            toastNotification('error', translate('Unable to update status!'));
            return back();
        }
    }
}
