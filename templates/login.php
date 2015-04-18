<!DOCTYPE html> 
<html> 
<head> 
  <meta name="viewport" content="width=device-width, initial-scale=1"> 
  <link rel="stylesheet" href="http://code.jquery.com/mobile/1.4.2/jquery.mobile-1.4.2.min.css" />
  <script src="http://code.jquery.com/jquery-1.9.1.min.js"></script>
  <script src="http://code.jquery.com/mobile/1.4.2/jquery.mobile-1.4.2.min.js"></script>
</head> 
<body> 

  <div data-role="page">
    <div data-role="header">
      <h1>Calorie Counter</h1>
    </div>
    
    <div data-role="content">	
      <div style="text-align:center"><?php echo $flash['message']; ?></div>
      <form action="/login" method="post" data-ajax="false">
      <div data-role="fieldcontain">
          <label for="email" class="ui-hidden-accessible">Email address:</label>
          <input type="text" name="email" id="email" placeholder="Email address" />
      </div>

      <div data-role="fieldcontain">
          <label for="password" class="ui-hidden-accessible">Password:</label>
          <input type="password" name="password" id="password" placeholder="Password" />
      </div>

      <div>
        <input type="submit" id="submit" value="Sign In" />
      </div>
      </form>
      <!-- using a separate form here to ensure that both buttons look the same once clicked -->
      <form action="/register" method="get" data-ajax="false">
        <input type="submit" id="submit" value="Sign Up" />      
      </form>
    </div>

  </div>


</body>
</html>