<?php
use Illuminate\Database\Eloquent\Model;
 
class Company extends Model{
    public $timestamps = false;
    protected $table = 'companies';
    protected $fillable = ['id', 'user_id', 'name', 'email', 'phone', 'address'];
    
    // Relación con el usuario: una compañía pertenece a un usuario
    public function user() {
        return $this->belongsTo('User', 'user_id');
    }
    
    // Relación con las salas: una compañía tiene muchas salas
    public function rooms() {
        return $this->hasMany('Room', 'company_id');
    }
}
