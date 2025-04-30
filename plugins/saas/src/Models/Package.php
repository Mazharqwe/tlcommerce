<?php

namespace Plugin\Saas\Models;

use Illuminate\Support\Facades\App;
use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    protected $table = "tl_saas_packages";

    public function translation($field = '', $lang = false)
    {
        $lang = $lang == false ? App::getLocale() : $lang;
        $package_translations = $this->package_translations->where('lang', $lang)->first();
        return $package_translations != null ? $package_translations->$field : $this->$field;
    }

    // A Blog Has Many Translations
    public function package_translations()
    {
        return $this->hasMany(TLSaasPackageTranslations::class, 'package_id');
    }
}
