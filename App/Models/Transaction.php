<?php
namespace App\Models;
use Flight;
use Medoo\Medoo;

class Transaction
{
    protected $db;
    protected $table = 'transaction';

    public function __construct()
    {
        $this->db = Flight::get('db');
    }

    public function create($data)
    {
        return $this->db->insert($this->table, $data);
    }

    public function find($id)
    {
        return $this->db->get($this->table, '*', ['id' => $id]);
    }

    public function findByReference($id)
    {
        return $this->db->get($this->table, '*', ['reference' => $id]);
    }

    public function update($id, $data)
    {
        return $this->db->update($this->table, $data, ['id' => $id]);
    }

    public function delete($id)
    {
        return $this->db->delete($this->table, ['id' => $id]);
    }

    public function all()
    {
        return $this->db->select($this->table, '*');
    }
}