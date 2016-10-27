<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
/** @noinspection PhpIncludeInspection */
require APPPATH . '/libraries/REST_Controller.php';

/**
 * Class Offers
 *
 * @property Users_model $Users_model
 * @property Logged_users_model $Logged_users_model
 * @property Offers_model $Offers_model
 * @property Requests_model $Requests_model
 * @property Products_model $Products_model
 */
class Offers extends REST_Controller
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

        $this->load->model('Offers_model');
        $this->load->model('Users_model');
        $this->load->model('Logged_users_model');
        $this->load->model('Requests_model');
        $this->load->model('Products_model');
        // Configure limits on our controller methods
        // Ensure you have created the 'limits' table and enabled 'limits' within application/config/rest.php
        //$this->methods['user_get']['limit'] = 500; // 500 requests per hour per user/key
        //$this->methods['user_post']['limit'] = 100; // 100 requests per hour per user/key
        //$this->methods['user_delete']['limit'] = 50; // 50 requests per hour per user/key
    }

    public function get_offers_post()
    {
        if (!$this->post('token') || !$this->post('email'))
            $this->response([
                'status' => FALSE,
                'message' => 'Invalid Request, missing parameters'
            ], REST_Controller::HTTP_BAD_REQUEST);

        $user_id = $this->Users_model->get_user_id_by_email($this->post('email'));

        if (!$user_id)
            $this->response([
                'status' => FALSE,
                'message' => 'User not found'
            ], REST_Controller::HTTP_FORBIDDEN);

        $returned_token = $this->Logged_users_model->getToken($user_id);

        if (!$returned_token)
            $this->response([
                'status' => FALSE,
                'message' => 'Session not found.'
            ], REST_Controller::HTTP_BAD_REQUEST);

        $token = $this->post('token');

        // Check user token
        if ($token != $returned_token)
            $this->response([
                'status' => FALSE,
                'message' => 'Unauthorized action: Invalid token.'
            ], REST_Controller::HTTP_UNAUTHORIZED);

        $offers = $this->Offers_model->get_user_offers($user_id);

        if (!$offers)
            $this->response([
                'status' => FALSE,
                'message' => 'Offers not found.'
            ], REST_Controller::HTTP_BAD_REQUEST);

        else
            $this->response([
                'status' => TRUE,
                'message' => 'Offers found.',
                'data' => $offers
            ], REST_Controller::HTTP_OK);
    }

    public function accept_offer_post()
    {
        if (!$this->post('token') || !$this->post('email') || !$this->post('offer_id'))
            $this->response([
                'status' => FALSE,
                'message' => 'Invalid Request, missing parameters'
            ], REST_Controller::HTTP_BAD_REQUEST);

        $user_id = $this->Users_model->get_user_id_by_email($this->post('email'));

        if (!$user_id)
            $this->response([
                'status' => FALSE,
                'message' => 'User not found'
            ], REST_Controller::HTTP_FORBIDDEN);

        $returned_token = $this->Logged_users_model->getToken($user_id);

        if (!$returned_token)
            $this->response([
                'status' => FALSE,
                'message' => 'Session not found.'
            ], REST_Controller::HTTP_BAD_REQUEST);

        $token = $this->post('token');

        // Check user token
        if ($token != $returned_token)
            $this->response([
                'status' => FALSE,
                'message' => 'Unauthorized action: Invalid token.'
            ], REST_Controller::HTTP_UNAUTHORIZED);

        $accepted = $this->Offers_model->accept_offer($this->post('offer_id'));

        if (!$accepted)
            $this->response([
                'status' => FALSE,
                'message' => 'Offer not accepted.'
            ], REST_Controller::HTTP_BAD_REQUEST);

        else
            $this->response([
                'status' => TRUE,
                'message' => 'Offer has been accepted.'
            ], REST_Controller::HTTP_OK);
    }
}