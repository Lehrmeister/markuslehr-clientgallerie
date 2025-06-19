<?php

namespace MarkusLehr\ClientGallerie\Infrastructure\Database\Schema;

/**
 * Schema für Client-Tabelle mit erweiterten Berechtigungen
 * 
 * @package MarkusLehr\ClientGallerie\Infrastructure\Database\Schema
 * @author Markus Lehr
 * @since 1.0.0
 */
class ClientSchema extends BaseSchema 
{
    protected function getTableSuffix(): string 
    {
        return 'mlcg_clients';
    }
    
    protected function getCreateTableSQL(): string 
    {
        return "CREATE TABLE {$this->tableName} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            email varchar(255) NOT NULL,
            company varchar(255) DEFAULT NULL,
            phone varchar(50) DEFAULT NULL,
            website varchar(255) DEFAULT NULL,
            access_key varchar(64) NOT NULL,
            access_expires datetime DEFAULT NULL,
            password_hash varchar(255) DEFAULT NULL,
            two_factor_secret varchar(32) DEFAULT NULL,
            two_factor_enabled tinyint(1) DEFAULT 0,
            login_attempts int(11) DEFAULT 0,
            locked_until datetime DEFAULT NULL,
            permissions longtext DEFAULT NULL COMMENT 'JSON permissions matrix',
            settings longtext DEFAULT NULL COMMENT 'JSON client preferences',
            notification_preferences longtext DEFAULT NULL COMMENT 'JSON notification settings',
            timezone varchar(50) DEFAULT 'UTC',
            language varchar(10) DEFAULT 'en',
            avatar_path varchar(500) DEFAULT NULL,
            status enum('active','inactive','blocked','pending_verification') DEFAULT 'pending_verification',
            verification_token varchar(64) DEFAULT NULL,
            verification_expires datetime DEFAULT NULL,
            reset_token varchar(64) DEFAULT NULL,
            reset_expires datetime DEFAULT NULL,
            terms_accepted_at datetime DEFAULT NULL,
            privacy_accepted_at datetime DEFAULT NULL,
            marketing_consent tinyint(1) DEFAULT 0,
            last_login datetime DEFAULT NULL,
            last_login_ip varchar(45) DEFAULT NULL,
            last_activity datetime DEFAULT NULL,
            total_downloads bigint(20) unsigned DEFAULT 0,
            total_galleries_accessed bigint(20) unsigned DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_by bigint(20) unsigned DEFAULT NULL,
            updated_by bigint(20) unsigned DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY email (email),
            UNIQUE KEY access_key (access_key),
            KEY status (status),
            KEY access_expires (access_expires),
            KEY verification_token (verification_token),
            KEY reset_token (reset_token),
            KEY created_by (created_by),
            KEY updated_by (updated_by),
            KEY last_login (last_login),
            KEY company (company),
            FULLTEXT KEY search_content (name, email, company)
        ) {$this->charset};";
    }
    
    protected function afterCreate(): void 
    {
        // Trigger für automatische Access Key Generierung
        $this->createAccessKeyTrigger();
        
        // Views für verschiedene Client-States
        $this->createActiveClientsView();
        $this->createVerificationPendingView();
        $this->createSecurityAlertsView();
    }
    
    private function createAccessKeyTrigger(): void 
    {
        $sql = "
        CREATE TRIGGER IF NOT EXISTS {$this->tableName}_access_key_trigger 
        BEFORE INSERT ON {$this->tableName}
        FOR EACH ROW
        BEGIN
            IF NEW.access_key = '' OR NEW.access_key IS NULL THEN
                SET NEW.access_key = SHA2(CONCAT(NEW.email, NOW(), RAND()), 256);
            END IF;
        END;";
        
        $this->wpdb->query($sql);
    }
    
    private function createActiveClientsView(): void 
    {
        $viewName = $this->tableName . '_active';
        $sql = "
        CREATE OR REPLACE VIEW {$viewName} AS
        SELECT 
            id, name, email, company, phone, access_key,
            access_expires, timezone, language, last_login,
            total_downloads, total_galleries_accessed, created_at
        FROM {$this->tableName}
        WHERE status = 'active'
        AND (access_expires IS NULL OR access_expires > NOW())
        AND (locked_until IS NULL OR locked_until < NOW())
        ORDER BY last_activity DESC;";
        
        $this->wpdb->query($sql);
    }
    
    private function createVerificationPendingView(): void 
    {
        $viewName = $this->tableName . '_verification_pending';
        $sql = "
        CREATE OR REPLACE VIEW {$viewName} AS
        SELECT 
            id, name, email, verification_token, verification_expires, created_at
        FROM {$this->tableName}
        WHERE status = 'pending_verification'
        AND verification_token IS NOT NULL
        AND (verification_expires IS NULL OR verification_expires > NOW())
        ORDER BY created_at DESC;";
        
        $this->wpdb->query($sql);
    }
    
    private function createSecurityAlertsView(): void 
    {
        $viewName = $this->tableName . '_security_alerts';
        $sql = "
        CREATE OR REPLACE VIEW {$viewName} AS
        SELECT 
            id, name, email, login_attempts, locked_until,
            last_login, last_login_ip, status
        FROM {$this->tableName}
        WHERE (login_attempts >= 3 OR locked_until > NOW() OR status = 'blocked')
        ORDER BY 
            CASE 
                WHEN status = 'blocked' THEN 1
                WHEN locked_until > NOW() THEN 2
                ELSE 3
            END,
            login_attempts DESC;";
        
        $this->wpdb->query($sql);
    }
    
    public function validate(): array 
    {
        $issues = parent::validate();
        
        if ($this->exists()) {
            // Prüfe doppelte E-Mail-Adressen
            $duplicateEmails = $this->wpdb->get_var("
                SELECT COUNT(*) - COUNT(DISTINCT email) 
                FROM {$this->tableName}
                WHERE status != 'inactive'
            ");
            
            if ($duplicateEmails > 0) {
                $issues[] = "Duplicate email addresses found in clients table";
            }
            
            // Prüfe abgelaufene Verifikations-Token
            $expiredTokens = $this->wpdb->get_var("
                SELECT COUNT(*) FROM {$this->tableName}
                WHERE status = 'pending_verification'
                AND verification_expires < NOW()
            ");
            
            if ($expiredTokens > 0) {
                $issues[] = "$expiredTokens clients with expired verification tokens";
            }
            
            // Prüfe schwache Access Keys
            $weakKeys = $this->wpdb->get_var("
                SELECT COUNT(*) FROM {$this->tableName}
                WHERE LENGTH(access_key) < 32
            ");
            
            if ($weakKeys > 0) {
                $issues[] = "$weakKeys clients with weak access keys found";
            }
        }
        
        return $issues;
    }
}
