<?php
use Illuminate\Database\Eloquent\Model;
 
class Room extends Model {
    public $timestamps = false;
    protected $table = 'rooms';
    protected $fillable = ['id', 'company_id', 'name', 'capacity', 'status', 'description', 'price'];
    
    public function company() {
        return $this->belongsTo('Company', 'company_id');
    }
    
    public function reservations() {
        return $this->hasMany('Reservation', 'room_id');
    }
}
