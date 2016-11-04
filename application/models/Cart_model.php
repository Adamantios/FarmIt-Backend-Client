<?php

class Cart_model extends CI_Model
{
    public function __construct()
    {
        // Call the CI_Model constructor
        parent::__construct();
        $this->load->database();
    }

    public function create_cart($user_id, $cart)
    {
        return $this->db->insert('cart', array('user_id' => $user_id, 'cart' => $cart));
    }

    public function get_cart($user_id)
    {
        $query = $this->db->get_where('cart', array('user_id' => $user_id));

        if ($query->num_rows() == 0)
            return FALSE;
        else
            return $query->result()[0]->cart;
    }

    public function change_cart($user_id, $cart)
    {
        $this->db->trans_start();

        if ($this->db->get_where('cart', array('user_id' => $user_id))->num_rows() != 0) {
            $this->db->where('user_id', $user_id);

            if (!$this->db->update('cart', array('cart' => $cart))) {
                $this->db->trans_rollback();
                return FALSE;
            }
        } else
            if (!$this->create_cart($user_id, $cart)) {
                $this->db->trans_rollback();
                return FALSE;
            }

        // Complete the transaction.
        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return FALSE;
        }

        return TRUE;
    }

    public function empty_cart($user_id)
    {
        return $this->db->delete('cart', array('user_id' => $user_id));
    }
}