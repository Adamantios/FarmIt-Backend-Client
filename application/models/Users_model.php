<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Class Users_model
 *
 * @property Logged_users_model $Logged_users_model
 */
class Users_model extends CI_Model
{

    public function __construct()
    {
        // Call the CI_Model constructor
        parent::__construct();
        $this->load->database();
    }

    public function get_user_id_by_email($email)
    {
        $query = $this->db->get_where('users', array('email' => $email));

        if ($query->num_rows() != 1) {
            return FALSE;
        } else {
            $user_id = $query->result()[0]->id;
            return $user_id;
        }
    }

    public function add_new_user($param)
    {
        $this->db->insert('users', $param);
        $insertedId = $this->db->insert_id();

        if (!$insertedId) {
            return FALSE;
        } else {
            return $insertedId;
        }
    }

    public function get_user_pass_by_email($email)
    {
        $query = $this->db->get_where('users', array('email' => $email));

        if ($query->num_rows() != 1) {
            return FALSE;
        } else {
            $user_pass = $query->result()[0]->password;
            return $user_pass;
        }
    }

    public function delete_user($user_id, $email)
    {
        // Start a transaction.
        $this->db->trans_start();
        $this->load->model('Logged_users_model');

        if (!$this->Logged_users_model->remove($user_id)) {
            $this->db->trans_rollback();
            return FALSE;
        }

        $this->db->where('email', $email);

        if (!$this->db->delete('users')) {
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

    public function update_user($updateFields, $userId)
    {
        $this->db->where('id', $userId);

        if ($this->db->update('users', $updateFields))
            return TRUE;
        else
            return FALSE;
    }

    public function update_product($user_id, $data, $name)
    {
        $this->db->where(array('producer_id' => $user_id, 'name' => $name));
        $this->db->update('products', $data);
        if ($this->db->affected_rows() == 1) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    public function remove_product($user_id, $name, $data)
    {
        $this->db->where(array('producer_id' => $user_id, 'name' => $name));
        $this->db->delete('products', $data);
        if ($this->db->affected_rows() == 1) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    public function get_user_session($id)
    {
        $query = $this->db->query('SELECT name, surname, email, tel_num, is_company FROM users where id=' . $id);
        if ($query->num_rows() != 1) {
            return FALSE;
        } else {
            return $query->result()[0];
        }
    }

    public function get_producers($start)
    {
        $limit = 10;

        $query = $this->db->get_where('users', array('is_company' => 1), $limit, $start);

        if ($query->num_rows() == 0)
            return FALSE;
        else
            return $query->result();
    }

    public function get_user($user_id)
    {
        $query = $this->db->get_where('users', array('id' => $user_id));

        if ($query->num_rows() == 0)
            return FALSE;
        else
            return $query->row_array();
    }

    public function get_statistics($user_id)
    {
        $this->db->select('id');
        $request_ids = $this->db->get_where('requests', array('user_id' => $user_id));
        $statistics['announcements_created'] = $request_ids->num_rows();

        $statistics['offers_accepted'] = 0;
        $statistics['offers_received'] = 0;

        foreach ($request_ids->result() as $request_id) {
            $this->db->where(array('request_id' => $request_id->id, 'accepted' => 1));
            $this->db->from('offers');
            $statistics['offers_accepted'] += $this->db->count_all_results();

            $this->db->where('request_id', $request_id->id);
            $this->db->from('offers');
            $statistics['offers_received'] += $this->db->count_all_results();
        }

        $this->db->where('user_id', $user_id);
        $this->db->from('instant_purchases');
        $statistics['instant_purchases'] = $this->db->count_all_results();

        return $statistics;
    }
}
