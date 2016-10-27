<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
/** @noinspection PhpIncludeInspection */
require APPPATH . '/libraries/REST_Controller.php';

/**
 * @property Users_model $Users_model
 * @property Logged_users_model $Logged_users_model
 * @property InstantPurchases_model $InstantPurchases_model
 * @property General_model $General_model
 */
class InstantPurchases extends REST_Controller
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
    }

    function create_instant_purchase_post()
    {
        if (!$this->post('email') || !$this->post('token') || !$this->post('products') || !$this->post('total_price')) {
            $this->response([
                'status' => FALSE,
                'message' => 'You have not provided complete data!'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        $this->load->model('Users_model');
        $user_id = $this->Users_model->get_user_id_by_email($this->post('email'));

        if (!$user_id) {
            log_message('info', "User not found!");
            $this->response([
                'status' => FALSE,
                'message' => 'User not found'
            ], REST_Controller::HTTP_FORBIDDEN);
        }

        $this->load->model('Logged_users_model');
        $returned_token = $this->Logged_users_model->getToken($user_id);

        if (!$returned_token) {
            $this->response([
                'status' => FALSE,
                'message' => 'Session not found.'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        $token = $this->post('token');

        // Check user token
        if ($token != $returned_token) {
            $this->response([
                'status' => FALSE,
                'message' => 'Unauthorized action: Invalid token.'
            ], REST_Controller::HTTP_UNAUTHORIZED);
        }

        $this->load->model('General_model');
        $timestamp = $this->General_model->get_DB_Time();

        $this->load->model('InstantPurchases_model');
        $result = $this->InstantPurchases_model->create_instant_purchase($user_id, $this->post('products'),
            $this->post('total_price'), $timestamp);

        if ($result) {
            $this->response([
                'status' => TRUE,
                'message' => 'Instant purchase created successfully!',
                'data' => $result
            ], REST_Controller::HTTP_OK);

        } else {
            $this->response([
                'status' => FALSE,
                'message' => 'Instant purchase could not be created!'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }
    }
}