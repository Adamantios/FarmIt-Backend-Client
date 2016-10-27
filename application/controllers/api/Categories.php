<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
/** @noinspection PhpIncludeInspection */
require APPPATH . '/libraries/REST_Controller.php';

/**
 * @property Categories_model $Categories_model
 */
class Categories extends REST_Controller
{
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

    public function get_categories_post()
    {
        $this->load->model('Categories_model');
        $categories = $this->Categories_model->getAllCategories();
        if (!$categories) {
            $this->response([
                'status' => FALSE,
                'message' => 'No categories found'
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        } else {
            $this->response([
                'status' => TRUE,
                'categories' => $categories
            ], REST_Controller::HTTP_OK);
        }
    }

    public function get_subcategories_post()
    {
        if ((!$this->post('category_id'))) {
            $this->response([
                'status' => FALSE,
                'message' => 'Invalid Request.'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        foreach ($this->input->get_post(NULL, FALSE) as $name => $value) {
            if (filter_var($value, FILTER_SANITIZE_STRING) === false) {
                $this->response([
                    'status' => FALSE,
                    'message' => "Parameter " . $name . " contains invalid characters "
                ], REST_Controller::HTTP_BAD_REQUEST);
            }
        }

        $this->load->model('Categories_model');
        $subcategories = $this->Categories_model->getSubcategories($this->post('category_id'));

        if (!$subcategories) {
            $this->response([
                'status' => FALSE,
                'message' => 'No subcategories found'
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        } else {
            $this->response([
                'status' => TRUE,
                'subcategories' => $subcategories
            ], REST_Controller::HTTP_OK);
        }
    }
}