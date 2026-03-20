<?php
class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    public $conn;

    public function __construct() {
        // Se a variável de ambiente existir (ex: no Render), usa ela. Se não, usa a padrão local/hostinger.
        $this->host = getenv('DB_HOST') !== false ? getenv('DB_HOST') : "localhost";
        $this->db_name = getenv('DB_NAME') !== false ? getenv('DB_NAME') : "u903648047_sis_caixa";
        $this->username = getenv('DB_USER') !== false ? getenv('DB_USER') : "u903648047_juniior";
        $this->password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : "Ezequiel_2014";
    }
    
    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
            
            // ⏰ CONFIGURAR TIMEZONE DA SESSÃO - FUNCIONA NA HOSTINGER
            $this->conn->exec("SET SESSION time_zone = '-03:00'");
            
            // Configurar modo de erro
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            error_log("Conexão com banco de dados estabelecida com sucesso - Timezone: -03:00");
            
        } catch(PDOException $exception) {
            error_log("Erro de conexão: " . $exception->getMessage());
            echo "Erro de conexão: " . $exception->getMessage();
        }
        
        return $this->conn;
    }
}

// ⏰ CONFIGURAR TIMEZONE DO PHP TAMBÉM
date_default_timezone_set('America/Sao_Paulo');
?>