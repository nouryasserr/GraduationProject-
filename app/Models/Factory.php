<?php

namespace App\Models;

use App\Models\Scopes\SortScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Enums\Factory\Status;
use App\Models\Scopes\SearchScope;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\SoftDeletes;

class Factory extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, HasApiTokens, SoftDeletes;

    protected $table = 'factories';

    protected $fillable = [
        'name',
        'phone',
        'email',
        'password',
        'payment_methods',
        'payment_account',
        'status',
        'description',
    ];

    protected $hidden = [
        'password',
    ];
    protected $dates = ['deleted_at'];

    public function isFactory(): bool
    {
        return class_basename($this) == 'Factory';
    }

    public function deals()
    {
        return $this->belongsToMany(Request::class, 'deals')
            ->withPivot(['price', 'status', 'deal_date'])
            ->withTimestamps();
    }


    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     */
    public function getJWTCustomClaims(): array
    {
        return [];
    }
    protected static function booted(): void
    {
        static::addGlobalScope(new SearchScope);
        static::addGlobalScope(new SortScope);
    }

    public function requests()
    {
        return $this->hasMany(Request::class);
    }

    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }

    public function averageRating()
    {
        return $this->ratings()->avg('rate');
    }


    // in app\Models\Factory.php

public function paidDeals()
{
    // Deals that have been finally paid
    return $this->deals()->where('is_final_paid', true);
}

public function getNumberOfPaidOrdersAttribute()
{
    return $this->paidDeals()->count();
}

public function getAvgOrderValueAttribute()
{
    return $this->paidDeals()->avg('final_payment_amount') ?? 0;
}

public function getTotalRevenueAttribute()
{
    return $this->paidDeals()->sum('final_payment_amount');
}

}
