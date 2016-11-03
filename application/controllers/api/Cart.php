<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
/** @noinspection PhpIncludeInspection */
require APPPATH . '/libraries/REST_Controller.php';

/**
 * @property Users_model $Users_model
 * @property Logged_users_model $Logged_users_model
 * @property Cart_model $Cart_model
 */
class Cart extends REST_Controller
{

    /**
     * Cart constructor.
     */
    function __construct()
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, "
            . "Content-Type, Accept, Access-Control-Request-Method");

        if ("OPTIONS" === $_SERVER['REQUEST_METHOD'])
            die();

        parent::__construct();
    }

    function get_cart_post()
    {
        if (!$this->post('email') || !$this->post('token')) {
            $this->response([
                'status' => FALSE,
                'message' => 'You have not provided all the requested data!'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        $this->load->model('Users_model');
        $user_id = $this->Users_model->get_user_id_by_email($this->post('email'));

        if (!$user_id) {
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

        // Check user's token
        if ($token != $returned_token) {
            $this->response([
                'status' => FALSE,
                'message' => 'Unauthorized action!'
            ], REST_Controller::HTTP_UNAUTHORIZED);
        }

        $cart = $this->Cart_model->get_cart($user_id);

        if ($cart)
            $this->response([
                'status' => TRUE,
                'message' => 'Cart has been fetched.',
                'data' => $cart
            ], REST_Controller::HTTP_OK);
        else
            $this->response([
                'status' => FALSE,
                'message' => 'Cart could not be fetched.'
            ], REST_Controller::HTTP_BAD_REQUEST);
    }

    function change_cart_post()
    {
        if (!$this->post('email') || !$this->post('token') || !$this->post('cart')) {
            $this->response([
                'status' => FALSE,
                'message' => 'You have not provided all the requested data!'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        $this->load->model('Users_model');
        $user_id = $this->Users_model->get_user_id_by_email($this->post('email'));

        if (!$user_id) {
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

        // Check user's token
        if ($token != $returned_token) {
            $this->response([
                'status' => FALSE,
                'message' => 'Unauthorized action!'
            ], REST_Controller::HTTP_UNAUTHORIZED);
        }

        $success = $this->Cart_model->change_cart($user_id, $this->post('cart'));

        if ($success)
            $this->response([
                'status' => TRUE,
                'message' => 'Cart has been successfully changed.',
            ], REST_Controller::HTTP_OK);
        else
            $this->response([
                'status' => FALSE,
                'message' => 'Cart has not been changed.'
            ], REST_Controller::HTTP_BAD_REQUEST);
    }
}