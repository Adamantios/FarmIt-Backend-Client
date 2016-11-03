<?php

class Cart_model extends CI_Model
{
    public function __construct()
    {
        // Call the CI_Model constructor
        parent::__construct();
        $this->load->database();
    }

    public function get_cart($user_id)
    {
        return $this->db->get_where('cart', array('user_id' => $user_id));
    }

    public function change_cart($user_id, $cart)
    {
        $this->db->where('user_id', $user_id);

        if ($this->db->update('cart', array('cart' => $cart)))
            return TRUE;
        else
            return FALSE;
    }
}