<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
/** @noinspection PhpIncludeInspection */
require APPPATH . '/libraries/REST_Controller.php';

/**
 * @property Products_model $Products_model
 */
class Products extends REST_Controller
{

    function __construct()
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, "
            . "Content-Type, Accept, Access-Control-Request-Method");

        if ("OPTIONS" === $_SERVER['REQUEST_METHOD'])
            die();

        // Construct the parent class
        parent::__construct();
        // Configure limits on our controller methods
        // Ensure you have created the 'limits' table and enabled 'limits' within application/config/rest.php
        //$this->methods['user_get']['limit'] = 500; // 500 requests per hour per user/key
        //$this->methods['user_post']['limit'] = 100; // 100 requests per hour per user/key
        //$this->methods['user_delete']['limit'] = 50; // 50 requests per hour per user/key
    }

    function search_products_producers_post()
    {
        if (!$this->post('match')) {
            $this->response([
                'status' => FALSE,
                'message' => 'You have not provided complete searching data!'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        $this->load->model('Products_model');
        $result = $this->Products_model->search_product_producer($this->post('match'), $this->post('start'));

        if ($result) {
            $this->response([
                'status' => TRUE,
                'message' => 'Results fetched!',
                'data' => $result
            ], REST_Controller::HTTP_OK);

        } else {
            $this->response([
                'status' => FALSE,
                'message' => 'Nothing found!'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }
    }

    function get_products_by_producer_email_post() {
        if (!$this->post('email'))
            $this->response([
                'status' => FALSE,
                'message' => "Missing producer's email."
            ], REST_Controller::HTTP_BAD_REQUEST);

        $this->load->model('Products_model');
        $result = $this->Products_model->get_product_by_producer_email($this->post('email'));

        if ($result) {
            $this->response([
                'status' => TRUE,
                'message' => 'Results fetched!',
                'data' => $result
            ], REST_Controller::HTTP_OK);

        } else {
            $this->response([
                'status' => FALSE,
                'message' => 'Nothing found!'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }
    }
}