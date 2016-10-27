<?php

class General_model extends CI_Model
{
    public function __construct()
    {
        // Call the CI_Model constructor
        parent::__construct();
        $this->load->database();
    }

    public function get_DB_Time()
    {
        $query = $this->db->query('SELECT CURRENT_TIMESTAMP as time');
        return $query->result()[0]->time;
    }
}