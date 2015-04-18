<!DOCTYPE html> 
<html> 
<head> 
  <meta name="viewport" content="width=device-width, initial-scale=1"> 
  <link rel="stylesheet" href="http://code.jquery.com/mobile/1.4.2/jquery.mobile-1.4.2.min.css" />
  <script src="http://code.jquery.com/jquery-1.9.1.min.js"></script>
  <script src="http://code.jquery.com/mobile/1.4.2/jquery.mobile-1.4.2.min.js"></script>
  <script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.3.0-beta.6/angular.min.js"></script>
  <script>
  var myApp = angular.module('myApp', []);
  
  function myAppController($scope, $http) {
    // related to search functionality
    $scope.mealItems = [];
    $scope.foodItems = {};
    $scope.foodItems.results = [];
    $scope.foodItems.query = '';
    
    $scope.search = function() {
      if ($scope.foodItems.query != '') {
        $http({
            method: 'GET',
            url: '/search/' + $scope.foodItems.query,
          }).
          success(function(data) {
            $scope.foodItems.results = data.hits;
          });
      };
    };
    
    $scope.addToMeal = function(foodItem) {
       $scope.mealItems.push(foodItem);
    };    

    // related to record functionality
    $scope.removeFromMeal = function(index) {
       $scope.mealItems.splice(index, 1);
    };    
    
    $scope.clearMeal = function() {
      $scope.mealItems.length = 0;
    };
    
    $scope.getTotalCalories = function() {
      var sum = 0;
      for(i=0; i<$scope.mealItems.length; i++) { 
        sum += $scope.mealItems[i].fields.nf_calories;
      }
      return sum.toFixed(2);
    };  
    
    $scope.record = function() {
      if ($scope.getTotalCalories() > 0) {
        $http({
            method: 'POST',
            url: '/record',
            data: {'totalCalories': $scope.getTotalCalories()}
          }).
          success(function(data) {
            $scope.clearMeal();
          });
        };   
    };
    
    // related to report functionality
    $scope.report = function() {
      $http({
          method: 'GET',
          url: '/report'
        }).
        success(function(data) {
          $scope.counts = data;
        });
    };        
  }
  </script>
</head> 
<body> 

  <div data-role="page">

    <div data-role="header">
      <h1>Calorie Counter</h1>
      <a data-ajax="false" href="/logout" class="ui-btn ui-btn-inline ui-btn-right">Sign out</a>
    </div>

    <div data-role="content" ng-app="myApp">	
      <div data-role="tabs" ng-controller="myAppController">
      
        <div data-role="navbar">
          <ul>
            <li><a href="#search" data-theme="a" class="ui-btn-active">Search</a></li>
            <li><a href="#record" data-theme="a">Record <span class="ui-li-count"> {{ getTotalCalories() }} / {{ mealItems.length }}</span></a></li>
            <li><a href="#report" data-theme="a" ng-click="report()">Report</a></li>
          </ul>
        </div>
        
        <div id="search">
          <h2 class="ui-bar ui-bar-a">Food Item Search</h2>
          <div class="ui-body">
              <input type="search" name="query" ng-model="foodItems.query" />
              <button ng-click="search()">Search</button>
              <div class="ui-body">
                <img src="images/nutritionix.png" width="150" />
              </div>              
          </div>   
          
          <h2 class="ui-bar ui-bar-a">Search Results</h2>   
          <div class="ui-body">
            <ul data-role="listview" data-split-theme="d">
              <li ng-repeat="r in foodItems.results">
                <a>{{r.fields.item_name}} / {{r.fields.nf_calories + ' calories'}}</a>
                <a href="#" data-inline="true" data-role="button" data-icon="plus" data-theme="a" ng-click="addToMeal(r)">Add</a> 
              </li>
            </ul>                    
          </div>
        </div>

        <div id="record">
          <h2 class="ui-bar ui-bar-a">Meal Record</h2>
          <div class="ui-body">
            <ul data-role="listview" data-split-theme="d">
              <li ng-repeat="item in mealItems track by $index">
                <a>{{item.fields.item_name}} / {{item.fields.nf_calories + ' calories'}}</a>
                <a href="#" data-inline="true" data-role="button" data-icon="minus" data-theme="a" ng-click="removeFromMeal($index)">Add</a> 
              </li>
            </ul>
          </div>          
          <div class="ui-body">
            <button ng-click="record()">Save</button>
          </div>
        </div>
        
        <div id="report">
          <h2 class="ui-bar ui-bar-a">Summary</h2>
          <div class="ui-body">
            <ul data-role="listview" data-inset="true" data-split-theme="d">
              <li>Today <span class="ui-li-count">{{ counts.c1 }}</span></li>
              <li>Last 7 days <span class="ui-li-count">{{ counts.c7 }}</span></li>
              <li>Last 30 days <span class="ui-li-count">{{ counts.c30 }}</span></li>
            </ul>          
          </div>          
          <div class="ui-body">
            <button ng-click="report()">Refresh</button>
          </div>
        </div>

      </div>
      
    </div>
    
  </div>

</body>
</html>