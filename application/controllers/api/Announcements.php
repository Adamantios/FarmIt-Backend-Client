<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
/** @noinspection PhpIncludeInspection */
require APPPATH . '/libraries/REST_Controller.php';

/**
 * @property Users_model $Users_model
 * @property Logged_users_model $Logged_users_model
 * @property Announcements_model $Announcements_model
 * @property General_model $General_model
 */
class Announcements extends REST_Controller
{
    /**
     * Announcements constructor.
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

    public function upload_post()
    {
        if ((!$this->post('announcement') || !$this->post('email')
            || !$this->post('token') || !$this->post('duration'))
        ) {

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

        $this->load->model('Announcements_model');
        $this->load->model('General_model');
        if ($this->Announcements_model->upload_announcement($user_id, $this->post('announcement'),
            $this->post('final_price'), $this->post('duration'), $this->General_model->get_DB_Time())
        ) {

            $this->response([
                'status' => TRUE,
                'message' => 'Announcement uploaded successfully.'
            ], REST_Controller::HTTP_OK);
        } else {
            $this->response([
                'status' => FALSE,
                'message' => 'Announcement not uploaded.'
            ], REST_Controller::HTTP_OK);
        }
    }
}