<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemAccountActionLog extends Model {
	protected $table = 'system_account_action_log';

	protected $primaryKey = 'id';

	protected $fillable = [
		'actionBeforeData',
		'actionAfterData',
		'action',
		'status',
		'accountId',
		'ip',
		'ipDesc',
	];

}