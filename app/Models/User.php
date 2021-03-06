<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use DB;

class User extends Model implements AuthenticatableContract,
                                    AuthorizableContract,
                                    CanResetPasswordContract
{
    use Authenticatable, Authorizable, CanResetPassword, SoftDeletes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['username', 'email', 'password','role_id'];

    /**
     * The attributes visible from the model's JSON form.
     *
     * @var array
     */
	protected $visible = ['id', 'username', 'score', 'money','level','next_level','email_frequency_id'];
	
	/**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['next_level'];
	/**
	 * One to One relation
	 *
	 * @return Illuminate\Database\Eloquent\Relations\belongsToMany
	 */
	public function friends()
	{
		return $this->hasMany('App\Models\Friend');
	}
	
	/**
	 * One to One relation
	 *
	 * @return Illuminate\Database\Eloquent\Relations\hasOne
	 */
	public function role() 
	{
		return $this->belongsTo('App\Models\Role');
	}

	/**
	 * One to One relation
	 *
	 * @return Illuminate\Database\Eloquent\Relations\hasOne
	 */
	public function level()
	{
		return $this->belongsTo('App\Models\Level');
	}

	/**
	 * Many to Many relation
	 *
	 * @return Illuminate\Database\Eloquent\Relations\belongToMany
	 */
	public function bonuses()
	{
		return $this->belongsToMany('App\Models\Bonus');
	}
	
	/**
	 * Many to Many relation
	 *
	 * @return Illuminate\Database\Eloquent\Relations\belongToMany
	 */
	public function objects()
	{
		return $this->belongsToMany('App\Models\Object');
	}

    public function getNextLevelAttribute()
    {
        return $this->level->getNext();
    }

	/**
	 * Many to Many relation
	 *
	 * @return Illuminate\Database\Eloquent\Relations\belongToMany
	 */
	public function inventaire()
	{
		$user_id = $this->id;
		return Object::leftJoin('object_user',function($join) use ($user_id) {
				$join->on('object_user.object_id','=','objects.id')
					->on('object_user.user_id','=',DB::raw($user_id));
					})
					->join('constant_games','key','=',DB::raw('"multiplier-price-ingame"'))
					->select('objects.*','user_id','object_user.id as object_user_id','seen','help_seen')
					->addSelect(DB::raw('ifnull(price*value,0) as price_ingame'))
					->addSelect(DB::raw('ifnull(quantity,0) as quantity'));
	}
				
	/**
	 * Many to Many relation
	 *
	 * @return Illuminate\Database\Eloquent\Relations\belongToMany
	 */
	public function trophies()
	{
		return $this->belongsToMany('App\Models\Trophy');
	}

	/**
	 * Many to Many relation
	 *
	 * @return Illuminate\Database\Eloquent\Relations\belongToMany
	 */
	public function duels()
	{
		return $this->belongsToMany('App\Models\Duel');
	}

	/**
	 * One to Many relation
	 *
	 * @return Illuminate\Database\Eloquent\Relations\hasMany
	 */
	public function annotation_user()
	{
		return $this->hasMany('App\Models\AnnotationUser');
	}

	/**
	 * One to Many relation
	 *
	 * @return Illuminate\Database\Eloquent\Relations\hasMany
	 */
	public function tutorials()
	{
		return $this->hasMany('App\Models\Tutorial');
	}

	/**
	 * One to Many relation
	 *
	 * @return Illuminate\Database\Eloquent\Relations\hasMany
	 */
	public function stats()
	{
		return $this->hasMany('App\Models\Stat');
	}

	/**
	 * One to Many relation
	 *
	 * @return Illuminate\Database\Eloquent\Relations\hasMany
	 */
	public function stat($relation_id)
	{
		return $this->stats()->firstOrCreate(array('relation_id'=>$relation_id,'user_id'=>$this->id));
	}

	/**
	 * Check admin role
	 *
	 * @return bool
	 */
	public function isAdmin()
	{
		return $this->role->slug == 'admin';
	}

	/**
	 *
	 * @return User
	 */
	public static function getAdmin()
	{
		return User::where('username','admin')->first();
	}

	/**
	 * Check not user role
	 *
	 * @return bool
	 */
	public function isUser()
	{
		return $this->role->slug == 'user'||$this->role->slug == 'admin';
	}

	/**
	 * Check not user role
	 *
	 * @return bool
	 */
	public function isGuest()
	{
		return $this->role->slug != 'user' && $this->role->slug == 'admin';
	}
	
	public function hasTrophy($trophy)
	{
		return $this->trophies->contains('id', $trophy->id);
	}
	
	public function hasFriend($user)
	{
		return $this->friends->contains('friend_id', $user->id);
	}
	
	public function getAcceptedFriends()
	{
		return $this->friends()->where('accepted', 1)->with('friend')->get();
	}

	public function getListFriends()
	{
		$enemies = $this->friends()->where('accepted', 1)->with('friend')->get()->lists('friend_id');
		return $this->select(DB::raw('concat(username," - '.trans('game.level').' ",level_id) as username'),'id')->whereIn('id',$enemies)->lists('username','id');
	}

	public function getPendingFriendRequests()
	{
		return $this->friends()->where('accepted', 0)->get();
	}	

	public function getAskFriendRequests()
	{
		return Friend::where('accepted', 0)->where('friend_id',$this->id)->get();
	}	

	public function getListAcceptedFriends()
	{
		return $this->friends()->where('accepted', 1)->lists('friend_id')->map(function($id)
		{
			return intval($id);
		});
	}	

	public function getListPendingFriendRequests()
	{
		return $this->friends()->where('accepted', 0)->lists('friend_id')->map(function($id)
		{
			return intval($id);
		});
	}	

	public function getListAskFriendRequests()
	{
		return Friend::where('accepted', 0)->where('friend_id',$this->id)->lists('user_id')->map(function($id)
		{
			return intval($id);
		});
	}

	public function getRememberToken()
	{
		return null; // not supported
	}

	public function setRememberToken($value)
	{
		// not supported
	}

	public function getRememberTokenName()
	{
		return "remember_token"; // not supported
	}

	/**
	* Overrides the method to ignore the remember token.
	*/
	public function setAttribute($key, $value)
	{
		$isRememberTokenAttribute = ($key == $this->getRememberTokenName());
		if (!$isRememberTokenAttribute)
		{
		  parent::setAttribute($key, $value);
		}
	}
}