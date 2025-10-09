<?php
declare(strict_types=1);

/**
 * Configuração do Supabase
 */
class SupabaseConfig {
    const SUPABASE_URL = 'https://pfmhoslnnnagdpyjtvir.supabase.co';
    const SUPABASE_ANON_KEY = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InBmbWhvc2xubm5hZ2RweWp0dmlyIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTk4OTQ3NTYsImV4cCI6MjA3NTQ3MDc1Nn0.74ULSOqhuQAGVIbj7rctJ_kre_xA3FMLtF1fOqGM71s';
    const SUPABASE_DB_HOST = 'db.pfmhoslnnnagdpyjtvir.supabase.co';
    const SUPABASE_DB_PORT = '5432';
    const SUPABASE_DB_NAME = 'postgres';
    const SUPABASE_DB_USER = 'postgres';
    const SUPABASE_DB_PASSWORD = ''; // Será necessário configurar a senha do banco
    
    /**
     * Obter string de conexão PDO para Supabase
     */
    public static function getConnectionString(): string {
        return sprintf(
            'pgsql:host=%s;port=%s;dbname=%s;user=%s;password=%s',
            self::SUPABASE_DB_HOST,
            self::SUPABASE_DB_PORT,
            self::SUPABASE_DB_NAME,
            self::SUPABASE_DB_USER,
            self::SUPABASE_DB_PASSWORD
        );
    }
    
    /**
     * Obter configurações de cabeçalho para API REST do Supabase
     */
    public static function getApiHeaders(): array {
        return [
            'apikey: ' . self::SUPABASE_ANON_KEY,
            'Authorization: Bearer ' . self::SUPABASE_ANON_KEY,
            'Content-Type: application/json',
            'Prefer: return=minimal'
        ];
    }
}
?>
