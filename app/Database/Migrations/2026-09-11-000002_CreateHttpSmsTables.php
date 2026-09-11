<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateHttpSmsTables extends Migration
{
    public function up()
    {
        // 1. Table: sms_phone_lines (Mapping fcm_token + phone_number + sim)
        $this->forge->addField([
            'id' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
            ],
            'user_id' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'default'    => 'usr_default_admin',
            ],
            'fcm_token' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'phone_number' => [
                'type'       => 'VARCHAR',
                'constraint' => '30',
            ],
            'sim' => [
                'type'       => 'VARCHAR',
                'constraint' => '10',
                'default'    => 'SIM1', // SIM1 or SIM2
            ],
            'device_id' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => true,
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('phone_number');
        $this->forge->createTable('sms_phone_lines', true);

        // 2. Table: sms_incoming_messages (Inbox received from Android)
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'sim' => [
                'type'       => 'VARCHAR',
                'constraint' => '10',
                'default'    => 'SIM1',
            ],
            'from_number' => [
                'type'       => 'VARCHAR',
                'constraint' => '30',
            ],
            'to_number' => [
                'type'       => 'VARCHAR',
                'constraint' => '30',
            ],
            'content' => [
                'type' => 'TEXT',
            ],
            'encrypted' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'attachments' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'received_timestamp' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('sms_incoming_messages', true);
    }

    public function down()
    {
        $this->forge->dropTable('sms_phone_lines', true);
        $this->forge->dropTable('sms_incoming_messages', true);
    }
}
