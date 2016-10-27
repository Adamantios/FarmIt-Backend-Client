<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Offers_model extends CI_Model
{

    public function __construct()
    {
        // Call the CI_Model constructor
        parent::__construct();
    }

    public function get_user_offers($user_id)
    {
        $this->db->trans_start();
        $this->db->select('id');
        $request_ids = $this->db->get_where('requests', array('user_id' => $user_id));

        if (!$request_ids) {
            $this->db->trans_rollback();
            return FALSE;
        }

        $offers = array();

        foreach ($request_ids->result() as $request_id) {
            $result_id = $request_id->id;
            $result_offers = $this->db->get_where('offers', array('request_id' => $request_id->id))->result_array();

            $request_offers_tmp['request_id'] = $result_id;
            $request_offers_tmp['request_offers'] = $result_offers;

            array_push($offers, $request_offers_tmp);

            if (sizeof($offers) == 0) {
                $this->db->trans_rollback();
                return FALSE;
            }
        }

        $offers_details = array();

        foreach ($offers as $requests_offers)
            foreach ($requests_offers['request_offers'] as $offer) {
                $this->db->select('product_id, offer_id, quantity');
                $result = $this->db->get_where('offers_details', array('offer_id' => $offer['id']))->result();

                if (!$result) {
                    $this->db->trans_rollback();
                    return FALSE;
                }

                array_push($offers_details, $result);
            }

        $i = 0;

        foreach ($offers_details as $offer_details) {
            $j = 0;

            foreach ($offer_details as $offer_detail) {
                $this->db->select('id, name, descr, origin, unit_price, stock, '
                    . 'base_shipping_cost, base_shipping_units, additional_shipping');
                $result = $this->db->get_where('products', array('id' => $offer_detail->product_id))->row_array();

                if (!$result) {
                    $this->db->trans_rollback();
                    return FALSE;
                }

                $offers_details[$i][$j]->product = $result;
                $j++;
            }

            $i++;
        }

        $index = 0;

        foreach ($offers as $requests_offers) {
            $k = 0;

            foreach ($requests_offers['request_offers'] as $offer) {
                $i = 0;
                $products = array();

                foreach ($offers_details as $offer_details) {
                    $j = 0;

                    foreach ($offer_details as $offer_detail) {
                        if ($offer['id'] == $offer_detail->offer_id) {
                            $offer_detail->product['quantity'] = $offer_detail->quantity;
                            array_push($products, $offer_detail->product);
                        }
                        $j++;
                    }

                    $i++;
                }

                $offers[$k]['request_offers'][$index]['products'] = $products;
                $k++;
            }

            $index++;
        }

        // Complete the transaction.
        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return FALSE;
        }

        return $offers;
    }

    public function accept_offer($offer_id)
    {
        $data['accepted'] = 1;
        $this->db->where('id', $offer_id);
        return $this->db->update('offers', $data);
    }

}