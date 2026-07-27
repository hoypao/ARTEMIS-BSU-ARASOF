<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * The `users` table is inherited from the legacy ARTEMIS PHP system and is
 * still shared with it, so it does not follow Laravel's conventions: the key
 * is `user_id`, the hash column is `password_hash`, and the name is split
 * across `first_name`/`last_name`.
 *
 * ARTEMIS does not use Eloquent or Laravel's auth guards — every controller
 * talks to PDO via getDB(), and sessions go through login_user()/current_user()
 * in app/Support/helpers.php. This model exists only so the provider reference
 * in config/auth.php resolves to something that matches the real schema, which
 * is created by database/migrations/2026_07_25_000000_create_artemis_schema.php.
 */
class User extends Authenticatable
{
    protected $table = 'users';

    protected $primaryKey = 'user_id';

    protected $fillable = [
        'role',
        'id_number',
        'qr_token',
        'email',
        'password_hash',
        'first_name',
        'last_name',
        'course',
        'college',
        'year_level',
        'contact_number',
        'status',
    ];

    protected $hidden = [
        'password_hash',
        'qr_token',
    ];

    /** The legacy column is `password_hash`, not Laravel's default `password`. */
    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }
}
