<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->group(['prefix' => 'auth'], function () use ($router) {

    //Auth APIs
    $router->post('login', 'AuthController@login');
    $router->post('logout', 'AuthController@logout');
    $router->post('refresh', 'AuthController@refresh');
    $router->post('me', 'AuthController@me');
    $router->post('sendCodeToUser', 'AuthController@sendCodeToUser');
    $router->post('sendCodeToVisitor', 'AuthController@sendCodeToVisitor');
    $router->post('register', 'AuthController@register');
    $router->post('resetPassword', 'AuthController@resetPassword');
    $router->post('checkCode', 'AuthController@checkCode');
    $router->post('sendCode','AuthController@sendCode');
    $router->post('notePayment', 'AuthController@notePayment');
    $router->post('classPayment', 'AuthController@classPayment');

});

$router->group(['prefix' => 'students'], function () use ($router) {
    $router->post('changeName', 'StudentController@changeName');
    $router->post('addComplaint', 'StudentController@addComplaint');
    $router->post('getLevels', 'StudentController@getLevels');
    $router->post('getPayments', 'StudentController@getPayments');
});

$router->group(['prefix' => 'exams'], function () use ($router) {
    $router->post('getExam', 'ExamController@getExam');
    $router->post('addResult', 'ExamController@addResult');
    $router->post('addQuestionComplaint', 'ExamController@addQuestionComplaint');
});

$router->get('/', function () use ($router) {
    return $router->app->version();
});
