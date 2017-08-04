<?php

use Phalcon\Loader;
use Phalcon\Mvc\Micro;
use Phalcon\Di\FactoryDefault;
use Phalcon\Db\Adapter\Pdo\Mysql as PdoMysql;
use Phalcon\Http\Response;

// Use Loader() to autoload our model
$loader = new Loader();

$loader->registerNamespaces(
    [
       'DevipuramPhalcon\models' => __DIR__ . '/models/',		 
    ]
);

$loader->register();

$di = new FactoryDefault();

// Set up the database service
$di->set(
    'db',
    function () {
        return new PdoMysql(
            [
               'host'     => 'localhost',
				 //'host' => 'mariadb132372-devipuram.j.layershift.co.uk'
                'username' => 'root',
                'password' => '',
				// 'password' => 'NEGkst44234',
                'dbname'   => 'devipuram',
				//'dbname'   => 'testdevipuram',
            ]
        );
    }
);

// Create and bind the DI to the application
$app = new Micro($di);
 
   $app->before(function() use ($app) {
  //$origin = $app->request->getHeader("ORIGIN") ? $app->request->getHeader("ORIGIN") : '*';

     //  $app->response->setHeader("Access-Control-Allow-Origin", '*')
   $app->response->setHeader("Access-Control-Allow-Origin", 'http://localhost:3000')
      ->setHeader("Access-Control-Allow-Methods", 'GET,PUT,POST,DELETE,OPTIONS')
	 // response.setHeader("Access-Control-Allow-Headers", "Access-Control-Allow-Headers, Origin,Accept, X-Requested-With, Content-Type, Access-Control-Request-Method, Access-Control-Request-Headers")
      ->setHeader("Access-Control-Allow-Headers", 'Accept,Origin, X-Requested-With, Content-Range, Content-Disposition, Content-Type, Authorization,Access-Control-Allow-Origin')
	 // ->setHeader("Access-Control-Allow-Headers", 'Origin, X-Requested-With, Content-Range, Content-Disposition, Content-Type, Authorization')
	  //->setHeader("Access-Control-Allow-Headers", 'X-Requested-With, Content-Range, Content-Disposition, Content-Type, Authorization')
      ->setHeader("Access-Control-Allow-Credentials", 'true');
});



$app->options('/{catch:(.*)}', function() use ($app) { 
    $app->response->setStatusCode(200, "OK")->send();
});



$app->get('/api/events', function() use ($app) {
 

		$phql = 'SELECT * from DevipuramPhalcon\models\events ORDER BY EventTitle';

        $events = $app->modelsManager->executeQuery($phql);

        $data = [];

        foreach ($events as $event) {
            $data[] = [
                'EventID'   => $event->EventID,
                'EventTitle' => $event->EventTitle,
            ];
        }

        echo json_encode($data); });


$app->get('/api/users', function() use ($app) {
 

		$phql = 'SELECT * from DevipuramPhalcon\models\users';

        $events = $app->modelsManager->executeQuery($phql);

        $data = [];

        foreach ($events as $event) {
            $data[] = [
                'UserID'   => $event->UserID,
                'UserName' => $event->UserName,
				'FirstName' => $event->FirstName,
				'Password' => $event->Password
            ];
        }

        return json_encode($data); });

  
$app->post('/api/userlogin', function() use ($app) {

         $robot = $app->request->getJsonRawBody();
		 $phql = 'select count(*) as count from DevipuramPhalcon\models\users WHERE UserName=:UserName: AND Password=:Password:';		 
		 $status = $app->modelsManager->executeQuery(
            $phql,
            [
                'UserName' => $robot->UserName,
                'Password' => sha1($robot->Password),             
            ]
        );

		   
        $response = new Response();
		  		 
		if ($status[0]->count > 0) {

		    $response->setStatusCode(200, 'loginsuccess');
        //  $robot->count = $status->getModel()->count;

            $response->setJsonContent(
                [
                    'status' => 'OK',
                     //'data'   => $robot,
				     'login'   => 'success',
                ]
            );


}
 else {
           
            $response->setStatusCode(409, 'Conflict');

            // Send errors to the client
            $errors = [];

            foreach ($status->getMessages() as $message) {
                $errors[] = $message->getMessage();
            }

            $response->setJsonContent(
                [
                    'status'   => 'ERROR',
                    //'messages' => $errors,
					'login' => 'failed',
                ]
            );
        }
	 
	  return $response;
 	 
	 });

$app->post('/api/usersignup',
  function () use ($app) {
    
        $robot = $app->request->getJsonRawBody();

        $phql = 'INSERT INTO DevipuramPhalcon\models\users (UserTypeID, UserName, FirstName,Password) VALUES (2, :UserName:, :FirstName:,:Password:)';

        $status = $app->modelsManager->executeQuery(
            $phql,
            [
                'UserName' => $robot->UserName,
                'FirstName' => $robot->FirstName,
                'Password' =>sha1($robot->Password),				
            ]
        );

        // Create a response


         $response = new Response();
		  
        // Check if the insertion was successful
        if ($status->success() === true) {
            // Change the HTTP status
            $response->setStatusCode(201, 'Created');

            $robot->id = $status->getModel()->id;

            $response->setJsonContent(
                [
                    'status' => 'OK',
                    'data'   => $robot,
                ]
            );
        } else {
            // Change the HTTP status
            $response->setStatusCode(409, 'Conflict');

            // Send errors to the client
            $errors = [];

            foreach ($status->getMessages() as $message) {
                $errors[] = $message->getMessage();
            }

            $response->setJsonContent(
                [
                    'status'   => 'ERROR',
                    'messages' => $errors,
                ]
            );
        }

        return $response;
    }
);

	  
$app->get(
    '/api/events/search/{name}',
    
	function ($name) use ($app) {
        $phql = 'SELECT * FROM DevipuramPhalcon\models\events WHERE EventTitle LIKE :EventTitle: ORDER BY EventTitle';

        $events = $app->modelsManager->executeQuery(
            $phql,
            [
                'EventTitle' => '%' . $name . '%'
            ]
        );

        $data = [];

        foreach ($events as $event) {
            $data[] = [
                'EventID'   => $event->EventID,
                'EventTitle' => $event->EventTitle,
            ];
        }

        echo json_encode($data);
    }

);

// Retrieves robots based on primary key
$app->get(
    '/api/events/{EventID:[0-9]+}',
    function ($id) use ($app) {
        $phql = 'SELECT * FROM DevipuramPhalcon\models\events WHERE EventID = :EventID:';

        $robot = $app->modelsManager->executeQuery(
            $phql,
            [
                'EventID' => $id,
            ]
        )->getFirst();

        // Create a response
        $response = new Response();

        if ($robot === false) {
            $response->setJsonContent(
                [
                    'status' => 'NOT-FOUND'
                ]
            );
        } else {
            $response->setJsonContent(
                [
                    'status' => 'FOUND',
                    'data'   => [
                        'EventID'   => $robot->EventID,
                        'EventTitle' => $robot->EventTitle
                    ]
                ]
            );
        }

        return $response;
    }
);

// Adds a new robot
$app->post(
    '/api/addevents',
  function () use ($app) {
    
        $robot = $app->request->getJsonRawBody();

        $phql = 'INSERT INTO DevipuramPhalcon\models\events (EventTitle, EventDescription, EventFromDate,EventToDate,EventImageUrl) VALUES (:EventTitle:, :EventDescription:, :EventFromDate:,:EventToDate:,:EventImageUrl:)';

        $status = $app->modelsManager->executeQuery(
            $phql,
            [
                'EventTitle' => $robot->EventTitle,
                'EventDescription' => $robot->EventDescription,
                'EventFromDate' => $robot->EventFromDate,
				'EventToDate' => $robot->EventToDate,
				'EventImageUrl' => $robot->EventImageUrl,
            ]
        );

        // Create a response


         $response = new Response();
		  
        // Check if the insertion was successful
        if ($status->success() === true) {
            // Change the HTTP status
            $response->setStatusCode(201, 'Created');

            $robot->id = $status->getModel()->id;

            $response->setJsonContent(
                [
                    'status' => 'OK',
                    'data'   => $robot,
                ]
            );
        } else {
            // Change the HTTP status
            $response->setStatusCode(409, 'Conflict');

            // Send errors to the client
            $errors = [];

            foreach ($status->getMessages() as $message) {
                $errors[] = $message->getMessage();
            }

            $response->setJsonContent(
                [
                    'status'   => 'ERROR',
                    'messages' => $errors,
                ]
            );
        }

        return $response;
    }
);

// Updates robots based on primary key
$app->put(
    '/api/events/{EventID:[0-9]+}',
  function ($EventID) use ($app) {
        $robot = $app->request->getJsonRawBody();

        $phql = 'UPDATE DevipuramPhalcon\models\events SET EventTitle = :EventTitle:, EventDescription = :EventDescription:, EventFromDate = :EventFromDate:,EventToDate = :EventToDate:,EventImageUrl = :EventImageUrl: WHERE EventID = :EventID:';

        $status = $app->modelsManager->executeQuery(
            $phql,
            [
                'EventID'   => $EventID,
                'EventTitle' => $robot->EventTitle,
                'EventDescription' => $robot->EventDescription,
                'EventFromDate' => $robot->EventFromDate,
				'EventToDate' => $robot->EventToDate,
				'EventImageUrl' => $robot->EventImageUrl,
            ]
        );

        // Create a response
        $response = new Response();

        // Check if the insertion was successful
        if ($status->success() === true) {
            $response->setJsonContent(
                [
                    'status' => 'OK'
                ]
            );
        } else {
            // Change the HTTP status
            $response->setStatusCode(409, 'Conflict');

            $errors = [];

            foreach ($status->getMessages() as $message) {
                $errors[] = $message->getMessage();
            }

            $response->setJsonContent(
                [
                    'status'   => 'ERROR',
                    'messages' => $errors,
                ]
            );
        }

        return $response;
    }
);

// Deletes robots based on primary key
$app->delete(
    '/api/events/{EventID:[0-9]+}',
    function ($EventID) use ($app) {
        $phql = 'DELETE FROM DevipuramPhalcon\models\events WHERE EventID = :EventID:';

        $status = $app->modelsManager->executeQuery(
            $phql,
            [
                'EventID' => $EventID,
            ]
        );

        // Create a response
        $response = new Response();

        if ($status->success() === true) {
            $response->setJsonContent(
                [
                    'status' => 'OK'
                ]
            );
        } else {
            // Change the HTTP status
            $response->setStatusCode(409, 'Conflict');

            $errors = [];

            foreach ($status->getMessages() as $message) {
                $errors[] = $message->getMessage();
            }

            $response->setJsonContent(
                [
                    'status'   => 'ERROR',
                    'messages' => $errors,
                ]
            );
        }

        return $response;
    }
);

$app->handle();