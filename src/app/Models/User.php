<?php
namespace App\Models;

use App\Services\DatabaseService;
use \PDO;

class User
{
    private $db;

    public function __construct()
    {
        $this->db = DatabaseService::getInstance()->getConnection();
    }

    public static function where($field, $value)
    {
        $instance = new self();
        $stmt = $instance->db->prepare("SELECT * FROM users WHERE {$field} = :value LIMIT 1");
        $stmt->bindParam(':value', $value);
        $stmt->execute();
        
        return $stmt->fetchObject(__CLASS__);
    }

    public static function find($id)
    {
        $instance = new self();
        $stmt = $instance->db->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchObject(__CLASS__);
    }

    public function getPermissions()
    {
        // This method should return the permissions for the user
        // For simplicity, returning an empty array here
        return [];
    }


    public function getId()
    {
        return $this->id ?? null;
    }

    public function getRole()
    {
        return $this->role ?? null;
    }

    public function getUsername()
    {
        return $this->username ?? null;
    }

    public function getPassword()
    {
        return $this->password ?? null;
    }
    public function getStatus()
    {
        return $this->status ?? null;
    }

    public function getEmail()
    {
        return $this->email ?? null;
    }

    public function getFullName()
    {
        return $this->full_name ?? null;
    }

    public function getCreatedAt()
    {
        return $this->created_at ?? null;
    }

    public function getUpdatedAt()
    {
        return $this->updated_at ?? null;
    }


    public function getDb()
    {
        return $this->db;
    }
    
    public function setDb($db)
    {
        $this->db = $db;
    }

  


}