<?php
// declare(strict_types=1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../includes/DbOperations.php';

/**
 * Instantiate App
 *
 * In order for the factory to work you need to ensure you have installed
 * a supported PSR-7 implementation of your choice e.g.: Slim PSR-7 and a supported
 * ServerRequest creator (included with Slim PSR-7)
 */
$app = AppFactory::create();
$app->setBasePath("/RestfulApiExample/public");

/**
  * The routing middleware should be added earlier than the ErrorMiddleware
  * Otherwise exceptions thrown from it will not be handled by the middleware
  */
$app->addRoutingMiddleware();

/**
 * Add Error Middleware
 *
 * @param bool                  $displayErrorDetails -> Should be set to false in production
 * @param bool                  $logErrors -> Parameter is passed to the default ErrorHandler
 * @param bool                  $logErrorDetails -> Display error details in error log
 * @param LoggerInterface|null  $logger -> Optional PSR-3 Logger  
 *
 * Note: This middleware should be added last. It will not handle any exceptions/errors
 * for middleware added after it.
 */
$errorMiddleware = $app->addErrorMiddleware(true, true, true);

// Define app routes
$app->get('/hello/{name}', function (Request $request, Response $response, $args) {
    $name = $args['name'];
    $response->getBody()->write("Hello, $name");
    return $response;
});



/* 
    endpoint: createuser
    parameters: email, password, name, school
    method: POST
*/

$app->post('/createuser', function(Request $request, Response $response){
	
   
    if(!haveEmptyParameters(array('email', 'password', 'name', 'school'), $request, $response)){

    
        $request_data = $request->getParsedBody(); 
        $email = $request_data['email'];
        $password = $request_data['password'];
        $name = $request_data['name'];
        $school = $request_data['school']; 
        $hash_password = password_hash($password, PASSWORD_DEFAULT);
    
        $db = new DbOperations; 

        $result = $db->createUser($email, $hash_password, $name, $school);
        // echo 'test';
        // echo $result;
        // exit();    
        if($result == USER_CREATED){

            $message = array(); 
            $message['error'] = false; 
            $message['message'] = 'User created successfully';

            $response->getBody()->write(json_encode($message));

            return $response
                        ->withHeader('Content-type', 'application/json')
                        ->withStatus(201);

        }else if($result == USER_FAILURE){

            $message = array(); 
            $message['error'] = true; 
            $message['message'] = 'Some error occurred';

            $response->getBody()->write(json_encode($message));

            return $response
                        ->withHeader('Content-type', 'application/json')
                        ->withStatus(422);    

        }else if($result == USER_EXISTS){
            $message = array(); 
            $message['error'] = true; 
            $message['message'] = 'User Already Exists';

            $response->getBody()->write(json_encode($message));

            return $response
                        ->withHeader('Content-type', 'application/json')
                        ->withStatus(422);    
        }
    }
    return $response
        ->withHeader('Content-type', 'application/json')
        ->withStatus(422);    
});


$app->post('/userlogin', function(Request $request, Response $response){

    if(!haveEmptyParameters(array('email', 'password'), $request, $response)){
        $request_data = $request->getParsedBody(); 

        $email = $request_data['email'];
        $password = $request_data['password'];
        
        $db = new DbOperations; 

        $result = $db->userLogin($email, $password);

        if($result == USER_AUTHENTICATED){
            
            $user = $db->getUserByEmail($email);
            $response_data = array();

            $response_data['error']=false; 
            $response_data['message'] = 'Login Successful';
            $response_data['user']=$user; 

            $response->write(json_encode($response_data));

            return $response
                ->withHeader('Content-type', 'application/json')
                ->withStatus(200);    

        }else if($result == USER_NOT_FOUND){
            $response_data = array();

            $response_data['error']=true; 
            $response_data['message'] = 'User not exist';

            $response->write(json_encode($response_data));

            return $response
                ->withHeader('Content-type', 'application/json')
                ->withStatus(200);    

        }else if($result == USER_PASSWORD_DO_NOT_MATCH){
            $response_data = array();

            $response_data['error']=true; 
            $response_data['message'] = 'Invalid credential';

            $response->write(json_encode($response_data));

            return $response
                ->withHeader('Content-type', 'application/json')
                ->withStatus(200);  
        }
    }

    return $response
        ->withHeader('Content-type', 'application/json')
        ->withStatus(422);    
});

$app->get('/allusers', function(Request $request, Response $response){

    $db = new DbOperations; 

    $users = $db->getAllUsers();

    $response_data = array();

    $response_data['error'] = false; 
    $response_data['users'] = $users; 

    $response->write(json_encode($response_data));

    return $response
    ->withHeader('Content-type', 'application/json')
    ->withStatus(200);  

});


$app->put('/updateuser/{id}', function(Request $request, Response $response, array $args){

    $id = $args['id'];

    if(!haveEmptyParameters(array('email','name','school'), $request, $response)){

        $request_data = $request->getParsedBody(); 
        $email = $request_data['email'];
        $name = $request_data['name'];
        $school = $request_data['school']; 
     

        $db = new DbOperations; 

        if($db->updateUser($email, $name, $school, $id)){
            $response_data = array(); 
            $response_data['error'] = false; 
            $response_data['message'] = 'User Updated Successfully';
            $user = $db->getUserByEmail($email);
            $response_data['user'] = $user; 

            $response->write(json_encode($response_data));

            return $response
            ->withHeader('Content-type', 'application/json')
            ->withStatus(200);  
        
        }else{
            $response_data = array(); 
            $response_data['error'] = true; 
            $response_data['message'] = 'Please try again later';
            $user = $db->getUserByEmail($email);
            $response_data['user'] = $user; 

            $response->write(json_encode($response_data));

            return $response
            ->withHeader('Content-type', 'application/json')
            ->withStatus(200);  
              
        }

    }
    
    return $response
    ->withHeader('Content-type', 'application/json')
    ->withStatus(200);  

});

$app->put('/updatepassword', function(Request $request, Response $response){

    if(!haveEmptyParameters(array('currentpassword', 'newpassword', 'email'), $request, $response)){
        
        $request_data = $request->getParsedBody(); 

        $currentpassword = $request_data['currentpassword'];
        $newpassword = $request_data['newpassword'];
        $email = $request_data['email']; 

        $db = new DbOperations; 

        $result = $db->updatePassword($currentpassword, $newpassword, $email);

        if($result == PASSWORD_CHANGED){
            $response_data = array(); 
            $response_data['error'] = false;
            $response_data['message'] = 'Password Changed';
            $response->write(json_encode($response_data));
            return $response->withHeader('Content-type', 'application/json')
                            ->withStatus(200);

        }else if($result == PASSWORD_DO_NOT_MATCH){
            $response_data = array(); 
            $response_data['error'] = true;
            $response_data['message'] = 'You have given wrong password';
            $response->write(json_encode($response_data));
            return $response->withHeader('Content-type', 'application/json')
                            ->withStatus(200);
        }else if($result == PASSWORD_NOT_CHANGED){
            $response_data = array(); 
            $response_data['error'] = true;
            $response_data['message'] = 'Some error occurred';
            $response->write(json_encode($response_data));
            return $response->withHeader('Content-type', 'application/json')
                            ->withStatus(200);
        }
    }

    return $response
        ->withHeader('Content-type', 'application/json')
        ->withStatus(422);  
});

$app->delete('/deleteuser/{id}', function(Request $request, Response $response, array $args){
    $id = $args['id'];

    $db = new DbOperations; 

    $response_data = array();

    if($db->deleteUser($id)){
        $response_data['error'] = false; 
        $response_data['message'] = 'User has been deleted';    
    }else{
        $response_data['error'] = true; 
        $response_data['message'] = 'Plase try again later';
    }

    $response->write(json_encode($response_data));

    return $response
    ->withHeader('Content-type', 'application/json')
    ->withStatus(200);
});

function haveEmptyParameters($required_params, $request, $response){
    $error = false; 
    $error_params = '';
    $request_params = $request->getParsedBody(); 

    foreach($required_params as $param){
        if(!isset($request_params[$param]) || strlen($request_params[$param])<=0){
            $error = true; 
            $error_params .= $param . ', ';
        }
    }

    if($error){
        $error_detail = array();
        $error_detail['error'] = true; 
        $error_detail['message'] = 'Required parameters ' . substr($error_params, 0, -2) . ' are missing or empty';
        $response->write(json_encode($error_detail));
    }
    return $error; 
}



// Run app
$app->run();