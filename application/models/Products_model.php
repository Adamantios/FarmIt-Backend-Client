<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Products_model extends CI_Model
{

    public function __construct()
    {
        // Call the CI_Model constructor
        parent::__construct();
        $this->load->database();
    }

    public function search_product_producer($match, $start)
    {
        $limit = 10;
        $results = array();

        $this->db->distinct();
        $this->db->select('id, producer_id, name, descr, origin, unit_price, stock, base_shipping_cost, '
            . 'additional_shipping');
        $this->db->like('name', $match);
        $this->db->or_like('descr', $match);
        $this->db->or_like('origin', $match);
        $products = $this->db->get('products', $limit, $start);

        if ($products->num_rows()) {
            $results_products = $products->result_array();
            $results_producers = array();

            foreach ($products->result_array() as $product) {
                $producer = $this->db->get_where('users', array('id' => $product['producer_id']));

                if ($producer->num_rows())
                    array_push($results_producers, $producer->result_array()[0]);
            }

            $results['products'] = $results_products;
            $results['producers'] = $results_producers;
        }

        $this->db->distinct();
        $this->db->select('name, tel_num, email');
        $this->db->like('name', $match);
        $producers_matched = $this->db->get('users', $limit, $start);

        if ($producers_matched->num_rows())
            $results['producers_matched'] = $producers_matched->result_array();

        if ($results)
            return $results;

        return FALSE;
    }

    public function get_product_by_producer_email($email)
    {
        $this->db->select('id');
        $id = $this->db->get_where('users', array('email=' => $email));

        if ($id->num_rows() == 1) {
            $this->db->select('id, name, descr, origin, unit_price, stock, '
                . 'base_shipping_cost, base_shipping_units, additional_shipping');
            $products = $this->db->get_where('products', array('producer_id=' => $id->result()[0]->id));

            if ($products)
                return $products->result_array();
        }

        return FALSE;
    }
}
