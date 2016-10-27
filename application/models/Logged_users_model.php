<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Logged_users_model extends CI_Model {

    public function __construct() {
        // Call the CI_Model constructor
        parent::__construct();
        $this->load->database();
    }

    public function add($id,$token) {
        // Making sure that a user session is not stored twice
        $this->db->delete('loggedUsers', array('user_id' => $id)); 
        
        $data = array();
        $data['user_id'] = $id;
        $data['user_token'] = $token;
        $this->db->insert('loggedUsers', $data);
        $insertedId = $this->db->insert_id();
        if (!$insertedId) {
            return FALSE;
        } else {
            return $insertedId;
        }
    }
    
    public function remove($id) {
        $this->db->where('user_id', $id);
        return $this->db->delete('loggedUsers');
    }
    
    public function getToken($id) {
        $query = $this->db->query('SELECT user_token FROM loggedUsers WHERE user_id = ' . $id);
        
        if ($query->num_rows() != 1) {
            return FALSE;
        } else {
            return $query->result()[0]->user_token;
        }
    }

}
