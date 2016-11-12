<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Maintenance extends Application
{

    function __construct() {
        parent::__construct();
        $this->load->helper('formfields');
        $this->error_messages = array();
    }


    public function index() {

        $role = $this->session->userdata('userrole');
        if ($role == 'admin') {
            $this->data['pagebody'] ='mtce';
            $this->data['items'] = $this->Menu->all();
        }
        else {
            $stuff = "You are not authorized to access this page. Go away";
            $this->data['content'] = $this->parsedown->parse($stuff);

        }

        // get the user role
        $this->data['userrole'] = $this->session->userdata('userrole');
        if ($this->data['userrole'] == NULL) $this->data['userrole'] = '?';

        $this->render(); 

    }

    public function edit($id=null) {
        // try the session first
        $key = $this->session->userdata('key');
        $record = $this->session->userdata('record');

        // if not there, get them from the database
        if (empty($record)) {
            $record = $this->Menu->get($id);
            $key = $id;
            $this->session->set_userdata('key',$id);
            $this->session->set_userdata('record',$record);
        }

        // build the form fields
        $this->data['fid'] = makeTextField('Menu code', 'id', $record->id);
        $this->data['fname'] = makeTextField('Item name', 'name', $record->name);
        $this->data['fdescription'] = makeTextArea('Description', 'description', $record->description);
        $this->data['fprice'] = makeTextField('Price, each', 'price', $record->price);
        $this->data['fpicture'] = makeTextField('Item image', 'picture', $record->picture);

        $cats = $this->Categories->all(); // get an array of category objects
        foreach ($cats as $code => $category){ // make it into an associative array
            $codes[$category->id] = $category->name;
        }
        $this->data['fcategory'] = makeCombobox('Category', 'category', $record->category, $codes);
        $this->data['zsubmit'] = makeSubmitButton('Save', 'Submit changes');

        // show the editing form
        $this->data['pagebody'] = "mtce-edit";
        $this->show_any_errors();
        $this->render();

    }

    public function cancel()
    {
        $this->session->unset_userdata('key');
        $this->session->unset_userdata('record');
        $this->index();
    }

    function save() {
        // try the session first
        $key = $this->session->userdata('key');
        $record = $this->session->userdata('record');
              
        // if not there, nothing is in progress
        if (empty($record)) {
            $this->index();
            return;
        }

        // update our data transfer object
        $incoming = $this->input->post();
        foreach(get_object_vars($record) as $index => $value){
            if (isset($incoming[$index])){
                $record->$index = $incoming[$index];
            }
        }


        $this->session->set_userdata('record',$record);  

        // validate
        $this->load->library('form_validation');
        $this->form_validation->set_rules($this->Menu->rules());
        if ($this->form_validation->run() != TRUE)
            $this->error_messages = $this->form_validation->error_array();

        // check menu code for additions
        if ($key == null){
            if ($this->menu->exists($record->id)){
                $this->error_messages[] = 'Duplicate key adding new menu item';
            }
        }

        if (! $this->Categories->exists($record->category)){
            $this->error_messages[] = 'Invalid category code: ' . $record->category;
        }


        // save or not
        if (! empty($this->error_messages)) {
            $this->edit();
            return;
        }

        // update our table, finally!
        if ($key == null){
            $this->menu->add($record);
        }
        else{
            $this->Menu->update($record);
        }


        // and redisplay the list
        $this->index();
    }


    function show_any_errors() {
        $result = '';
        if (empty($this->error_messages)) {
            $this->data['error_messages'] = '';
            return;
        }

        // add the error messages to a single string with breaks
        foreach($this->error_messages as $onemessage)
            $result .= $onemessage . '<br/>';

        // and wrap these per our view fragment
        $this->data['error_messages'] = $this->parser->parse('mtce-errors',['error_messages' => $result], true);
    }

}

