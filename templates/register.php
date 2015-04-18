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
      <form action="/register" method="post" data-ajax="false">
      <div data-role="fieldcontain">
          <label for="email" class="ui-hidden-accessible">Email address:</label>
          <input type="text" name="email" id="email" placeholder="Email address" />
      </div>

      <div data-role="fieldcontain">
          <label for="password" class="ui-hidden-accessible">Password:</label>
          <input type="password" name="password" id="password" placeholder="Password" />
      </div>

      <div data-role="fieldcontain">
          <label for="passwordconfirm" class="ui-hidden-accessible">Password (again):</label>
          <input type="password" name="passwordconfirm" id="passwordconfirm" placeholder="Password (again)" />
      </div>
      
      <div>
        <button type="submit" id="submit">Sign Up</button>
      </div>
      </form>
    </div>

  </div>

</body>
</html>