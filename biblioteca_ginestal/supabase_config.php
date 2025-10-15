<?php
declare(strict_types=1);

/**
 * Configuração do Supabase
 */
class SupabaseConfig {
    const SUPABASE_URL = 'https://ibkzqvfpkuuwquiyhrtt.supabase.co';
    const SUPABASE_ANON_KEY = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Imlia3pxdmZwa3V1d3F1aXlocnR0Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3NjAyMjUxMzMsImV4cCI6MjA3NTgwMTEzM30.4R2WVFWgwgdAlmg4jOb3k9oFRzyWsOeZ07BNRYkIV1g';
    const SUPABASE_DB_HOST = 'db.ibkzqvfpkuuwquiyhrtt.supabase.co';
    const SUPABASE_DB_PORT = '5432';
    const SUPABASE_DB_NAME = 'postgres';
    const SUPABASE_DB_USER = 'postgres';
    const SUPABASE_DB_PASSWORD = 'gmbiblioteca123'; // Senha configurada para o projeto
    const SUPABASE_SCHEMA = 'gm_biblioteca'; // Usar schema gm_biblioteca
    
    /**
     * Obter string de conexão PDO para Supabase - APENAS PostgreSQL
     */
    public static function getConnectionString(): string {
        // SEMPRE usar PostgreSQL Supabase - SEM fallback para SQLite
        if (!self::isPostgreSQLAvailable()) {
            throw new Exception('Extensão pdo_pgsql não está disponível. É necessário instalar o driver PostgreSQL para PHP.');
        }
        
        return 'pgsql:host=' . self::SUPABASE_DB_HOST . 
               ';port=' . self::SUPABASE_DB_PORT . 
               ';dbname=' . self::SUPABASE_DB_NAME . 
               ';user=' . self::SUPABASE_DB_USER . 
               ';password=' . self::SUPABASE_DB_PASSWORD;
    }
    
    /**
     * Verificar se PostgreSQL está disponível
     * FORÇANDO USO DA API REST APENAS
     */
    public static function isPostgreSQLAvailable(): bool {
        return false; // Sempre usar API REST
    }
    
    /**
     * Obter URL da API REST do Supabase
     */
    public static function getApiUrl(string $endpoint = ''): string {
        $baseUrl = self::SUPABASE_URL . '/rest/v1/';
        $endpoint = ltrim($endpoint, '/');
        
        if ($endpoint) {
            return $baseUrl . $endpoint;
        }
        
        return $baseUrl;
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
