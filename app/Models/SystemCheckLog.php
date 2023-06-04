<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemCheckLog extends Model {
	protected $table = 'system_check_log';

	protected $primaryKey = 'id';

	protected $fillable = [
		'admin_id',
		'commiter_id',
		'status',
		'contents',
		'desc',
		'type',
		'created_at',
		'updated_at',
		'ip',
		'ipDesc',
	];

}