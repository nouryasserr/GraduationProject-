<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'total_price',
        'status',
        'address_id',
        'second_phone'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function orderItems()
    {
        return $this->hasMany(Order_item::class);
    }
    
    // public function items()
    // {
    //     return $this->hasMany(OrderItem::class);
    // }
    public function address()
{
    return $this->belongsTo(Address::class);
}

}
