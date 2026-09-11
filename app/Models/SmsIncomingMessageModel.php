<?php

namespace App\Models;

use CodeIgniter\Model;

class SmsIncomingMessageModel extends Model
{
    protected $table            = 'sms_incoming_messages';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'sim',
        'from_number',
        'to_number',
        'content',
        'encrypted',
        'attachments',
        'received_timestamp',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
