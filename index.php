<?php
// use Composer autoloader
require 'vendor/autoload.php';
\Slim\Slim::registerAutoloader();

// configure credentials
// ... for Nutritionix
$config["nutritionix"]["appId"] = 'APP-ID';
$config["nutritionix"]["appKey"] = 'APP-KEY';
// ... for MySQL
$config["db"]["name"] = 'test';
$config["db"]["host"] = 'localhost';
$config["db"]["port"] = '3306';
$config["db"]["user"] = 'root';
$config["db"]["password"] = 'guessme';
// ... for SendGrid
$config["sg"] = '';

// if BlueMix VCAP_SERVICES environment available
// overwrite with credentials from BlueMix
if ($services = getenv("VCAP_SERVICES")) {
  $services_json = json_decode($services, true);
  $config["db"] = $services_json["mysql-5.5"][0]["credentials"];
  $config["sg"] = $services_json["sendgrid"][0]["credentials"];
} 

// configure Slim application instance
$app = new \Slim\Slim();
$app->config(array(
  'debug' => true,
  'templates.path' => './templates'
));

// initialize PDO object
$db = $config["db"]["name"];
$host = $config["db"]["host"];
$port = $config["db"]["port"];
$username = $config["db"]["user"];
$password = $config["db"]["password"];  
$dbh = new PDO("mysql:host=$host;dbname=$db;port=$port;charset=utf8", $username, $password);

// start session
session_start();

// index page handlers
$app->get('/', function () use ($app) {
  $app->redirect('/index');
});

$app->get('/index', 'authenticate', function () use ($app) {
  $app->render('main.php');
});

// login handlers
$app->get('/login', function () use ($app) {
  $app->render('login.php');
});

// login processor
$app->post('/login', function () use ($app, $dbh) {
  try {
    // check for valid login
    // if found, set user id in session
    $userEmail = $app->request->params('email');
    $userPassword = $app->request->params('password');  
    $stmt = $dbh->query("SELECT id FROM users WHERE email = '$userEmail' AND password = PASSWORD('$userPassword') AND status = '1'");
    if ($stmt->rowCount() == 1) {
      $row = $stmt->fetch(PDO::FETCH_ASSOC);
      $_SESSION['uid'] = $row['id'];
    } else {
      throw new Exception('Login failed');
    }
  } catch (Exception $e) {
    $app->flash('message', $e->getMessage());
    $app->redirect('/login');      
  }
  $app->redirect('/index');
});

// logout handlers
$app->get('/logout', function () use ($app) {
  session_destroy();
  $app->redirect('/login');
});

// registration handlers
$app->get('/register', function () use ($app) {
  $app->render('register.php');
});

// registration processor
$app->post('/register', function () use ($app, $dbh, $config) {
  try {
    $userEmail = $app->request->params('email');
    $userPassword = $app->request->params('password');    
    $userPasswordConfirm = $app->request->params('passwordconfirm');
    
    // validate user input
    if (!filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
      throw new Exception('Invalid email address');
    }
    if ($userPassword != $userPasswordConfirm) {
      throw new Exception('Passwords do not match');
    }
    $stmt = $dbh->query("SELECT id FROM users WHERE email = '$userEmail'");
    if ($stmt->rowCount() == 1) {
      throw new Exception('Email address already in use');
    }
    
    // generate unique code for confirmation email
    // create account with status inactive
    $userHash = md5(uniqid(rand(), true));
    $stmt = $dbh->prepare('INSERT INTO users (email, password, code, status, ip) VALUES(?, PASSWORD(?), ?, ?, ?)');
    $stmt->execute(array($userEmail, $userPassword, $userHash, '0', $_SERVER['SERVER_ADDR']));
    
    // generate confirmation email
    $confirmUrl = 'http://' . $_SERVER['HTTP_HOST'] . "/confirm/$userEmail/$userHash";
    $message = "Please confirm your account: $confirmUrl";
    $subject = 'Calorie counter: account confirmation';
    $from = 'no-reply@' . $_SERVER['HTTP_HOST'];
    
    if (!empty($config["sg"])) {
      $sendgrid = new SendGrid($config["sg"]['username'], $config["sg"]['password']);
      $email = new SendGrid\Email();
      $email->addTo($userEmail)
            ->setFrom($from)
            ->setSubject($subject)
            ->setText($message);
      $sendgrid->send($email);
    } else {
      mail($userEmail, $subject, $message, "From: $from");
    }
    
    $app->flash('message', 'You will shortly receive an email to confirm your account.');
  } catch (Exception $e) {
    $app->flash('message', $e->getMessage());
  }
  $app->redirect('/login');        
});

// account confirmation handler
$app->get('/confirm/:email/:code', function ($email, $code) use ($app, $dbh) {
  try {
    // check for a matching email and code
    // if found, remove code and make account active
    $stmt = $dbh->query("SELECT id FROM users WHERE email = '$email' AND code = '$code'");
    if ($stmt->rowCount() == 1) {
      $row = $stmt->fetch(PDO::FETCH_ASSOC);
      $dbh->exec("UPDATE users SET code = '', status = '1' WHERE id = '" . $row['id'] . "'");
      $app->flash('message', 'Thank you for confirming your account. You can now sign in.');
    } 
  } catch (Exception $e) {
    $app->flash('message', $e->getMessage());
  }
  $app->redirect('/login');
});

// search handler
$app->get('/search/:query', 'authenticate',  function ($query) use ($app, $config) {
  try {
    // execute search on Nutritionix API
    // specify search scope and required response fields 
    $qs = http_build_query(array('appId' => $config["nutritionix"]["appId"], 'appKey' => $config["nutritionix"]["appKey"], 'item_type' => '3', 'fields' => 'item_name,brand_name,nf_calories'));
    $url = 'https://api.nutritionix.com/v1_1/search/' . str_replace(' ', '+', $query) . '?' . $qs;
    $ch = curl_init();    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  
    curl_exec($ch);
    curl_close($ch);
  } catch (Exception $e) {
    $app->response()->status(400);
    $app->response()->header('X-Status-Reason', $e->getMessage());
  }
});

// record handler
$app->post('/record', 'authenticate',  function () use ($app, $dbh) {
  try {
    // get and decode JSON request body
    $request = $app->request();
    $body = $request->getBody();
    $input = json_decode($body); 

    // insert meal record
    $stmt = $dbh->prepare('INSERT INTO meals (uid, calories, rdate, ip) VALUES(?, ?, ?, ?)');
    $stmt->execute(array($_SESSION['uid'], $input->totalCalories, date('Y-m-d h:i:s', time()), $_SERVER['SERVER_ADDR']));
    $input->id = $dbh->lastInsertId();
    
    // return JSON-encoded response body
    $app->response()->header('Content-Type', 'application/json');
    echo json_encode($input);    
  } catch (Exception $e) {
    $app->response()->status(400);
    $app->response()->header('X-Status-Reason', $e->getMessage());
  }  
});

// report handler
$app->get('/report', 'authenticate',  function () use ($app, $dbh) {
  $counts = array();
  $counts['c1'] = $counts['c7'] = $counts['c30'] = 0;
  try {
    // get calorie counts    
    // ... for today
    $stmt = $dbh->query("SELECT IFNULL(SUM(calories),0) AS sum FROM meals WHERE uid = '" . $_SESSION['uid'] . "' and DATE(rdate) = DATE (NOW())");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $counts['c1'] = $row['sum'];
    
    // ... for the last 7 days
    $stmt = $dbh->query("SELECT IFNULL(SUM(calories),0) AS sum FROM meals WHERE uid = '" . $_SESSION['uid'] . "' and DATE(rdate) BETWEEN DATE(DATE_SUB(NOW(), INTERVAL 7 DAY)) AND DATE (NOW())");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $counts['c7'] = $row['sum'];
    
    // ... for the last 30 days
    $stmt = $dbh->query("SELECT IFNULL(SUM(calories),0) AS sum FROM meals WHERE uid = '" . $_SESSION['uid'] . "' and DATE(rdate) BETWEEN DATE(DATE_SUB(NOW(), INTERVAL 30 DAY)) AND DATE (NOW())");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $counts['c30'] = $row['sum'];
    
    // return JSON-encoded response body
    $app->response()->header('Content-Type', 'application/json');
    echo json_encode($counts);    
  } catch (Exception $e) {
    $app->response()->status(400);
    $app->response()->header('X-Status-Reason', $e->getMessage());
  }  
});

// create MySQL schema
$app->get('/install-schema',  function () use ($app, $dbh) {
  $dbh->exec('DROP TABLE meals');
  $dbh->exec('DROP TABLE users');
  $dbh->exec('CREATE TABLE meals (id int(11) NOT NULL AUTO_INCREMENT, uid varchar(255) NOT NULL, calories decimal(10,2) NOT NULL, rdate timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP, ip varchar(20) NOT NULL, PRIMARY KEY (id)) ENGINE=InnoDB DEFAULT CHARSET=utf8');
  $dbh->exec('CREATE TABLE users (id int(11) NOT NULL AUTO_INCREMENT, email varchar(255) NOT NULL, `password` varchar(255) NOT NULL, code varchar(255) DEFAULT NULL, `status` int(11) NOT NULL, rdate timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP, ip varchar(20) NOT NULL, PRIMARY KEY (id)) ENGINE=InnoDB  DEFAULT CHARSET=utf8');
  echo 'Schema installed';
});

$app->run();

// middleware to restrict access to authenticated users only
function authenticate () {
  $app = \Slim\Slim::getInstance();
  if (!isset($_SESSION['uid'])) {
    $app->redirect('/login');
  }
}  
