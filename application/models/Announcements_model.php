<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Announcements_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    public function upload_announcement($user_id, $announcement, $final_price, $duration, $time)
    {
        $data['user_id'] = $user_id;

        if ($final_price != null)
            $data['final_price'] = $final_price;

        $data['duration'] = $duration;
        $data['created_at'] = $time;

        // Start a transaction.
        $this->db->trans_start();
        $this->db->insert('requests', $data);
        /** @noinspection PhpUndefinedMethodInspection */
        $insertedId = $this->db->insert_id();

        if (!$insertedId) {
            $this->db->trans_rollback();
            return FALSE;
        }

        foreach ($announcement as $product) {
            $product_data['announcement_id'] = $insertedId;
            $product_data['category_id'] = $product['category']['id'];
            $product_data['subcategory_id'] = $product['subcategory']['id'];
            $product_data['amount'] = $product['amount'];

            $this->db->insert('requests_details', $product_data);
            /** @noinspection PhpUndefinedMethodInspection */
            $inserted_requestId = $this->db->insert_id();

            if (!$inserted_requestId) {
                $this->db->trans_rollback();
                return FALSE;
            }
        }

        // Complete the transaction.
        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return FALSE;
        }

        return $insertedId;
    }

    public function delete_announcements($user_id)
    {
        // Start a transaction.
        $this->db->trans_start();
        $this->db->select('id');
        $this->db->where('user_id', $user_id);
        $announcements_results = $this->db->get('requests');

        if (!$announcements_results) {
            $this->db->trans_rollback();
            return FALSE;
        }

        foreach ($announcements_results->result_array() as $announcement_result) {
            $this->db->where('announcement_id', $announcement_result['id']);

            if (!$this->db->delete('requests_details')) {
                $this->db->trans_rollback();
                return FALSE;
            }
        }

        $this->db->where('user_id', $user_id);

        if (!$this->db->delete('requests')) {
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
}