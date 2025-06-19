<?php

declare(strict_types=1);

namespace MarkusLehr\ClientGallerie\Infrastructure\Database\Migration;

use MarkusLehr\ClientGallerie\Infrastructure\Database\Migration\BaseMigration;

/**
 * Example migration: Add social media fields to clients table
 * 
 * Version: 1.1.0
 */
class Migration_001_AddSocialMediaToClients extends BaseMigration
{
    protected string $version = '1.1.0';
    protected string $description = 'Add social media fields to clients table';

    /**
     * Run the migration
     */
    public function up(): bool
    {
        $tableName = $this->wpdb->prefix . 'ml_clientgallerie_clients';

        // Add social media columns
        $success = true;
        
        $success &= $this->addColumn($tableName, 'facebook_url', 'VARCHAR(255) NULL');
        $success &= $this->addColumn($tableName, 'instagram_url', 'VARCHAR(255) NULL');
        $success &= $this->addColumn($tableName, 'twitter_url', 'VARCHAR(255) NULL');
        $success &= $this->addColumn($tableName, 'linkedin_url', 'VARCHAR(255) NULL');
        $success &= $this->addColumn($tableName, 'social_media_preferences', 'JSON NULL');

        if ($success) {
            $this->logMigration('up', 'Successfully added social media fields to clients table');
        }

        return $success;
    }

    /**
     * Rollback the migration
     */
    public function down(): bool
    {
        $tableName = $this->wpdb->prefix . 'ml_clientgallerie_clients';

        // Remove social media columns
        $success = true;
        
        $success &= $this->dropColumn($tableName, 'facebook_url');
        $success &= $this->dropColumn($tableName, 'instagram_url');
        $success &= $this->dropColumn($tableName, 'twitter_url');
        $success &= $this->dropColumn($tableName, 'linkedin_url');
        $success &= $this->dropColumn($tableName, 'social_media_preferences');

        if ($success) {
            $this->logMigration('down', 'Successfully removed social media fields from clients table');
        }

        return $success;
    }

    /**
     * Validate prerequisites
     */
    public function validatePrerequisites(): array
    {
        $tableName = $this->wpdb->prefix . 'ml_clientgallerie_clients';
        
        if (!$this->tableExists($tableName)) {
            return [
                'valid' => false,
                'messages' => ['Clients table does not exist']
            ];
        }

        return ['valid' => true, 'messages' => []];
    }

    /**
     * Get warnings for this migration
     */
    public function getWarnings(): array
    {
        return [
            'This migration adds social media fields to the clients table',
            'Existing client data will not be affected',
            'New fields will be NULL by default'
        ];
    }
}
