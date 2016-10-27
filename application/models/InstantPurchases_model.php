<?php

class InstantPurchases_model extends CI_Model
{

    public function create_instant_purchase($user_id, $products, $total_price, $timestamp)
    {
        $data['user_id'] = $user_id;
        $data['total_price'] = $total_price;
        $data['timestamp'] = $timestamp;
        $data['completed'] = 0;

        // Start a transaction.
        $this->db->trans_start();
        $this->db->insert('instant_purchases', $data);
        /** @noinspection PhpUndefinedMethodInspection */
        $insertedId = $this->db->insert_id();

        if (!$insertedId) {
            $this->db->trans_rollback();
            return FALSE;
        }

        foreach ($products as $product) {
            $product_data['instant_purchases_id'] = $insertedId;
            $product_data['product_id'] = $product['id'];
            $product_data['quantity'] = $product['quantity'];
            $product_data['price'] = $product['price'];

            $this->db->insert('instant_purchases_details', $product_data);
            /** @noinspection PhpUndefinedMethodInspection */
            $inserted_productId = $this->db->insert_id();

            if (!$inserted_productId) {
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

    public function delete_instant_purchases($user_id)
    {
        // Start a transaction.
        $this->db->trans_start();
        $this->db->select('id');
        $this->db->where('user_id', $user_id);
        $purchases_results = $this->db->get('instant_purchases');

        if (!$purchases_results) {
            $this->db->trans_rollback();
            return FALSE;
        }

        foreach ($purchases_results->result_array() as $purchase_result) {
            $this->db->where('instant_purchases_id', $purchase_result['id']);

            if (!$this->db->delete('instant_purchases_details')) {
                $this->db->trans_rollback();
                return FALSE;
            }
        }

        $this->db->where('user_id', $user_id);

        if (!$this->db->delete('instant_purchases')) {
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