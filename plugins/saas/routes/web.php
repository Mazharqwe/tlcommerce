<?php

use Illuminate\Support\Facades\Route;
use Plugin\Saas\Http\Controllers\Admin\StoreController;
use Plugin\Saas\Http\Controllers\Admin\CouponController;
use Plugin\Saas\Http\Controllers\Admin\SystemController;
use Plugin\Saas\Http\Controllers\Admin\PackageController;
use Plugin\Saas\Http\Controllers\Admin\CurrencyController;
use Plugin\Saas\Http\Controllers\Admin\CustomerController;
use Plugin\Saas\Http\Controllers\Admin\DashboardController;
use Plugin\Saas\Http\Controllers\Payment\PaymentController;
use Plugin\Saas\Http\Controllers\Admin\SubscriptionController;

Route::group(['prefix' => getAdminPrefix(), 'middleware' => ['handle.expired.account']], function () {
    Route::group(['prefix' => '/'.getSaasPrefix().''], function () {

        //get packages of redeem coupon
        Route::post('/get-packages-according-to-redeemable-coupon', [CouponController::class, 'getPackagesAccordingToRedeemableCoupon'])->name('plugin.saas.get.packages.according.to.redeemable.coupon');

        //get plans according to package
        Route::post('/get-plans-according-to-package', [PackageController::class, 'getPlansAccordingToPackage'])->name('plugin.saas.get.plans.according.to.package');

        $applied_middleware = ['auth', 'admin.subscriber.separation:1', 'is-saas'];

        Route::group(['middleware' => $applied_middleware], function () {
            //Packages
            Route::get('/create-package', [PackageController::class, 'createPackage'])->name('plugin.saas.create.package')->middleware('can:Manage Packages');
            Route::post('/store-package', [PackageController::class, 'storePackage'])->name('plugin.saas.store.package');
            Route::get('/packages', [PackageController::class, 'packages'])->name('plugin.saas.packages')->middleware('can:Manage Packages');
            Route::post('/get-packages-according-to-plan', [PackageController::class, 'getPackageAccordingToPlan'])
                ->name('plugin.saas.get.packages.according.to.plan')
                ->withoutMiddleware('admin.subscriber.separation:1');

            Route::get('/edit-package/{id}', [PackageController::class, 'editPackage'])->name('plugin.saas.edit.package')->middleware('can:Manage Packages');
            Route::post('/update-package', [PackageController::class, 'updatePackage'])->name('plugin.saas.update.package');
            Route::post('/delete-package', [PackageController::class, 'deletePackage'])->name('plugin.saas.delete.package');

            //Package plannings
            Route::get('/package-plans', [PackageController::class, 'packagePlans'])->name('plugin.saas.package.plans')->middleware('can:Manage Packages');
            Route::post('/store-package-plan', [PackageController::class, 'storePackagePlans'])->name('plugin.saas.store.package.plan');
            Route::post('/update-package-plan', [PackageController::class, 'updatePackagePlan'])->name('plugin.saas.update.package.plan');
            Route::post('/delete-package-plan', [PackageController::class, 'deletePackagePlan'])->name('plugin.saas.delete.package.plan');

            //Coupons Controlling
            Route::get('/create-coupons', [CouponController::class, 'createCoupons'])->name('plugin.saas.create.coupons')->middleware('can:Manage Coupons');
            Route::post('/store-coupons', [CouponController::class, 'storeCoupons'])->name('plugin.saas.store.coupons');
            Route::get('/coupons', [CouponController::class, 'coupons'])->name('plugin.saas.coupons')->middleware('can:Manage Coupons');
            Route::get('/edit-coupon/{id}', [CouponController::class, 'editCoupon'])->name('plugin.saas.edit.coupon')->middleware('can:Manage Coupons');
            Route::post('/update-coupon', [CouponController::class, 'updateCoupon'])->name('plugin.saas.update.coupon');
            Route::post('/delete-coupon', [CouponController::class, 'deleteCoupon'])->name('plugin.saas.delete.coupon');

            //Payment methods configurations
            Route::get('/payment-methods', [PaymentController::class, 'paymentMethods'])->name('plugin.saas.payments.methods')->middleware('can:Manage Payments');
            Route::post('/change-payment-method-status', [PaymentController::class, 'changePaymentMethodStatus'])->name('plugin.saas.payments.methods.status.update');
            Route::post('/update-payment-method-credential', [PaymentController::class, 'updatePaymentMethodCredential'])->name('plugin.saas.payments.methods.credential.update');

            //Currency Settings
            Route::get('/add-currency', [CurrencyController::class, 'addCurrency'])->name('plugin.saas.add.currency')->middleware('can:Manage SAAS Settings');
            Route::post('/add-currency', [CurrencyController::class, 'storeCurrency'])->name('plugin.saas.store.currency');
            Route::get('/all-currencies', [CurrencyController::class, 'allCurrencies'])->name('plugin.saas.all.currencies')->middleware('can:Manage SAAS Settings');
            Route::post('/update-currency-status', [CurrencyController::class, 'updateCurrencyStatus'])->name('plugin.saas.update.currency.status');
            Route::get('/edit-currency/{id}', [CurrencyController::class, 'editCurrency'])->name('plugin.saas.edit.currency')->middleware('can:Manage SAAS Settings');
            Route::post('/update-currency', [CurrencyController::class, 'updateCurrency'])->name('plugin.saas.update.currency');
            Route::post('/delete-currency', [CurrencyController::class, 'deleteCurrency'])->name('plugin.saas.currency.delete');

            //Saas General Settings
            Route::get('/general-settings', [SystemController::class, 'generalSettings'])->name('plugin.saas.general.settings')->middleware('can:Manage SAAS Settings');
            Route::post('/store-general-settings', [SystemController::class, 'storeGeneralSettings'])->name('plugin.saas.admin.store.general.settings');
            Route::get('/notification-settings', [SystemController::class, 'saasNotificationSettings'])->name('plugin.saas.notification.settings')->middleware('can:Manage SAAS Settings');
            Route::post('/notification-settings', [SystemController::class, 'storeSaasNotificationSettings'])->name('plugin.saas.admin.notification.settings');

            //subscription
            Route::post('/store-delete', [StoreController::class, 'storeDelete'])->name('plugin.saas.store.delete');
            Route::get('/all-stores', [StoreController::class, 'index'])->name('plugin.saas.all.stores')->middleware('can:Manage Subscriptions');
            Route::get('/store-details/{store_id}', [StoreController::class, 'storeDetails'])->name('plugin.saas.store.details')->middleware('can:Manage Subscriptions');
            Route::get('/print-subscription-payment-invoice/{store_id}', [StoreController::class, 'printInvoice'])->name('plugin.saas.admin.print.subscription.payment.invoice')->middleware('can:Manage Subscriptions');
            Route::post('/update-store-status', [StoreController::class, 'updateStoreStatus'])->name('plugin.saas.update.store.status');

            Route::post('/create-tenant', [StoreController::class, 'createTenantDatabase'])->name('plugin.saas.create.tenant');
            Route::post('/update-tenant', [StoreController::class, 'updateTenantDatabase'])->name('plugin.saas.update.tenant');
            Route::post('/update-tenant-plugin', [StoreController::class, 'updateTenantPluginDatabase'])->name('plugin.saas.update.tenant.plugin');
            Route::post('/update-tenant-system', [StoreController::class, 'updateTenantCoreDatabase'])->name('plugin.saas.update.tenant.system');

            Route::get('/all-customers', [CustomerController::class, 'index'])->name('plugin.saas.all.customers')->middleware('can:Manage Subscriptions');
            Route::get('/edit-customer-profile/{id}', [CustomerController::class, 'editCustomerProfile'])->name('plugin.saas.edit.customer.profile')->middleware('can:Manage Subscriptions');
            Route::post('/update-customer-profile', [CustomerController::class, 'updateCustomerProfile'])->name('plugin.saas.update.customer.profile');
            Route::get('/customer-details/{id}', [CustomerController::class, 'customerDetails'])->name('plugin.saas.customer.details')->middleware('can:Manage Subscriptions');
            Route::post('/customer-delete', [CustomerController::class, 'customerDelete'])->name('plugin.saas.customer.delete');
            Route::post('/assign-store', [CustomerController::class, 'assignStore'])->name('plugin.saas.assign.store');
            Route::post('/update-store-plan', [CustomerController::class, 'updateStorePlan'])->name('plugin.saas.update.store.plan');

            Route::post('/sales-chart-report', [DashboardController::class, 'salesChartReport'])->name('plugin.saas.dash.sales.chart');

            //Transaction History
            Route::get('/payment-history', [SubscriptionController::class, 'paymentHistory'])->name('plugin.saas.admin.payment.history')->middleware('can:Manage Subscriptions');


            //Custom domain request
            Route::get('/custom-domain-request', [SubscriptionController::class, 'customDomainRequest'])->name('plugin.saas.admin.custom.domain.request')->middleware('can:Manage Subscriptions');
            Route::post('/delete-custom-domain', [SubscriptionController::class, 'deleteCustomDomain'])->name('plugin.saas.admin.delete.custom.domain');
            Route::post('/update-custom-domain', [SubscriptionController::class, 'updateCustomDomain'])->name('plugin.saas.admin.update.custom.domain');
        });

        Route::get('/clear-system-cache', [SystemController::class, 'clearSystemCache'])->name('saas.admin.clear.system.cache');
    });
});
