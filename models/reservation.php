<?php
use Illuminate\Database\Eloquent\Model;
 
class Reservation extends Model {    public $timestamps = false;
    protected $table = 'reservations';
    protected $fillable = ['id', 'user_id', 'room_id', 'date', 'start_time', 'end_time', 'status', 'total_price', 'payment_status', 'payment_method', 'card_last_digits'];
    
    public function user() {
        return $this->belongsTo('User', 'user_id');
    }
    
    public function room() {
        return $this->belongsTo('Room', 'room_id');
    }
}
