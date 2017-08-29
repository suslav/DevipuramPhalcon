<?php

use Phalcon\Loader;
use Phalcon\Mvc\Micro;
use Phalcon\Di\FactoryDefault;
use Phalcon\Db\Adapter\Pdo\Mysql as PdoMysql;
use Phalcon\Http\Response;

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

    $app->response->setHeader("Access-Control-Allow-Origin", 'http://localhost:3000')
	// $app->response->setHeader("Access-Control-Allow-Origin", '*')
	// ->setHeader("Access-Control-Allow-Methods", '*')
	// ->setHeader("Access-Control-Allow-Headers", '*')
	// ->setHeader("Access-Control-Allow-Credentials", 'true');

      ->setHeader("Access-Control-Allow-Methods", 'GET,PUT,POST,DELETE,OPTIONS')
	//->setHeader("Access-Control-Allow-Headers", 'Accept,Origin, X-Requested-With, Content-Range, Content-Disposition, Content-Type, Authorization,Access-Control-Allow-Origin','Access-Control-Allow-Credentials','Access-Control-Allow-Methods')
	  ->setHeader("Access-Control-Allow-Headers", 'Accept,Content-Type')     
	  ->setHeader("Access-Control-Allow-Credentials", 'true');

});



$app->options('/{catch:(.*)}', function() use ($app) { 
    $app->response->setStatusCode(200, "OK")->send();
});

//events

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

//users

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
		 $phql = 'select count(*) as count,UserID from DevipuramPhalcon\models\users WHERE UserName=:UserName: AND Password=:Password:';	
		 $status = $app->modelsManager->executeQuery(
            $phql,
            [
               'UserName' => $robot->UserName,
               'Password' => sha1($robot->Password),    
			  
			  // 'UserName' => $robot,
              //  'Password' => sha1(123456),     
            ]
        );

		   
        $response = new Response();
		  		 
		if ($status[0]->count > 0) {

		    $response->setStatusCode(200, 'loginsuccess');
        
			 $response->setJsonContent(
                [
                    'status' => 'OK',
				     'login'   => 'success',
					 'id'   => $status[0]->UserID
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


		 $pql = 'select count(*) as count from DevipuramPhalcon\models\users WHERE UserName=:UserName:';

		  $code = $app->modelsManager->executeQuery(
            $pql,
            [
                'UserName' => $robot->UserName               				
            ]
        );

		  $response = new Response();

		  if ($code[0]->count > 0) {

		  $response->setStatusCode(500, 'Duplicate');

		  $response->setJsonContent(
                [
                    'status'   => 'Duplicate',
                    'message' => 'email already exists',
                ]
            );

		  }
		  else
		  {
		  
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


      //   $response = new Response();
		  
        // Check if the insertion was successful
        if ($status->success() === true) {
            // Change the HTTP status
            $response->setStatusCode(201, 'Created');

          //  $robot->id = $status->getModel()->id;

           // $response->setJsonContent(
           //     [
           //         'status' => 'OK',
           //         'data'   => $robot,
           //     ]
           // );

		      $response->setJsonContent(
                [
                    'status' => 'OK',
                    'message'   => 'created',
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

           // $response->setJsonContent(186

           //     [
           //         'status'   => 'ERROR',
          //          'messages' => $errors,
          //      ]
          //  );

		   $response->setJsonContent(
                [
                    'status'   => 'ERROR',
                    'message' => 'ERROR',
                ]
            );
        }

		  }



       

        return $response;
    }
);

//photoalbum

$app->get('/api/photoalbum', function() use ($app) {

		$phql = 'SELECT * from DevipuramPhalcon\models\photoalbum';

        $events = $app->modelsManager->executeQuery($phql);

        $data = [];

        foreach ($events as $event) {
            $data[] = [
                'AlbumId'   => $event->AlbumId,
                'Title' => $event->Title,
				'Description' => $event->Description,
				'AlbumUrl' => $event->AlbumUrl,
				'AlbumThumbUrl' => $event->AlbumThumbUrl
            ];
        }
 return json_encode($data);  
 });

 //generalvisitorsanswers

 $app->post('/api/generalvisitorsanswersinsert',
  function () use ($app) {
    
        $robot = $app->request->getJsonRawBody();

		$now = new \DateTime();
        $datetime_field = $now->format('Y-m-d');

		$pql = 'INSERT INTO DevipuramPhalcon\models\visitors (UserID, FormTypeID, Date) VALUES (:UserID:, :FormTypeID:, :Date:)';

		$status1 = $app->modelsManager->executeQuery(		
            $pql,
            [            
			     'UserID' => $robot->UserID,
                 'FormTypeID' => 1,
                 'Date' =>$datetime_field	
            ]
        ); 

		 $response = new Response();

		   if ($status1->success() === true) {
           // $response->setStatusCode(201, 'Created');
			  $robot2->id = $status1->getModel()->VisitorFormID;


		        $phql = 'INSERT INTO DevipuramPhalcon\models\generalvisitorsanswers (GVAnswer, GVQuestionID, VisitorFormID) VALUES (:GVAnswer:, :GVQuestionID:, :VisitorFormID:)';

        $status = $app->modelsManager->executeQuery(		
            $phql,
            [
              //   'GVAnswer' => $robot->GVAnswer,
              //    'GVQuestionID' => $robot->GVQuestionID,
             //    'VisitorFormID' =>$robot->VisitorFormID	

			//	if($robot.contains('|'))
			//	{
			//	$answer = $robot.Split('|');

			//	for($i=0;$i<$answer.length,$i++){

			//	$answer2 = $answer[i]
			//	'GVAnswer' => $answer2->$answer2.Split('~')[0],
			//	'GVQuestionID' => $answer2->$answer2.Split('~')[1],
			//	'VisitorFormID' =>1

			//	}
			//	}

			  // 'GVAnswer' => $robot,
			   'GVAnswer' => $robot->GVAnswer,
                 'GVQuestionID' => 1,
                  'VisitorFormID' =>$robot2->id	

            ]
        ); 

        
		          
        if ($status->success() === true) {
            $response->setStatusCode(201, 'Created');
			  $robot2->id = $status->getModel()->GVAnswerID;
		      $response->setJsonContent(
                [
                    'status' => 'OK',
                    'message'   => 'created',					
                ]

				);

        } else {
          
            $response->setStatusCode(409, 'Conflict');
 
            $errors = [];

            foreach ($status->getMessages() as $message) {
                $errors[] = $message->getMessage();
            }           
		   $response->setJsonContent(
                [
                    'status'   => 'ERROR',
                    'message' => 'ERROR',
                ]
            );
        }
		 

        } else {
          
            $response->setStatusCode(409, 'Conflict');
 
            $errors = [];

            foreach ($status1->getMessages() as $message) {
                $errors[] = $message->getMessage();
            }           
		   $response->setJsonContent(
                [
                    'status'   => 'ERROR',
                    'message' => 'ERROR',
                ]
            );
        }		 
		 
        return $response;
    }
);

//generalvisitorsinteranswers

 $app->post('/api/generalvisitorsinteranswersinsert',
  function () use ($app) {
    
        $robot = $app->request->getJsonRawBody();
		
        $phql = 'INSERT INTO DevipuramPhalcon\models\generalvisitorsinteranswers (GVIAnswer, GVIQuestionID, VisitorFormID) VALUES (:GVIAnswer:, :GVIQuestionID:, :VisitorFormID:)';

        $status = $app->modelsManager->executeQuery(
            $phql,
            [              
			   'GVIAnswer' => $robot,
                 'GVIQuestionID' => 1,
                  'VisitorFormID' =>11	

            ]
        ); 

         $response = new Response();
		          
        if ($status->success() === true) {
            $response->setStatusCode(201, 'Created');
			 
		      $response->setJsonContent(
                [
                    'status' => 'OK',
                    'message'   => 'created',
                ]

				);

        } else {
          
            $response->setStatusCode(409, 'Conflict');
 
            $errors = [];

            foreach ($status->getMessages() as $message) {
                $errors[] = $message->getMessage();
            }           
		   $response->setJsonContent(
                [
                    'status'   => 'ERROR',
                    'message' => 'ERROR',
                ]
            );
        }		 
        return $response;
    }
);

//svcanswer

 $app->post('/api/svcanswerinsert',
  function () use ($app) {
    
        $robot = $app->request->getJsonRawBody();
		
        $phql = 'INSERT INTO DevipuramPhalcon\models\svcanswer (SVCAnswer, SVCQuestionID, VisitorFormID) VALUES (:SVCAnswer:, :SVCQuestionID:, :VisitorFormID:)';

        $status = $app->modelsManager->executeQuery(
            $phql,
            [              
			   'SVCAnswer' => $robot,
                 'SVCQuestionID' => 1,
                  'VisitorFormID' =>11	

            ]
        ); 

         $response = new Response();
		          
        if ($status->success() === true) {
            $response->setStatusCode(201, 'Created');
			 
		      $response->setJsonContent(
                [
                    'status' => 'OK',
                    'message'   => 'created',
                ]

				);

        } else {
          
            $response->setStatusCode(409, 'Conflict');
 
            $errors = [];

            foreach ($status->getMessages() as $message) {
                $errors[] = $message->getMessage();
            }           
		   $response->setJsonContent(
                [
                    'status'   => 'ERROR',
                    'message' => 'ERROR',
                ]
            );
        }		 
        return $response;
    }
);

//srimahameruanswers

 $app->post('/api/srimahameruanswersinsert',
  function () use ($app) {
    
        $robot = $app->request->getJsonRawBody();
		
        $phql = 'INSERT INTO DevipuramPhalcon\models\srimahameruanswers (SMMAnswer, SMMQuestionID, VisitorFormID) VALUES (:SMMAnswer:, :SMMQuestionID:, :VisitorFormID:)';

        $status = $app->modelsManager->executeQuery(
            $phql,
            [              
			   'SMMAnswer' => $robot,
                 'SMMQuestionID' => 1,
                  'VisitorFormID' =>11	

            ]
        ); 

         $response = new Response();
		          
        if ($status->success() === true) {
            $response->setStatusCode(201, 'Created');
			 
		      $response->setJsonContent(
                [
                    'status' => 'OK',
                    'message'   => 'created',
                ]

				);

        } else {
          
            $response->setStatusCode(409, 'Conflict');
 
            $errors = [];

            foreach ($status->getMessages() as $message) {
                $errors[] = $message->getMessage();
            }           
		   $response->setJsonContent(
                [
                    'status'   => 'ERROR',
                    'message' => 'ERROR',
                ]
            );
        }		 
        return $response;
    }
);


//visitors

$app->get('/api/visitors/{date}', function($date) use ($app) {

		//$phql = 'SELECT v.* from DevipuramPhalcon\models\visitors v WHERE v.Date = :date:';

		$phql = 'SELECT v.VisitorFormID,v.UserID,v.FormTypeID,v.Date, u.UserName,vf.FormType FROM DevipuramPhalcon\models\visitors v JOIN DevipuramPhalcon\models\users u ON v.UserID=u.UserID JOIN DevipuramPhalcon\models\visitorformtypes vf ON v.FormTypeID=vf.FormTypeID WHERE v.Date = :date:';

		//$phql = 'SELECT v.*,(select UserName from DevipuramPhalcon\models\users u where u.UserID =v.UserID) as UserName,(select FormType from DevipuramPhalcon\models\visitorformtypes f where f.FormTypeID =v.FormTypeID) as FormType FROM DevipuramPhalcon\models\visitors v WHERE v.Date = :date:';

        $events = $app->modelsManager->executeQuery($phql,
		[
		'date' => $date,
		]
		);

        $data = [];

        foreach ($events as $event) {
            $data[] = [
                'VisitorFormID'   => $event->VisitorFormID,
                'UserID' => $event->UserID,
				'FormTypeID' => $event->FormTypeID,
				'Date' => $event->Date,
				'UserName' => $event->UserName,
				'FormType' => $event->FormType
            ];
        }
 return json_encode($data);  
 });

	  
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