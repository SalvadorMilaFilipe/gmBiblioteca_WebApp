<?php
declare(strict_types=1);

/**
 * Cliente REST para Supabase (alternativa quando PostgreSQL não está disponível)
 */
class SupabaseRestClient {
    private string $baseUrl;
    private array $headers;
    
    public function __construct() {
        // Usar schema public com prefixo gm_
        $this->baseUrl = SupabaseConfig::SUPABASE_URL . '/rest/v1/';
        $this->headers = [
            'apikey: ' . SupabaseConfig::SUPABASE_ANON_KEY,
            'Authorization: Bearer ' . SupabaseConfig::SUPABASE_ANON_KEY,
            'Content-Type: application/json',
            'Prefer: return=minimal'
        ];
    }
    
    /**
     * Executar consulta SELECT
     */
    public function select(string $table, array $columns = ['*'], array $filters = [], string $orderBy = ''): array {
        $url = $this->baseUrl . $table . '?select=' . implode(',', $columns);
        
        if (!empty($filters)) {
            foreach ($filters as $key => $value) {
                if ($value !== null) {
                    $url .= '&' . $key . '=eq.' . urlencode((string)$value);
                }
            }
        }
        
        if ($orderBy) {
            // Converter formato DESC para sintaxe do Supabase
            $orderBy = str_replace(' DESC', '.desc', $orderBy);
            $orderBy = str_replace(' ASC', '.asc', $orderBy);
            $url .= '&order=' . urlencode($orderBy);
        }
        
        $response = $this->makeRequest('GET', $url);
        return json_decode($response, true) ?? [];
    }
    
    /**
     * Inserir dados
     */
    public function insert(string $table, array $data): bool {
        $url = $this->baseUrl . $table;
        
        // Limpar e validar dados
        $cleanData = [];
        foreach ($data as $key => $value) {
            if ($value !== null) {
                $cleanData[$key] = $value;
            }
        }
        
        $jsonData = json_encode($cleanData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($jsonData === false) {
            throw new Exception('Erro ao codificar dados JSON: ' . json_last_error_msg());
        }
        
        $response = $this->makeRequest('POST', $url, $jsonData);
        return $response !== false;
    }
    
    /**
     * Atualizar dados
     */
    public function update(string $table, array $data, array $filters): bool {
        $url = $this->baseUrl . $table . '?';
        foreach ($filters as $key => $value) {
            $url .= $key . '=eq.' . urlencode((string)$value) . '&';
        }
        $url = rtrim($url, '&');
        
        // Codificar dados JSON com flags apropriadas
        $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($jsonData === false) {
            throw new Exception('Erro ao codificar dados JSON: ' . json_last_error_msg());
        }
        
        $response = $this->makeRequest('PATCH', $url, $jsonData);
        return $response !== false;
    }
    
    /**
     * Eliminar dados
     */
    public function delete(string $table, array $filters): bool {
        $url = $this->baseUrl . $table . '?';
        foreach ($filters as $key => $value) {
            $url .= $key . '=eq.' . urlencode((string)$value) . '&';
        }
        $url = rtrim($url, '&');
        
        $response = $this->makeRequest('DELETE', $url);
        return $response !== false;
    }
    
    /**
     * Executar consulta SQL personalizada (usando RPC)
     */
    public function rpc(string $function, array $params = []): array {
        $url = $this->baseUrl . 'rpc/' . $function;
        $response = $this->makeRequest('POST', $url, json_encode($params));
        return json_decode($response, true) ?? [];
    }
    
    /**
     * Fazer requisição HTTP
     */
    private function makeRequest(string $method, string $url, string $data = ''): string|false {
        // Validar URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new Exception('URL inválida: ' . $url);
        }
        
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $this->headers,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception('Erro cURL: ' . $error . ' (URL: ' . $url . ')');
        }
        
        curl_close($ch);
        
        if ($httpCode >= 400) {
            throw new Exception('Erro HTTP ' . $httpCode . ': ' . $response);
        }
        
        return $response;
    }
    
    /**
     * Verificar se a API está acessível
     */
    public function testConnection(): bool {
        try {
            // Tentar uma consulta simples para testar a conexão
            $response = $this->makeRequest('GET', $this->baseUrl . 'idioma?select=id_idioma&limit=1');
            return $response !== false;
        } catch (Exception $e) {
            // Se falhar, tentar uma consulta ainda mais simples
            try {
                $response = $this->makeRequest('GET', $this->baseUrl . 'idioma?limit=1');
                return $response !== false;
            } catch (Exception $e2) {
                return false;
            }
        }
    }
}
?>
