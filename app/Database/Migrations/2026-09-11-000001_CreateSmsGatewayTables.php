<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSmsGatewayTables extends Migration
{
    public function up()
    {
        // 1. Table: sms_gateways
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'device_id' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'unique'     => true,
            ],
            'device_name' => [
                'type'       => 'VARCHAR',
                'constraint' => '150',
            ],
            'token_hash' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => '20',
                'default'    => 'OFFLINE', // ONLINE, OFFLINE, DISABLED
            ],
            'sim_operator' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => true,
            ],
            'sim_slot' => [
                'type'       => 'INT',
                'constraint' => 5,
                'default'    => 1,
            ],
            'phone_number' => [
                'type'       => 'VARCHAR',
                'constraint' => '30',
                'null'       => true,
            ],
            'battery_level' => [
                'type'       => 'INT',
                'constraint' => 5,
                'null'       => true,
            ],
            'signal_strength' => [
                'type'       => 'INT',
                'constraint' => 5,
                'null'       => true,
            ],
            'is_charging' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'app_version' => [
                'type'       => 'VARCHAR',
                'constraint' => '30',
                'null'       => true,
            ],
            'rate_limit_per_minute' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 30,
            ],
            'rate_limit_per_hour' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 500,
            ],
            'rate_limit_per_day' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 5000,
            ],
            'last_seen_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('status');
        $this->forge->createTable('sms_gateways', true);

        // 2. Table: sms_pairing_codes
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'code' => [
                'type'       => 'VARCHAR',
                'constraint' => '32',
                'unique'     => true,
            ],
            'device_name' => [
                'type'       => 'VARCHAR',
                'constraint' => '150',
                'null'       => true,
            ],
            'expires_at' => [
                'type' => 'DATETIME',
            ],
            'is_used' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'used_by_device_id' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => true,
            ],
            'used_at' => [
                'type' => 'DATETIME',
                'null' => true,
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
        $this->forge->addKey(['is_used', 'expires_at']);
        $this->forge->createTable('sms_pairing_codes', true);

        // 3. Table: sms_jobs
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'job_id' => [
                'type'       => 'VARCHAR',
                'constraint' => '64',
                'unique'     => true,
            ],
            'client_message_id' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => true,
            ],
            'recipient' => [
                'type'       => 'VARCHAR',
                'constraint' => '30',
            ],
            'message' => [
                'type' => 'TEXT',
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => '30',
                'default'    => 'PENDING', // PENDING, CLAIMED, SENDING, SENT, DELIVERED, FAILED, RETRY, FAILED_PERMANENT
            ],
            'priority' => [
                'type'       => 'INT',
                'constraint' => 5,
                'default'    => 2, // 1 = High, 2 = Normal, 3 = Low
            ],
            'attempt' => [
                'type'       => 'INT',
                'constraint' => 5,
                'default'    => 0,
            ],
            'max_attempt' => [
                'type'       => 'INT',
                'constraint' => 5,
                'default'    => 3,
            ],
            'assigned_device_id' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => true,
            ],
            'available_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'claimed_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'claim_expires_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'sent_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'delivered_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'failed_reason' => [
                'type' => 'TEXT',
                'null' => true,
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
        $this->forge->addKey('client_message_id');
        $this->forge->addKey(['status', 'priority', 'available_at']);
        $this->forge->addKey(['assigned_device_id', 'status']);
        $this->forge->createTable('sms_jobs', true);

        // 4. Table: sms_delivery_reports
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'job_id' => [
                'type'       => 'VARCHAR',
                'constraint' => '64',
            ],
            'device_id' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => true,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => '30',
            ],
            'operator_status_code' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'null'       => true,
            ],
            'operator_status_message' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
            ],
            'raw_payload' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'reported_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('job_id');
        $this->forge->createTable('sms_delivery_reports', true);

        // 5. Table: sms_audit_logs
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'actor_type' => [
                'type'       => 'VARCHAR',
                'constraint' => '30', // ADMIN, GATEWAY, SYSTEM, API_CLIENT
            ],
            'actor_id' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => true,
            ],
            'action' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
            ],
            'target' => [
                'type'       => 'VARCHAR',
                'constraint' => '150',
                'null'       => true,
            ],
            'ip_address' => [
                'type'       => 'VARCHAR',
                'constraint' => '45',
                'null'       => true,
            ],
            'metadata' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['actor_type', 'created_at']);
        $this->forge->createTable('sms_audit_logs', true);
    }

    public function down()
    {
        $this->forge->dropTable('sms_audit_logs', true);
        $this->forge->dropTable('sms_delivery_reports', true);
        $this->forge->dropTable('sms_jobs', true);
        $this->forge->dropTable('sms_pairing_codes', true);
        $this->forge->dropTable('sms_gateways', true);
    }
}
