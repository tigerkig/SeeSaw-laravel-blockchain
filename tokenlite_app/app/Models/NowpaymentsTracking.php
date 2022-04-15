<?php

namespace App\Models;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;

class NowpaymentsTracking extends Model
{
    protected $table = 'nowpayments_tracking';

    protected $fillable = [
        'tnx_id',
        'data'
    ];

    public static function log(string $type='callback', string $request = '', string $response = '', int $status_code = 0)
    {
        $save_data = [
            'id' => uniqid('', true),
            'type' => $type,
            'status_code' => $status_code,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'request' => $request,
            'response' => $response
        ];
        $decoded_response = json_decode($response, true);
        if (!empty($response) && !empty($decoded_response)) {
            if (!empty($decoded_response['order_id'])) {
                $save_data['tnx_id'] = $decoded_response['order_id'];
            }
        }
        NowpaymentsTracking::insertGetId($save_data);
        return $save_data['id'];
    }
}
