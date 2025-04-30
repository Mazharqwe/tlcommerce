<?php

namespace Plugin\Saas\Models;

use Core\Models\User;
use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Models\Domain;

class SaasAccount extends Model
{
    protected $table = "tl_saas_accounts";

    /**
     * One to one relation with domain
     */
    public function domain()
    {
        return $this->hasOne(Domain::class, 'tenant_id', 'tenant_id');
    }

    /**
     * One to one relation with custom domain
     */
    public function customDomain()
    {
        return $this->hasOne(CustomDomain::class, 'store_id');
    }

    /**
     * check if store has pending domain request
     */
    public function hasPendingDomainRequest()
    {
        return $this->customDomain()->where('status', 0)->exists(); // return true if exists and false otherwise
    }

    /**
     * One to one relation with users
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
